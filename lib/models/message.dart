import '../utils/api.dart';

class Message {
  final int id;
  final int chatId;
  final int senderId;
  final String? text;
  final String? image;
  final String? file;
  final String? type;
  final DateTime? createdAt;

  Message({
    required this.id,
    required this.chatId,
    required this.senderId,
    this.text,
    this.image,
    this.file,
    this.type,
    this.createdAt,
  });

  factory Message.fromJson(Map<String, dynamic> json) {
    return Message(
      id: _parseInt(json['id']),
      chatId: _parseInt(json['chat_id']),
      senderId: _parseInt(json['sender_id'] ?? json['author']),
      text: json['text']?.toString(),
      image: json['image']?.toString(),
      file: json['file']?.toString(),
      type: json['type']?.toString() ?? 'text',
      createdAt: _parseDateTime(json['created_at'] ?? json['date']),
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

  String? get imageUrl {
    if (image == null || image!.isEmpty) return null;
    if (image!.startsWith('http')) return image;
    return '${ApiConfig.uploadsUrl}/$image';
  }

  String? get fileUrl {
    if (file == null || file!.isEmpty) return null;
    if (file!.startsWith('http')) return file;
    return '${ApiConfig.uploadsUrl}/$file';
  }
}