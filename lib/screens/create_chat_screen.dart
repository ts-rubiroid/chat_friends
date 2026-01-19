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
    if (_isGroup && _nameController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Введите название чата')),
      );
      return;
    }

    // Проверка для личного чата
    if (!_isGroup && _selectedUserIds.length != 1) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Для личного чата выберите одного пользователя')),
      );
      return;
    }

    // Проверка для группового чата
    if (_isGroup && _selectedUserIds.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Выберите хотя бы одного участника для группового чата')),
      );
      return;
    }

    // Отладка
    print('[UI DEBUG] Создание чата: группа=$_isGroup, участники=$_selectedUserIds');

    try {
      await ApiService.createChat(
        _nameController.text,
        _isGroup,
        participants: _selectedUserIds,
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
                    
                    if (!_isGroup) {
                      // Для личного чата - radio button (только один выбор)
                      return RadioListTile<int>(
                        value: user.id,
                        groupValue: _selectedUserIds.isNotEmpty ? _selectedUserIds.first : null,
                        onChanged: (value) {
                          setState(() {
                            _selectedUserIds = [value!]; // Только один ID
                          });
                        },
                        title: Text(user.displayName),
                      );
                    } else {
                      // Для группового чата - checkbox (множественный выбор)
                      return CheckboxListTile(
                        value: _selectedUserIds.contains(user.id),
                        onChanged: (value) => _toggleUserSelection(user.id),
                        title: Text(user.displayName),
                      );
                    }
                  },
                ),
              ),
            
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

  void _toggleUserSelection(int userId) {
    setState(() {
      if (_selectedUserIds.contains(userId)) {
        _selectedUserIds.remove(userId);
      } else {
        _selectedUserIds.add(userId);
      }
    });
  }

}