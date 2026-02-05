import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';

class MediaCache {
  static const String _prefix = 'media_meta_';
  
  /// Сохраняет мета-информацию для сообщения
  static Future<void> saveMediaMeta(int messageId, Map<String, dynamic> meta) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('$_prefix$messageId', json.encode(meta));
      print('[MediaCache] 💾 Сохранена мета-информация для сообщения $messageId: ${meta['name']}');
    } catch (e) {
      print('[MediaCache] ❌ Ошибка сохранения: $e');
    }
  }
  
  /// Загружает мета-информацию для сообщения
  static Future<Map<String, dynamic>?> getMediaMeta(int messageId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final metaString = prefs.getString('$_prefix$messageId');
      if (metaString != null) {
        final meta = json.decode(metaString) as Map<String, dynamic>;
        print('[MediaCache] 📖 Загружена мета-информация для сообщения $messageId');
        return meta;
      }
      return null;
    } catch (e) {
      print('[MediaCache] ❌ Ошибка загрузки: $e');
      return null;
    }
  }
  
  /// Удаляет мета-информацию для сообщения
  static Future<void> deleteMediaMeta(int messageId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('$_prefix$messageId');
      print('[MediaCache] 🗑️ Удалена мета-информация для сообщения $messageId');
    } catch (e) {
      print('[MediaCache] ❌ Ошибка удаления: $e');
    }
  }
  
  /// Очищает весь кэш медиа
  static Future<void> clearAll() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final keys = prefs.getKeys().where((key) => key.startsWith(_prefix)).toList();
      for (final key in keys) {
        await prefs.remove(key);
      }
      print('[MediaCache] 🧹 Очищен весь кэш медиа (${keys.length} записей)');
    } catch (e) {
      print('[MediaCache] ❌ Ошибка очистки: $e');
    }
  }
}