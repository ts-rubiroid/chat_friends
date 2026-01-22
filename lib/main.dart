import 'package:flutter/material.dart';
import 'package:chat_friends/screens/login_screen.dart';
import 'package:chat_friends/screens/chats_screen.dart';
import 'package:chat_friends/services/api_service.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(MyApp());
}

class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Чат Друзей',
      theme: ThemeData(
        primarySwatch: Colors.blue,
        visualDensity: VisualDensity.adaptivePlatformDensity,
        // Дополнительно можно задать padding для Scaffold
        scaffoldBackgroundColor: Colors.white,
      ),
      // Простое глобальное решение
      builder: (context, child) {
        return SafeArea(
          bottom: true, // защита от нижней панели
          left: true,
          right: true,
          top: true,
          minimum: EdgeInsets.only(bottom: 16), // +16 пикселей снизу
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