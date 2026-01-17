import 'package:flutter/material.dart';
import 'package:chat_friends/services/api_service.dart';
import 'package:chat_friends/screens/login_screen.dart';
import 'package:chat_friends/models/user.dart';
import '../utils/api.dart';

class ProfileScreen extends StatefulWidget {
  @override
  _ProfileScreenState createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  User? _user;
  bool _isLoading = true;
  String _error = '';

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  Future<void> _loadProfile() async {
    try {
      // ИСПРАВЛЕНИЕ: заменил getProfile() на getCurrentUser()
      final user = await ApiService.getCurrentUser();
      setState(() {
        _user = user;
        _isLoading = false;
      });
    } catch (e) {
      print('Ошибка загрузки профиля: $e');
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  void _logout() async {
    await ApiService.logout();
    Navigator.pushAndRemoveUntil(
      context,
      MaterialPageRoute(builder: (context) => LoginScreen()),
      (route) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Профиль')),
      body: _isLoading
          ? Center(child: CircularProgressIndicator())
          : _user == null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text('Ошибка загрузки профиля'),
                      if (_error.isNotEmpty)
                        Padding(
                          padding: EdgeInsets.all(20),
                          child: Text(
                            _error,
                            style: TextStyle(color: Colors.red, fontSize: 12),
                            textAlign: TextAlign.center,
                          ),
                        ),
                      ElevatedButton(
                        onPressed: _loadProfile,
                        child: Text('Повторить'),
                      ),
                    ],
                  ),
                )
              : Padding(
                  padding: const EdgeInsets.all(20.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Center(
                        child: CircleAvatar(
                          radius: 60,
                          backgroundImage: _user!.avatar != null && _user!.avatar!.isNotEmpty
                              ? NetworkImage(
                                  // Используем хелпер для получения URL аватара
                                  _user!.avatar!.startsWith('http')
                                      ? _user!.avatar!
                                      : '${ApiConfig.uploadsUrl}/${_user!.avatar!}',
                                )
                              : AssetImage('assets/default_avatar.png')
                                  as ImageProvider,
                        ),
                      ),
                      SizedBox(height: 20),
                      Text(
                        // Безопасное формирование ФИО
                        _formatUserName(),
                        style: TextStyle(
                            fontSize: 24, fontWeight: FontWeight.bold),
                      ),
                      SizedBox(height: 10),
                      if (_user!.nickname != null && _user!.nickname!.isNotEmpty)
                        Text('Никнейм: ${_user!.nickname}'),
                      SizedBox(height: 10),
                      if (_user!.phone != null && _user!.phone!.isNotEmpty)
                        Text('Телефон: ${_user!.phone}'),
                      SizedBox(height: 10),
                      if (_user!.createdAt != null)
                        Text(
                          'Зарегистрирован: ${_user!.createdAt!.day}.${_user!.createdAt!.month}.${_user!.createdAt!.year}',
                        ),
                      Spacer(),
                      ElevatedButton(
                        onPressed: _logout,
                        child: Text('Выйти'),
                        style: ElevatedButton.styleFrom(
                          minimumSize: Size(double.infinity, 50),
                          backgroundColor: Colors.red,
                        ),
                      ),
                    ],
                  ),
                ),
    );
  }
  
  // Вспомогательный метод для формирования ФИО
  String _formatUserName() {
    List<String> parts = [];
    
    if (_user!.lastName != null && _user!.lastName!.isNotEmpty) {
      parts.add(_user!.lastName!);
    }
    if (_user!.firstName != null && _user!.firstName!.isNotEmpty) {
      parts.add(_user!.firstName!);
    }
    if (_user!.middleName != null && _user!.middleName!.isNotEmpty) {
      parts.add(_user!.middleName!);
    }
    
    return parts.isEmpty ? 'Пользователь' : parts.join(' ');
  }
}