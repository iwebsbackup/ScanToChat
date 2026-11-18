/// RTDB timestamps = milliseconds since epoch (ServerValue.timestamp).
String fmtDate(dynamic ts) {
  if (ts is! num) return '—';
  final d = DateTime.fromMillisecondsSinceEpoch(ts.toInt()).toLocal();
  String two(int n) => n.toString().padLeft(2, '0');
  return '${two(d.day)}/${two(d.month)}/${d.year} ${two(d.hour)}:${two(d.minute)}';
}
