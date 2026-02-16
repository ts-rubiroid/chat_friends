import 'dart:convert';
import 'dart:typed_data';
import 'package:background_fetch/background_fetch.dart';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:chat_friends/models/chat.dart';
import 'package:chat_friends/services/api_service.dart';
import 'package:chat_friends/services/background_notification_task.dart';

const String _prefLastIds = 'notify_last_known_ids';
const String _prefCurrentUserId = 'notify_current_user_id';

/// Вызывать из main() после runApp — регистрирует headless-задачу.
void initBackgroundFetch() {
  BackgroundFetch.registerHeadlessTask(_headlessTask);
}

@pragma('vm:entry-point')
void _headlessTask(HeadlessTask task) async {
  final taskId = task.taskId;
  final isTimeout = task.timeout;
  if (isTimeout) {
    BackgroundFetch.finish(taskId);
    return;
  }
  try {
    await _checkAndNotify();
  } catch (e) {
    debugPrint('[BackgroundFetch] Headless error: $e');
  }
  BackgroundFetch.finish(taskId);
}

/// Проверить новые сообщения и показать минимальное уведомление + бейдж.
Future<void> _checkAndNotify() async {
  final prefs = await SharedPreferences.getInstance();
  final token = prefs.getString('token');
  if (token == null || token.isEmpty) return;

  final currentUserId = prefs.getInt(_prefCurrentUserId);
  if (currentUserId == null) return;

  final idsJson = prefs.getString(_prefLastIds);
  final Map<int, int?> lastKnown = {};
  if (idsJson != null && idsJson.isNotEmpty) {
    try {
      final map = json.decode(idsJson) as Map<String, dynamic>;
      for (final e in map.entries) {
        final k = int.tryParse(e.key);
        if (k == null) continue;
        if (e.value is int) lastKnown[k] = e.value as int;
        if (e.value is num) lastKnown[k] = (e.value as num).toInt();
      }
    } catch (_) {}
  }

  List<Chat> chats;
  try {
    chats = await ApiService.getChats();
  } catch (_) {
    return;
  }

  int newCount = 0;
  final Map<int, int?> updated = {};
  for (final chat in chats) {
    final lastMsg = chat.lastMessage;
    final effectiveId = chat.lastMessageId ?? lastMsg?.id ?? 0;
    final knownId = lastKnown[chat.id];
    if (lastMsg == null) {
      if (effectiveId != 0) updated[chat.id] = effectiveId;
      continue;
    }
    if (knownId != null && effectiveId != 0 && knownId == effectiveId) {
      updated[chat.id] = effectiveId;
      continue;
    }
    if (lastMsg.senderId == currentUserId) {
      updated[chat.id] = effectiveId != 0 ? effectiveId : knownId;
      continue;
    }
    newCount++;
    updated[chat.id] = effectiveId != 0 ? effectiveId : knownId;
  }
  for (final chat in chats) {
    final id = chat.lastMessageId ?? chat.lastMessage?.id;
    if (id != null && id != 0) updated[chat.id] = id;
  }

  if (newCount == 0) return;

  await saveLastKnownMessageIds(updated);
  await _showMinimalNotificationAndBadge(newCount);
}

/// Показать одно минимальное уведомление (звук + вибрация) и обновить бейдж.
/// Можно вызывать из headless или из обычного callback BackgroundFetch.
Future<void> _showMinimalNotificationAndBadge(int count) async {
  const channelId = 'chat_friends_messages';
  const channelName = 'Новые сообщения';

  final plugin = FlutterLocalNotificationsPlugin();
  await plugin.initialize(
    settings: const InitializationSettings(
      android: AndroidInitializationSettings('@mipmap/ic_launcher'),
    ),
  );
  final androidPlugin = plugin.resolvePlatformSpecificImplementation<
      AndroidFlutterLocalNotificationsPlugin>();
  final channel = AndroidNotificationChannel(
    channelId,
    channelName,
    description: 'Уведомления о новых сообщениях в чате',
    importance: Importance.high,
    playSound: true,
    enableVibration: true,
    vibrationPattern: Int64List.fromList([0, 250, 250, 250]),
  );
  await androidPlugin?.createNotificationChannel(channel);

  const title = 'Чат Друзей';
  const body = 'Новое сообщение';
  const payload = '{}';
  await plugin.show(
    id: 0,
    title: title,
    body: body,
    notificationDetails: const NotificationDetails(
      android: AndroidNotificationDetails(
        channelId,
        channelName,
        channelDescription: 'Уведомления о новых сообщениях в чате',
        importance: Importance.high,
        priority: Priority.high,
        playSound: true,
        enableVibration: true,
        visibility: NotificationVisibility.public,
      ),
    ),
    payload: payload,
  );
}

/// Настроить и запустить фоновую проверку (вызывать при старте приложения после логина).
Future<void> configureAndStartBackgroundFetch() async {
  final status = await BackgroundFetch.configure(
    BackgroundFetchConfig(
      minimumFetchInterval: 15,
      stopOnTerminate: false,
      enableHeadless: true,
      requiresBatteryNotLow: false,
      requiresCharging: false,
      requiresStorageNotLow: false,
      requiresDeviceIdle: false,
      requiredNetworkType: NetworkType.ANY,
    ),
    (String taskId) async {
      try {
        await _checkAndNotify();
      } catch (e) {
        debugPrint('[BackgroundFetch] Callback error: $e');
      }
      BackgroundFetch.finish(taskId);
    },
    (String taskId) async {
      BackgroundFetch.finish(taskId);
    },
  );
  debugPrint('[BackgroundFetch] configure status: $status');
}
