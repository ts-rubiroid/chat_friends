import 'package:flutter/material.dart';
import '../utils/api.dart';
import 'message.dart';
import 'user.dart';

class Chat {
  final int id;
  final String name;
  final String? avatar;
  final bool isGroup;
  final DateTime? createdAt;
  final List<int>? userIds;
  final int? lastMessageId;
  final Message? lastMessage;
  final List<User>? members;
  final int unreadCount;
  final User? creator; // Создатель чата

  Chat({
    required this.id,
    required this.name,
    this.avatar,
    required this.isGroup,
    this.createdAt,
    this.userIds,
    this.lastMessageId,
    this.lastMessage,
    this.members,
    this.unreadCount = 0,
    this.creator,
  });

  factory Chat.fromJson(Map<String, dynamic> json) {
    // Парсим участников
    List<User>? members;
    if (json['members'] != null && json['members'] is List) {
      members = (json['members'] as List)
          .whereType<Map<String, dynamic>>()
          .map((memberJson) => User.fromJson(memberJson))
          .toList();
    }

    // Парсим последнее сообщение
    Message? lastMessage;
    if (json['last_message'] != null && json['last_message'] is Map<String, dynamic>) {
      lastMessage = Message.fromJson(json['last_message'] as Map<String, dynamic>);
    }

    // Ищем создателя в нескольких возможных местах
    User? creator;
    
    // 1. Прямое поле 'creator'
    if (json['creator'] != null && json['creator'] is Map<String, dynamic>) {
      creator = User.fromJson(json['creator'] as Map<String, dynamic>);
    }
    // 2. Поле 'created_by' 
    else if (json['created_by'] != null && json['created_by'] is Map<String, dynamic>) {
      creator = User.fromJson(json['created_by'] as Map<String, dynamic>);
    }
    // 3. Поле 'author'
    else if (json['author'] != null && json['author'] is Map<String, dynamic>) {
      creator = User.fromJson(json['author'] as Map<String, dynamic>);
    }
    // 4. Вложенное поле 'data.creator'
    else if (json['data'] != null && 
             json['data'] is Map<String, dynamic> && 
             json['data']['creator'] != null) {
      creator = User.fromJson(json['data']['creator'] as Map<String, dynamic>);
    }
    // 5. Первый участник как создатель (резервный вариант)
    else if (members != null && members.isNotEmpty) {
      creator = members.first;
    }

    // КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Добавляем обработку поля 'members_count'
    final membersCount = json['members_count'] ?? 
                        json['membersCount'] ?? 
                        (members != null ? members.length : 0);

    return Chat(
      id: _parseInt(json['id']),
      name: json['name']?.toString() ?? 'Без названия',
      // Важно: avatar может быть null - это нормально!
      avatar: json['avatar']?.toString(),
      isGroup: json['is_group'] == true || json['is_group'] == 1 || json['is_group'] == '1',
      createdAt: _parseDateTime(json['created_at'] ?? json['date']),
      userIds: json['user_ids'] is List
          ? (json['user_ids'] as List).map((e) => _parseInt(e)).toList()
          : null,
      lastMessageId: _parseInt(json['last_message_id']),
      lastMessage: lastMessage,
      members: members,
      // Используем поле из API или вычисляем
      unreadCount: _parseInt(json['unread_count'] ?? json['unreadCount']),
      creator: creator,
    );
  }

  static int _parseInt(dynamic value) {
    if (value == null) return 0;
    if (value is int) return value;
    if (value is String) {
      if (value == 'null' || value.isEmpty) return 0;
      return int.tryParse(value) ?? 0;
    }
    if (value is bool) return value ? 1 : 0;
    return 0;
  }

  static DateTime? _parseDateTime(dynamic value) {
    if (value == null) return null;
    if (value is DateTime) return value;
    if (value is String) {
      if (value == 'null' || value.isEmpty) return null;
      try {
        return DateTime.parse(value);
      } catch (_) {
        return null;
      }
    }
    return null;
  }

  // КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Метод должен возвращать строку, а не null
  String get avatarUrl {
    // 1. Если аватар есть и валиден
    if (avatar != null && avatar!.isNotEmpty && avatar != 'null' && avatar != 'false') {
      if (avatar!.startsWith('http')) {
        return avatar!;
      }
      // Если относительный путь
      if (ApiConfig.uploadsUrl != null) {
        return '${ApiConfig.uploadsUrl}/$avatar';
      }
    }
    
    // 2. Если аватара нет - возвращаем пустую строку
    // Это важно для UI, чтобы не получать null
    return '';
  }

  // Метод для проверки, есть ли реальный URL аватарки
  bool get hasAvatar => avatarUrl.isNotEmpty;

  // Генерация инициалов для дефолтного аватара
  String get initials {
    if (name.isNotEmpty) {
      final words = name.split(' ');
      
      // ВАЖНО: Проверяем что массив слов не пустой
      if (words.isNotEmpty) {
        if (words.length >= 2) {
          // Проверяем что оба слова имеют хотя бы 1 символ
          if (words[0].isNotEmpty && words[1].isNotEmpty) {
            return '${words[0][0]}${words[1][0]}'.toUpperCase();
          }
        }
        
        // Безопасный доступ к первому слову
        if (words[0].isNotEmpty) {
          final length = words[0].length;
          if (length >= 2) {
            return words[0].substring(0, 2).toUpperCase();
          }
          if (length == 1) {
            return words[0][0].toUpperCase();
          }
        }
      }
    }
    return '?';
  }


  // Цвет для дефолтного аватара на основе ID
  Color get avatarColor {
    final colors = [
      Colors.blue.shade700,
      Colors.green.shade700,
      Colors.orange.shade700,
      Colors.purple.shade700,
      Colors.red.shade700,
      Colors.teal.shade700,
      Colors.indigo.shade700,
      Colors.pink.shade700,
    ];
    return colors[id % colors.length];
  }

  // Вспомогательные методы для UI (остаются без изменений)
  String getDisplayName(User currentUser, {List<User>? allUsers}) {
    if (isGroup) {
      return name;
    } else {
      final otherUser = getOtherUser(currentUser, allUsers);
      return otherUser?.displayName ?? 'Личный чат';
    }
  }

  User? getOtherUser(User currentUser, List<User>? allUsers) {
    if (isGroup) return null;
    
    // 1. Ищем в members
    if (members != null && members!.isNotEmpty) {
      for (final user in members!) {
        if (user.id != currentUser.id && user.id > 0) {
          return user;
        }
      }
    }
    
    // 2. Ищем в allUsers по userIds
    if (userIds != null && userIds!.isNotEmpty && allUsers != null) {
      for (final id in userIds!) {
        if (id != currentUser.id && id > 0) {
          final user = allUsers.firstWhere(
            (u) => u.id == id,
            orElse: () => User.empty(),
          );
          if (user.id > 0) return user;
        }
      }
    }
    
    return null;
  }

  String getSubtitle(User currentUser, {List<User>? allUsers}) {
    if (isGroup) {
      return 'Групповой чат: $name';
    } else {
      final otherUser = getOtherUser(currentUser, allUsers);
      final otherName = otherUser?.displayName ?? 'Пользователь';
      return 'Личный чат с: $otherName';
    }
  }

  String getLastMessagePreview() {
    if (lastMessage != null && lastMessage!.text != null && lastMessage!.text!.isNotEmpty) {
      final text = lastMessage!.text!;
      if (text.length > 40) {
        return '${text.substring(0, 40)}...';
      }
      return text;
    }
    return 'Нет сообщений';
  }

  bool get hasUnread => unreadCount > 0;

  String getCorrectedName() {
    if (!isGroup) return name;
    
    // Исправляем неправильное название от сервера
    if (name.contains('Сообщение от пользователя')) {
      // Возвращаем имя первого участника + " (группа)"
      if (members != null && members!.isNotEmpty) {
        return '${members!.first.displayName} (группа)';
      }
    }
    
    return name;
  }

  // Имя создателя для отображения (ник или имя)
  String get creatorDisplayName {
    if (creator != null) {
      // Используем никнейм если есть, иначе имя
      if (creator!.nickname != null && creator!.nickname!.isNotEmpty) {
        return creator!.nickname!;
      }
      return creator!.firstName ?? 'Неизвестно';
    }
    return 'Неизвестно';
  }

  // Количество участников
  int get memberCount {
    if (members != null && members!.isNotEmpty) {
      return members!.length;
    }
    if (userIds != null && userIds!.isNotEmpty) {
      return userIds!.length;
    }
    return 0;
  }

  // Текст с правильным склонением "участников"
  String get memberCountText {
    final count = memberCount;
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

  // Фон для группового чата (темнее чем для личного)
  Color get backgroundColor {
    if (isGroup) {
      return Colors.grey[200]!; // Темный серый для групповых
    }
    return Colors.transparent; // Прозрачный для личных
  }
}