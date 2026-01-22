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
      avatar: json['avatar']?.toString(),
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
    if (value is String) return int.tryParse(value) ?? 0;
    return 0;
  }

  static DateTime? _parseDateTime(dynamic value) {
    if (value == null) return null;
    if (value is DateTime) return value;
    if (value is String) {
      try {
        return DateTime.parse(value);
      } catch (_) {
        return null;
      }
    }
    return null;
  }

  // В конце класса User (после метода avatarUrl) добавьте:
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
    };
  }


  String get shortName {
    if (nickname != null && nickname!.isNotEmpty) {
      return nickname![0].toUpperCase();
    }
    
    if (firstName != null && firstName!.isNotEmpty) {
      return firstName![0].toUpperCase();
    }
    
    if (lastName != null && lastName!.isNotEmpty) {
      return lastName![0].toUpperCase();
    }
    
    return 'U';
  }

  String? get avatarUrl {
    if (avatar == null || avatar!.isEmpty || avatar == 'false') return null;
    if (avatar!.startsWith('http')) return avatar;
    return '${ApiConfig.uploadsUrl}/$avatar';
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