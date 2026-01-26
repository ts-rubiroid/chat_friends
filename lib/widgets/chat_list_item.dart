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
    String? avatarUrl;
    String fallbackText;
    
    if (chat.isGroup) {
      // Для группового чата - используем аватар "предполагаемого" создателя
      if (displayCreator != null) {
        avatarUrl = displayCreator.avatarUrl;
        fallbackText = displayCreator.shortName;
      } else {
        // Если не нашли создателя, используем первую букву названия
        fallbackText = _getGroupFallbackText();
      }
    } else {
      // Для личного чата - аватар собеседника
      avatarUrl = otherUser?.avatarUrl;
      fallbackText = otherUser?.shortName ?? '?';
    }
    
    return Stack(
      children: [
        Container(
          width: 50,
          height: 50,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: chat.isGroup ? Colors.blueGrey[100] : Colors.grey[300],
          ),
          child: avatarUrl != null && avatarUrl.isNotEmpty
              ? ClipOval(
                  child: CachedNetworkImage(
                    imageUrl: avatarUrl,
                    placeholder: (context, url) => Center(
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        valueColor: AlwaysStoppedAnimation<Color>(Colors.green),
                      ),
                    ),
                    errorWidget: (context, url, error) => Center(
                      child: Text(
                        fallbackText,
                        style: TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                          fontSize: 18,
                        ),
                      ),
                    ),
                    fit: BoxFit.cover,
                  ),
                )
              : Center(
                  child: Text(
                    fallbackText,
                    style: TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                      fontSize: 18,
                    ),
                  ),
                ),
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

  // Вспомогательные методы
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

  String _getCorrectedGroupName(bool isCurrentUserCreator) {
    if (isCurrentUserCreator) {
      return 'Моя группа';
    }
    
    final creator = _getDisplayCreator();
    if (creator != null) {
      return 'Группа ${creator.displayName}';
    }
    
    return 'Групповой чат';
  }

  String _getGroupFallbackText() {
    final creator = _getDisplayCreator();
    if (creator != null) {
      return creator.shortName;
    }
    
    return chat.name.isNotEmpty ? chat.name[0].toUpperCase() : 'Г';
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