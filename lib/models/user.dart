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

  String get displayName {
    List<String> parts = [];
    if (lastName != null && lastName!.isNotEmpty) parts.add(lastName!);
    if (firstName != null && firstName!.isNotEmpty) parts.add(firstName!);
    if (middleName != null && middleName!.isNotEmpty) parts.add(middleName!);
    return parts.isEmpty ? (nickname ?? phone ?? 'Пользователь $id') : parts.join(' ');
  }

  String? get avatarUrl {
    if (avatar == null || avatar!.isEmpty) return null;
    if (avatar!.startsWith('http')) return avatar;
    return '${ApiConfig.uploadsUrl}/$avatar';
  }
}