/// Formats server clock-in distance text (m → km when over 999 m).
String tichFormatClockInDistanceMessage(String message) {
  return message.replaceAllMapped(
    RegExp(r'(\d+(?:\.\d+)?)\s*m from', caseSensitive: false),
    (match) {
      final meters = double.tryParse(match.group(1)!);
      if (meters != null && meters > 999) {
        return '${(meters / 1000).toStringAsFixed(2)} km from';
      }
      return match.group(0)!;
    },
  );
}
