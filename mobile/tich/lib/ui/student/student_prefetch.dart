import '../../data/student_api.dart';
import '../../data/tich_pdf_cache.dart';
import 'student_helpers.dart';

/// Starts list/detail payloads before route transition (stale-while-revalidate).
abstract final class StudentPrefetch {
  static const homeKey = 'student.home';
  static const academicsKey = 'student.academics';
  static const academicsUnitsKey = 'student.academics.units';
  static const academicsContentKey = 'student.academics.content';
  static const academicsAssessmentsKey = 'student.academics.assessments';
  static const timetableKey = 'student.timetable';
  static const financeKey = 'student.finance';
  static const profileKey = 'student.profile';
  static const enrolmentKey = 'student.enrolment';
  static const documentsKey = 'student.documents';
  static const notificationsKey = 'student.notifications';
  static const clearanceKey = 'student.clearance';
  static const evaluationsKey = 'student.evaluations';
  static const lifecycleKey = 'student.lifecycle';

  static void home(StudentApi api) {
    studentWarm(homeKey, () => api.dashboard());
  }

  static void academics(StudentApi api) {
    studentWarm(academicsKey, () => api.academics());
  }

  static void academicsUnits(StudentApi api) {
    studentWarm(academicsUnitsKey, () => api.academicsUnits());
  }

  static void academicsContent(StudentApi api) {
    studentWarm(academicsContentKey, () => api.academicsContent());
  }

  static void academicsAssessments(StudentApi api) {
    studentWarm(academicsAssessmentsKey, () => api.academicsAssessments());
  }

  static void timetable(StudentApi api) {
    studentWarm(timetableKey, () => api.timetable());
  }

  static String _timetablePdfCacheName(Map<String, dynamic> meta) {
    final id = (meta['id'] as num).toInt();
    final kind = meta['kind']?.toString() ?? 'lesson';
    final base = 'timetable-$kind-$id';
    if (meta['is_upload_document'] == true) {
      final name = meta['source_name']?.toString() ?? '';
      if (name.contains('.')) {
        final ext = name.split('.').last.toLowerCase();
        if (ext.isNotEmpty && ext.length <= 5) {
          return '$base.$ext';
        }
      }
    }
    return '$base.pdf';
  }

  /// Warm timetable file bytes (matches in-app viewer cache keys).
  static void timetablePdf(StudentApi api, Map<String, dynamic> docMeta) {
    final id = (docMeta['id'] as num?)?.toInt();
    if (id == null) return;
    final key = _timetablePdfCacheName(docMeta);
    // ignore: unawaited_futures
    TichPdfCache.load(key, () => api.downloadTimetablePdf(id));
  }

  static void timetableDocumentsFromCache(StudentApi api) {
    final cached = studentReadCache(timetableKey);
    if (cached == null) return;
    final tt = Map<String, dynamic>.from(cached['timetable'] as Map? ?? {});
    if (tt['is_provisional'] == true) return;
    final docs = tt['documents'] as List? ?? tt['timetables'] as List? ?? const [];
    for (final raw in docs) {
      final m = Map<String, dynamic>.from(raw as Map);
      if (m['has_document'] == false) continue;
      if (m['kind']?.toString() == 'lesson') {
        timetablePdf(api, m);
        return;
      }
    }
    for (final raw in docs) {
      final m = Map<String, dynamic>.from(raw as Map);
      if (m['has_document'] != false) {
        timetablePdf(api, m);
        return;
      }
    }
  }

  static void finance(StudentApi api) {
    studentWarm(financeKey, () => api.finance());
  }

  static void profile(StudentApi api) {
    studentWarm(profileKey, () => api.profile());
  }

  static void enrolment(StudentApi api) {
    studentWarm(enrolmentKey, () => api.profile());
  }

  static void documents(StudentApi api) {
    studentWarm(documentsKey, () => api.documents());
  }

  static void notifications(StudentApi api) {
    studentWarm(notificationsKey, () => api.notifications());
  }

  static void clearance(StudentApi api) {
    studentWarm(clearanceKey, () => api.clearance());
  }

  static void evaluations(StudentApi api) {
    studentWarm(evaluationsKey, () => api.evaluations());
  }

  static void lifecycle(StudentApi api) {
    studentWarm(lifecycleKey, () => api.lifecycleRequests());
  }

  static String assessmentKey(int id) =>
      studentDetailCacheKey('student.assessment', id);

  static void assessment(StudentApi api, int id) {
    studentWarm(assessmentKey(id), () => api.assessment(id));
  }

  static String examCardPdfKey(int semesterId) =>
      'student.exam-card.pdf.$semesterId';

  /// Warm exam card PDF bytes (eligibility screen / before tap).
  static void examCardPdf(StudentApi api, int semesterId) {
    final key = examCardPdfKey(semesterId);
    // ignore: unawaited_futures
    TichPdfCache.load(
      key,
      () => api.downloadExamCardPdf(semesterId: semesterId),
    );
  }

  /// Prefetch all academics hub sub-lists (idle / tab warm).
  static void academicsSubsections(StudentApi api) {
    academicsUnits(api);
    academicsContent(api);
    academicsAssessments(api);
  }

  /// Background warm for non-home tabs (staggered after shell open).
  static void warmDeferredTabs(StudentApi api) {
    academics(api);
    timetable(api);
    finance(api);
    academicsSubsections(api);
  }

  /// Staggered warm for More drawer destinations.
  static void warmMoreMenu(StudentApi api) {
    profile(api);
    enrolment(api);
    notifications(api);
    documents(api);
    clearance(api);
    evaluations(api);
    lifecycle(api);
  }

  /// More-menu module keys match drawer labels loosely.
  static void warmListForModule(StudentApi api, String moduleKey) {
    switch (moduleKey) {
      case 'profile':
        profile(api);
      case 'enrolment':
        enrolment(api);
      case 'documents':
        documents(api);
      case 'clearance':
        clearance(api);
      case 'evaluations':
        evaluations(api);
      case 'lifecycle':
        lifecycle(api);
      case 'notifications':
        notifications(api);
      case 'finance':
        finance(api);
      case 'timetable':
        timetable(api);
      case 'academics':
        academics(api);
        academicsSubsections(api);
      case 'home':
        home(api);
      default:
        break;
    }
  }
}
