import 'dart:convert';
import 'dart:typed_data';

import 'package:flutter/foundation.dart';
import 'package:unifiedpush/unifiedpush.dart';

import '../utils/push_constants.dart';
import 'notification_service.dart';

/// Сервис UnifiedPush (ntfy): подписка на топик пользователя и обработка push-уведомлений.
/// При получении сообщения в фоне/закрытом приложении показываем локальное уведомление.
/// В фореграунде — только триггерим обновление списка чатов (без дублирования уведомления).
class UnifiedPushService {
  UnifiedPushService._();

  static bool _initialized = false;

  /// true, когда приложение на экране (resumed). Нужно не показывать системное уведомление в фореграунде.
  static bool appInForeground = true;

  /// Колбэк для немедленной проверки новых сообщений (когда приложение в фореграунде).
  static void Function()? onForegroundMessage;

  /// Инициализация. Вызывать из main() после NotificationService.init().
  /// [args] — аргументы запуска (для --unifiedpush-bg на Android).
  static Future<void> init(List<String> args) async {
    if (_initialized) return;

    await UnifiedPush.initialize(
      onNewEndpoint: _onNewEndpoint,
      onRegistrationFailed: _onRegistrationFailed,
      onUnregistered: _onUnregistered,
      onMessage: _onMessage,
    );

    _initialized = true;
    debugPrint('[UnifiedPush] init done');
  }

  static void _onNewEndpoint(String endpoint, String instance) {
    debugPrint('[UnifiedPush] onNewEndpoint instance=$instance endpoint=$endpoint');
  }

  static void _onRegistrationFailed(String instance) {
    debugPrint('[UnifiedPush] onRegistrationFailed instance=$instance');
  }

  static void _onUnregistered(String instance) {
    debugPrint('[UnifiedPush] onUnregistered instance=$instance');
  }

  static void _onMessage(Uint8List messageBytes, String instance) {
    try {
      final raw = utf8.decode(messageBytes);
      if (raw.isEmpty) return;

      // Ожидаем JSON: { title?, message: { chatId, ... } } или { chatId, ... }; тело от ntfy может приходить как есть
      Map<String, dynamic>? data;
      try {
        final decoded = json.decode(raw);
        if (decoded is Map<String, dynamic>) {
          data = decoded;
        } else if (decoded is String) {
          // Двойная кодировка: тело пришло как строка JSON
          data = json.decode(decoded) as Map<String, dynamic>?;
        }
      } catch (_) {
        debugPrint('[UnifiedPush] onMessage: не удалось распарсить JSON: $raw');
        return;
      }
      if (data == null) return;

      // Поддержка формата { "message": { ... } } от WordPress
      final messageData = data['message'] is Map<String, dynamic>
          ? data['message'] as Map<String, dynamic>
          : (data['message'] is String
                ? _tryParseJsonMap(data['message'] as String)
                : null) ??
              data;
      final chatId = _parseInt(messageData['chatId']);
      final messageId = _parseInt(messageData['messageId']);
      final senderId = _parseInt(messageData['senderId']);
      final text = messageData['text']?.toString();
      final type = messageData['type']?.toString() ?? 'text';

      if (chatId <= 0) return;

      final title = data['title']?.toString() ?? 'Чат Друзей';
      final body = _notificationBody(text, type);

      onForegroundMessage?.call();
      // В фореграунде не показываем системное уведомление (только обновление списка)
      if (!appInForeground) {
        NotificationService.showPushNotification(
          chatId: chatId,
          title: title,
          body: body,
          messageId: messageId,
          senderId: senderId,
        );
      }
    } catch (e, st) {
      debugPrint('[UnifiedPush] onMessage error: $e');
      debugPrint('[UnifiedPush] $st');
    }
  }

  static Map<String, dynamic>? _tryParseJsonMap(String s) {
    try {
      final v = json.decode(s);
      return v is Map<String, dynamic> ? v : null;
    } catch (_) {
      return null;
    }
  }

  static int _parseInt(dynamic v) {
    if (v == null) return 0;
    if (v is int) return v;
    if (v is num) return v.toInt();
    if (v is String) return int.tryParse(v) ?? 0;
    return 0;
  }

  static String _notificationBody(String? text, String type) {
    if (text != null && text.isNotEmpty) {
      return text.length > 80 ? '${text.substring(0, 80)}...' : text;
    }
    switch (type) {
      case 'image':
        return 'Фото';
      case 'video':
        return 'Видео';
      case 'audio':
        return 'Аудио';
      case 'file':
        return 'Файл';
      default:
        return 'Новое сообщение';
    }
  }

  /// Подписаться на топик пользователя (вызывать после успешного входа).
  /// Использует instance = "user_{userId}" для подписки на топик в ntfy.
  static Future<void> registerTopic(int userId) async {
    if (userId <= 0) return;
    final instance = PushConstants.topicForUser(userId);
    try {
      String? distributor = await UnifiedPush.getDistributor();
      if (distributor == null) {
        final distributors = await UnifiedPush.getDistributors();
        if (distributors.isEmpty) {
          debugPrint('[UnifiedPush] Дистрибьютор не найден (установите ntfy и укажите сервер).');
          return;
        }
        await UnifiedPush.saveDistributor(distributors.first);
      }
      await UnifiedPush.registerApp(instance);
      debugPrint('[UnifiedPush] registerTopic: $instance');
    } catch (e) {
      debugPrint('[UnifiedPush] registerTopic error: $e');
    }
  }

  /// Отписаться от push (вызывать при выходе из аккаунта).
  static Future<void> unregister() async {
    try {
      await UnifiedPush.unregister();
      debugPrint('[UnifiedPush] unregister done');
    } catch (e) {
      debugPrint('[UnifiedPush] unregister error: $e');
    }
  }
}
