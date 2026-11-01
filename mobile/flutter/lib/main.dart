import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'constants/colors.dart';
import 'screens/login_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  SystemChrome.setPreferredOrientations([
    DeviceOrientation.portraitUp,
    DeviceOrientation.portraitDown,
  ]);
  SystemChrome.setSystemUIOverlayStyle(
    const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.light,
      systemNavigationBarColor: VoicColors.surface,
      systemNavigationBarIconBrightness: Brightness.light,
    ),
  );
  runApp(const VoicMobileApp());
}

class VoicMobileApp extends StatelessWidget {
  const VoicMobileApp({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'VOIC Mobile',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        brightness: Brightness.dark,
        scaffoldBackgroundColor: VoicColors.background,
        primaryColor: VoicColors.primary,
        colorScheme: const ColorScheme.dark(
          primary: VoicColors.primary,
          secondary: VoicColors.secondary,
          surface: VoicColors.surface,
          background: VoicColors.background,
        ),
        textTheme: GoogleFonts.interTextTheme(
          ThemeData.dark().textTheme,
        ),
        useMaterial3: true,
      ),
      home: const LoginScreen(),
    );
  }
}
