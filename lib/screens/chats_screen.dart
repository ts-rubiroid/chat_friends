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

class _ChatsScreenState extends State<ChatsScreen>
    with SingleTickerProviderStateMixin {
  List<Chat> _chats = [];
  List<User> _allUsers = [];
  User? _currentUser;
  bool _isLoading = true;
  final RefreshController _refreshController = RefreshController();
  
  // Для управления вкладками
  late TabController _tabController;
  List<Chat> _personalChats = [];
  List<Chat> _groupChats = [];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadData();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  // Разделение чатов на личные и групповые
  void _categorizeChats() {
    _personalChats = [];
    _groupChats = [];
    
    for (final chat in _chats) {
      if (chat.isGroup) {
        _groupChats.add(chat);
      } else {
        _personalChats.add(chat);
      }
    }
    
    // Сортировка внутри категорий
    _sortChatsList(_personalChats);
    _sortChatsList(_groupChats);
  }

  void _sortChatsList(List<Chat> chatList) {
    chatList.sort((a, b) {
      if (a.hasUnread && !b.hasUnread) return -1;
      if (!a.hasUnread && b.hasUnread) return 1;
      
      final aTime = a.lastMessage?.createdAt ?? a.createdAt ?? DateTime(1970);
      final bTime = b.lastMessage?.createdAt ?? b.createdAt ?? DateTime(1970);
      return bTime.compareTo(aTime);
    });
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
        _chats = chats; // ИСХОДНЫЕ данные, без тестовых изменений
        _allUsers = allUsers;
        _currentUser = currentUser;
        _isLoading = false;
      });
      
      _categorizeChats();
    } catch (e) {
      print('Ошибка загрузки данных: $e');
      setState(() { _isLoading = false; });
    }
  }

  void _onRefresh() async {
    await _loadData();
    _refreshController.refreshCompleted();
  }

  Widget _buildChatsList(List<Chat> chats) {
    if (chats.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              _tabController.index == 0 
                ? Icons.person_outline 
                : Icons.group_outlined,
              size: 64,
              color: Colors.grey,
            ),
            SizedBox(height: 16),
            Text(
              _tabController.index == 0 
                ? 'Нет личных чатов' 
                : 'Нет групповых чатов',
              style: TextStyle(fontSize: 18, color: Colors.grey),
            ),
            SizedBox(height: 8),
            Text(
              _tabController.index == 0 
                ? 'Создайте первый личный чат' 
                : 'Создайте первый групповой чат',
              style: TextStyle(color: Colors.grey),
            ),
          ],
        ),
      );
    }

    return ListView.builder(
      itemCount: chats.length,
      itemBuilder: (context, index) {
        final chat = chats[index];
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
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Чаты'),

        bottom: TabBar(
          controller: _tabController,
          tabs: [
            Tab(icon: Icon(Icons.person), text: 'Личные'),
            Tab(icon: Icon(Icons.group), text: 'Групповые'),
          ],
          onTap: (index) {
            setState(() {});
          },
        ),      
        
        
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
          : TabBarView(
              controller: _tabController,
              children: [
                // Вкладка личных чатов
                SmartRefresher(
                  controller: _refreshController,
                  onRefresh: _onRefresh,
                  child: _buildChatsList(_personalChats),
                ),
                // Вкладка групповых чатов
                SmartRefresher(
                  controller: RefreshController(),
                  onRefresh: _onRefresh,
                  child: _buildChatsList(_groupChats),
                ),
              ],
            ),
      floatingActionButton: FloatingActionButton(
        onPressed: () async {
          final result = await Navigator.push(
            context,
            MaterialPageRoute(builder: (context) => CreateChatScreen()),
          );
          
          if (result is Chat) {
            print('[DEBUG] Получен созданный чат: ${result.id}');
            await _loadData();
            
            await Future.delayed(Duration(milliseconds: 300));
            
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (context) => ChatScreen(chat: result),
              ),
            ).then((_) => _loadData());
          } else if (result == null) {
            _loadData();
          }
        },
        child: Icon(Icons.add),
      ),
    );
  }
  // В классе _ChatsScreenState добавьте методы для подсчета
  int get _unreadPersonalCount {
    return _personalChats.where((chat) => chat.hasUnread).length;
  }

  int get _unreadGroupCount {
    return _groupChats.where((chat) => chat.hasUnread).length;
  }

  List<Chat> _addTestUnreadCounts(List<Chat> originalChats) {
    return originalChats.map((chat) {
      // Тестовые данные: делаем первые 2 личных и 1 групповой чат непрочитанными
      bool makeUnread = false;
      
      if (!chat.isGroup) {
        // Первые 2 личных чата
        final personalIndex = originalChats
            .where((c) => !c.isGroup)
            .toList()
            .indexOf(chat);
        makeUnread = personalIndex < 2;
      } else {
        // Первый групповой чат
        final groupIndex = originalChats
            .where((c) => c.isGroup)
            .toList()
            .indexOf(chat);
        makeUnread = groupIndex == 0;
      }
      
      if (makeUnread) {
        print('[TEST] Делаю чат ${chat.id} "${chat.name}" непрочитанным');
        return Chat(
          id: chat.id,
          name: chat.name,
          avatar: chat.avatar,
          isGroup: chat.isGroup,
          createdAt: chat.createdAt,
          userIds: chat.userIds,
          lastMessageId: chat.lastMessageId,
          lastMessage: chat.lastMessage,
          members: chat.members,
          unreadCount: 3, // Тестовое значение
          creator: chat.creator,
        );
      }
      
      return chat;
    }).toList();
  }


  
}