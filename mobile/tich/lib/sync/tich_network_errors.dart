import 'dart:async';
import 'dart:io';

import '../data/api_client.dart';

bool tichIsTransientNetworkError(Object? error) {
  if (error is ApiException) {
    final code = error.statusCode;
    if (code != null && code >= 500) {
      return true;
    }
    return false;
  }
  if (error is TimeoutException) {
    return true;
  }
  if (error is SocketException) {
    return true;
  }
  final text = (error ?? '').toString();
  return text.contains('SocketException') ||
      text.contains('TimeoutException') ||
      text.contains('Failed host lookup') ||
      text.contains('Connection refused') ||
      text.contains('ClientException');
}
