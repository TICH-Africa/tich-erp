/// Whether to show the pulsing clock-in dot on the employee bottom nav.
bool employeeNeedsClockInPulse(Map<String, dynamic> attendance) {
  final status = attendance['clock_in_status'];
  if (status is Map) {
    final map = Map<String, dynamic>.from(status);
    if (map.containsKey('needs_clock_in')) {
      return map['needs_clock_in'] == true;
    }
  }

  final today = attendance['today'];
  if (today is Map) {
    if (today['clock_in_at'] != null) return false;
    if (today['clock_out_at'] != null) return false;
  }

  // Fallback when API has no clock_in_status (older server build).
  final nairobi = DateTime.now().toUtc().add(const Duration(hours: 3));
  if (nairobi.weekday == DateTime.saturday ||
      nairobi.weekday == DateTime.sunday) {
    return false;
  }
  return today == null ||
      (today is Map && today['clock_in_at'] == null);
}
