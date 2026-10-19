import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/staff_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import 'staff_timetable_grid.dart';

class StaffLessonPlanCreatePage extends StatefulWidget {
  const StaffLessonPlanCreatePage({
    super.key,
    required this.api,
    required this.allocations,
  });

  final StaffApi api;
  final List<Map<String, dynamic>> allocations;

  @override
  State<StaffLessonPlanCreatePage> createState() =>
      _StaffLessonPlanCreatePageState();
}

class _StaffLessonPlanCreatePageState extends State<StaffLessonPlanCreatePage> {
  static const _stepTitles = [
    'Session details',
    'Objectives & resources',
    'Session plan',
  ];

  int _step = 0;
  bool _busy = false;
  bool _loadingContext = false;
  bool _sessionTimeEdited = false;
  List<Map<String, dynamic>> _timetableSessions = const [];

  int? _allocationId;
  DateTime _plannedDate = DateTime.now();

  final _lessonTitle = TextEditingController();
  final _weekNumber = TextEditingController(text: '1');
  final _sessionTime = TextEditingController();
  final _intakeClass = TextEditingController();
  final _venue = TextEditingController();
  final _contactHours = TextEditingController(text: '2');

  final _generalObjective = TextEditingController();
  final _lessonObjectives = TextEditingController();
  final _competencies = TextEditingController();
  final _priorKnowledge = TextEditingController();
  final _resources = TextEditingController();

  final _assignment = TextEditingController();
  final _references = TextEditingController();

  final List<_LessonSessionRow> _sessionRows = [_LessonSessionRow()];

  @override
  void initState() {
    super.initState();
    if (widget.allocations.isNotEmpty) {
      _allocationId = (widget.allocations.first['id'] as num?)?.toInt();
      WidgetsBinding.instance.addPostFrameCallback((_) {
        _loadTimetable();
        _refreshContext();
      });
    }
  }

  Future<void> _loadTimetable() async {
    try {
      final data = await widget.api.timetable();
      if (!mounted) return;
      final sessions = (data['sessions'] as List? ?? const [])
          .map((e) => Map<String, dynamic>.from(e as Map))
          .toList();
      setState(() => _timetableSessions = sessions);
      _applySessionTimeFromTimetable();
    } catch (_) {}
  }

  String? _selectedUnitCode() {
    if (_allocationId == null) return null;
    for (final raw in widget.allocations) {
      if ((raw['id'] as num?)?.toInt() == _allocationId) {
        final code = raw['unit_code']?.toString().trim();
        if (code != null && code.isNotEmpty) return code;
      }
    }
    return null;
  }

  void _applySessionTimeFromTimetable() {
    if (_sessionTimeEdited) return;
    final range = _sessionTimeRangeFromTimetable();
    if (range == null || range.isEmpty) return;
    setState(() => _sessionTime.text = range);
  }

  String? _sessionTimeRangeFromTimetable() {
    final unitCode = _selectedUnitCode();
    if (unitCode == null || _timetableSessions.isEmpty) return null;

    final dow = _plannedDate.weekday;
    Map<String, dynamic>? match;
    for (final s in _timetableSessions) {
      if (s['unit_code']?.toString() != unitCode) continue;
      if ((s['day_of_week'] as num?)?.toInt() == dow) {
        match = s;
        break;
      }
    }
    if (match == null) {
      for (final s in _timetableSessions) {
        if (s['unit_code']?.toString() == unitCode) {
          match = s;
          break;
        }
      }
    }

    if (match == null) return null;
    return staffTimetableSessionTimeRange(match);
  }

  @override
  void dispose() {
    _lessonTitle.dispose();
    _weekNumber.dispose();
    _sessionTime.dispose();
    _intakeClass.dispose();
    _venue.dispose();
    _contactHours.dispose();
    _generalObjective.dispose();
    _lessonObjectives.dispose();
    _competencies.dispose();
    _priorKnowledge.dispose();
    _resources.dispose();
    _assignment.dispose();
    _references.dispose();
    for (final row in _sessionRows) {
      row.dispose();
    }
    super.dispose();
  }

  String _dateApi(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  Future<void> _refreshContext() async {
    final id = _allocationId;
    if (id == null) return;
    setState(() => _loadingContext = true);
    try {
      final data = await widget.api.lessonPlanContext(
        allocationId: id,
        plannedDate: _dateApi(_plannedDate),
      );
      if (!mounted) return;
      final defaults =
          Map<String, dynamic>.from(data['defaults'] as Map? ?? {});
      setState(() {
        if (defaults['week_number'] != null) {
          _weekNumber.text = '${defaults['week_number']}';
        }
        if (!_sessionTimeEdited &&
            '${defaults['session_time'] ?? ''}'.trim().isNotEmpty) {
          _sessionTime.text = '${defaults['session_time']}';
        }
        if ('${defaults['intake_class'] ?? ''}'.trim().isNotEmpty) {
          _intakeClass.text = '${defaults['intake_class']}';
        }
        if ('${defaults['venue'] ?? ''}'.trim().isNotEmpty) {
          _venue.text = '${defaults['venue']}';
        }
        if (defaults['contact_hours'] != null) {
          _contactHours.text = '${defaults['contact_hours']}';
        }
      });
    } catch (_) {
      // Context autofill is optional; tutor can still complete the form.
      _applySessionTimeFromTimetable();
    } finally {
      if (mounted) setState(() => _loadingContext = false);
    }
  }

  void _snack(String message) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }

  bool _validateStep(int step) {
    switch (step) {
      case 0:
        if (_allocationId == null) {
          _snack('Choose a unit.');
          return false;
        }
        if (_lessonTitle.text.trim().isEmpty) {
          _snack('Enter the lesson topic.');
          return false;
        }
        if (_sessionTime.text.trim().isEmpty) {
          _snack('Enter session time.');
          return false;
        }
        if (_intakeClass.text.trim().isEmpty) {
          _snack('Enter class / intake.');
          return false;
        }
        if (_venue.text.trim().isEmpty) {
          _snack('Enter venue.');
          return false;
        }
        final hours = int.tryParse(_contactHours.text.trim());
        if (hours == null || hours < 1) {
          _snack('Enter contact hours for this session.');
          return false;
        }
        return true;
      case 1:
        if (_generalObjective.text.trim().isEmpty) {
          _snack('Enter the general objective.');
          return false;
        }
        if (_lessonObjectives.text.trim().isEmpty) {
          _snack('Enter specific objectives / learning outcomes.');
          return false;
        }
        if (_competencies.text.trim().isEmpty) {
          _snack('Enter key competencies targeted.');
          return false;
        }
        if (_priorKnowledge.text.trim().isEmpty) {
          _snack('Enter prior knowledge / entry behaviour.');
          return false;
        }
        if (_resources.text.trim().isEmpty) {
          _snack('Enter resources / materials required.');
          return false;
        }
        return true;
      case 2:
        final hasRow = _sessionRows.any((r) => r.hasContent);
        if (!hasRow) {
          _snack('Add at least one session plan row with content.');
          return false;
        }
        return true;
      default:
        return true;
    }
  }

  void _next() {
    if (!_validateStep(_step)) return;
    if (_step < 2) {
      setState(() => _step += 1);
    }
  }

  void _previous() {
    if (_step > 0) setState(() => _step -= 1);
  }

  Future<void> _saveDraft() async {
    for (var s = 0; s < 3; s++) {
      if (!_validateStep(s)) {
        setState(() => _step = s);
        return;
      }
    }
    setState(() => _busy = true);
    try {
      await widget.api.createLessonPlan({
        'source_type': 'form',
        'allocation_id': _allocationId,
        'lesson_title': _lessonTitle.text.trim(),
        'planned_date': _dateApi(_plannedDate),
        'week_number': int.tryParse(_weekNumber.text.trim()) ?? 1,
        'contact_hours': int.tryParse(_contactHours.text.trim()) ?? 2,
        'lesson_objectives': _lessonObjectives.text.trim(),
        'competencies_targeted': _competencies.text.trim(),
        'resources_required': _resources.text.trim(),
        'general_objective': _generalObjective.text.trim(),
        'prior_knowledge': _priorKnowledge.text.trim(),
        'venue': _venue.text.trim(),
        'session_time': _sessionTime.text.trim(),
        'intake_class': _intakeClass.text.trim(),
        'assignment': _assignment.text.trim(),
        'references': _references.text.trim(),
        'session_rows': [
          for (final row in _sessionRows)
            if (row.hasContent) row.toMap(),
        ],
      });
      if (!mounted) return;
      _snack('Lesson plan saved as draft.');
      Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      if (!mounted) return;
      _snack(e.message);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'New lesson plan',
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Section ${_step + 1} of 3 · ${_stepTitles[_step]}',
                  style: tichUi(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: TichColors.neutralMuted,
                  ),
                ),
                const SizedBox(height: 8),
                ClipRRect(
                  borderRadius: TichRadius.borderRadiusSm,
                  child: LinearProgressIndicator(
                    value: (_step + 1) / 3,
                    minHeight: 6,
                    backgroundColor: TichColors.neutralBorder,
                    color: TichColors.green,
                  ),
                ),
              ],
            ),
          ),
          if (_loadingContext)
            const LinearProgressIndicator(minHeight: 2),
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
              child: switch (_step) {
                0 => _buildStepOne(),
                1 => _buildStepTwo(),
                _ => _buildStepThree(),
              },
            ),
          ),
          SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
              child: Row(
                children: [
                  if (_step > 0)
                    Expanded(
                      child: OutlinedButton(
                        onPressed: _busy ? null : _previous,
                        child: const Text('Previous'),
                      ),
                    ),
                  if (_step > 0) const SizedBox(width: 10),
                  Expanded(
                    child: FilledButton(
                      onPressed: _busy
                          ? null
                          : (_step < 2 ? _next : _saveDraft),
                      child: _busy
                          ? const SizedBox(
                              height: 22,
                              width: 22,
                              child: AdaptiveProgress(),
                            )
                          : Text(_step < 2 ? 'Next' : 'Save draft'),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStepOne() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        TichDropdownFormField<int>(
          value: _allocationId,
          decoration: const InputDecoration(labelText: 'Unit'),
          items: [
            for (final raw in widget.allocations)
              DropdownMenuItem(
                value: (raw['id'] as num).toInt(),
                child: Text(
                  raw['label']?.toString() ?? 'Unit',
                  overflow: TextOverflow.ellipsis,
                ),
              ),
          ],
          onChanged: _busy
              ? null
              : (v) {
                  setState(() {
                    _allocationId = v;
                    _sessionTimeEdited = false;
                  });
                  _refreshContext();
                },
        ),
        const SizedBox(height: 12),
        TextField(
          controller: _lessonTitle,
          decoration: const InputDecoration(
            labelText: 'Lesson topic',
            hintText: 'e.g. Governance',
          ),
          textInputAction: TextInputAction.next,
        ),
        const SizedBox(height: 12),
        ListTile(
          contentPadding: EdgeInsets.zero,
          title: const Text('Planned date'),
          subtitle: Text(formatDate(_dateApi(_plannedDate))),
          trailing: const Icon(Icons.calendar_today_outlined),
          onTap: _busy
              ? null
              : () async {
                  final picked = await pickDateOnly(
                    context,
                    initial: _plannedDate,
                  );
                  if (picked == null) return;
                  setState(() {
                    _plannedDate = picked;
                    if (!_sessionTimeEdited) {
                      _sessionTime.clear();
                    }
                  });
                  _refreshContext();
                },
        ),
        const SizedBox(height: 8),
        TextField(
          controller: _weekNumber,
          decoration: const InputDecoration(labelText: 'Week number'),
          keyboardType: TextInputType.number,
          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
        ),
        const SizedBox(height: 12),
        TextField(
          controller: _sessionTime,
          onChanged: (_) {
            if (!_sessionTimeEdited) {
              setState(() => _sessionTimeEdited = true);
            }
          },
          decoration: InputDecoration(
            labelText: 'Session time',
            hintText: '08:00 - 10:00',
            helperText: _sessionTimeEdited
                ? null
                : 'From your timetable — edit if needed',
            helperMaxLines: 2,
          ),
        ),
        const SizedBox(height: 12),
        TextField(
          controller: _intakeClass,
          decoration: const InputDecoration(
            labelText: 'Class / intake',
            hintText: 'CHP intake, group, or cohort',
          ),
        ),
        const SizedBox(height: 12),
        TextField(
          controller: _venue,
          decoration: const InputDecoration(
            labelText: 'Venue',
            hintText: 'Classroom / lab / field site',
          ),
        ),
        const SizedBox(height: 12),
        TextField(
          controller: _contactHours,
          decoration: const InputDecoration(
            labelText: 'Contact hours (this session)',
          ),
          keyboardType: TextInputType.number,
          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
        ),
      ],
    );
  }

  Widget _buildStepTwo() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _multilineField(
          controller: _generalObjective,
          label: 'General objective',
          minLines: 2,
        ),
        const SizedBox(height: 12),
        _multilineField(
          controller: _lessonObjectives,
          label: 'Specific objectives / learning outcomes',
          minLines: 3,
        ),
        const SizedBox(height: 12),
        _multilineField(
          controller: _competencies,
          label: 'Key competencies targeted',
          minLines: 2,
        ),
        const SizedBox(height: 12),
        _multilineField(
          controller: _priorKnowledge,
          label: 'Prior knowledge / entry behaviour',
          minLines: 2,
        ),
        const SizedBox(height: 12),
        TextField(
          controller: _resources,
          decoration: const InputDecoration(
            labelText: 'Resources / materials required',
            hintText: 'Projector, handouts, flip charts, models',
          ),
          maxLines: 2,
        ),
      ],
    );
  }

  Widget _buildStepThree() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          'Lesson session plan',
          style: TichTheme.display(fontSize: 15),
        ),
        const SizedBox(height: 4),
        Text(
          'Add a row for each stage of the session. Fields stack vertically so everything stays on screen.',
          style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
        ),
        const SizedBox(height: 12),
        for (var i = 0; i < _sessionRows.length; i++) ...[
          if (i > 0) const SizedBox(height: 10),
          TichCard(
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        'Row ${i + 1}',
                        style: tichUi(fontWeight: FontWeight.w700),
                      ),
                    ),
                    if (_sessionRows.length > 1)
                      TextButton(
                        onPressed: _busy
                            ? null
                            : () {
                                setState(() {
                                  _sessionRows[i].dispose();
                                  _sessionRows.removeAt(i);
                                });
                              },
                        child: const Text('Remove'),
                      ),
                  ],
                ),
                const SizedBox(height: 8),
                ..._sessionRows[i].fields.map(
                  (f) => Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: TextField(
                      controller: f.controller,
                      decoration: InputDecoration(
                        labelText: f.label,
                        alignLabelWithHint: true,
                      ),
                      maxLines: f.maxLines,
                      minLines: f.minLines,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
        const SizedBox(height: 8),
        Align(
          alignment: Alignment.centerLeft,
          child: OutlinedButton.icon(
            onPressed: _busy
                ? null
                : () => setState(() => _sessionRows.add(_LessonSessionRow())),
            icon: const Icon(Icons.add, size: 18),
            label: const Text('Add row'),
          ),
        ),
        const SizedBox(height: 16),
        _multilineField(
          controller: _assignment,
          label: 'Assignment / homework',
          minLines: 2,
          required: false,
        ),
        const SizedBox(height: 12),
        _multilineField(
          controller: _references,
          label: 'References',
          minLines: 2,
          required: false,
        ),
      ],
    );
  }

  Widget _multilineField({
    required TextEditingController controller,
    required String label,
    int minLines = 2,
    bool required = true,
  }) {
    return TextField(
      controller: controller,
      decoration: InputDecoration(
        labelText: required ? label : '$label (optional)',
        alignLabelWithHint: true,
      ),
      minLines: minLines,
      maxLines: minLines + 4,
    );
  }
}

class _LessonSessionRow {
  _LessonSessionRow()
      : time = TextEditingController(),
        content = TextEditingController(),
        trainerActivities = TextEditingController(),
        learnerActivities = TextEditingController(),
        evaluation = TextEditingController();

  final TextEditingController time;
  final TextEditingController content;
  final TextEditingController trainerActivities;
  final TextEditingController learnerActivities;
  final TextEditingController evaluation;

  bool get hasContent =>
      time.text.trim().isNotEmpty ||
      content.text.trim().isNotEmpty ||
      trainerActivities.text.trim().isNotEmpty ||
      learnerActivities.text.trim().isNotEmpty ||
      evaluation.text.trim().isNotEmpty;

  List<({String label, TextEditingController controller, int minLines, int maxLines})>
      get fields => [
            (label: 'Time', controller: time, minLines: 1, maxLines: 2),
            (label: 'Content', controller: content, minLines: 2, maxLines: 6),
            (
              label: 'Trainers activities',
              controller: trainerActivities,
              minLines: 2,
              maxLines: 6,
            ),
            (
              label: 'Learners activities',
              controller: learnerActivities,
              minLines: 2,
              maxLines: 6,
            ),
            (
              label: 'Evaluation / assessment',
              controller: evaluation,
              minLines: 2,
              maxLines: 6,
            ),
          ];

  Map<String, String> toMap() => {
        'time': time.text.trim(),
        'content': content.text.trim(),
        'trainer_activities': trainerActivities.text.trim(),
        'learner_activities': learnerActivities.text.trim(),
        'evaluation': evaluation.text.trim(),
      };

  void dispose() {
    time.dispose();
    content.dispose();
    trainerActivities.dispose();
    learnerActivities.dispose();
    evaluation.dispose();
  }
}
