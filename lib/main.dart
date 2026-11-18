import 'dart:async';
import 'dart:ui' show PlatformDispatcher;

import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/material.dart';
import 'firebase_options.dart';
import 'screens/splash_screen.dart';
import 'services/push_service.dart';
import 'services/app_log.dart';
import 'theme/app_theme.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  // Catch ALL uncaught errors into in-app log.
  FlutterError.onError = (d) {
    AppLog.d('FLUTTER ERROR: ${d.exceptionAsString()}');
    FlutterError.presentError(d);
  };
  PlatformDispatcher.instance.onError = (e, st) {
    AppLog.d('UNCAUGHT ERROR: $e\n$st');
    return true;
  };
  AppLog.d('App start');
  await Firebase.initializeApp(
    options: DefaultFirebaseOptions.currentPlatform,
  );
  unawaited(PushService.init());
  runApp(const Scan2ChatApp());
}

class Scan2ChatApp extends StatelessWidget {
  const Scan2ChatApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Scan2Chat',
      debugShowCheckedModeBanner: false,
      theme: buildAppTheme(),
      navigatorKey: rootNavigatorKey,
      scaffoldMessengerKey: rootMessengerKey,
      home: const SplashScreen(),
    );
  }
}