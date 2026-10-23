import 'package:intl/intl.dart';

final _number = NumberFormat.decimalPattern();

String money(num? amount, String currency) => '$currency ${_number.format((amount ?? 0).round())}';

String shortDate(DateTime? date) => date == null ? '-' : DateFormat('d MMM yyyy').format(date);

String dateTime(DateTime? date) => date == null ? '-' : DateFormat('d MMM yyyy, h:mm a').format(date);

String apiDate(DateTime date) => DateFormat('yyyy-MM-dd').format(date);

String humanize(String value) {
  if (value.isEmpty) {
    return value;
  }
  final text = value.replaceAll('_', ' ');
  return text[0].toUpperCase() + text.substring(1);
}
