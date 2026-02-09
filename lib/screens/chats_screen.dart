import 'package:chat_friends/utils/local_unread_helper.dart';
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


  // ЭТИ ПОЛЯ ДЛЯ ЛОКАЛЬНЫХ НЕПРОЧИТАННЫХ
  // Map<int, int> _lastSeenByChat = {}; // chatId -> lastSeenMessageId
  Map<int, bool> _hasUnreadByChat = {}; // chatId -> hasUnread
  bool _hasUnreadPersonal = false;
  bool _hasUnreadGroup = false;





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
      
      // Старая сортировка (убрать или оставить, но она использует серверный unreadCount)
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
      
      // ДОБАВЬТЕ ЭТУ СТРОКУ: Загружаем локальные статусы непрочитанных
      await _loadUnreadStatus();
      
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

        // ДОБАВЬТЕ ЭТУ ПРОВЕРКУ: Используем локальный статус вместо серверного
        final hasLocalUnread = _hasUnreadByChat[chat.id] ?? false;

        return ChatListItem(
          chat: chat,
          currentUser: _currentUser!,
          allUsers: _allUsers,
          hasUnread: hasLocalUnread, // ← ПЕРЕДАЕМ ЛОКАЛЬНЫЙ СТАТУС
          onTap: () {
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (context) => ChatScreen(chat: chat),
              ),
            ).then((_) {
              // При возврате обновляем данные
              _loadData();
            });
          },
        );
      },
    );
  }


  /// Загружает состояние для всех чатов и вычисляет непрочитанные
  Future<void> _loadUnreadStatus() async {
    try {
      if (_chats.isEmpty) {
        print('[LocalUnread] Нет чатов для проверки');
        return;
      }
      
      print('[LocalUnread] Проверка ${_chats.length} чатов...');
      
      final Map<int, bool> hasUnreadMap = {};
      bool hasUnreadPersonal = false;
      bool hasUnreadGroup = false;
      
      for (final chat in _chats) {
        // Получаем данные для проверки
        final currentText = chat.getLastMessagePreview();
        final lastMessageTime = chat.lastMessage?.createdAt ?? chat.createdAt;
        
        // Оцениваем количество сообщений (примерно)
        int messageCount = 0;
        if (chat.lastMessage != null) messageCount = 1;
        if (chat.unreadCount > 0) messageCount = chat.unreadCount + 1;
        
        print('  Чат ${chat.id} "${chat.name}":');
        print('    Текст: "$currentText"');
        print('    Время: $lastMessageTime');
        print('    Кол-во сообщений: $messageCount');
        
        // Проверяем есть ли непрочитанные
        final hasUnread = await LocalUnreadHelper.hasUnreadMessages(
          chatId: chat.id,
          currentText: currentText,
          lastMessageTime: lastMessageTime,
          currentMessageCount: messageCount,
        );
        
        hasUnreadMap[chat.id] = hasUnread;
        
        // Обновляем флаги для вкладок
        if (hasUnread) {
          if (chat.isGroup) {
            hasUnreadGroup = true;
          } else {
            hasUnreadPersonal = true;
          }
        }
      }
      
      // Обновляем состояние
      if (mounted) {
        setState(() {
          _hasUnreadByChat = hasUnreadMap;
          _hasUnreadPersonal = hasUnreadPersonal;
          _hasUnreadGroup = hasUnreadGroup;
        });
      }
      
      print('[LocalUnread] ИТОГО:');
      print('[LocalUnread] Непрочитанные личные: $hasUnreadPersonal');
      print('[LocalUnread] Непрочитанные групповые: $hasUnreadGroup');
      print('[LocalUnread] Чатов с непрочитанными: ${hasUnreadMap.values.where((v) => v).length} из ${_chats.length}');
      
    } catch (e) {
      print('[LocalUnread] Ошибка загрузки статусов: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Чаты'),

        bottom: TabBar(
          controller: _tabController,
          tabs: [
            Tab(
              icon: Stack(
                children: [
                  Icon(Icons.person),
                  if (_hasUnreadPersonal)
                    Positioned(
                      right: 0,
                      top: 0,
                      child: Container(
                        padding: EdgeInsets.all(1),
                        decoration: BoxDecoration(
                          color: Colors.red,
                          borderRadius: BorderRadius.circular(6),
                        ),
                        constraints: BoxConstraints(
                          minWidth: 12,
                          minHeight: 12,
                        ),
                      ),
                    ),
                ],
              ),
              text: 'Личные',
            ),
            Tab(
              icon: Stack(
                children: [
                  Icon(Icons.group),
                  if (_hasUnreadGroup)
                    Positioned(
                      right: 0,
                      top: 0,
                      child: Container(
                        padding: EdgeInsets.all(1),
                        decoration: BoxDecoration(
                          color: Colors.red,
                          borderRadius: BorderRadius.circular(6),
                        ),
                        constraints: BoxConstraints(
                          minWidth: 12,
                          minHeight: 12,
                        ),
                      ),
                    ),
                ],
              ),
              text: 'Групповые',
            ),
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
  int get _unreadPersonalCount {
    return _personalChats.where((chat) => chat.hasUnread).length;
  }

  int get _unreadGroupCount {
    return _groupChats.where((chat) => chat.hasUnread).length;
  }
}