import 'package:flutter/foundation.dart';

/// In-app log buffer (printed to terminal only).
class AppLog {
  AppLog._();
  static final ValueNotifier<List<String>> lines = ValueNotifier<List<String>>([]);

  static void d(String msg) {
    final t = DateTime.now();
    String two(int n) => n.toString().padLeft(2, '0');
    final line = '${two(t.hour)}:${two(t.minute)}:${two(t.second)}  $msg';
    debugPrint('[S2C] $line');
    final next = [...lines.value, line];
    lines.value = next.length > 300 ? next.sublist(next.length - 300) : next;
  }

  static void clear() => lines.value = [];
}
