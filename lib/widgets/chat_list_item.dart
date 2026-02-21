import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:chat_friends/models/chat.dart';
import 'package:chat_friends/models/user.dart';

class ChatListItem extends StatelessWidget {
  final Chat chat;
  final User currentUser;
  final List<User>? allUsers;
  final VoidCallback onTap;
  final bool? hasUnread; // ← НОВЫЙ ПАРАМЕТР для локального unread
  
  const ChatListItem({
    super.key,
    required this.chat,
    required this.currentUser,
    this.allUsers,
    required this.onTap,
    this.hasUnread, // ← Добавлен в конструктор
  });

  @override
  Widget build(BuildContext context) {
    final otherUser = chat.isGroup ? null : chat.getOtherUser(currentUser, allUsers);
    final isCurrentUserCreator = _isCurrentUserCreator();
    final displayCreator = _getDisplayCreator();
    
    // ВАЖНО: Используем локальный hasUnread если передан, иначе серверный
    final bool showUnreadIndicator = hasUnread ?? chat.hasUnread;
    
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: ListTile(
        onTap: onTap,
        leading: _buildAvatar(otherUser, displayCreator, showUnreadIndicator),
        title: _buildTitle(otherUser, showUnreadIndicator),
        subtitle: _buildSubtitle(otherUser, displayCreator, showUnreadIndicator),
        trailing: _buildTrailing(showUnreadIndicator),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
        minVerticalPadding: 12,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        tileColor: const Color(0xFF222632),
      ),
    );
  }

  Widget _buildAvatar(User? otherUser, User? displayCreator, bool showUnreadIndicator) {
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
        }
        fallbackText = otherUser.initials;
        fallbackColor = otherUser.avatarColor;
      }
    }
    
    // Если не нашли аватар вручную, пробуем через метод
    if (avatarUrl.isEmpty && otherUser != null && !chat.isGroup) {
      avatarUrl = otherUser.avatarUrl;
    }
    
    final isValidUrl = avatarUrl.isNotEmpty && 
                      avatarUrl.startsWith('http') &&
                      !avatarUrl.contains(r'\/');
    
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
                    errorBuilder: (context, error, stackTrace) =>
                        _buildFallbackAvatar(fallbackText, fallbackColor),
                  ),
                )
              : _buildFallbackAvatar(fallbackText, fallbackColor),
        ),
        // Зелёный кружок для непрочитанных - используем локальный статус
        if (showUnreadIndicator)
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

  Widget _buildTitle(User? otherUser, bool showUnreadIndicator) {
    if (chat.isGroup) {
      // ДЛЯ ГРУППОВЫХ: Просто показываем название чата
      return Text(
        chat.name.isNotEmpty ? chat.name : 'Групповой чат',
        style: TextStyle(
          fontWeight: showUnreadIndicator ? FontWeight.w600 : FontWeight.w500,
          fontSize: 16,
          color: Colors.white,
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
          fontWeight: showUnreadIndicator ? FontWeight.w600 : FontWeight.w500,
          fontSize: 16,
          color: Colors.white,
        ),
        overflow: TextOverflow.ellipsis,
        maxLines: 1,
      );
    }
  }

  Widget _buildSubtitle(User? otherUser, User? displayCreator, bool showUnreadIndicator) {
    if (chat.isGroup) {
      // Групповой чат - ВЫВОДИМ СПИСОК ИМЕН УЧАСТНИКОВ + создателя
      final participantsNames = _getParticipantsNames();
      final creatorName = displayCreator?.displayName ?? 'Неизвестно';
      
      if (participantsNames.isNotEmpty) {
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Создан: $creatorName',
              style: TextStyle(
                color: showUnreadIndicator ? Colors.white70 : Colors.white54,
                fontSize: 11, // Меньший размер для создателя
              ),
              overflow: TextOverflow.ellipsis,
              maxLines: 1,
            ),
            SizedBox(height: 2),
            Text(
              participantsNames,
              style: TextStyle(
                color: showUnreadIndicator ? Colors.white70 : Colors.white54,
                fontSize: 12,
              ),
              overflow: TextOverflow.ellipsis,
              maxLines: 1,
            ),
          ],
        );
      } else {
        // Если не удалось получить имена участников
        final memberCount = _getGroupMemberCount();
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Создан: $creatorName',
              style: TextStyle(
                color: showUnreadIndicator ? Colors.white70 : Colors.white54,
                fontSize: 11,
              ),
              overflow: TextOverflow.ellipsis,
              maxLines: 1,
            ),
            SizedBox(height: 2),
            Text(
              '${memberCount} участник(ов)',
              style: TextStyle(
                color: showUnreadIndicator ? Colors.white70 : Colors.white54,
                fontSize: 12,
              ),
              overflow: TextOverflow.ellipsis,
              maxLines: 1,
            ),
          ],
        );
      }
    } else {
      // Личный чат - оставляем как было
      final messagePreview = chat.getLastMessagePreview();
      return Text(
        messagePreview,
        style: TextStyle(
          color: showUnreadIndicator ? Colors.white : Colors.white60,
          fontSize: 14,
          fontStyle: messagePreview == 'Нет сообщений' ? FontStyle.italic : FontStyle.normal,
        ),
        overflow: TextOverflow.ellipsis,
        maxLines: 1,
      );
    }
  }

  Widget _buildTrailing(bool showUnreadIndicator) {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        if (chat.lastMessage != null && chat.lastMessage!.createdAt != null)
          Text(
            _formatTime(chat.lastMessage!.createdAt!),
            style: TextStyle(
              fontSize: 12,
              color: Colors.white54,
            ),
          ),
        SizedBox(height: 4),
        // Показываем счетчик только если есть локальные непрочитанные
        // (серверный unreadCount игнорируем, так как он всегда 0)
        if (showUnreadIndicator)
          Container(
            padding: EdgeInsets.symmetric(horizontal: 6, vertical: 2),
            decoration: BoxDecoration(
              color: Color(0xFF4F8BFF),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Text(
              '1', // Показываем просто "1" для локальных непрочитанных
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

  String _getParticipantsNames() {
    if (!chat.isGroup || chat.members == null || chat.members!.isEmpty) {
      return '';
    }
    
    try {
      // Получаем список участников, исключая текущего пользователя
      final otherMembers = chat.members!
          .where((member) => member.id != currentUser.id)
          .toList();
      
      // Если только текущий пользователь в чате
      if (otherMembers.isEmpty) {
        return 'Только вы';
      }
      
      // Ограничиваем до 3-х имен для компактности
      final maxNames = 3;
      final limitedMembers = otherMembers.take(maxNames).toList();
      
      // Собираем имена
      final names = limitedMembers.map((member) {
        // Используем nickname, displayName или firstName
        return member.nickname?.isNotEmpty == true
            ? member.nickname!
            : member.displayName.isNotEmpty
                ? member.displayName
                : member.firstName ?? 'Пользователь';
      }).toList();
      
      String result = names.join(', ');
      
      // Если участников больше, добавляем "и X еще"
      if (otherMembers.length > maxNames) {
        final remaining = otherMembers.length - maxNames;
        result += ' и ещё $remaining';
        
        // Склонение для "ещё"
        final lastDigit = remaining % 10;
        final lastTwoDigits = remaining % 100;
        
        if (lastTwoDigits >= 11 && lastTwoDigits <= 19) {
          result += ' участников';
        } else {
          switch (lastDigit) {
            case 1:
              result += ' участник';
              break;
            case 2:
            case 3:
            case 4:
              result += ' участника';
              break;
            default:
              result += ' участников';
          }
        }
      } else if (otherMembers.length == 1) {
        result += ' (1 участник)';
      } else {
        result += ' (${otherMembers.length} участника)';
      }
      
      return result;
    } catch (e) {
      return '';
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