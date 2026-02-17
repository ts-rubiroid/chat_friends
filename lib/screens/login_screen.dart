import 'package:flutter/material.dart';
import 'package:chat_friends/services/api_service.dart';
import 'package:chat_friends/screens/register_screen.dart';
import 'package:chat_friends/screens/chats_screen.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:open_filex/open_filex.dart';
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:path_provider/path_provider.dart';

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
  String? _downloadedApkPath;
  String? _latestVersion;

  void _login() async {
    if (_formKey.currentState!.validate()) {
      setState(() {
        _isLoading = true;
        _error = '';
      });

      try {
        final result = await ApiService.login(
          _phoneController.text.trim(),
          _passwordController.text.trim(),
        );

        // Если запрос завершился, но API вернул ошибку (401, 403 и т.п.) —
        // не пускаем в приложение, а показываем понятное сообщение.
        final bool ok = (result['success'] == true) || result.containsKey('token');
        if (!ok) {
          final status = result['statusCode'];
          final backendMessage = result['error'] ?? result['message'];

          // Для 401 показываем стандартное сообщение о неверных данных,
          // не "ломая" пользователя техническими текстами WordPress.
          final friendly = status == 401
              ? 'Неверный телефон или пароль. Проверьте данные и попробуйте ещё раз.'
              : (backendMessage?.toString().isNotEmpty == true
                  ? backendMessage.toString()
                  : 'Не удалось войти. Попробуйте ещё раз.');

          setState(() {
            _error = friendly;
          });
          return;
        }

        if (!mounted) return;
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (context) => ChatsScreen()),
        );
      } catch (e) {
        setState(() {
          // Сетевые/непредвиденные ошибки — отдельным сообщением
          _error = 'Ошибка входа: ${e.toString()}';
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

  Future<void> _manualCheckUpdate() async {
    setState(() {
      _checkingUpdate = true;
      _updateStatus = 'Проверяем обновления...';
      _downloadedApkPath = null;
    });

    try {
      PackageInfo packageInfo = await PackageInfo.fromPlatform();
      final currentVersion = packageInfo.version;
      
      final updateUrl = 'https://chatnews.remont-gazon.ru/update/update.json';
      final response = await http.get(Uri.parse(updateUrl));
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final latestVersion = data['latest_version'];
        final apkUrl = data['apk_url'];
        _latestVersion = latestVersion;
        
        if (latestVersion != currentVersion) {
          setState(() {
            _updateStatus = 'Найдена версия $latestVersion! Скачиваем...';
          });
          
          await _downloadApk(apkUrl);
          
          setState(() {
            _updateStatus = 'Готово к установке';
          });
        } else {
          setState(() {
            _updateStatus = 'У вас актуальная версия $currentVersion ✓';
          });
        }
      } else {
        setState(() {
          _updateStatus = 'Ошибка соединения с сервером';
        });
      }
    } catch (e) {
      setState(() {
        _updateStatus = 'Ошибка: ${e.toString()}';
      });
    } finally {
      setState(() {
        _checkingUpdate = false;
      });
    }
  }

  Future<void> _downloadApk(String apkUrl) async {
    try {
      var response = await http.get(Uri.parse(apkUrl));
      
      Directory tempDir = await getTemporaryDirectory();
      String apkPath = '${tempDir.path}/chat_friends_${_latestVersion}.apk';
      File apkFile = File(apkPath);
      await apkFile.writeAsBytes(response.bodyBytes);
      
      setState(() {
        _downloadedApkPath = apkPath;
      });
    } catch (e) {
      setState(() {
        _updateStatus = 'Ошибка скачивания: ${e.toString()}';
      });
    }
  }

  Future<void> _installApk() async {
    if (_downloadedApkPath == null || !File(_downloadedApkPath!).existsSync()) {
      setState(() {
        _updateStatus = 'APK не найден, проверьте обновление снова';
      });
      return;
    }

    try {
      var status = await Permission.manageExternalStorage.request();
      if (!status.isGranted) {
        setState(() {
          _updateStatus = 'Нужно разрешение на установку';
        });
        return;
      }
      
      final result = await OpenFilex.open(_downloadedApkPath!);
      print('Результат открытия файла: $result');
    } catch (e) {
      setState(() {
        _updateStatus = 'Ошибка установки: ${e.toString()}';
      });
    }
  }

  Widget _buildUpdateStatus() {
    if (_updateStatus.isEmpty) return SizedBox();
    
    if (_downloadedApkPath != null && _updateStatus == 'Готово к установке') {
      return Padding(
        padding: const EdgeInsets.symmetric(vertical: 10),
        child: GestureDetector(
          onTap: _installApk,
          child: Container(
            padding: EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.blue[50],
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: Colors.blue),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.system_update, color: Colors.blue),
                SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Обновление скачано!',
                        style: TextStyle(
                          color: Colors.blue[800],
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      Text(
                        'Нажмите здесь чтобы установить версию $_latestVersion',
                        style: TextStyle(
                          color: Colors.blue[600],
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
                Icon(Icons.arrow_forward, color: Colors.blue),
              ],
            ),
          ),
        ),
      );
    }
    
    return Padding(
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
    );
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
              
              SizedBox(height: 30),
              Divider(),
              SizedBox(height: 10),
              Text(
                'Обновление приложения',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: 10),
              
              _buildUpdateStatus(),
              
              _checkingUpdate
                  ? Center(child: CircularProgressIndicator())
                  : OutlinedButton.icon(
                      onPressed: _manualCheckUpdate,
                      icon: Icon(Icons.system_update),
                      label: Text('Проверить обновление'),
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