import 'dart:convert';
import 'dart:collection';
import 'package:crypto/crypto.dart';
import 'package:shared_preferences/shared_preferences.dart';

class LocalUnreadHelper {
  // Ключи для хранения данных
  static const _textHashKeyPrefix = 'last_text_hash_';
  static const _viewTimeKeyPrefix = 'last_view_time_';
  static const _messageCountKeyPrefix = 'last_message_count_';

  // === МЕТОДЫ ДЛЯ ХРАНЕНИЯ ДАННЫХ ЧАТА ===

  /// Сохранить состояние чата при просмотре
  static Future<void> saveChatState({
    required int chatId,
    required String lastText,
    required int messageCount,
  }) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final now = DateTime.now();
      
      // 1. Сохраняем хэш текста
      final textHash = _generateTextHash(lastText);
      await prefs.setString('$_textHashKeyPrefix$chatId', textHash);
      
      // 2. Сохраняем время просмотра
      await prefs.setInt('$_viewTimeKeyPrefix$chatId', now.millisecondsSinceEpoch);
      
      // 3. Сохраняем количество сообщений
      await prefs.setInt('$_messageCountKeyPrefix$chatId', messageCount);
      
      print('[LocalUnread] Сохранено состояние чата $chatId:');
      print('  Текст: "${lastText.substring(0, min(lastText.length, 30))}..."');
      print('  Хэш: $textHash');
      print('  Время: $now');
      print('  Кол-во сообщений: $messageCount');
      
    } catch (e) {
      print('[LocalUnread] Ошибка сохранения состояния чата $chatId: $e');
    }
  }

  /// Получить сохраненное состояние чата
  static Future<ChatState> getChatState(int chatId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      
      final textHash = prefs.getString('$_textHashKeyPrefix$chatId');
      final timestamp = prefs.getInt('$_viewTimeKeyPrefix$chatId');
      final messageCount = prefs.getInt('$_messageCountKeyPrefix$chatId');
      
      return ChatState(
        textHash: textHash,
        lastViewTime: timestamp != null 
            ? DateTime.fromMillisecondsSinceEpoch(timestamp) 
            : null,
        messageCount: messageCount ?? 0,
      );
      
    } catch (e) {
      print('[LocalUnread] Ошибка получения состояния чата $chatId: $e');
      return ChatState();
    }
  }

  // === МЕТОДЫ ПРОВЕРКИ НЕПРОЧИТАННЫХ ===

  /// Основной метод: Проверить, есть ли непрочитанные сообщения
  static Future<bool> hasUnreadMessages({
    required int chatId,
    required String currentText,
    required DateTime? lastMessageTime,
    required int currentMessageCount,
  }) async {
    try {
      // Получаем сохраненное состояние
      final savedState = await getChatState(chatId);
      
      print('══════════════════════════════════════');
      print('[LocalUnread] ПРОВЕРКА ЧАТА $chatId:');
      print('  Текущий текст: "${_truncateText(currentText)}"');
      print('  Текущее время сообщения: $lastMessageTime');
      print('  Текущее кол-во сообщений: $currentMessageCount');
      print('  Сохраненное состояние: $savedState');
      
      // УСЛОВИЕ 1: Чат никогда не открывался
      if (savedState.lastViewTime == null) {
        print('  → Чат никогда не открывался → ВСЕ сообщения непрочитанные');
        return true;
      }
      
      // УСЛОВИЕ 2: Хэш текста изменился
      final currentHash = _generateTextHash(currentText);
      final textChanged = savedState.textHash != currentHash;
      
      if (textChanged) {
        print('  → Текст изменился!');
        print('    Было: ${savedState.textHash}');
        print('    Стало: $currentHash');
      }
      
      // УСЛОВИЕ 3: Время последнего сообщения новее времени просмотра
      bool timeChanged = false;
      if (lastMessageTime != null) {
        timeChanged = lastMessageTime.isAfter(savedState.lastViewTime!);
        if (timeChanged) {
          print('  → Время сообщения новее времени просмотра!');
          print('    Сообщение: $lastMessageTime');
          print('    Просмотр: ${savedState.lastViewTime}');
        }
      }
      
      // УСЛОВИЕ 4: Количество сообщений увеличилось
      final countChanged = currentMessageCount > savedState.messageCount;
      if (countChanged) {
        print('  → Количество сообщений увеличилось!');
        print('    Было: ${savedState.messageCount}');
        print('    Стало: $currentMessageCount');
      }
      
      // ИТОГ: Есть непрочитанные если ЛЮБОЕ условие истинно
      final hasUnread = textChanged || savedState.lastViewTime == null;
      
      print('  → ИТОГО: $hasUnread (text:$textChanged, time:$timeChanged, count:$countChanged)');
      print('══════════════════════════════════════');
      
      return hasUnread;
      
    } catch (e) {
      print('[LocalUnread] Ошибка проверки чата $chatId: $e');
      return true; // В случае ошибки считаем непрочитанным
    }
  }

  // === ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ===

  /// Генерация хэша для текста
  static String _generateTextHash(String text) {
    if (text.isEmpty) return 'EMPTY';
    
    // Добавляем соль для уникальности
    final saltedText = '${text}_${text.length}';
    final bytes = utf8.encode(saltedText);
    final digest = sha256.convert(bytes);
    
    return digest.toString().substring(0, 16); // Берем первые 16 символов
  }

  /// Обрезка текста для логов
  static String _truncateText(String text) {
    if (text.length <= 30) return text;
    return '${text.substring(0, 27)}...';
  }

  /// Очистить все сохраненные данные
  static Future<void> clearAll() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final keys = prefs.getKeys().where((key) => 
          key.startsWith(_textHashKeyPrefix) ||
          key.startsWith(_viewTimeKeyPrefix) ||
          key.startsWith(_messageCountKeyPrefix));
      
      for (final key in keys) {
        await prefs.remove(key);
      }
      
      print('[LocalUnread] Все данные очищены');
    } catch (e) {
      print('[LocalUnread] Ошибка очистки: $e');
    }
  }

  /// Получить статистику
  static Future<void> printStats() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final chatIds = <int>{};
      
      for (final key in prefs.getKeys()) {
        if (key.startsWith(_textHashKeyPrefix)) {
          final chatId = int.parse(key.replaceAll(_textHashKeyPrefix, ''));
          chatIds.add(chatId);
        }
      }
      
      print('[LocalUnread] Статистика:');
      print('  Всего сохраненных чатов: ${chatIds.length}');
      
      for (final chatId in chatIds) {
        final state = await getChatState(chatId);
        print('  Чат $chatId: $state');
      }
    } catch (e) {
      print('[LocalUnread] Ошибка статистики: $e');
    }
  }
}

/// Класс для хранения состояния чата
class ChatState {
  final String? textHash;
  final DateTime? lastViewTime;
  final int messageCount;
  
  ChatState({
    this.textHash,
    this.lastViewTime,
    this.messageCount = 0,
  });
  
  @override
  String toString() {
    return 'ChatState(textHash: ${textHash != null ? "${textHash!.substring(0, 8)}..." : "null"}, '
           'lastViewTime: $lastViewTime, messageCount: $messageCount)';
  }
  
  bool get wasViewed => lastViewTime != null;
}

// Вспомогательная функция
int min(int a, int b) => a < b ? a : b;