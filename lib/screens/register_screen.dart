import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'dart:io';
import 'package:chat_friends/services/api_service.dart';
import 'package:chat_friends/screens/chats_screen.dart';

class RegisterScreen extends StatefulWidget {
  @override
  _RegisterScreenState createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _firstNameController = TextEditingController();
  final _middleNameController = TextEditingController();
  final _nicknameController = TextEditingController();
  
  File? _avatar;
  bool _isLoading = false;
  String _error = '';

  Future<void> _pickImage() async {
    final picker = ImagePicker();
    final pickedFile = await picker.pickImage(source: ImageSource.gallery);
    
    if (pickedFile != null) {
      setState(() {
        _avatar = File(pickedFile.path);
      });
    }
  }


  void _register() async {
    if (_formKey.currentState!.validate()) {
      setState(() {
        _isLoading = true;
        _error = '';
      });

      try {
        String? avatarUrl;
        
        // 1. Загружаем аватар
        if (_avatar != null) {
          print('Загружаю аватар...');
          avatarUrl = await ApiService.uploadAvatar(_avatar!);
          
          if (avatarUrl == null) {
            print('Аватар не загружен, продолжаем без него');
          }
        }
        
        // 2. Подготавливаем данные
        final data = {
          'first_name': _firstNameController.text.trim(),
          'last_name': _lastNameController.text.trim(),
          'nickname': _nicknameController.text.isNotEmpty 
              ? _nicknameController.text.trim()
              : _firstNameController.text.trim(),
          'avatar': avatarUrl ?? '', // URL или пустая строка
        };

        // 3. Регистрируем пользователя
        print('Регистрирую...');
        final registerResponse = await ApiService.register(
          _phoneController.text.trim(),
          _passwordController.text.trim(),
          data,
        );
        
        print('Ответ регистрации: $registerResponse');
        
        // 4. Проверяем успех
        if (registerResponse['success'] == true) {
          print('✓ Регистрация успешна!');
          
          // 5. Авторизуемся
          final loginResponse = await ApiService.login(
            _phoneController.text.trim(),
            _passwordController.text.trim(),
          );
          
          if (loginResponse['success'] == true) {
            print('✓ Авторизация успешна!');
            
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(builder: (context) => ChatsScreen()),
            );
          } else {
            throw Exception('Ошибка авторизации');
          }
        } else {
          // Ошибка регистрации
          final errorMsg = registerResponse['message'] ?? registerResponse['error'] ?? 'Ошибка регистрации';
          throw Exception(errorMsg);
        }
        
      } catch (e) {
        print('Ошибка: $e');
        setState(() {
          _error = e.toString();
        });
        
        // Показываем Snackbar
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_error),
            backgroundColor: Colors.red,
          ),
        );
      } finally {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Регистрация')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20.0),
        child: Form(
          key: _formKey,
          child: Column(
            children: [
              GestureDetector(
                onTap: _pickImage,
                child: CircleAvatar(
                  radius: 50,
                  backgroundImage: _avatar != null 
                      ? FileImage(_avatar!)
                      : AssetImage('assets/default_avatar.png') as ImageProvider,
                  child: _avatar == null ? Icon(Icons.camera_alt, size: 30) : null,
                ),
              ),
              SizedBox(height: 20),
              TextFormField(
                controller: _phoneController,
                decoration: InputDecoration(
                  labelText: 'Телефон*',
                  border: OutlineInputBorder(),
                ),
                keyboardType: TextInputType.phone,
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Обязательное поле';
                  }
                  return null;
                },
              ),
              SizedBox(height: 15),
              TextFormField(
                controller: _passwordController,
                decoration: InputDecoration(
                  labelText: 'Пароль*',
                  border: OutlineInputBorder(),
                ),
                obscureText: true,
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Обязательное поле';
                  }
                  if (value.length < 3) {
                    return 'Минимум 3 символа';
                  }
                  return null;
                },
              ),
              SizedBox(height: 15),
              TextFormField(
                controller: _lastNameController,
                decoration: InputDecoration(
                  labelText: 'Фамилия',
                  border: OutlineInputBorder(),
                ),
              ),
              SizedBox(height: 15),
              TextFormField(
                controller: _firstNameController,
                decoration: InputDecoration(
                  labelText: 'Имя*',
                  border: OutlineInputBorder(),
                ),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Обязательное поле';
                  }
                  return null;
                },
              ),
              SizedBox(height: 15),
              TextFormField(
                controller: _middleNameController,
                decoration: InputDecoration(
                  labelText: 'Отчество',
                  border: OutlineInputBorder(),
                ),
              ),
              SizedBox(height: 15),
              TextFormField(
                controller: _nicknameController,
                decoration: InputDecoration(
                  labelText: 'Никнейм',
                  border: OutlineInputBorder(),
                ),
              ),
              SizedBox(height: 20),
              if (_error.isNotEmpty)
                Text(
                  _error,
                  style: TextStyle(color: Colors.red),
                ),
              SizedBox(height: 20),
              _isLoading
                  ? CircularProgressIndicator()
                  : ElevatedButton(
                      onPressed: _register,
                      child: Text('Зарегистрироваться'),
                      style: ElevatedButton.styleFrom(
                        minimumSize: Size(double.infinity, 50),
                      ),
                    ),
            ],
          ),
        ),
      ),
    );
  }
}