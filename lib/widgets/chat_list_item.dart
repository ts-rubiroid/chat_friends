import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:chat_friends/models/chat.dart';
import 'package:chat_friends/models/user.dart';

class ChatListItem extends StatelessWidget {
  final Chat chat;
  final User currentUser;
  final List<User>? allUsers;
  final VoidCallback onTap;
  
  const ChatListItem({
    super.key,
    required this.chat,
    required this.currentUser,
    this.allUsers,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final otherUser = chat.isGroup ? null : chat.getOtherUser(currentUser, allUsers);
    final isCurrentUserCreator = _isCurrentUserCreator();
    final displayCreator = _getDisplayCreator();
    
    return Container(
      padding: EdgeInsets.symmetric(vertical: 4),
      decoration: BoxDecoration(
        color: chat.isGroup ? Colors.grey[50] : Colors.transparent,
        borderRadius: BorderRadius.circular(8),
      ),
      child: ListTile(
        onTap: onTap,
        leading: _buildAvatar(otherUser, displayCreator),
        title: _buildTitle(otherUser),
        subtitle: _buildSubtitle(otherUser, displayCreator),
        trailing: _buildTrailing(),
        contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        minVerticalPadding: 12,
      ),
    );
  }

  Widget _buildAvatar(User? otherUser, User? displayCreator) {
    // === ОТЛАДОЧНЫЙ КОД ===
    print('══════════════════════════════════════');
    print('🔄 DEBUG AVATAR для чата ID: ${chat.id}');
    print('📝 Название чата: ${chat.name}');
    print('👥 Тип: ${chat.isGroup ? "Групповой" : "Личный"}');
    
    if (chat.isGroup) {
      print('👑 Групповой чат');
      if (displayCreator != null) {
        print('   Создатель ID: ${displayCreator.id}');
        print('   Создатель имя: ${displayCreator.displayName}');
        print('   Поле avatar (сырое): ${displayCreator.avatar}');
        print('   Метод avatarUrl: ${displayCreator.avatarUrl}');
        print('   hasAvatar: ${displayCreator.hasAvatar}');
        print('   Длина URL: ${displayCreator.avatarUrl.length}');
        print('   Начинается с http?: ${displayCreator.avatarUrl.startsWith("http")}');
      } else {
        print('   ❌ Создатель не найден');
      }
    } else {
      print('🤝 Личный чат');
      if (otherUser != null) {
        print('   Собеседник ID: ${otherUser.id}');
        print('   Собеседник имя: ${otherUser.displayName}');
        print('   Поле avatar (сырое): "${otherUser.avatar}"');
        print('   Метод avatarUrl: "${otherUser.avatarUrl}"');
        print('   hasAvatar: ${otherUser.hasAvatar}');
        print('   Длина URL: ${otherUser.avatarUrl.length}');
        print('   Начинается с http?: ${otherUser.avatarUrl.startsWith("http")}');
        print('   Начинается с https?: ${otherUser.avatarUrl.startsWith("https")}');
        print('   Содержит \\/: ${otherUser.avatar?.contains(r"\/") ?? false}');
        print('   Явная проверка: avatar != null: ${otherUser.avatar != null}');
        print('   Явная проверка: avatar.isNotEmpty: ${otherUser.avatar?.isNotEmpty ?? false}');
        print('   Явная проверка: avatar != "null": ${otherUser.avatar != "null"}');
        print('   Явная проверка: avatar != "false": ${otherUser.avatar != "false"}');
      } else {
        print('   ❌ Собеседник не найден');
      }
    }
    print('══════════════════════════════════════');
    // === КОНЕЦ ОТЛАДОЧНОГО КОДА ===
    
    // Определяем аватар для отображения
    String avatarUrl = '';
    String fallbackText = '?';
    Color fallbackColor = Colors.blue;
    
    if (chat.isGroup) {
      // Групповой чат
      if (displayCreator != null) {
        // ПРЯМАЯ ПРОВЕРКА аватара создателя
        if (displayCreator.avatar != null && 
            displayCreator.avatar!.isNotEmpty && 
            displayCreator.avatar != 'null' &&
            displayCreator.avatar != 'false') {
          
          // Очищаем URL от экранированных слешей
          avatarUrl = displayCreator.avatar!.replaceAll(r'\/', '/');
          print('✅ Используем аватар создателя: $avatarUrl');
        }
        fallbackText = displayCreator.initials;
        fallbackColor = displayCreator.avatarColor;
      } else {
        fallbackText = chat.initials;
        fallbackColor = chat.avatarColor;
      }
    } else {
      // Личный чат
      if (otherUser != null) {
        // ПРЯМАЯ ПРОВЕРКА аватара собеседника
        if (otherUser.avatar != null && 
            otherUser.avatar!.isNotEmpty && 
            otherUser.avatar != 'null' &&
            otherUser.avatar != 'false') {
          
          // Очищаем URL от экранированных слешей
          avatarUrl = otherUser.avatar!.replaceAll(r'\/', '/');
          print('✅ Используем аватар собеседника: $avatarUrl');
        }
        fallbackText = otherUser.initials;
        fallbackColor = otherUser.avatarColor;
      }
    }
    
    // Если не нашли аватар вручную, пробуем через метод
    if (avatarUrl.isEmpty && otherUser != null && !chat.isGroup) {
      avatarUrl = otherUser.avatarUrl;
      print('🔄 Пробуем через avatarUrl метод: $avatarUrl');
    }
    
    // Финальная проверка URL
    final isValidUrl = avatarUrl.isNotEmpty && 
                      avatarUrl.startsWith('http') &&
                      !avatarUrl.contains(r'\/');
    
    print('🔍 Финальная проверка:');
    print('   avatarUrl: "$avatarUrl"');
    print('   isValidUrl: $isValidUrl');
    print('   isNotEmpty: ${avatarUrl.isNotEmpty}');
    print('   startsWith http: ${avatarUrl.startsWith("http")}');
    print('   содержит \/: ${avatarUrl.contains(r"\/")}');
    
    return Stack(
      children: [
        Container(
          width: 50,
          height: 50,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: isValidUrl ? Colors.transparent : fallbackColor,
          ),
          child: isValidUrl
              ? ClipOval(
                  child: Image.network(
                    avatarUrl,
                    fit: BoxFit.cover,
                    loadingBuilder: (context, child, loadingProgress) {
                      if (loadingProgress == null) return child;
                      return Center(
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          valueColor: AlwaysStoppedAnimation<Color>(Colors.green),
                        ),
                      );
                    },
                    errorBuilder: (context, error, stackTrace) {
                      print('❌ Ошибка загрузки аватара: $error');
                      print('❌ URL: $avatarUrl');
                      return _buildFallbackAvatar(fallbackText, fallbackColor);
                    },
                  ),
                )
              : _buildFallbackAvatar(fallbackText, fallbackColor),
        ),
        // Зелёный кружок для непрочитанных
        if (chat.hasUnread)
          Positioned(
            right: 0,
            top: 0,
            child: Container(
              width: 12,
              height: 12,
              decoration: BoxDecoration(
                color: Colors.green,
                shape: BoxShape.circle,
                border: Border.all(color: Colors.white, width: 2),
              ),
            ),
          ),
      ],
    );
  }

  // Вспомогательный метод для отображения аватара с инициалами
  Widget _buildFallbackAvatar(String text, Color color) {
    return Container(
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: color,
      ),
      child: Center(
        child: Text(
          text,
          style: TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.bold,
            fontSize: 16,
          ),
        ),
      ),
    );
  }

  Widget _buildTitle(User? otherUser) {
    if (chat.isGroup) {
      // ДЛЯ ГРУППОВЫХ: Просто показываем название чата
      return Text(
        chat.name.isNotEmpty ? chat.name : 'Групповой чат',
        style: TextStyle(
          fontWeight: chat.hasUnread ? FontWeight.bold : FontWeight.normal,
          fontSize: 16,
          color: chat.hasUnread ? Colors.black : Colors.grey[800],
        ),
        overflow: TextOverflow.ellipsis,
        maxLines: 1,
      );
    } else {
      // ДЛЯ ЛИЧНЫХ: имя собеседника
      String title;
      
      if (otherUser != null && otherUser.id > 0) {
        title = otherUser.displayName;
      } else {
        title = chat.name;
      }
      
      return Text(
        title,
        style: TextStyle(
          fontWeight: chat.hasUnread ? FontWeight.bold : FontWeight.normal,
          fontSize: 16,
          color: chat.hasUnread ? Colors.black : Colors.grey[800],
        ),
        overflow: TextOverflow.ellipsis,
        maxLines: 1,
      );
    }
  }

  Widget _buildSubtitle(User? otherUser, User? displayCreator) {
    if (chat.isGroup) {
      // Групповой чат
      final creatorName = displayCreator?.displayName ?? 'Неизвестно';
      final memberCount = _getGroupMemberCount();
      final countText = _getParticipantsText(memberCount);
      
      return Text(
        'Создал: $creatorName, $countText',
        style: TextStyle(
          color: chat.hasUnread ? Colors.black87 : Colors.grey[600],
          fontSize: 12,
        ),
        overflow: TextOverflow.ellipsis,
        maxLines: 1,
      );
    } else {
      // Личный чат
      final messagePreview = chat.getLastMessagePreview();
      return Text(
        messagePreview,
        style: TextStyle(
          color: chat.hasUnread ? Colors.black87 : Colors.grey[600],
          fontSize: 14,
          fontStyle: messagePreview == 'Нет сообщений' ? FontStyle.italic : FontStyle.normal,
        ),
        overflow: TextOverflow.ellipsis,
        maxLines: 1,
      );
    }
  }

  Widget _buildTrailing() {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        if (chat.lastMessage != null && chat.lastMessage!.createdAt != null)
          Text(
            _formatTime(chat.lastMessage!.createdAt!),
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey[500],
            ),
          ),
        SizedBox(height: 4),
        if (chat.hasUnread && chat.unreadCount > 1)
          Container(
            padding: EdgeInsets.symmetric(horizontal: 6, vertical: 2),
            decoration: BoxDecoration(
              color: Colors.green,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Text(
              chat.unreadCount > 9 ? '9+' : chat.unreadCount.toString(),
              style: TextStyle(
                color: Colors.white,
                fontSize: 10,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
      ],
    );
  }

  // Вспомогательные методы (без изменений)
  bool _isCurrentUserCreator() {
    if (!chat.isGroup) return false;
    
    // Проверяем, есть ли текущий пользователь в участниках
    if (chat.members != null) {
      return chat.members!.any((member) => member.id == currentUser.id);
    }
    
    return false;
  }

  User? _getDisplayCreator() {
    if (!chat.isGroup) return null;
    
    // 1. Сначала проверяем, может текущий пользователь - создатель
    if (_isCurrentUserCreator()) {
      return currentUser;
    }
    
    // 2. Ищем первого участника (временное решение)
    if (chat.members != null && chat.members!.isNotEmpty) {
      return chat.members!.first;
    }
    
    return null;
  }

  String _getGroupFallbackText() {
    final creator = _getDisplayCreator();
    if (creator != null) {
      return creator.shortName;
    }
    
    // ВАЖНО: Проверяем что имя не пустое перед доступом к [0]
    if (chat.name.isNotEmpty) {
      return chat.name[0].toUpperCase();
    }
    
    return 'Г'; // Запасной вариант
  }

  int _getGroupMemberCount() {
    if (!chat.isGroup) return 0;
    
    if (chat.members != null) {
      return chat.members!.length;
    }
    
    if (chat.userIds != null) {
      return chat.userIds!.length;
    }
    
    return 0;
  }

  String _getParticipantsText(int count) {
    if (count == 0) return 'нет участников';
    
    final lastDigit = count % 10;
    final lastTwoDigits = count % 100;
    
    if (lastTwoDigits >= 11 && lastTwoDigits <= 19) {
      return '$count участников';
    }
    
    switch (lastDigit) {
      case 1:
        return '$count участник';
      case 2:
      case 3:
      case 4:
        return '$count участника';
      default:
        return '$count участников';
    }
  }

  String _formatTime(DateTime time) {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final yesterday = today.subtract(Duration(days: 1));
    
    if (time.isAfter(today)) {
      return '${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}';
    } else if (time.isAfter(yesterday)) {
      return 'Вчера';
    } else {
      return '${time.day.toString().padLeft(2, '0')}.${time.month.toString().padLeft(2, '0')}';
    }
  }
}