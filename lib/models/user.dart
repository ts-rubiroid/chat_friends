import 'package:flutter/material.dart';
import '../utils/api.dart';

class User {
  final int id;
  final String? phone;
  final String? avatar;
  final String? lastName;
  final String? firstName;
  final String? middleName;
  final String? nickname;
  final DateTime? createdAt;

  User({
    required this.id,
    this.phone,
    this.avatar,
    this.lastName,
    this.firstName,
    this.middleName,
    this.nickname,
    this.createdAt,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: _parseInt(json['id']),
      phone: json['phone']?.toString(),
      // ИСПРАВЛЕНИЕ: Используем очистку URL
      avatar: _cleanAvatarUrl(json['avatar']?.toString()),
      lastName: json['last_name']?.toString(),
      firstName: json['first_name']?.toString(),
      middleName: json['middle_name']?.toString(),
      nickname: json['nickname']?.toString(),
      createdAt: _parseDateTime(json['created_at']),
    );
  }

  static int _parseInt(dynamic value) {
    if (value == null) return 0;
    if (value is int) return value;
    if (value is String) {
      if (value == 'null' || value.isEmpty) return 0;
      return int.tryParse(value) ?? 0;
    }
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

  // ДОБАВЬТЕ ЭТОТ МЕТОД СЮДА:
  static String? _cleanAvatarUrl(String? url) {
    if (url == null || url.isEmpty) return null;
    
    // Убираем экранированные слеши из JSON
    final cleanedUrl = url.replaceAll(r'\/', '/');
    
    // Убираем возможные двойные слеши
    return cleanedUrl.replaceAll('//', '/').replaceFirst(':/', '://');
  }

  // КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Метод должен возвращать строку, а не null
  String get avatarUrl {
    // 1. Если аватар есть и валиден
    if (avatar != null && avatar!.isNotEmpty && avatar != 'null' && avatar != 'false') {
      // Уже очищено в конструкторе, но на всякий случай еще раз
      final cleanedUrl = avatar!.replaceAll(r'\/', '/');
      
      if (cleanedUrl.startsWith('http')) {
        return cleanedUrl;
      }
      
      // Если относительный путь
      if (ApiConfig.uploadsUrl != null) {
        // Если начинается с /, убираем его
        final path = cleanedUrl.startsWith('/') ? cleanedUrl.substring(1) : cleanedUrl;
        return '${ApiConfig.uploadsUrl}/$path';
      }
      
      // Последняя попытка - добавляем base URL
      if (!cleanedUrl.startsWith('http')) {
        return 'https://chat.remont-gazon.ru$cleanedUrl';
      }
    }
    
    // 2. Если аватара нет - возвращаем пустую строку
    return '';
  }

  // ... остальные методы класса User остаются без изменений
  // Метод для проверки, есть ли реальный URL аватарки
  bool get hasAvatar => avatarUrl.isNotEmpty;

  // Генерация инициалов для дефолтного аватара
  String get initials {
    // Сначала пробуем никнейм
    if (nickname != null && nickname!.isNotEmpty) {
      return nickname![0].toUpperCase();
    }
    
    // Потом пробуем имя + фамилия
    if (firstName != null && firstName!.isNotEmpty && lastName != null && lastName!.isNotEmpty) {
      return '${firstName![0]}${lastName![0]}'.toUpperCase();
    }
    
    // Только имя
    if (firstName != null && firstName!.isNotEmpty) {
      final length = firstName!.length;
      if (length >= 2) {
        return firstName!.substring(0, 2).toUpperCase();
      }
      if (length == 1) {
        return firstName!.toUpperCase();
      }
    }
    
    // Только фамилия
    if (lastName != null && lastName!.isNotEmpty) {
      final length = lastName!.length;
      if (length >= 2) {
        return lastName!.substring(0, 2).toUpperCase();
      }
      if (length == 1) {
        return lastName!.toUpperCase();
      }
    }
    
    // Если телефон есть - используем первую цифру
    if (phone != null && phone!.isNotEmpty) {
      final digits = phone!.replaceAll(RegExp(r'[^0-9]'), '');
      if (digits.isNotEmpty) {
        return digits[0];
      }
    }
    
    // Запасной вариант - безопасный
    return 'U';
  }

  // Короткое имя для отображения в аватаре
  String get shortName {
    return initials;
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
      Colors.brown.shade700,
      Colors.cyan.shade700,
      Colors.deepOrange.shade700,
      Colors.lime.shade700,
    ];
    return colors[id % colors.length];
  }

  String get displayName {
    // 1. Проверяем никнейм
    if (nickname != null && nickname!.isNotEmpty) {
      return nickname!;
    }
    
    // 2. Формируем полное имя из частей
    final List<String> parts = [];
    if (firstName != null && firstName!.isNotEmpty) parts.add(firstName!);
    if (lastName != null && lastName!.isNotEmpty) parts.add(lastName!);
    
    if (parts.isNotEmpty) {
      return parts.join(' ');
    }
    
    // 3. Если есть телефон, показываем его
    if (phone != null && phone!.isNotEmpty) {
      // Обрезаем телефон для красоты: +79161234567 → +7*** *** **67
      final phoneStr = phone!;
      if (phoneStr.length > 4) {
        return 'Пользователь ${phoneStr.substring(0, 2)}***${phoneStr.substring(phoneStr.length - 2)}';
      }
      return 'Пользователь $phoneStr';
    }
    
    // 4. Если совсем нет данных
    return 'Пользователь';
  }

  // Также добавьте метод для отладки (в конце класса User):
  Map<String, dynamic> toDebugMap() {
    return {
      'id': id,
      'phone': phone,
      'firstName': firstName,
      'lastName': lastName,
      'nickname': nickname,
      'displayName': displayName,
      'hasAvatar': hasAvatar,
      'avatarUrl': avatarUrl,
    };
  }

  // Для создания пустого пользователя
  static User empty() {
    return User(
      id: 0,
      phone: null,
      avatar: null,
      lastName: null,
      firstName: null,
      middleName: null,
      nickname: null,
      createdAt: null,
    );
  }
}