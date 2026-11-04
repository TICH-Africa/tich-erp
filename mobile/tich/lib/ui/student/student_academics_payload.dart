import 'package:flutter/material.dart';

/// Parsed once from the student academics API map (avoids per-frame JSON cloning).
class StudentAcademicsPayload {
  StudentAcademicsPayload._({
    required this.academics,
    required this.student,
    required this.registeredUnits,
    required this.unitContent,
    required this.assessments,
    required this.grades,
    required this.catScores,
    required this.examResults,
    required this.attendance,
    required this.periods,
    required this.curriculumUnits,
    required this.specialExamRequests,
    required this.supplementaryExamRequests,
  });

  final Map<String, dynamic> academics;
  final Map<String, dynamic> student;
  final List<Map<String, dynamic>> registeredUnits;
  final List<Map<String, dynamic>> unitContent;
  final List<Map<String, dynamic>> assessments;
  final List<Map<String, dynamic>> grades;
  final List<Map<String, dynamic>> catScores;
  final List<Map<String, dynamic>> examResults;
  final List<Map<String, dynamic>> attendance;
  final List<Map<String, dynamic>> periods;
  final List<Map<String, dynamic>> curriculumUnits;
  final List<Map<String, dynamic>> specialExamRequests;
  final List<Map<String, dynamic>> supplementaryExamRequests;

  factory StudentAcademicsPayload.parse(Map<String, dynamic> data) {
    final academics = _map(data['academics']);
    final student = _map(data['student']);
    return StudentAcademicsPayload._(
      academics: academics,
      student: student,
      registeredUnits: _listMaps(academics['registered_units']),
      unitContent: _listMaps(academics['unit_content']),
      assessments: _listMaps(academics['assessments']),
      grades: _listMaps(academics['grades']),
      catScores: _listMaps(academics['cat_scores']),
      examResults: _listMaps(academics['exam_results']),
      attendance: _listMaps(academics['attendance']),
      periods: _listMaps(academics['periods']),
      curriculumUnits: _listMaps(academics['curriculum_units']),
      specialExamRequests: _listMaps(data['special_exam_requests']),
      supplementaryExamRequests: _listMaps(data['supplementary_exam_requests']),
    );
  }

  static Map<String, dynamic> _map(Object? raw) {
    if (raw is Map<String, dynamic>) return raw;
    if (raw is Map) return Map<String, dynamic>.from(raw);
    return {};
  }

  static List<Map<String, dynamic>> _listMaps(Object? raw) {
    if (raw is! List) return const [];
    return [
      for (final e in raw)
        if (e is Map) Map<String, dynamic>.from(e),
    ];
  }
}

class AcademicsHubSection {
  const AcademicsHubSection({
    required this.title,
    required this.icon,
    required this.badgeKey,
    required this.onTap,
  });

  final String title;
  final IconData icon;
  final String badgeKey;
  final VoidCallback onTap;
}
