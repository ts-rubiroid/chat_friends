import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:vibration/vibration.dart';
import 'package:chat_friends/models/chat.dart';
import 'package:chat_friends/models/message.dart' as app_models;

/// Сервис уведомлений: звук, вибрация и системные уведомления о новых сообщениях.
/// При тапе по уведомлению открывается экран общего списка чатов.
class NotificationService {
  static final FlutterLocalNotificationsPlugin _plugin =
      FlutterLocalNotificationsPlugin();

  static GlobalKey<NavigatorState>? _navigatorKey;
  static bool _initialized = false;

  /// ID чата, который пользователь сейчас просматривает (чтобы не дублировать уведомления).
  static int? currentChatId;

  /// Флаг: приложение запущено по тапу на уведомление (чтобы показать список чатов).
  static bool pendingOpenChatsList = false;

  static const String _channelId = 'chat_friends_messages';
  static const String _channelName = 'Новые сообщения';

  /// Инициализация. Вызывать из main() после WidgetsFlutterBinding.
  static Future<void> init(GlobalKey<NavigatorState> navigatorKey) async {
    if (_initialized) return;
    _navigatorKey = navigatorKey;

    const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
    const iosSettings = DarwinInitializationSettings(
      requestAlertPermission: true,
      requestSoundPermission: true,
    );
    const initSettings = InitializationSettings(
      android: androidSettings,
      iOS: iosSettings,
    );

    await _plugin.initialize(
      settings: initSettings,
      onDidReceiveNotificationResponse: _onNotificationTapped,
    );

    if (Platform.isAndroid) {
      await _createAndroidChannel();
      await _requestPermission();
    }

    final launchDetails = await _plugin.getNotificationAppLaunchDetails();
    if (launchDetails?.didNotificationLaunchApp == true &&
        launchDetails?.notificationResponse != null) {
      pendingOpenChatsList = true;
    }

    _initialized = true;
    print('[NotificationService] Инициализация завершена. pendingOpenChatsList=$pendingOpenChatsList');
  }

  static Future<void> _createAndroidChannel() async {
    final channel = AndroidNotificationChannel(
      _channelId,
      _channelName,
      description: 'Уведомления о новых сообщениях в чате',
      importance: Importance.high,
      playSound: true,
      enableVibration: true,
      vibrationPattern: Int64List.fromList([0, 250, 250, 250]),
      enableLights: true,
      ledColor: const Color.fromARGB(255, 33, 150, 243),
    );
    await _plugin
        .resolvePlatformSpecificImplementation<
            AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(channel);
  }

  static Future<void> _requestPermission() async {
    if (Platform.isAndroid) {
      final androidPlugin = _plugin.resolvePlatformSpecificImplementation<
          AndroidFlutterLocalNotificationsPlugin>();
      final granted = await androidPlugin?.requestNotificationsPermission();
      print('[NotificationService] Разрешение на уведомления: granted=$granted');
    }
  }

  static void _onNotificationTapped(NotificationResponse response) {
    _openChatsList();
  }

  /// Переход на экран общего списка чатов.
  static void _openChatsList() {
    final key = _navigatorKey;
    if (key?.currentState == null) {
      pendingOpenChatsList = true;
      return;
    }
    key!.currentState!.popUntil((route) => route.isFirst);
  }

  /// Показать уведомление о новом сообщении и вызвать звук/вибрацию.
  static Future<void> showNewMessageNotification({
    required Chat chat,
    required app_models.Message message,
    String? senderName,
  }) async {
    print('[NotificationService] showNewMessageNotification: чат ${chat.id} "${chat.name}", msgId=${message.id}, currentChatId=$currentChatId');
    if (chat.id == currentChatId) {
      print('[NotificationService] Пропуск — пользователь уже в этом чате');
      return;
    }

    final title = chat.name;
    final body = _notificationBody(message, senderName);
    final payload = json.encode({'chatId': chat.id});

    const androidDetails = AndroidNotificationDetails(
      _channelId,
      _channelName,
      channelDescription: 'Уведомления о новых сообщениях в чате',
      importance: Importance.high,
      priority: Priority.high,
      playSound: true,
      enableVibration: true,
      visibility: NotificationVisibility.public,
    );
    const iosDetails = DarwinNotificationDetails(
      presentAlert: true,
      presentSound: true,
    );
    const details = NotificationDetails(
      android: androidDetails,
      iOS: iosDetails,
    );

    final id = chat.id.hashCode.abs() % 100000;
    await _plugin.show(id: id, title: title, body: body, notificationDetails: details, payload: payload);
    print('[NotificationService] Уведомление показано: id=$id, title="$title", body="$body"');
    await soundAndVibrate();
  }

  static String _notificationBody(app_models.Message message, String? senderName) {
    final prefix = senderName != null ? '$senderName: ' : '';
    if (message.text != null && message.text!.isNotEmpty) {
      final text = message.text!;
      return prefix + (text.length > 80 ? '${text.substring(0, 80)}...' : text);
    }
    if (message.hasImage) return '${prefix}Фото';
    if (message.hasFile) {
      final type = message.fileType?.toLowerCase() ?? '';
      if (type.startsWith('video/')) return '${prefix}Видео';
      if (type.startsWith('audio/')) return '${prefix}Аудио';
      return '${prefix}Файл';
    }
    return '${prefix}Новое сообщение';
  }

  /// Воспроизвести звук и вибрацию (при новом сообщении).
  static Future<void> soundAndVibrate() async {
    try {
      if (await Vibration.hasVibrator() == true) {
        Vibration.vibrate(duration: 200);
      }
    } catch (_) {}
  }

}
