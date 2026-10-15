import 'dart:async';
import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:timezone/data/latest_all.dart' as tzdata;
import 'package:timezone/timezone.dart' as tz;
import 'package:uuid/uuid.dart';
import 'package:workmanager/workmanager.dart';

import '../data/api_client.dart';

const _channelId = 'tich_alerts';
const _channelName = 'TICH Alerts';
const _syncTask = 'tich.notification.sync';
const _deviceIdKey = 'tich_push_device_id';
const _shownIdsKey = 'tich_shown_notification_ids';
const _lastClockInNudgeKey = 'tich_last_clockin_nudge_ms';

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
  ApiClient _api = ApiClient();
  bool _ready = false;
  bool _initStarted = false;
  String? _deviceId;
  Future<void>? _clockInInflight;
  Future<void>? _lessonInflight;
  final List<int> _clockInIds = [];
  final List<int> _lessonIds = [];

  void attachApiClient(ApiClient client) {
    _api = client;
  }

  Future<void> init() async {
    if (_ready || _initStarted) return _ready ? Future.value() : _waitReady();
    _initStarted = true;

    try {
      // Smaller timezone DB load path than default when available.
      tzdata.initializeTimeZones();
      tz.setLocalLocation(tz.getLocation('Africa/Nairobi'));

      const android = AndroidInitializationSettings('@mipmap/ic_launcher');
      const ios = DarwinInitializationSettings(
        requestAlertPermission: false,
        requestBadgePermission: false,
        requestSoundPermission: false,
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

      final prefs = await SharedPreferences.getInstance();
      _deviceId = prefs.getString(_deviceIdKey);
      if (_deviceId == null || _deviceId!.isEmpty) {
        _deviceId = const Uuid().v4();
        await prefs.setString(_deviceIdKey, _deviceId!);
      }

      _ready = true;

      // Ask for notification permission before any schedules fire.
      await _ensurePermissions();
      unawaited(_initWorkmanager());
    } catch (_) {
      _ready = true; // allow app to proceed even if notifications fail
    }
  }

  Future<void> _waitReady() async {
    while (!_ready) {
      await Future<void>.delayed(const Duration(milliseconds: 40));
    }
  }

  Future<void> _ensurePermissions() async {
    try {
      if (kIsWeb || !(Platform.isAndroid || Platform.isIOS)) return;
      final androidPlugin = _plugin.resolvePlatformSpecificImplementation<
          AndroidFlutterLocalNotificationsPlugin>();
      await Permission.notification.request();
      await androidPlugin?.requestNotificationsPermission();
      await androidPlugin?.requestExactAlarmsPermission();
    } catch (_) {}
  }

  Future<void> _initWorkmanager() async {
    try {
      await Workmanager().initialize(tichNotificationDispatcherCallback);
      await Workmanager().registerPeriodicTask(
        'tich-notification-sync',
        _syncTask,
        frequency: const Duration(minutes: 15),
        existingWorkPolicy: ExistingPeriodicWorkPolicy.keep,
        constraints: Constraints(networkType: NetworkType.connected),
      );
    } catch (_) {}
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

  Future<void> onAuthenticated({required bool isStudent}) async {
    if (!_ready) await init();
    await registerDeviceToken();
    // Sync tray popups without blocking portal UI.
    unawaited(syncFromServerInBackground(isStudent: isStudent));
  }

  NotificationDetails get _details => const NotificationDetails(
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
        iOS: DarwinNotificationDetails(
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

  Future<void> scheduleClockInReminders({required bool alreadyClockedIn}) {
    _clockInInflight ??=
        _scheduleClockInReminders(alreadyClockedIn).whenComplete(() {
      _clockInInflight = null;
    });
    return _clockInInflight!;
  }

  Future<void> _scheduleClockInReminders(bool alreadyClockedIn) async {
    if (!_ready) await init();
    await _ensurePermissions();
    await _cancelTracked(_clockInIds);
    if (alreadyClockedIn) {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(_lastClockInNudgeKey);
      return;
    }

    final now = tz.TZDateTime.now(tz.local);
    if (now.weekday == DateTime.saturday || now.weekday == DateTime.sunday) {
      return;
    }

    const title = 'Clock-in reminder';
    const body =
        'You have not clocked in yet today. Open TICH in Africa to clock in.';

    // Immediate heads-up if already past 08:00 EAT and still not clocked in
    // (throttled so opening Home/Clock does not spam every few seconds).
    final eightAm = tz.TZDateTime(tz.local, now.year, now.month, now.day, 8);
    if (!now.isBefore(eightAm) && now.hour < 18) {
      final prefs = await SharedPreferences.getInstance();
      final lastMs = prefs.getInt(_lastClockInNudgeKey) ?? 0;
      final last = DateTime.fromMillisecondsSinceEpoch(lastMs);
      if (now.difference(last) >= const Duration(minutes: 25)) {
        await showPopup(
          title: title,
          body: body,
          id: 7999,
          payload: 'attendance',
        );
        await prefs.setInt(
          _lastClockInNudgeKey,
          now.millisecondsSinceEpoch,
        );
      }
    }

    // Remaining half-hour slots today (08:00–17:30), then tomorrow morning.
    var id = 8000;
    for (var dayOffset = 0; dayOffset <= 1; dayOffset++) {
      final day = now.add(Duration(days: dayOffset));
      if (day.weekday == DateTime.saturday || day.weekday == DateTime.sunday) {
        continue;
      }
      var slot = tz.TZDateTime(tz.local, day.year, day.month, day.day, 8, 0);
      while (slot.hour < 18) {
        if (slot.isAfter(now)) {
          await _plugin.zonedSchedule(
            id: id,
            scheduledDate: slot,
            notificationDetails: _details,
            androidScheduleMode: AndroidScheduleMode.exactAllowWhileIdle,
            title: title,
            body: body,
            payload: 'attendance',
          );
          _clockInIds.add(id);
          id++;
        }
        slot = slot.add(const Duration(minutes: 30));
      }
    }
  }

  Future<void> cancelClockInReminders() async {
    await _cancelTracked(_clockInIds);
  }

  Future<void> scheduleLessonReminders(
    List<Map<String, dynamic>> sessions,
  ) {
    _lessonInflight ??=
        _scheduleLessonReminders(sessions).whenComplete(() {
      _lessonInflight = null;
    });
    return _lessonInflight!;
  }

  Future<void> _scheduleLessonReminders(
    List<Map<String, dynamic>> sessions,
  ) async {
    if (!_ready) await init();
    await _cancelTracked(_lessonIds);

    final now = tz.TZDateTime.now(tz.local);
    var id = 9000;
    var scheduled = 0;

    for (final s in sessions) {
      if (scheduled >= 8) break;
      final day = (s['day_of_week'] as num?)?.toInt();
      if (day == null) continue;
      final startRaw = s['start_time']?.toString() ?? '';
      final parts = startRaw.split(':');
      if (parts.length < 2) continue;
      final hour = int.tryParse(parts[0]) ?? 0;
      final minute = int.tryParse(parts[1]) ?? 0;

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
      if (delta == 0 && !start.isAfter(now)) delta = 7;
      if (delta > 0) start = start.add(Duration(days: delta));

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
        id: id,
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
      _lessonIds.add(id);
      id++;
      scheduled++;
    }
  }

  Future<void> _cancelTracked(List<int> ids) async {
    if (ids.isEmpty) return;
    final copy = List<int>.from(ids);
    ids.clear();
    for (final id in copy) {
      await _plugin.cancel(id: id);
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
    while (list.length > 120) {
      list.removeAt(0);
    }
    await prefs.setStringList(_shownIdsKey, list);
  }

  Future<void> syncFromServerInBackground({bool? isStudent}) async {
    try {
      if (!_ready) await init();
      final shown = await _shownIds();

      Future<void> drain(String path, String readField) async {
        final data = await _api.get(path);
        final items = data['items'] as List? ?? const [];
        for (final raw in items.take(5)) {
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
        await drain('/student/notifications', 'read_at');
        return;
      }
      if (isStudent == false) {
        await drain('/employee/notifications', 'is_read');
        return;
      }
      try {
        await drain('/employee/notifications', 'is_read');
      } catch (_) {
        await drain('/student/notifications', 'read_at');
      }
    } catch (_) {}
  }
}
