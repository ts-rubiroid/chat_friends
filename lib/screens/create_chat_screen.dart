import 'package:flutter/material.dart';
import 'package:chat_friends/services/api_service.dart';
import 'package:chat_friends/models/user.dart';

class CreateChatScreen extends StatefulWidget {
  @override
  _CreateChatScreenState createState() => _CreateChatScreenState();
}

class _CreateChatScreenState extends State<CreateChatScreen> {
  final _nameController = TextEditingController();
  bool _isGroup = true;
  List<User> _users = [];
  List<int> _selectedUserIds = [];
  bool _isLoading = true;
  String _error = '';

  @override
  void initState() {
    super.initState();
    _loadUsers();
  }

  Future<void> _loadUsers() async {
    try {
      final users = await ApiService.getAllUsers();
      setState(() {
        _users = users;
        _isLoading = false;
      });
    } catch (e) {
      print('Ошибка загрузки пользователей: $e');
      if (!mounted) return;
      setState(() {
        _error = 'Не удалось загрузить пользователей';
        _isLoading = false;
      });
    }
  }

  void _createChat() async {
    if (_nameController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Введите название чата')),
      );
      return;
    }

    if (_isGroup && _selectedUserIds.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Выберите хотя бы одного участника для группового чата')),
      );
      return;
    }

    try {
      await ApiService.createChat(
        _nameController.text,
        _isGroup,
        userIds: _selectedUserIds.isNotEmpty ? _selectedUserIds : null,
      );
      
      Navigator.pop(context, true);
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Ошибка создания чата: $e')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Создать чат')),
      body: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            TextField(
              controller: _nameController,
              decoration: InputDecoration(
                labelText: 'Название чата',
                border: OutlineInputBorder(),
              ),
            ),
            SizedBox(height: 20),
            Row(
              children: [
                Text('Групповой чат:', style: TextStyle(fontSize: 16)),
                SizedBox(width: 10),
                Switch(
                  value: _isGroup,
                  onChanged: (value) {
                    setState(() {
                      _isGroup = value;
                    });
                  },
                ),
              ],
            ),
            SizedBox(height: 20),
            if (_isGroup) ...[
              Text('Выберите участников:', style: TextStyle(fontSize: 16)),
              SizedBox(height: 10),
              if (_isLoading)
                Center(child: CircularProgressIndicator())
              else if (_error.isNotEmpty)
                Text(_error, style: TextStyle(color: Colors.red))
              else if (_users.isEmpty)
                Text('Нет других пользователей', style: TextStyle(color: Colors.grey))
              else
                Expanded(
                  child: ListView.builder(
                    itemCount: _users.length,
                    itemBuilder: (context, index) {
                      final user = _users[index];
                      
                      // Формируем отображаемое имя
                      String displayName = user.displayName;
                      
                      // Формируем подзаголовок с телефоном
                      String? phoneSubtitle = user.phone;
                      
                      return CheckboxListTile(
                        title: Text(displayName),
                        subtitle: phoneSubtitle != null ? Text(phoneSubtitle) : null,
                        value: _selectedUserIds.contains(user.id),
                        onChanged: (value) {
                          setState(() {
                            if (value == true) {
                              _selectedUserIds.add(user.id);
                            } else {
                              _selectedUserIds.remove(user.id);
                            }
                          });
                        },
                      );
                    },
                  ),
                ),
            ],
            SizedBox(height: 20),
            ElevatedButton(
              onPressed: _createChat,
              child: Text('Создать чат'),
              style: ElevatedButton.styleFrom(
                minimumSize: Size(double.infinity, 50),
              ),
            ),
          ],
        ),
      ),
    );
  }
}