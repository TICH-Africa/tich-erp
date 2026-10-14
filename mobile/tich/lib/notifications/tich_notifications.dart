import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:timezone/data/latest.dart' as tzdata;
import 'package:timezone/timezone.dart' as tz;
import 'package:uuid/uuid.dart';
import 'package:workmanager/workmanager.dart';

import '../data/api_client.dart';

const _channelId = 'tich_alerts';
const _channelName = 'TICH Alerts';
const _syncTask = 'tich.notification.sync';
const _deviceIdKey = 'tich_push_device_id';
const _shownIdsKey = 'tich_shown_notification_ids';

@pragma('vm:entry-point')
void tichNotificationDispatcherCallback() {
  Workmanager().executeTask((task, inputData) async {
    if (task == _syncTask) {
      await TichNotifications.instance.syncFromServerInBackground();
    }
    return true;
  });
}

class TichNotifications {
  TichNotifications._();
  static final TichNotifications instance = TichNotifications._();

  final FlutterLocalNotificationsPlugin _plugin =
      FlutterLocalNotificationsPlugin();
  final ApiClient _api = ApiClient();
  bool _ready = false;
  String? _deviceId;

  Future<void> init() async {
    if (_ready) return;
    tzdata.initializeTimeZones();
    tz.setLocalLocation(tz.getLocation('Africa/Nairobi'));

    const android = AndroidInitializationSettings('@mipmap/ic_launcher');
    const ios = DarwinInitializationSettings(
      requestAlertPermission: true,
      requestBadgePermission: true,
      requestSoundPermission: true,
    );
    await _plugin.initialize(
      settings: const InitializationSettings(android: android, iOS: ios),
    );

    final androidPlugin = _plugin.resolvePlatformSpecificImplementation<
        AndroidFlutterLocalNotificationsPlugin>();
    await androidPlugin?.createNotificationChannel(
      const AndroidNotificationChannel(
        _channelId,
        _channelName,
        description: 'Clock-in, class, and portal alerts',
        importance: Importance.max,
        playSound: true,
        enableVibration: true,
        showBadge: true,
      ),
    );

    if (!kIsWeb && (Platform.isAndroid || Platform.isIOS)) {
      await Permission.notification.request();
      await androidPlugin?.requestNotificationsPermission();
      await androidPlugin?.requestExactAlarmsPermission();
    }

    try {
      await Workmanager().initialize(tichNotificationDispatcherCallback);
      await Workmanager().registerPeriodicTask(
        'tich-notification-sync',
        _syncTask,
        frequency: const Duration(minutes: 15),
        existingWorkPolicy: ExistingPeriodicWorkPolicy.keep,
        constraints: Constraints(networkType: NetworkType.connected),
      );
    } catch (_) {
      // Workmanager may be unavailable on desktop/web.
    }

    final prefs = await SharedPreferences.getInstance();
    _deviceId = prefs.getString(_deviceIdKey);
    if (_deviceId == null || _deviceId!.isEmpty) {
      _deviceId = const Uuid().v4();
      await prefs.setString(_deviceIdKey, _deviceId!);
    }

    _ready = true;
  }

  Future<void> registerDeviceToken() async {
    if (!_ready) await init();
    try {
      await _api.post('/auth/device-token', body: {
        'device_token': _deviceId,
        'platform': !kIsWeb && Platform.isIOS ? 'ios' : 'android',
        'device_name': Platform.operatingSystem,
        'app_version': '1.0.0',
      });
    } catch (_) {}
  }

  /// After login: refresh device registration and pull unread into system trays.
  Future<void> onAuthenticated({required bool isStudent}) async {
    if (!_ready) await init();
    await registerDeviceToken();
    await syncFromServerInBackground(isStudent: isStudent);
  }

  NotificationDetails get _details => NotificationDetails(
        android: AndroidNotificationDetails(
          _channelId,
          _channelName,
          channelDescription: 'Clock-in, class, and portal alerts',
          importance: Importance.max,
          priority: Priority.max,
          category: AndroidNotificationCategory.reminder,
          visibility: NotificationVisibility.public,
          playSound: true,
          enableVibration: true,
          ticker: 'TICH',
        ),
        iOS: const DarwinNotificationDetails(
          presentAlert: true,
          presentBadge: true,
          presentSound: true,
          interruptionLevel: InterruptionLevel.timeSensitive,
        ),
      );

  Future<void> showPopup({
    required String title,
    required String body,
    int id = 0,
    String? payload,
  }) async {
    if (!_ready) await init();
    await _plugin.show(
      id: id == 0
          ? DateTime.now().millisecondsSinceEpoch.remainder(100000)
          : id,
      title: title,
      body: body,
      notificationDetails: NotificationDetails(
        android: AndroidNotificationDetails(
          _channelId,
          _channelName,
          channelDescription: 'Clock-in, class, and portal alerts',
          importance: Importance.max,
          priority: Priority.max,
          category: AndroidNotificationCategory.reminder,
          visibility: NotificationVisibility.public,
          styleInformation: BigTextStyleInformation(body),
          playSound: true,
          enableVibration: true,
          ticker: title,
        ),
        iOS: const DarwinNotificationDetails(
          presentAlert: true,
          presentBadge: true,
          presentSound: true,
          interruptionLevel: InterruptionLevel.timeSensitive,
        ),
      ),
      payload: payload,
    );
  }

  Future<void> scheduleClockInReminders({required bool alreadyClockedIn}) async {
    if (!_ready) await init();
    await cancelClockInReminders();
    if (alreadyClockedIn) return;

    final now = tz.TZDateTime.now(tz.local);
    var id = 8000;

    // Cover today and the next weekday so reminders survive overnight without
    // opening the app (until clock-in cancels them).
    for (var dayOffset = 0; dayOffset <= 1; dayOffset++) {
      final day = now.add(Duration(days: dayOffset));
      if (day.weekday == DateTime.saturday || day.weekday == DateTime.sunday) {
        continue;
      }

      var slot = tz.TZDateTime(tz.local, day.year, day.month, day.day, 8, 0);
      while (slot.hour < 18 || (slot.hour == 18 && slot.minute == 0)) {
        if (slot.hour >= 18) break;
        if (slot.isAfter(now)) {
          await _plugin.zonedSchedule(
            id: id,
            scheduledDate: slot,
            notificationDetails: _details,
            androidScheduleMode: AndroidScheduleMode.exactAllowWhileIdle,
            title: 'Clock-in reminder',
            body:
                'You have not clocked in yet today. Open TICH to clock in.',
            payload: 'attendance',
          );
        }
        slot = slot.add(const Duration(minutes: 30));
        id++;
        if (id >= 8100) return;
      }
    }
  }

  Future<void> cancelClockInReminders() async {
    for (var id = 8000; id < 8100; id++) {
      await _plugin.cancel(id: id);
    }
  }

  Future<void> scheduleLessonReminders(
    List<Map<String, dynamic>> sessions,
  ) async {
    if (!_ready) await init();
    for (var id = 9000; id < 9200; id++) {
      await _plugin.cancel(id: id);
    }

    final now = tz.TZDateTime.now(tz.local);
    var id = 9000;

    for (final s in sessions) {
      final day = (s['day_of_week'] as num?)?.toInt();
      if (day == null) continue;
      final startRaw = s['start_time']?.toString() ?? '';
      final parts = startRaw.split(':');
      if (parts.length < 2) continue;
      final hour = int.tryParse(parts[0]) ?? 0;
      final minute = int.tryParse(parts[1]) ?? 0;

      // Schedule for the next occurrence of that weekday (today or later this week).
      var start = tz.TZDateTime(
        tz.local,
        now.year,
        now.month,
        now.day,
        hour,
        minute,
      );
      var delta = (day - now.weekday) % 7;
      if (delta < 0) delta += 7;
      if (delta == 0 && !start.isAfter(now)) {
        delta = 7;
      }
      if (delta > 0) {
        start = start.add(Duration(days: delta));
      }

      final remindAt = start.subtract(const Duration(minutes: 30));
      if (!remindAt.isAfter(now)) continue;

      final unit = [
        s['unit_code'],
        s['unit_name'],
      ].where((e) => e != null && '$e'.isNotEmpty).join(' · ');
      final room = s['room']?.toString();
      final body =
          '${unit.isEmpty ? 'Your next class' : unit} starts at ${parts[0].padLeft(2, '0')}:${parts[1].padLeft(2, '0')}'
          '${room != null && room.isNotEmpty ? ' in $room' : ''}.';

      await _plugin.zonedSchedule(
        id: id++,
        scheduledDate: remindAt,
        notificationDetails: NotificationDetails(
          android: AndroidNotificationDetails(
            _channelId,
            _channelName,
            importance: Importance.max,
            priority: Priority.max,
            category: AndroidNotificationCategory.reminder,
            styleInformation: BigTextStyleInformation(body),
            playSound: true,
            enableVibration: true,
            ticker: 'Class starting soon',
          ),
          iOS: const DarwinNotificationDetails(
            presentAlert: true,
            presentBadge: true,
            presentSound: true,
            interruptionLevel: InterruptionLevel.timeSensitive,
          ),
        ),
        androidScheduleMode: AndroidScheduleMode.exactAllowWhileIdle,
        title: 'Class starting soon',
        body: body,
        payload: 'timetable',
      );
      if (id >= 9200) break;
    }
  }

  Future<Set<String>> _shownIds() async {
    final prefs = await SharedPreferences.getInstance();
    return (prefs.getStringList(_shownIdsKey) ?? const []).toSet();
  }

  Future<void> _markShown(String key) async {
    final prefs = await SharedPreferences.getInstance();
    final list = prefs.getStringList(_shownIdsKey) ?? <String>[];
    if (list.contains(key)) return;
    list.add(key);
    while (list.length > 200) {
      list.removeAt(0);
    }
    await prefs.setStringList(_shownIdsKey, list);
  }

  Future<void> syncFromServerInBackground({bool? isStudent}) async {
    try {
      if (!_ready) await init();
      final shown = await _shownIds();

      Future<void> drain(String path, String idField, String readField) async {
        final data = await _api.get(path);
        final items = data['items'] as List? ?? const [];
        for (final raw in items.take(8)) {
          final n = Map<String, dynamic>.from(raw as Map);
          final id = n['id']?.toString() ?? '';
          if (id.isEmpty) continue;
          final key = '$path:$id';
          if (shown.contains(key)) continue;
          final isRead = readField == 'is_read'
              ? n['is_read'] == true
              : n['read_at'] != null;
          if (isRead) continue;
          await showPopup(
            title: n['title']?.toString() ?? 'TICH',
            body: n['body']?.toString() ?? '',
            id: 7000 + (int.tryParse(id) ?? 0) % 500,
          );
          await _markShown(key);
        }
      }

      if (isStudent == true) {
        await drain('/student/notifications', 'id', 'read_at');
        return;
      }
      if (isStudent == false) {
        await drain('/employee/notifications', 'id', 'is_read');
        return;
      }

      try {
        await drain('/employee/notifications', 'id', 'is_read');
      } catch (_) {
        await drain('/student/notifications', 'id', 'read_at');
      }
    } catch (_) {}
  }
}
