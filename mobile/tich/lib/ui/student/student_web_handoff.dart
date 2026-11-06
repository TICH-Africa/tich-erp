import 'package:flutter/material.dart';

import '../../data/student_api.dart';
import '../../models/auth_user.dart';
import '../widgets/tich_authenticated_web_handoff.dart';

String studentFinancePaymentWebPath({int? invoiceId}) {
  final base = '/portal?section=finance';
  final withInvoice =
      invoiceId != null ? '$base&invoice=$invoiceId' : base;
  return '$withInvoice#pay-with-mpesa';
}

Future<void> studentOpenFinancePaymentOnWeb(
  BuildContext context, {
  required AuthUser user,
  required StudentApi api,
  int? invoiceId,
}) {
  return tichConfirmAndOpenWebHandoff(
    context,
    user: user,
    createHandoff: (path) => api.createWebHandoff(path: path),
    path: studentFinancePaymentWebPath(invoiceId: invoiceId),
  );
}
