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
    // УЛУЧШЕНИЕ 1: Более гибкий парсинг sender_id
    final senderId = json['sender_id'] ?? 
                    json['sender'] ?? 
                    json['author'] ?? 
                    json['user_id'] ?? 0;
    
    // УЛУЧШЕНИЕ 2: Парсим chat_id из разных полей
    final chatId = json['chat_id'] ?? 
                   json['chat'] ?? 
                   json['room_id'] ?? 0;

    return Message(
      id: _parseInt(json['id']),
      chatId: _parseInt(chatId),
      senderId: _parseInt(senderId),
      text: json['text']?.toString(),
      image: json['image']?.toString() ?? json['image_url']?.toString(),
      file: json['file']?.toString() ?? json['file_url']?.toString(),
      type: json['type']?.toString() ?? 'text',
      createdAt: _parseDateTime(json['created_at'] ?? json['date'] ?? json['timestamp']),
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

  // УЛУЧШЕНИЕ 3: Методы возвращают строку вместо null
  String get imageUrl {
    if (image != null && image!.isNotEmpty && image != 'null') {
      if (image!.startsWith('http')) return image!;
      if (ApiConfig.uploadsUrl != null) {
        return '${ApiConfig.uploadsUrl}/$image';
      }
    }
    return '';
  }

  String get fileUrl {
    if (file != null && file!.isNotEmpty && file != 'null') {
      if (file!.startsWith('http')) return file!;
      if (ApiConfig.uploadsUrl != null) {
        return '${ApiConfig.uploadsUrl}/$file';
      }
    }
    return '';
  }

  // Проверка наличия изображения/файла
  bool get hasImage => imageUrl.isNotEmpty;
  bool get hasFile => fileUrl.isNotEmpty;

  // Проверка типа сообщения
  bool get isText => type == null || type == 'text';
  bool get isImage => type == 'image' || hasImage;
  bool get isFile => type == 'file' || hasFile;
  bool get isSystem => type == 'system';

  // Форматированное время для UI
  String get formattedTime {
    if (createdAt == null) return '';
    
    final time = createdAt!;
    final hour = time.hour.toString().padLeft(2, '0');
    final minute = time.minute.toString().padLeft(2, '0');
    
    return '$hour:$minute';
  }

  // Полная дата и время
  String get formattedDateTime {
    if (createdAt == null) return '';
    
    final time = createdAt!;
    final day = time.day.toString().padLeft(2, '0');
    final month = time.month.toString().padLeft(2, '0');
    final year = time.year.toString();
    final hour = time.hour.toString().padLeft(2, '0');
    final minute = time.minute.toString().padLeft(2, '0');
    
    return '$day.$month.$year $hour:$minute';
  }

  // Для создания локального сообщения (при отправке)
  factory Message.createLocal({
    required int chatId,
    required String text,
    required int senderId,
    String? image,
    String? file,
    String type = 'text',
  }) {
    return Message(
      id: DateTime.now().millisecondsSinceEpoch,
      chatId: chatId,
      senderId: senderId,
      text: text,
      image: image,
      file: file,
      type: type,
      createdAt: DateTime.now(),
    );
  }

  // Для копирования с изменениями
  Message copyWith({
    int? id,
    int? chatId,
    int? senderId,
    String? text,
    String? image,
    String? file,
    String? type,
    DateTime? createdAt,
  }) {
    return Message(
      id: id ?? this.id,
      chatId: chatId ?? this.chatId,
      senderId: senderId ?? this.senderId,
      text: text ?? this.text,
      image: image ?? this.image,
      file: file ?? this.file,
      type: type ?? this.type,
      createdAt: createdAt ?? this.createdAt,
    );
  }

  // Для отладки
  Map<String, dynamic> toDebugMap() {
    return {
      'id': id,
      'chatId': chatId,
      'senderId': senderId,
      'text': text?.substring(0, min(text!.length, 30)),
      'hasImage': hasImage,
      'hasFile': hasFile,
      'type': type,
      'createdAt': createdAt?.toIso8601String(),
    };
  }
}

// Вспомогательная функция для минимума
int min(int a, int b) => a < b ? a : b;