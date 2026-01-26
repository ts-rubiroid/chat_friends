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
      final chats = await ApiService.getChats();
      final allUsers = await ApiService.getAllUsers();
      final currentUser = await ApiService.getCurrentUser();
      
      chats.sort((a, b) {
        if (a.hasUnread && !b.hasUnread) return -1;
        if (!a.hasUnread && b.hasUnread) return 1;
        
        final aTime = a.lastMessage?.createdAt ?? a.createdAt ?? DateTime(1970);
        final bTime = b.lastMessage?.createdAt ?? b.createdAt ?? DateTime(1970);
        return bTime.compareTo(aTime);
      });

      setState(() {
        _chats = chats;
        _allUsers = allUsers;
        _currentUser = currentUser;
        _isLoading = false;
      });
    } catch (e) {
      print('Ошибка загрузки данных: $e');
      setState(() { _isLoading = false; });
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
                            ).then((_) => _loadData());
                          },
                        );
                      },
                    ),
            ),
      floatingActionButton: FloatingActionButton(
        onPressed: () async {
          // ОТКРЫВАЕМ ЭКРАН СОЗДАНИЯ И ЖДЕМ РЕЗУЛЬТАТ
          final result = await Navigator.push(
            context,
            MaterialPageRoute(builder: (context) => CreateChatScreen()),
          );
          
          // ЕСЛИ ВЕРНУЛСЯ СОЗДАННЫЙ ЧАТ - ОТКРЫВАЕМ ЕГО
          if (result is Chat) {
            print('[DEBUG] Получен созданный чат: ${result.id}');
            await _loadData(); // Обновляем список
            
            // НЕМНОГО ЖДЕМ, ЧТОБЫ СПИСОК ОБНОВИЛСЯ
            await Future.delayed(Duration(milliseconds: 300));
            
            // ОТКРЫВАЕМ СОЗДАННЫЙ ЧАТ
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (context) => ChatScreen(chat: result),
              ),
            ).then((_) => _loadData());
          } 
          // ЕСЛИ ЧАТ НЕ БЫЛ СОЗДАН (null) - ПРОСТО ОБНОВЛЯЕМ СПИСОК
          else if (result == null) {
            _loadData();
          }
        },
        child: Icon(Icons.add),
      ),
    );
  }
}