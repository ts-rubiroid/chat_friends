import '../utils/api.dart';
import 'dart:math' as math; // Добавляем импорт для функции min

class Message {
  final int id;
  final int chatId;
  final int senderId;
  final String? text;
  final String? image;
  final String? file;
  final String? type;
  final DateTime? createdAt;
  
  // НОВЫЕ ПОЛЯ для метаданных файлов
  final String? fileName;
  final String? fileType;
  final int? fileSize;
  final String? localPath; // Для локального кэширования

  Message({
    required this.id,
    required this.chatId,
    required this.senderId,
    this.text,
    this.image,
    this.file,
    this.type,
    this.createdAt,
    // Новые параметры
    this.fileName,
    this.fileType,
    this.fileSize,
    this.localPath,
  });

  factory Message.fromJson(Map<String, dynamic> json) {
    // УЛУЧШЕНИЕ: WordPress возвращает message_id вместо id
    final messageId = json['message_id'] ?? json['id'];
    
    // УЛУЧШЕНИЕ: Более гибкий парсинг sender_id
    final senderId = json['sender_id'] ?? 
                    json['sender'] ?? 
                    json['author'] ?? 
                    json['user_id'] ?? 0;
    
    // УЛУЧШЕНИЕ: Парсим chat_id из разных полей
    final chatId = json['chat_id'] ?? 
                   json['chat'] ?? 
                   json['room_id'] ?? 0;

    // НОВОЕ: Парсим метаданные файла из WordPress ответа
    String? fileName;
    String? fileType;
    int? fileSize;
    
    if (json['file'] is Map<String, dynamic>) {
      final fileData = json['file'] as Map<String, dynamic>;
      fileName = fileData['name']?.toString();
      fileType = fileData['type']?.toString();
      fileSize = _parseInt(fileData['size']);
    }

    return Message(
      id: _parseInt(messageId),
      chatId: _parseInt(chatId),
      senderId: _parseInt(senderId),
      text: json['text']?.toString(),
      image: json['image']?.toString() ?? json['image_url']?.toString(),
      file: json['file']?.toString() ?? json['file_url']?.toString(),
      type: json['type']?.toString() ?? 'text',
      createdAt: _parseDateTime(json['created_at'] ?? json['date'] ?? json['timestamp']),
      // Новые поля
      fileName: fileName ?? json['file_name']?.toString(),
      fileType: fileType ?? json['file_type']?.toString(),
      fileSize: fileSize ?? _parseInt(json['file_size']),
    );
  }

  static int _parseInt(dynamic value) {
    if (value == null) return 0;
    if (value is int) return value;
    if (value is String) {
      if (value == 'null' || value.isEmpty) return 0;
      return int.tryParse(value) ?? 0;
    }
    if (value is double) return value.toInt();
    return 0;
  }

  static DateTime? _parseDateTime(dynamic value) {
    if (value == null) return null;
    if (value is DateTime) return value;
    if (value is String) {
      if (value == 'null' || value.isEmpty) return null;
      try {
        // Пробуем разные форматы
        if (value.contains('T')) {
          return DateTime.parse(value);
        } else {
          // Формат WordPress: "2026-02-05 09:15:26"
          return DateTime.parse(value.replaceAll(' ', 'T'));
        }
      } catch (_) {
        return null;
      }
    }
    return null;
  }

  // УЛУЧШЕНИЕ: Используем ApiConfig.getFileUrl для корректных URL
  String get imageUrl {
    if (image != null && image!.isNotEmpty && image != 'null') {
      return ApiConfig.getFileUrl(image);
    }
    return '';
  }

  String get fileUrl {
    if (file != null && file!.isNotEmpty && file != 'null') {
      return ApiConfig.getFileUrl(file);
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
    String? fileName,
    String? fileType,
    int? fileSize,
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
      fileName: fileName,
      fileType: fileType,
      fileSize: fileSize,
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
    String? fileName,
    String? fileType,
    int? fileSize,
    String? localPath,
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
      fileName: fileName ?? this.fileName,
      fileType: fileType ?? this.fileType,
      fileSize: fileSize ?? this.fileSize,
      localPath: localPath ?? this.localPath,
    );
  }

  // Для отладки
  Map<String, dynamic> toDebugMap() {
    return {
      'id': id,
      'chatId': chatId,
      'senderId': senderId,
      'text': text?.substring(0, math.min(text?.length ?? 0, 30)),
      'hasImage': hasImage,
      'hasFile': hasFile,
      'type': type,
      'fileName': fileName,
      'fileSize': fileSize,
      'createdAt': createdAt?.toIso8601String(),
    };
  }

  // НОВЫЙ МЕТОД: Получение отображаемого имени файла
  String get displayFileName {
    if (fileName != null && fileName!.isNotEmpty) {
      return fileName!;
    }
    if (hasFile) {
      final url = fileUrl;
      final segments = url.split('/');
      return segments.last;
    }
    return 'Файл';
  }

  // НОВЫЙ МЕТОД: Получение размера файла в читаемом формате
  String get formattedFileSize {
    if (fileSize == null || fileSize == 0) return '';
    
    if (fileSize! < 1024) {
      return '${fileSize} Б';
    } else if (fileSize! < 1024 * 1024) {
      return '${(fileSize! / 1024).toStringAsFixed(1)} КБ';
    } else {
      return '${(fileSize! / (1024 * 1024)).toStringAsFixed(1)} МБ';
    }
  }
}