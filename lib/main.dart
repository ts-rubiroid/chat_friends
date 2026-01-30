import 'package:flutter/material.dart';
import 'package:chat_friends/screens/login_screen.dart';
import 'package:chat_friends/screens/chats_screen.dart';
import 'package:chat_friends/services/api_service.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:open_filex/open_filex.dart';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:path_provider/path_provider.dart';

// ВАЖНО: Замените 'ВАШ_САЙТ.ru' на ваш реальный адрес BeGet!
const String _updateUrl = 'https://chatnews.remont-gazon.ru/update';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Запускаем проверку обновлений в фоне
  _checkForUpdate();
  
  runApp(MyApp());
}

// Функция проверки обновлений
Future<void> _checkForUpdate() async {
  try {
    // 1. Получаем текущую версию приложения
    PackageInfo packageInfo = await PackageInfo.fromPlatform();
    int currentVersion = int.parse(packageInfo.version.replaceAll('.', ''));

    // 2. Получаем версию с сервера
    final response = await http.get(Uri.parse('$_updateUrl/version.txt'));
    if (response.statusCode == 200) {
      int latestVersion = int.parse(response.body.trim().replaceAll('.', ''));

      // 3. Сравниваем версии
      if (latestVersion > currentVersion) {
        // 4. Запрашиваем разрешение на установку
        if (await Permission.manageExternalStorage.request().isGranted) {
          // 5. Скачиваем и устанавливаем APK
          await _downloadAndInstallApk();
        }
      }
    }
  } catch (e) {
    print('Ошибка при проверке обновления: $e');
  }
}

// Функция скачивания и установки APK
Future<void> _downloadAndInstallApk() async {
  try {
    // 1. Скачиваем APK
    var response = await http.get(Uri.parse('$_updateUrl/app-release.apk'));
    
    // 2. Сохраняем в папку Downloads
    Directory? downloadsDir = await getExternalStorageDirectory();
    if (downloadsDir == null) return;
    
    String downloadsPath = '${downloadsDir.path}/Downloads';
    await Directory(downloadsPath).create(recursive: true);
    
    String apkPath = '$downloadsPath/chat_friends_update.apk';
    File apkFile = File(apkPath);
    await apkFile.writeAsBytes(response.bodyBytes);
    
    // 3. Открываем APK для установки
    await OpenFilex.open(apkPath);
  } catch (e) {
    print('Ошибка при установке обновления: $e');
  }
}

class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Чат Друзей',
      theme: ThemeData(
        primarySwatch: Colors.blue,
        visualDensity: VisualDensity.adaptivePlatformDensity,
        scaffoldBackgroundColor: Colors.white,
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