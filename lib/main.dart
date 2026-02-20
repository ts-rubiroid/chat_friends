import 'package:flutter/material.dart';
import 'package:chat_friends/screens/login_screen.dart';
import 'package:chat_friends/screens/chats_screen.dart';
import 'package:chat_friends/services/api_service.dart';
import 'package:chat_friends/services/notification_service.dart';
import 'package:chat_friends/services/background_fetch_service.dart';

final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  initBackgroundFetch();
  await NotificationService.init(navigatorKey);
  runApp(MyApp());
}

class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      navigatorKey: navigatorKey,
      title: 'Чат Друзей',
      theme: ThemeData(
        brightness: Brightness.dark,
        visualDensity: VisualDensity.adaptivePlatformDensity,
        scaffoldBackgroundColor: const Color(0xFF111820),
        primaryColor: const Color(0xFF4F8BFF),
        colorScheme: const ColorScheme.dark(
          primary: Color(0xFF4F8BFF),
          secondary: Color(0xFF4F8BFF),
          background: Color(0xFF111820),
          surface: Color(0xFF181C25),
        ),
        appBarTheme: const AppBarTheme(
          backgroundColor: Color(0xFF181C25),
          elevation: 0,
          centerTitle: true,
          titleTextStyle: TextStyle(
            color: Colors.white,
            fontSize: 18,
            fontWeight: FontWeight.w600,
          ),
          iconTheme: IconThemeData(color: Colors.white),
        ),
        textTheme: const TextTheme(
          bodyMedium: TextStyle(color: Colors.white),
          bodySmall: TextStyle(color: Colors.white70),
        ),
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: const Color(0xFF181C25),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.all(Radius.circular(24)),
            borderSide: BorderSide.none,
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.all(Radius.circular(24)),
            borderSide: BorderSide.none,
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.all(Radius.circular(24)),
            borderSide: BorderSide(color: Color(0xFF4F8BFF)),
          ),
          hintStyle: TextStyle(color: Colors.white38),
          contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        ),
      ),
      builder: (context, child) {
        return SafeArea(
          bottom: true,
          left: true,
          right: true,
          top: true,
          minimum: EdgeInsets.only(bottom: 16),
          child: child!,
        );
      },
      home: FutureBuilder(
        future: ApiService.getToken(),
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return Scaffold(
              body: Center(child: CircularProgressIndicator()),
            );
          }
          if (snapshot.hasData && snapshot.data != null) {
            return ChatsScreen();
          } else {
            return LoginScreen();
          }
        },
      ),
      debugShowCheckedModeBanner: false,
    );
  }
}

