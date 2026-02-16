import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';

const String _prefLastIds = 'notify_last_known_ids';
const String _prefCurrentUserId = 'notify_current_user_id';

/// Сохранить последние известные id сообщений (для возможной фоновой проверки в будущем).
Future<void> saveLastKnownMessageIds(Map<int, int?> ids) async {
  final prefs = await SharedPreferences.getInstance();
  final map = <String, dynamic>{};
  for (final e in ids.entries) {
    if (e.value != null) map['${e.key}'] = e.value;
  }
  await prefs.setString(_prefLastIds, json.encode(map));
}

/// Сохранить ID текущего пользователя.
Future<void> saveCurrentUserId(int userId) async {
  final prefs = await SharedPreferences.getInstance();
  await prefs.setInt(_prefCurrentUserId, userId);
}

/// Вызывать из ChatsScreen при загрузке и после опроса (состояние для уведомлений).
Future<void> persistNotificationState(Map<int, int?> lastKnownIds, int? currentUserId) async {
  await saveLastKnownMessageIds(lastKnownIds);
  if (currentUserId != null) await saveCurrentUserId(currentUserId);
}
