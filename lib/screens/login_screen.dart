import 'package:flutter/material.dart';
import 'package:chat_friends/services/api_service.dart';
import 'package:chat_friends/screens/register_screen.dart';
import 'package:chat_friends/screens/chats_screen.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:open_filex/open_filex.dart';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:path_provider/path_provider.dart';

// URL для обновления (должен совпадать с main.dart)
const String _updateUrl = 'https://ВАШ_САЙТ.ru/update';

class LoginScreen extends StatefulWidget {
  @override
  _LoginScreenState createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isLoading = false;
  String _error = '';
  bool _checkingUpdate = false;
  String _updateStatus = '';

  void _login() async {
    if (_formKey.currentState!.validate()) {
      setState(() {
        _isLoading = true;
        _error = '';
      });

      try {
        await ApiService.login(
          _phoneController.text.trim(),
          _passwordController.text.trim(),
        );
        
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (context) => ChatsScreen()),
        );
      } catch (e) {
        setState(() {
          _error = e.toString();
        });
      } finally {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  void _quickLogin() {
    _phoneController.text = '70000000000';
    _passwordController.text = '123123';
    _login();
  }

  // Функция ручной проверки обновлений
  Future<void> _manualCheckUpdate() async {
    setState(() {
      _checkingUpdate = true;
      _updateStatus = 'Проверяем обновления...';
    });

    try {
      // 1. Получаем текущую версию приложения
      PackageInfo packageInfo = await PackageInfo.fromPlatform();
      int currentVersion = int.parse(packageInfo.version.replaceAll('.', ''));
      
      // 2. Получаем версию с сервера
      final response = await http.get(Uri.parse('$_updateUrl/version.txt'));
      
      if (response.statusCode == 200) {
        String serverVersionText = response.body.trim();
        int latestVersion = int.parse(serverVersionText.replaceAll('.', ''));

        if (latestVersion > currentVersion) {
          setState(() {
            _updateStatus = 'Найдена новая версия! Скачиваем...';
          });
          
          // Запрашиваем разрешение
          var status = await Permission.manageExternalStorage.request();
          
          if (status.isGranted) {
            // Скачиваем и устанавливаем APK
            await _downloadAndInstallApk();
            setState(() {
              _updateStatus = 'Обновление скачано! Откройте файл для установки.';
            });
          } else {
            setState(() {
              _updateStatus = 'Нужно разрешение на установку';
            });
          }
        } else {
          setState(() {
            _updateStatus = 'У вас актуальная версия';
          });
        }
      } else {
        setState(() {
          _updateStatus = 'Ошибка соединения: ${response.statusCode}';
        });
      }
    } catch (e) {
      setState(() {
        _updateStatus = 'Ошибка: $e';
      });
    } finally {
      setState(() {
        _checkingUpdate = false;
      });
    }
  }

  // Функция скачивания и установки APK
  Future<void> _downloadAndInstallApk() async {
    try {
      // 1. Скачиваем APK
      var response = await http.get(Uri.parse('$_updateUrl/app.apk'));
      
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Form(
          key: _formKey,
          child: ListView(
            children: [
              SizedBox(height: 60),
              Text(
                'Чат Друзей',
                style: TextStyle(fontSize: 32, fontWeight: FontWeight.bold),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: 30),
              TextFormField(
                controller: _phoneController,
                decoration: InputDecoration(
                  labelText: 'Телефон',
                  prefixIcon: Icon(Icons.phone),
                  border: OutlineInputBorder(),
                ),
                keyboardType: TextInputType.phone,
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Введите телефон';
                  }
                  return null;
                },
              ),
              SizedBox(height: 15),
              TextFormField(
                controller: _passwordController,
                decoration: InputDecoration(
                  labelText: 'Пароль',
                  prefixIcon: Icon(Icons.lock),
                  border: OutlineInputBorder(),
                ),
                obscureText: true,
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Введите пароль';
                  }
                  if (value.length < 3) {
                    return 'Минимум 3 символа';
                  }
                  return null;
                },
              ),
              SizedBox(height: 20),
              if (_error.isNotEmpty)
                Text(
                  _error,
                  style: TextStyle(color: Colors.red),
                  textAlign: TextAlign.center,
                ),
              SizedBox(height: 20),
              _isLoading
                  ? Center(child: CircularProgressIndicator())
                  : ElevatedButton(
                      onPressed: _login,
                      child: Text('Войти', style: TextStyle(fontSize: 18)),
                      style: ElevatedButton.styleFrom(
                        minimumSize: Size(double.infinity, 50),
                      ),
                    ),
              SizedBox(height: 10),
              TextButton(
                onPressed: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(builder: (context) => RegisterScreen()),
                  );
                },
                child: Text('Регистрация'),
              ),
              
              // Кнопка проверки обновлений
              SizedBox(height: 30),
              Divider(),
              SizedBox(height: 10),
              Text(
                'Обновление приложения',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: 10),
              
              if (_updateStatus.isNotEmpty)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 10),
                  child: Text(
                    _updateStatus,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: _updateStatus.contains('Ошибка') 
                          ? Colors.red 
                          : _updateStatus.contains('актуальная')
                            ? Colors.green
                            : Colors.blue,
                    ),
                  ),
                ),
              
              _checkingUpdate
                  ? Center(child: CircularProgressIndicator())
                  : OutlinedButton(
                      onPressed: _manualCheckUpdate,
                      child: Text('Проверить обновление'),
                      style: OutlinedButton.styleFrom(
                        minimumSize: Size(double.infinity, 45),
                      ),
                    ),
              
              SizedBox(height: 20),
              Divider(),
              SizedBox(height: 10),
              OutlinedButton(
                onPressed: _quickLogin,
                child: Text('Быстрый вход (тест)'),
                style: OutlinedButton.styleFrom(
                  minimumSize: Size(double.infinity, 45),
                ),
              ),
              SizedBox(height: 40),
            ],
          ),
        ),
      ),
    );
  }
}