import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:chat_friends/models/chat.dart';
import 'package:chat_friends/models/user.dart';
import 'package:chat_friends/utils/api.dart';

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
    
    return Container(
      padding: EdgeInsets.symmetric(vertical: 4),
      child: ListTile(
        onTap: onTap,
        leading: _buildAvatar(otherUser),
        title: _buildTitle(otherUser),
        subtitle: _buildSubtitle(otherUser),
        trailing: _buildTrailing(),
        contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        minVerticalPadding: 12,
      ),
    );
  }


  Widget _buildAvatar(User? otherUser) {
    String? avatarUrl;
    String fallbackText;
    
    if (chat.isGroup) {
      // Для группового чата
      avatarUrl = chat.avatarUrl;
      fallbackText = chat.name.isNotEmpty ? chat.name[0].toUpperCase() : 'G';
    } else {
      // Для личного чата - аватар собеседника
      avatarUrl = otherUser?.avatarUrl;
      fallbackText = otherUser?.shortName ?? 'U';
    }
    
    return Stack(
      children: [
        Container(
          width: 50,
          height: 50,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: Colors.grey[300],
          ),
          child: avatarUrl != null
              ? ClipOval(
                  child: CachedNetworkImage(
                    imageUrl: avatarUrl!,
                    placeholder: (context, url) => Center(child: CircularProgressIndicator(strokeWidth: 2)),
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
      // Групповой чат: название + создатель
      final creator = _getGroupCreator();
      final creatorName = creator?.displayName ?? 'Неизвестно';
      
      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Название чата (жирное)
          Text(
            chat.name,
            style: TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 16,
              color: chat.hasUnread ? Colors.black : Colors.grey[800],
            ),
            overflow: TextOverflow.ellipsis,
            maxLines: 1,
          ),
          // Создатель (меньший шрифт)
          SizedBox(height: 2),
          Text(
            'Создан: $creatorName',
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey[600],
            ),
            overflow: TextOverflow.ellipsis,
            maxLines: 1,
          ),
        ],
      );
    } else {
      // Личный чат: "От [Имя/Никнейм]"
      String title;
      
      if (otherUser != null && otherUser.id > 0) {
        title = 'От ${otherUser.displayName}';
      } else {
        title = _extractNameFromChatTitle(chat.name);
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


  Widget _buildSubtitle(User? otherUser) {
    if (chat.isGroup) {
      // Групповой чат: "Группа: [количество] участника"
      final memberCount = _getGroupMemberCount();
      final countText = _getParticipantsText(memberCount);
      
      return Text(
        'Группа: $countText',
        style: TextStyle(
          color: chat.hasUnread ? Colors.black87 : Colors.grey[600],
          fontSize: 14,
        ),
        overflow: TextOverflow.ellipsis,
        maxLines: 1,
      );
    } else {
      // Личный чат: "[начало сообщения...]"
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
  User? _getGroupCreator() {
    if (!chat.isGroup) return null;
    
    // Если есть members, берём первого (обычно это создатель)
    if (chat.members != null && chat.members!.isNotEmpty) {
      return chat.members!.first;
    }
    
    // Если есть userIds, ищем первого пользователя в allUsers
    if (chat.userIds != null && chat.userIds!.isNotEmpty && allUsers != null) {
      for (final id in chat.userIds!) {
        if (id > 0) {
          final creator = allUsers!.firstWhere(
            (user) => user.id == id,
            orElse: () => User.empty(),
          );
          if (creator.id > 0) return creator;
        }
      }
    }
    
    return null;
  }

  int _getGroupMemberCount() {
    if (!chat.isGroup) return 0;
    
    // Из members
    if (chat.members != null && chat.members!.isNotEmpty) {
      return chat.members!.length;
    }
    
    // Из userIds
    if (chat.userIds != null && chat.userIds!.isNotEmpty) {
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

  String _extractNameFromChatTitle(String chatTitle) {
    if (chatTitle.contains('Последнее от')) {
      final idMatch = RegExp(r'пользователя (\d+)').firstMatch(chatTitle);
      if (idMatch != null) {
        final id = idMatch.group(1);
        return 'От Ползовател $id';
      }
    }
    
    if (chatTitle.contains(' и ')) {
      final parts = chatTitle.split(' и ');
      if (parts.length > 1) {
        return 'От ${parts.last}';
      }
    }
    
    return chatTitle;
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