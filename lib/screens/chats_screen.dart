import 'package:flutter/material.dart';
import 'package:pull_to_refresh/pull_to_refresh.dart';
import 'package:chat_friends/services/api_service.dart';
import 'package:chat_friends/models/chat.dart';
import 'package:chat_friends/models/user.dart';
import 'package:chat_friends/widgets/chat_list_item.dart';
import 'package:chat_friends/screens/chat_screen.dart';
import 'package:chat_friends/screens/create_chat_screen.dart';
import 'package:chat_friends/screens/profile_screen.dart';

class ChatsScreen extends StatefulWidget {
  @override
  _ChatsScreenState createState() => _ChatsScreenState();
}

class _ChatsScreenState extends State<ChatsScreen> {
  List<Chat> _chats = [];
  List<User> _allUsers = [];
  User? _currentUser;
  bool _isLoading = true;
  final RefreshController _refreshController = RefreshController();

  @override
  void initState() {
    super.initState();
    _loadData();
  }


  Future<void> _loadData() async {
    try {
      // Загружаем данные последовательно для простоты
      final chats = await ApiService.getChats();
      final allUsers = await ApiService.getAllUsers();
      final currentUser = await ApiService.getCurrentUser();
      
      print('[DEBUG ChatsScreen] Loaded:');
      print('  Chats: ${chats.length}');
      print('  All Users: ${allUsers.length}');
      print('  Current User ID: ${currentUser.id}');

      // Для каждого чата выводим информацию
      for (final chat in chats) {
        print('  Chat ${chat.id}: ${chat.name} | Group: ${chat.isGroup} | User IDs: ${chat.userIds}');
      }




      setState(() {
        _chats = chats;
        _allUsers = allUsers;
        _currentUser = currentUser;
        _isLoading = false;
      });
    } catch (e) {
      print('Ошибка загрузки данных: $e');
      if (!mounted) return;
      setState(() {
        _isLoading = false;
      });
    }
  }



  void _onRefresh() async {
    await _loadData();
    _refreshController.refreshCompleted();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Чаты'),
        actions: [
          IconButton(
            icon: Icon(Icons.person),
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(builder: (context) => ProfileScreen()),
              ).then((_) => _loadData());
            },
          ),
        ],
      ),
      body: _isLoading || _currentUser == null
          ? Center(child: CircularProgressIndicator())
          : SmartRefresher(
              controller: _refreshController,
              onRefresh: _onRefresh,
              child: _chats.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.chat_bubble_outline, size: 64, color: Colors.grey),
                          SizedBox(height: 16),
                          Text(
                            'Нет чатов',
                            style: TextStyle(fontSize: 18, color: Colors.grey),
                          ),
                          SizedBox(height: 8),
                          Text(
                            'Создайте первый чат',
                            style: TextStyle(color: Colors.grey),
                          ),
                        ],
                      ),
                    )
                  : ListView.builder(
                      itemCount: _chats.length,
                      itemBuilder: (context, index) {
                        final chat = _chats[index];
                        return ChatListItem(
                          chat: chat,
                          currentUser: _currentUser!,
                          allUsers: _allUsers,
                          onTap: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (context) => ChatScreen(chat: chat),
                              ),
                            ).then((_) => _loadData()); // Обновляем при возврате
                          },
                        );
                      },
                    ),
            ),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          Navigator.push(
            context,
            MaterialPageRoute(builder: (context) => CreateChatScreen()),
          ).then((_) => _loadData());
        },
        child: Icon(Icons.add),
      ),
    );
  }
}