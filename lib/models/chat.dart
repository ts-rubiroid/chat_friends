import '../utils/api.dart';

class Chat {
  final int id;
  final String name;
  final String? avatar;
  final bool? isGroup;
  final DateTime? createdAt;
  final List<int>? userIds;
  final int? lastMessageId;

  Chat({
    required this.id,
    required this.name,
    this.avatar,
    this.isGroup,
    this.createdAt,
    this.userIds,
    this.lastMessageId,
  });

  factory Chat.fromJson(Map<String, dynamic> json) {
    return Chat(
      id: _parseInt(json['id']),
      name: json['name']?.toString() ?? 'Без названия',
      avatar: json['avatar']?.toString(),
      isGroup: json['is_group'] == true || json['is_group'] == 1,
      createdAt: _parseDateTime(json['created_at'] ?? json['date']),
      userIds: json['user_ids'] is List
          ? (json['user_ids'] as List).map((e) => _parseInt(e)).toList()
          : null,
      lastMessageId: _parseInt(json['last_message_id']),
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

  String? get avatarUrl {
    if (avatar == null || avatar!.isEmpty) return null;
    if (avatar!.startsWith('http')) return avatar;
    return '${ApiConfig.uploadsUrl}/$avatar';
  }
}