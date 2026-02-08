import '../utils/api.dart';
import 'dart:math' as math;

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
    // WordPress возвращает message_id вместо id
    final messageId = json['message_id'] ?? json['id'];
    
    final senderId = json['sender_id'] ?? 
                    json['sender'] ?? 
                    json['author'] ?? 
                    json['user_id'] ?? 0;
    
    final chatId = json['chat_id'] ?? 
                  json['chat'] ?? 
                  json['room_id'] ?? 0;

    // КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: WordPress может возвращать строку "null"
    String? imageUrl;
    String? fileUrl;
    
    // Проверяем поле image
    if (json['image'] != null) {
      if (json['image'] is String) {
        final imageValue = json['image'] as String;
        // УБИРАЕМ проверку на 'null' - пусть даже "null" пройдет
        if (imageValue.isNotEmpty && imageValue != 'false') {
          imageUrl = imageValue;
        }
      } else if (json['image'] is Map<String, dynamic>) {
        // Если это объект (из upload ответа)
        final imageObj = json['image'] as Map<String, dynamic>;
        if (imageObj['url'] != null) {
          imageUrl = imageObj['url'] as String;
        }
      }
    }
    
    // Проверяем поле file
    if (json['file'] != null) {
      if (json['file'] is String) {
        final fileValue = json['file'] as String;
        // УБИРАЕМ проверку на 'null'
        if (fileValue.isNotEmpty && fileValue != 'false') {
          fileUrl = fileValue;
        }
      } else if (json['file'] is Map<String, dynamic>) {
        // Если это объект
        final fileObj = json['file'] as Map<String, dynamic>;
        if (fileObj['url'] != null) {
          fileUrl = fileObj['url'] as String;
        }
      }
    }

    // ДОПОЛНИТЕЛЬНО: Проверяем альтернативные поля
    if (imageUrl == null || imageUrl.isEmpty) {
      if (json['image_url'] != null && json['image_url'] is String) {
        final altValue = json['image_url'] as String;
        if (altValue.isNotEmpty && altValue != 'false') {
          imageUrl = altValue;
        }
      }
    }
    
    if (fileUrl == null || fileUrl.isEmpty) {
      if (json['file_url'] != null && json['file_url'] is String) {
        final altValue = json['file_url'] as String;
        if (altValue.isNotEmpty && altValue != 'false') {
          fileUrl = altValue;
        }
      }
    }

    // Определяем тип
    String type = 'text';
    if (imageUrl != null && imageUrl.isNotEmpty && imageUrl != 'null') {
      type = 'image';
    } else if (fileUrl != null && fileUrl.isNotEmpty && fileUrl != 'null') {
      type = 'file';
    } else if (json['type'] != null && json['type'] is String) {
      type = json['type'] as String;
    }

    return Message(
      id: _parseInt(messageId),
      chatId: _parseInt(chatId),
      senderId: _parseInt(senderId),
      text: json['text']?.toString(),
      // Если imageUrl содержит "null", сохраняем как null
      image: (imageUrl != null && imageUrl != 'null' && imageUrl.isNotEmpty) ? imageUrl : null,
      file: (fileUrl != null && fileUrl != 'null' && fileUrl.isNotEmpty) ? fileUrl : null,
      type: type,
      createdAt: _parseDateTime(json['created_at'] ?? json['date'] ?? json['timestamp']),
      fileName: json['file_name']?.toString(),
      fileType: json['file_type']?.toString(),
      fileSize: _parseInt(json['file_size']),
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
    if (image != null && image!.isNotEmpty && image != 'null' && image != 'false') {
      // Если image уже полный URL, возвращаем как есть
      if (image!.startsWith('http')) {
        return image!;
      }
      return ApiConfig.getFileUrl(image);
    }
    return '';
  }

  String get fileUrl {
    if (file != null && file!.isNotEmpty && file != 'null' && file != 'false') {
      if (file!.startsWith('http')) {
        return file!;
      }
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