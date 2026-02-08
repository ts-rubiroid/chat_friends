import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';

class MediaStorage {
  static const String _storageKey = 'chat_media_storage_v1';
  
  // Сохранить метаданные медиа для сообщения
  static Future<void> saveMediaForMessage(int messageId, {
    String? imageUrl,
    String? fileUrl,
    String? fileName,
    String? fileType,
    int? fileSize,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    final storage = await _getAllMedia();
    
    storage['$messageId'] = {
      'imageUrl': imageUrl,
      'fileUrl': fileUrl,
      'fileName': fileName,
      'fileType': fileType,
      'fileSize': fileSize,
      'savedAt': DateTime.now().toIso8601String(),
    };
    
    // Ограничиваем 30 записями
    await _cleanupOldEntries(storage);
    
    await prefs.setString(_storageKey, json.encode(storage));
    
    print('[MediaStorage] Сохранены метаданные для сообщения $messageId');
  }
  
  // Получить метаданные для сообщения
  static Future<Map<String, dynamic>> getMediaForMessage(int messageId) async {
    final storage = await _getAllMedia();
    final data = storage['$messageId'];
    
    if (data != null && data is Map<String, dynamic>) {
      return data;
    }
    
    return {};
  }
  
  // Получить URL изображения
  static Future<String?> getImageUrl(int messageId) async {
    final data = await getMediaForMessage(messageId);
    return data['imageUrl'];
  }
  
  // Получить URL файла
  static Future<String?> getFileUrl(int messageId) async {
    final data = await getMediaForMessage(messageId);
    return data['fileUrl'];
  }
  
  // Вспомогательные методы
  static Future<Map<String, dynamic>> _getAllMedia() async {
    final prefs = await SharedPreferences.getInstance();
    final jsonString = prefs.getString(_storageKey);
    
    if (jsonString == null || jsonString.isEmpty) return {};
    
    try {
      return json.decode(jsonString);
    } catch (e) {
      print('[MediaStorage] Ошибка парсинга: $e');
      return {};
    }
  }
  
  static Future<void> _cleanupOldEntries(Map<String, dynamic> storage) async {
    if (storage.length <= 30) return;
    
    final entries = storage.entries.toList();
    entries.sort((a, b) {
      final timeA = DateTime.parse(a.value['savedAt'] ?? '2000-01-01');
      final timeB = DateTime.parse(b.value['savedAt'] ?? '2000-01-01');
      return timeA.compareTo(timeB);
    });
    
    final toRemove = entries.sublist(0, storage.length - 30);
    for (final entry in toRemove) {
      storage.remove(entry.key);
    }
    
    print('[MediaStorage] Удалено ${toRemove.length} старых записей');
  }
}