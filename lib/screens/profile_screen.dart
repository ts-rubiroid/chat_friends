import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import 'package:chat_friends/services/api_service.dart';
import 'package:chat_friends/services/unifiedpush_service.dart';
import 'package:chat_friends/screens/login_screen.dart';
import 'package:chat_friends/models/user.dart';

class ProfileScreen extends StatefulWidget {
  @override
  _ProfileScreenState createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  User? _user;
  bool _isLoading = true;
  String _error = '';

  // Поля редактирования
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _middleNameController = TextEditingController();
  final _nicknameController = TextEditingController();
  final _positionController = TextEditingController();

  bool _isSaving = false;
  File? _newAvatarFile;
  String? _newAvatarUrl;

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _middleNameController.dispose();
    _nicknameController.dispose();
    _positionController.dispose();
    super.dispose();
  }

  Future<void> _loadProfile() async {
    try {
      // ИСПРАВЛЕНИЕ: заменил getProfile() на getCurrentUser()
      final user = await ApiService.getCurrentUser();
      setState(() {
        _user = user;
        _isLoading = false;
        _initControllersFromUser();
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

  void _initControllersFromUser() {
    if (_user == null) return;
    _firstNameController.text = _user!.firstName ?? '';
    _lastNameController.text = _user!.lastName ?? '';
    _middleNameController.text = _user!.middleName ?? '';
    _nicknameController.text = _user!.nickname ?? '';
    _positionController.text = _user!.position ?? '';
    _newAvatarFile = null;
    _newAvatarUrl = null;
  }

  void _logout() async {
    await UnifiedPushService.unregister();
    await ApiService.logout();
    Navigator.pushAndRemoveUntil(
      context,
      MaterialPageRoute(builder: (context) => LoginScreen()),
      (route) => false,
    );
  }

  Future<void> _pickNewAvatar() async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(source: ImageSource.gallery, maxWidth: 1024);
    if (picked == null) return;

    setState(() {
      _newAvatarFile = File(picked.path);
    });
  }

  Future<void> _saveProfile() async {
    if (_user == null) return;

    setState(() {
      _isSaving = true;
      _error = '';
    });

    try {
      String? avatarUrl = _user!.avatar;

      // Если выбран новый файл аватара – сначала загружаем его
      if (_newAvatarFile != null) {
        final uploadedUrl = await ApiService.uploadAvatar(_newAvatarFile!);
        if (uploadedUrl != null && uploadedUrl.isNotEmpty) {
          avatarUrl = uploadedUrl;
          _newAvatarUrl = uploadedUrl;
        }
      }

      final data = <String, dynamic>{
        'first_name': _firstNameController.text.trim(),
        'last_name': _lastNameController.text.trim(),
        'middle_name': _middleNameController.text.trim(),
        'nickname': _nicknameController.text.trim(),
        'position': _positionController.text.trim(),
      };

      if (avatarUrl != null) {
        data['avatar'] = avatarUrl;
      }

      final updatedUser = await ApiService.updateProfile(data);

      if (!mounted) return;
      setState(() {
        _user = updatedUser;
        _isSaving = false;
        _initControllersFromUser();
      });
    } catch (e) {
      print('Ошибка сохранения профиля: $e');
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _isSaving = false;
      });
    }
  }

  Future<void> _confirmAndDeleteProfile() async {
    if (_user == null) return;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Удалить профиль?'),
        content: const Text(
          'Ваш профиль будет перемещён в корзину на сервере. '
          'Вы не сможете больше войти в приложение под этим аккаунтом.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Отмена'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text(
              'Удалить',
              style: TextStyle(color: Colors.red),
            ),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    setState(() {
      _isSaving = true;
      _error = '';
    });

    final success = await ApiService.deleteProfile();

    if (!mounted) return;

    setState(() {
      _isSaving = false;
    });

    if (success) {
      await UnifiedPushService.unregister();
      await ApiService.logout();
      Navigator.pushAndRemoveUntil(
        context,
        MaterialPageRoute(builder: (context) => LoginScreen()),
        (route) => false,
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Не удалось удалить профиль')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Профиль')),
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
                  child: SingleChildScrollView(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Center(
                          child: Stack(
                            alignment: Alignment.bottomRight,
                            children: [
                              CircleAvatar(
                                radius: 60,
                                backgroundImage: _buildAvatarImage(),
                              ),
                              Positioned(
                                bottom: 4,
                                right: 4,
                                child: InkWell(
                                  onTap: _isSaving ? null : _pickNewAvatar,
                                  child: CircleAvatar(
                                    radius: 18,
                                    backgroundColor: Theme.of(context).colorScheme.primary,
                                    child: const Icon(
                                      Icons.camera_alt,
                                      size: 18,
                                      color: Colors.white,
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 20),
                        Text(
                          _formatUserName(),
                          style: const TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 4),
                        if (_user!.phone != null && _user!.phone!.isNotEmpty)
                          Text(
                            'Телефон (нельзя изменить): ${_user!.phone}',
                            style: const TextStyle(fontSize: 12, color: Colors.grey),
                          ),
                        const SizedBox(height: 20),

                        TextField(
                          controller: _lastNameController,
                          decoration: const InputDecoration(
                            labelText: 'Фамилия',
                          ),
                        ),
                        const SizedBox(height: 10),
                        TextField(
                          controller: _firstNameController,
                          decoration: const InputDecoration(
                            labelText: 'Имя',
                          ),
                        ),
                        const SizedBox(height: 10),
                        TextField(
                          controller: _middleNameController,
                          decoration: const InputDecoration(
                            labelText: 'Отчество',
                          ),
                        ),
                        const SizedBox(height: 10),
                        TextField(
                          controller: _positionController,
                          decoration: const InputDecoration(
                            labelText: 'Должность',
                          ),
                        ),
                        const SizedBox(height: 10),
                        TextField(
                          controller: _nicknameController,
                          decoration: const InputDecoration(
                            labelText: 'Никнейм',
                          ),
                        ),
                        const SizedBox(height: 20),
                        if (_user!.createdAt != null)
                          Text(
                            'Зарегистрирован: '
                            '${_user!.createdAt!.day.toString().padLeft(2, '0')}.'
                            '${_user!.createdAt!.month.toString().padLeft(2, '0')}.'
                            '${_user!.createdAt!.year}',
                            style: const TextStyle(fontSize: 12, color: Colors.grey),
                          ),
                        const SizedBox(height: 20),
                        if (_error.isNotEmpty)
                          Padding(
                            padding: const EdgeInsets.only(bottom: 12),
                            child: Text(
                              _error,
                              style: const TextStyle(color: Colors.red, fontSize: 12),
                            ),
                          ),
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            onPressed: _isSaving ? null : _saveProfile,
                            child: _isSaving
                                ? const SizedBox(
                                    width: 20,
                                    height: 20,
                                    child: CircularProgressIndicator(strokeWidth: 2),
                                  )
                                : const Text('Сохранить изменения'),
                          ),
                        ),
                        const SizedBox(height: 8),
                        SizedBox(
                          width: double.infinity,
                          child: OutlinedButton(
                            onPressed: _isSaving ? null : _logout,
                            child: const Text('Выйти из аккаунта'),
                          ),
                        ),
                        const SizedBox(height: 16),
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            onPressed: _isSaving ? null : _confirmAndDeleteProfile,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.red,
                            ),
                            child: const Text('Удалить профиль'),
                          ),
                        ),
                      ],
                    ),
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

  ImageProvider _buildAvatarImage() {
    // Приоритет: локально выбранный новый файл
    if (_newAvatarFile != null) {
      return FileImage(_newAvatarFile!);
    }

    // Затем – новый URL после загрузки
    if (_newAvatarUrl != null && _newAvatarUrl!.isNotEmpty) {
      return NetworkImage(_newAvatarUrl!);
    }

    // Затем – аватар из User модели (avatarUrl хелпер уже учитывает uploadsUrl)
    if (_user != null && _user!.avatarUrl.isNotEmpty) {
      return NetworkImage(_user!.avatarUrl);
    }

    // Фолбэк – дефолтный локальный аватар
    return const AssetImage('assets/default_avatar.png');
  }
}