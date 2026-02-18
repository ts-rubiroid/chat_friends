import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:http_parser/http_parser.dart'; // Добавляем этот импорт
import 'package:shared_preferences/shared_preferences.dart';
import 'package:chat_friends/utils/api.dart';
import 'package:chat_friends/models/user.dart';
import 'package:chat_friends/models/chat.dart';
import 'package:chat_friends/models/message.dart';
import 'package:chat_friends/services/media_storage.dart';

class ApiService {
  // === ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ===
  

  static Future<Map<String, dynamic>> _handleResponse(
      http.Response response) async {
    ApiConfig.logRequest(response.request?.method ?? 'GET', 
        response.request?.url.toString() ?? '');
    
    if (response.statusCode >= 200 && response.statusCode < 300) {
      try {
        return json.decode(response.body);
      } catch (e) {
        // Иногда сервер может "приклеить" Warning/Notice перед JSON (WP_DEBUG_DISPLAY),
        // из-за чего json.decode падает. Попробуем восстановить JSON из тела.
        final recovered = _tryRecoverJson(response.body);
        if (recovered != null) {
          return recovered;
        }

        print('[WARNING] Ошибка парсинга JSON: $e');
        return {'success': false, 'error': 'Invalid JSON response'};
      }
    } else {
      // Для ошибок 400-500
      try {
        final errorData = json.decode(response.body);
        final errorMessage = errorData['message'] ?? 
                          errorData['error'] ?? 
                          'Ошибка ${response.statusCode}';
        // Возвращаем Map с ошибкой, а не бросаем исключение
        return {
          'success': false,
          'error': errorMessage,
          'statusCode': response.statusCode
        };
      } catch (_) {
        return {
          'success': false,
          'error': 'Ошибка ${response.statusCode}: ${response.body}'
        };
      }
    }
  }

  /// Пытаемся вытащить первый валидный JSON-объект/массив из строки,
  /// если перед ним есть текст (например, PHP Warning).
  static Map<String, dynamic>? _tryRecoverJson(String body) {
    final trimmed = body.trimLeft();
    if (trimmed.isEmpty) return null;

    // Быстрый путь: уже начинается с JSON
    if (trimmed.startsWith('{') || trimmed.startsWith('[')) {
      try {
        final decoded = json.decode(trimmed);
        if (decoded is Map<String, dynamic>) return decoded;
        if (decoded is List) {
          // Иногда API возвращает список; оборачиваем в единый формат
          return {'success': true, 'data': decoded};
        }
      } catch (_) {
        // continue
      }
    }

    // Ищем первое '{' или '[' и пытаемся декодировать с него
    final objIdx = body.indexOf('{');
    final arrIdx = body.indexOf('[');
    int start = -1;
    if (objIdx == -1) {
      start = arrIdx;
    } else if (arrIdx == -1) {
      start = objIdx;
    } else {
      start = objIdx < arrIdx ? objIdx : arrIdx;
    }

    if (start == -1) return null;
    final candidate = body.substring(start).trimLeft();
    try {
      final decoded = json.decode(candidate);
      if (decoded is Map<String, dynamic>) return decoded;
      if (decoded is List) return {'success': true, 'data': decoded};
    } catch (_) {
      return null;
    }

    return null;
  }



  static Future<Map<String, String>> _getHeaders() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');
    
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    
    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }
    
    return headers;
  }

  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('token');
  }

  static Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('token', token);
    print('[API] Токен сохранен');
  }

  static Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('token');
    print('[API] Токен удален');
  }

  // === АУТЕНТИФИКАЦИЯ ===

  static Future<Map<String, dynamic>> register(
      String phone, String password, Map<String, dynamic> userData) async {
    final url = Uri.parse(ApiConfig.registerEndpoint);
    
    // Правильная подготовка данных
    final Map<String, dynamic> body = {
      'phone': phone.trim(),
      'password': password.trim(),
      'first_name': (userData['first_name'] ?? '').trim(),
      'last_name': (userData['last_name'] ?? '').trim(),
      'nickname': (userData['nickname'] ?? '').trim(),
      'avatar': (userData['avatar'] ?? '').trim(),
    };

    // Добавляем middle_name если есть
    final middleName = (userData['middle_name'] ?? '').trim();
    if (middleName.isNotEmpty) {
      body['middle_name'] = middleName;
    }

    // DEBUG
    print('[API] Регистрация пользователя:');
    print('  URL: $url');
    print('  Данные: $body');

    try {
      // Делаем простой запрос без сложной обработки
      final response = await http.post(
        url,
        headers: {
          'Content-Type': 'application/json; charset=utf-8',
          'Accept': 'application/json',
        },
        body: json.encode(body),
      );

      print('[API] Ответ сервера: ${response.statusCode}');
      print('[API] Тело ответа: ${response.body}');

      // Прямой парсинг без _handleResponse
      final Map<String, dynamic> result = json.decode(response.body);
      
      if (response.statusCode >= 200 && response.statusCode < 300) {
        final token = result['token'];
        if (token != null && token is String) {
          await saveToken(token);
          print('[API] Токен сохранен');
        }
        return result;
      } else {
        // Возвращаем ошибку как есть
        return {
          'success': false,
          'error': result['message'] ?? 'Ошибка регистрации',
          'statusCode': response.statusCode
        };
      }
      
    } catch (e) {
      print('[API] Критическая ошибка регистрации: $e');
      return {
        'success': false,
        'error': 'Сетевая ошибка: $e'
      };
    }
  }

  static Future<Map<String, dynamic>> login(
      String phone, String password) async {
    final url = Uri.parse(ApiConfig.loginEndpoint);
    
    final body = {
      'phone': phone,
      'password': password,
    };

    ApiConfig.logRequest('POST', url.toString(), body: body);
    
    final response = await http.post(
      url,
      headers: {'Content-Type': 'application/json'},
      body: json.encode(body),
    );

    final result = await _handleResponse(response);
    
    final token = result['token'] ?? result['jwt_token'] ?? result['data']?['token'];
    if (token != null) {
      await saveToken(token);
    }
    
    return result;
  }

  // === ПОЛЬЗОВАТЕЛИ ===

  static Future<User> getCurrentUser() async {
    final headers = await _getHeaders();
    final url = Uri.parse(ApiConfig.meEndpoint);

    ApiConfig.logRequest('GET', url.toString());
    
    try {
      final response = await http.get(url, headers: headers);
      
      final Map<String, dynamic> result = await _handleResponse(response);
      
      if (result.containsKey('success') && result['success'] == true) {
        if (result.containsKey('user') && result['user'] is Map<String, dynamic>) {
          return User.fromJson(result['user'] as Map<String, dynamic>);
        } else if (result.containsKey('data') && result['data'] is Map<String, dynamic>) {
          return User.fromJson(result['data'] as Map<String, dynamic>);
        }
      } else if (result.containsKey('id')) {
        return User.fromJson(result);
      }
      
      throw Exception('Неверный формат ответа от API: $result');
    } catch (e) {
      print('[ERROR] Ошибка в getCurrentUser: $e');
      rethrow;
    }
  }

  static Future<List<User>> getAllUsers() async {
    final headers = await _getHeaders();
    final url = Uri.parse(ApiConfig.usersEndpoint);

    ApiConfig.logRequest('GET', url.toString());
    
    try {
      final response = await http.get(url, headers: headers);
      
      final Map<String, dynamic> result = await _handleResponse(response);
      
      List<Map<String, dynamic>> usersList = [];
      
      if (result.containsKey('success') && result['success'] == true) {
        if (result.containsKey('users') && result['users'] is List) {
          final List<dynamic> rawList = result['users'] as List<dynamic>;
          usersList = rawList.cast<Map<String, dynamic>>();
        } else if (result.containsKey('data') && result['data'] is List) {
          final List<dynamic> rawList = result['data'] as List<dynamic>;
          usersList = rawList.cast<Map<String, dynamic>>();
        }
      } else if (result.containsKey('items') && result['items'] is List) {
        final List<dynamic> rawList = result['items'] as List<dynamic>;
        usersList = rawList.cast<Map<String, dynamic>>();
      } else if (result is List) {
        final List<dynamic> rawList = result as List<dynamic>;
        usersList = rawList.cast<Map<String, dynamic>>();
      }
      
      return usersList.map((json) => User.fromJson(json)).toList();
    } catch (e) {
      print('[ERROR] Ошибка в getAllUsers: $e');
      rethrow;
    }
  }

  static Future<User> updateProfile(Map<String, dynamic> data) async {
    final headers = await _getHeaders();
    final url = Uri.parse(ApiConfig.updateProfileEndpoint);

    ApiConfig.logRequest('POST', url.toString(), body: data);
    
    final response = await http.post(
      url,
      headers: headers,
      body: json.encode(data),
    );
    
    final result = await _handleResponse(response);

    // Поддерживаем несколько форматов ответа (как в других методах)
    if (result['success'] == true) {
      final dynamic userJson = result['user'] ?? result['data'] ?? result['profile'];
      if (userJson is Map<String, dynamic>) {
        return User.fromJson(userJson);
      }
      // Иногда сервер может вернуть пользователя "плоско"
      return User.fromJson(result);
    }

    // Ошибка
    final err = result['error'] ?? result['message'] ?? 'Не удалось обновить профиль';
    throw Exception(err.toString());
  }

  /// Удаление (soft-delete) профиля текущего пользователя.
  /// После успешного удаления токен остаётся технически валидным, поэтому
  /// вызывающему коду рекомендуется вызывать [logout] и переводить пользователя
  /// на экран входа.
  static Future<bool> deleteProfile({int? userIdForAdmin}) async {
    final headers = await _getHeaders();
    final url = Uri.parse(ApiConfig.deleteProfileEndpoint);

    final body = <String, dynamic>{};
    if (userIdForAdmin != null) {
      body['user_id'] = userIdForAdmin;
    }

    ApiConfig.logRequest('POST', url.toString(), body: body.isEmpty ? null : body);

    final response = await http.post(
      url,
      headers: headers,
      body: json.encode(body),
    );

    final result = await _handleResponse(response);

    if (result['success'] == true) {
      return true;
    }

    final err = result['error'] ?? result['message'] ?? 'Не удалось удалить профиль';
    print('[ERROR] Ошибка удаления профиля: $err');
    return false;
  }

  // === ЧАТЫ ===

  static Future<List<Chat>> getChats() async {
    final headers = await _getHeaders();
    final url = Uri.parse(ApiConfig.chatsEndpoint);

    ApiConfig.logRequest('GET', url.toString());
    
    try {
      final response = await http.get(url, headers: headers);
      final result = await _handleResponse(response);
      
      final List chatsData = result is List ? result : (result['data'] ?? result['chats'] ?? []);
      return chatsData.map((json) => Chat.fromJson(json)).toList();
    } catch (e) {
      print('[ERROR] Ошибка получения чатов: $e');
      return [];
    }
  }




  static Future<Chat> getChatDetail(int chatId) async {
    final headers = await _getHeaders();
    final url = Uri.parse(ApiConfig.chatDetailEndpoint(chatId)); // ← ИСПРАВЛЕНО

    ApiConfig.logRequest('GET', url.toString());
    
    try {
      final response = await http.get(url, headers: headers);
      final result = await _handleResponse(response);
      return Chat.fromJson(result);
    } catch (e) {
      print('[ERROR] Ошибка получения деталей чата $chatId: $e');
      rethrow;
    }
  }


  // Создать чат - ФИНАЛЬНАЯ ИСПРАВЛЕННАЯ ВЕРСИЯ

  static Future<Chat> createChat(String name, bool isGroup,
      {List<int>? participants}) async {
    final headers = await _getHeaders();
    final url = Uri.parse(ApiConfig.createChatEndpoint);

    Map<String, dynamic> body;
    
    // ВЫНОСИМ ПЕРЕМЕННЫЕ ЗА ПРЕДЕЛЫ УСЛОВИЙ
    final currentUser = await getCurrentUser();
    final currentUserId = currentUser.id;
    List<int> allParticipants = [];
    
    if (isGroup) {
      if (participants == null || participants.isEmpty) {
        throw Exception('Для группового чата нужны участники');
      }
      
      // Формируем полный список участников (текущий + выбранные)
      allParticipants = [currentUserId, ...participants];
      
      // Правильное поле для группового чата - 'members'
      final membersAsStrings = allParticipants.map((id) => id.toString()).toList();
      
      body = {
        'name': name.trim(),
        'is_group': true,
        'members': membersAsStrings, // ← КЛЮЧЕВОЕ ИСПРАВЛЕНИЕ
      };
      
      print('[DEBUG] Создание группового чата. Участники (все): $allParticipants');
    } else {
      if (participants == null || participants.isEmpty) {
        throw Exception('Для личного чата нужен ID другого пользователя');
      }
      
      // Для личного чата используем только первого участника
      allParticipants = [currentUserId, participants.first];
      
      body = {
        'user_id': participants.first, // Для личного чата остаётся 'user_id'
      };
    }

    ApiConfig.logRequest('POST', url.toString(), body: body);
    
    try {
      final response = await http.post(
        url,
        headers: headers,
        body: json.encode(body),
      );

      final result = await _handleResponse(response);
      print('[DEBUG] Ответ создания чата: $result');
      
      if (result is Map<String, dynamic> && result['success'] == true) {
        int? chatId;
        if (result['chat_id'] != null) {
          chatId = result['chat_id'] is int ? result['chat_id'] : int.tryParse(result['chat_id'].toString());
        }
        
        if (chatId != null) {
          // Создаём объект Chat с актуальными данными
          return Chat(
            id: chatId,
            name: isGroup ? name.trim() : 'Чат',
            avatar: null,
            isGroup: isGroup,
            createdAt: DateTime.now(),
            userIds: allParticipants, // ← теперь переменная доступна
            lastMessageId: null,
          );
        }
        
        // Если чат уже существует, всё равно создаём объект
        if (result['chat_exists'] == true || result['message']?.contains('уже существует') == true) {
          throw Exception('Чат уже существует. Обновите список чатов.');
        }
        
        throw Exception('Чат успешно создан, но не получен ID. Ответ: $result');
      }
      
      throw Exception('Не удалось создать чат. Ответ: $result');
      
    } catch (e) {
      print('[ERROR] Ошибка создания чата: $e');
      rethrow;
    }
  }


  // Обновить чат - временно отключаем, если не работает
  static Future<Chat> updateChat(int chatId, Map<String, dynamic> data) async {
    // Временно возвращаем ошибку или заглушку
    print('[WARNING] updateChat не реализован на бэкенде');
    throw Exception('Функция обновления чата временно недоступна');
  }

  // Удалить чат - временно отключаем
  static Future<bool> deleteChat(int chatId) async {
    print('[WARNING] deleteChat не реализован на бэкенде');
    return false;
  }

  // НОВЫЙ МЕТОД: Получить создателя чата
  static Future<User> getChatCreator(int chatId) async {
    final headers = await _getHeaders();
    final url = Uri.parse('${ApiConfig.baseUrl}/chat-api/v1/chats/$chatId/creator');

    ApiConfig.logRequest('GET', url.toString());
    
    try {
      final response = await http.get(url, headers: headers);
      final result = await _handleResponse(response);
      
      if (result.containsKey('success') && result['success'] == true) {
        if (result.containsKey('creator') && result['creator'] is Map<String, dynamic>) {
          return User.fromJson(result['creator'] as Map<String, dynamic>);
        }
      }
      
      // Если не удалось получить создателя, возвращаем пустого пользователя
      print('[WARNING] Не удалось получить создателя чата $chatId. Ответ: $result');
      return User.empty();
    } catch (e) {
      print('[ERROR] Ошибка получения создателя чата $chatId: $e');
      return User.empty();
    }
  }



  // === СООБЩЕНИЯ ===

  // Получить сообщения чата - ИСПРАВЛЕННАЯ ВЕРСИЯ

  static Future<List<Message>> getMessages(int chatId) async {
    final headers = await _getHeaders();
    final url = Uri.parse('${ApiConfig.messagesEndpoint}?chat_id=$chatId&page=1&per_page=50');

    ApiConfig.logRequest('GET', url.toString());
    
    try {
      final response = await http.get(url, headers: headers);
      final result = await _handleResponse(response);
      

      // ДОБАВЬТЕ ЭТОТ ОТЛАДОЧНЫЙ КОД
      print('[DEBUG] Ответ getMessages:');
      print('Статус: ${response.statusCode}');
      print('Тело: ${response.body}');






      final List messagesData = result is List ? result : (result['data'] ?? result['messages'] ?? []);

      // Проверьте структуру первого сообщения с медиа
      if (messagesData.isNotEmpty) {
        for (var msg in messagesData) {
          if (msg is Map<String, dynamic>) {
            print('[DEBUG] Сообщение ID: ${msg['id']}');
            print('[DEBUG] Поле image: ${msg['image']}');
            print('[DEBUG] Поле file: ${msg['file']}');
            print('[DEBUG] Поле text: ${msg['text']}');
            
            // Если нашли медиа - остановитесь
            if (msg['image'] != null && msg['image'] != 'null') {
              print('[DEBUG] ✅ Найден image URL: ${msg['image']}');
              break;
            }
          }
        }
      }
      // КОНЕЦ ОТЛАДОЧНОГО КОДА





      // Преобразуем каждое сообщение с учетом локальных метаданных
      final List<Message> messages = [];
      
      for (final json in messagesData) {
        final message = await _createMessageWithLocalMedia(json);
        messages.add(message);
      }
      
      return messages;
      
    } catch (e) {
      print('[ERROR] Ошибка получения сообщений: $e');
      return [];
    }
  }

  // НОВЫЙ ВСПОМОГАТЕЛЬНЫЙ МЕТОД
  static Future<Message> _createMessageWithLocalMedia(Map<String, dynamic> json) async {
    final messageId = _parseInt(json['message_id'] ?? json['id']);
    
    // 1. Пробуем получить из локального хранилища
    final localMedia = await MediaStorage.getMediaForMessage(messageId);
    
    String? imageUrl = localMedia['imageUrl'];
    String? fileUrl = localMedia['fileUrl'];
    String? fileName = localMedia['fileName'];
    String? fileType = localMedia['fileType'];
    int? fileSize = _parseInt(localMedia['fileSize']);
    
    // 2. Если нет в хранилище, пробуем из JSON (на случай, если сервер всё же вернул)
    if (imageUrl == null || imageUrl.isEmpty) {
      if (json['image'] != null && json['image'] is String) {
        final imageValue = json['image'] as String;
        if (imageValue.isNotEmpty && imageValue != 'null' && imageValue != 'false') {
          imageUrl = imageValue;
        }
      }
    }
    
    if (fileUrl == null || fileUrl.isEmpty) {
      if (json['file'] != null && json['file'] is String) {
        final fileValue = json['file'] as String;
        if (fileValue.isNotEmpty && fileValue != 'null' && fileValue != 'false') {
          fileUrl = fileValue;
        }
      }
    }
    
    // 3. Определяем тип
    String type = 'text';
    if (imageUrl != null && imageUrl.isNotEmpty) {
      type = 'image';
    } else if (fileUrl != null && fileUrl.isNotEmpty) {
      type = 'file';
    } else if (json['type'] != null && json['type'] is String) {
      type = json['type'] as String;
    }
    
    // Логирование для отладки
    if (type == 'image' || type == 'file') {
      print('[API] Восстановлен URL для сообщения $messageId: ${imageUrl ?? fileUrl}');
    }
    
    return Message(
      id: messageId,
      chatId: _parseInt(json['chat_id'] ?? json['chat'] ?? json['room_id'] ?? 0),
      senderId: _parseInt(json['sender_id'] ?? json['sender'] ?? json['author'] ?? json['user_id'] ?? 0),
      text: json['text']?.toString(),
      image: imageUrl,
      file: fileUrl,
      type: type,
      createdAt: _parseDateTime(json['created_at'] ?? json['date'] ?? json['timestamp']),
      fileName: fileName ?? json['file_name']?.toString(),
      fileType: fileType ?? json['file_type']?.toString(),
      fileSize: fileSize ?? _parseInt(json['file_size']),
    );
  }




  static Future<Message> sendTextMessage(int chatId, String text) async {
    final headers = await _getHeaders();
    final url = Uri.parse(ApiConfig.sendMessageEndpoint);

    final body = {
      'chat_id': chatId,
      'text': text.trim(),
    };

    ApiConfig.logRequest('POST', url.toString(), body: body);

    try {
      final response = await http.post(
        url,
        headers: headers,
        body: json.encode(body),
      );

      final result = await _handleResponse(response);
      print('[DEBUG] Ответ отправки текста: $result');
      
      // Обрабатываем WordPress ответ
      if (result is Map<String, dynamic>) {
        if (result.containsKey('success') && result['success'] == true) {
          
          // ВАРИАНТ 1: WordPress возвращает message_id вместо id
          if (result.containsKey('message_id')) {
            // Создаём Map в формате для Message.fromJson
            Map<String, dynamic> messageJson = {
              'id': result['message_id'],
              'chat_id': result['chat_id'],
              'sender_id': result['sender_id'],
              'text': result['text'],
              'image': null,
              'file': null,
              'type': 'text',
              'created_at': result['created_at'],
            };
            
            return Message.fromJson(messageJson);
          }
          
          // ВАРИАНТ 2: Есть вложенный объект message
          if (result.containsKey('message') && result['message'] is Map<String, dynamic>) {
            final messageData = result['message'] as Map<String, dynamic>;
            
            // Если в message тоже message_id вместо id
            if (messageData.containsKey('message_id') && !messageData.containsKey('id')) {
              messageData['id'] = messageData['message_id'];
            }
            
            // Добавляем type если нет
            if (!messageData.containsKey('type')) {
              messageData['type'] = 'text';
            }
            
            return Message.fromJson(messageData);
          }
          
          // ВАРИАНТ 3: Есть вложенный объект data
          if (result.containsKey('data') && result['data'] is Map<String, dynamic>) {
            final data = result['data'] as Map<String, dynamic>;
            
            // Если в data тоже message_id вместо id
            if (data.containsKey('message_id') && !data.containsKey('id')) {
              data['id'] = data['message_id'];
            }
            
            // Добавляем type если нет
            if (!data.containsKey('type')) {
              data['type'] = 'text';
            }
            
            return Message.fromJson(data);
          }
        }
        
        // ВАРИАНТ 4: Ответ уже содержит id напрямую
        if (result.containsKey('id')) {
          // Добавляем type если нет
          final Map<String, dynamic> resultWithType = Map.from(result);
          if (!resultWithType.containsKey('type')) {
            resultWithType['type'] = 'text';
          }
          
          return Message.fromJson(resultWithType);
        }
      }
      
      // Если ничего не подошло, но статус был 200-299
      // Создаём локальное сообщение
      if (result is Map<String, dynamic>) {
        final messageJson = {
          'id': result['message_id'] ?? DateTime.now().millisecondsSinceEpoch,
          'chat_id': chatId,
          'sender_id': result['sender_id'] ?? 0,
          'text': text,
          'image': null,
          'file': null,
          'type': 'text',
          'created_at': result['created_at'] ?? DateTime.now().toIso8601String(),
        };
        
        return Message.fromJson(messageJson);
      }
      
      // Резервный вариант
      return Message(
        id: DateTime.now().millisecondsSinceEpoch,
        chatId: chatId,
        senderId: 0, // Временный ID
        text: text,
        image: null,
        file: null,
        type: 'text',
        createdAt: DateTime.now(),
      );
      
    } catch (e) {
      print('[ERROR] Ошибка отправки сообщения: $e');
      
      // Даже при ошибке создаём локальное сообщение
      return Message(
        id: DateTime.now().millisecondsSinceEpoch,
        chatId: chatId,
        senderId: 0, // Временный ID
        text: text,
        image: null,
        file: null,
        type: 'text',
        createdAt: DateTime.now(),
      );
    }
  }

  // === ЗАГРУЗКА ФАЙЛОВ - ИСПРАВЛЕННАЯ ВЕРСИЯ ===

  // СТАРЫЙ МЕТОД: Загрузка аватара (сохраняем для совместимости с register_screen.dart)
  static Future<String?> uploadAvatar(File imageFile) async {
    try {
      print('📤 Загрузка аватара...');
      
      var request = http.MultipartRequest(
        'POST',
        Uri.parse(ApiConfig.uploadEndpoint),
      );
      
      // Получаем токен для авторизации
      final token = await getToken();
      if (token != null) {
        request.headers['Authorization'] = 'Bearer $token';
      }
      
      request.files.add(await http.MultipartFile.fromPath(
        'file',
        imageFile.path,
        filename: 'avatar_${DateTime.now().millisecondsSinceEpoch}.jpg',
      ));
      
      final response = await request.send();
      final responseBody = await response.stream.bytesToString();
      final result = json.decode(responseBody);
      
      print('📥 Ответ: ${response.statusCode}');
      
      if (response.statusCode == 200 && result['success'] == true) {
        final url = result['file']['url'];
        print('✅ Аватар загружен: $url');
        return url;
      } else {
        print('❌ Ошибка: ${result['message']}');
        return null;
      }
      
    } catch (e) {
      print('❌ Ошибка загрузки: $e');
      return null;
    }
  }

  // НОВЫЙ МЕТОД: Универсальная загрузка файла на WordPress сервер
  static Future<Map<String, dynamic>> uploadFile(File file, {String? fileName}) async {
    final token = await getToken();
    if (token == null) throw Exception('Требуется авторизация');

    final url = Uri.parse(ApiConfig.uploadEndpoint);
    
    try {
      print('[API] Загрузка файла на WordPress: ${file.path}');
      
      var request = http.MultipartRequest('POST', url);

      // WordPress требует Authorization заголовок
      request.headers['Authorization'] = 'Bearer $token';
      
      // Определяем тип контента на основе расширения файла
      String? contentType;
      final extension = file.path.split('.').last.toLowerCase();
      if (['jpg', 'jpeg'].contains(extension)) {
        contentType = 'image/jpeg';
      } else if (extension == 'png') {
        contentType = 'image/png';
      } else if (extension == 'gif') {
        contentType = 'image/gif';
      } else if (extension == 'pdf') {
        contentType = 'application/pdf';
      } else if (extension == 'mp4') {
        contentType = 'video/mp4';
      } else if (extension == 'mov') {
        contentType = 'video/quicktime';
      } else if (extension == 'webm') {
        contentType = 'video/webm';
      } else if (extension == 'mp3') {
        contentType = 'audio/mpeg';
      } else if (extension == 'm4a') {
        contentType = 'audio/mp4';
      } else if (extension == 'aac') {
        contentType = 'audio/aac';
      } else if (extension == 'wav') {
        contentType = 'audio/wav';
      } else if (extension == 'ogg') {
        contentType = 'audio/ogg';
      } else if (extension == 'doc') {
        contentType = 'application/msword';
      } else if (extension == 'docx') {
        contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
      } else if (extension == 'xls') {
        contentType = 'application/vnd.ms-excel';
      } else if (extension == 'xlsx') {
        contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
      } else {
        contentType = 'application/octet-stream';
      }
      
      final multipartFile = await http.MultipartFile.fromPath(
        'file',
        file.path,
        filename: fileName ?? 'file_${DateTime.now().millisecondsSinceEpoch}.$extension',
        contentType: contentType != null ? MediaType.parse(contentType) : null,
      );
      
      request.files.add(multipartFile);
      
      ApiConfig.logRequest('POST', url.toString(), body: {
        'file': file.path,
        'filename': multipartFile.filename,
        'contentType': contentType,
      });
      
      final response = await request.send();
      final responseBody = await response.stream.bytesToString();
      
      print('[API] Ответ загрузки: ${response.statusCode}');
      print('[API] Тело ответа: $responseBody');
      
      if (response.statusCode >= 200 && response.statusCode < 300) {
        final result = json.decode(responseBody);
        
        // Проверяем WordPress формат ответа
        if (result is Map<String, dynamic>) {
          if (result['success'] == true) {
            final fileData = result['file'];
            
            // Возвращаем полные данные о файле
            return {
              'success': true,
              'url': fileData['url'],
              'name': fileData['name'],
              'type': fileData['type'],
              'size': fileData['size'],
              'id': fileData['id'],
              'uploaded_at': fileData['uploaded_at'],
            };
          } else {
            throw Exception(result['message'] ?? 'Ошибка загрузки файла');
          }
        }
        throw Exception('Неверный формат ответа от сервера');
      } else {
        final errorResult = json.decode(responseBody);
        throw Exception(errorResult['message'] ?? 'Ошибка ${response.statusCode}');
      }
    } catch (e) {
      print('[API] Ошибка загрузки файла: $e');
      rethrow;
    }
  }

 


  // ИСПРАВЛЕННЫЙ МЕТОД: Отправка сообщения с файлом

  static Future<Message> sendMessageWithFile(
      int chatId, String text, File file, String type) async {
    
    print('[API] Отправка файла типа: $type');
    
    try {
      // 1. Загружаем файл на WordPress
      final uploadResult = await uploadFile(file);
      
      final fileUrl = uploadResult['url'] as String;
      final fileName = uploadResult['name'] as String;
      final fileType = uploadResult['type'] as String;
      final fileSize = uploadResult['size'] as int;
      
      print('[API] Файл загружен: $fileUrl');
      
      // 2. Отправляем сообщение
      final headers = await _getHeaders();
      final url = Uri.parse(ApiConfig.sendMessageEndpoint);
      
      final body = {
        'chat_id': chatId,
        'text': text.isNotEmpty ? text : (type == 'image' ? 'Изображение' : 'Файл'),
        'type': type,
      };
      
      if (type == 'image') {
        body['image_url'] = fileUrl;
      } else if (type == 'file') {
        body['file_url'] = fileUrl;
      }
      
      final response = await http.post(
        url,
        headers: headers,
        body: json.encode(body),
      );
      
      final result = await _handleResponse(response);
      
      // 3. Получаем ID сообщения из ответа сервера
      int messageId;
      int senderId;
      String createdAt;
      
      if (result is Map<String, dynamic> && result['success'] == true) {
        messageId = _parseInt(result['message_id'] ?? DateTime.now().millisecondsSinceEpoch);
        senderId = _parseInt(result['sender_id'] ?? 0);
        createdAt = result['created_at']?.toString() ?? DateTime.now().toIso8601String();
      } else {
        messageId = DateTime.now().millisecondsSinceEpoch;
        senderId = 0;
        createdAt = DateTime.now().toIso8601String();
      }
      
      // 4. ВАЖНО: Сохраняем в локальное хранилище
      await MediaStorage.saveMediaForMessage(
        messageId,
        imageUrl: type == 'image' ? fileUrl : null,
        fileUrl: type == 'file' ? fileUrl : null,
        fileName: type == 'file' ? fileName : null,
        fileType: type == 'file' ? fileType : null,
        fileSize: type == 'file' ? fileSize : null,
      );
      
      print('[API] Локально сохранен URL для сообщения $messageId: $fileUrl');
      
      // 5. Создаем объект Message с правильными данными
      return Message(
        id: messageId,
        chatId: chatId,
        senderId: senderId,
        text: text.isNotEmpty ? text : (type == 'image' ? 'Изображение' : 'Файл'),
        image: type == 'image' ? fileUrl : null,
        file: type == 'file' ? fileUrl : null,
        type: type,
        createdAt: _parseDateTime(createdAt),
        fileName: type == 'file' ? fileName : null,
        fileType: type == 'file' ? fileType : null,
        fileSize: type == 'file' ? fileSize : null,
      );
      
    } catch (e) {
      print('[API] Ошибка отправки: $e');
      final err = e.toString().replaceFirst('Exception: ', '').trim();
      return Message.createLocal(
        chatId: chatId,
        text: err.isNotEmpty ? 'Не удалось отправить файл: $err' : 'Не удалось отправить файл',
        senderId: 0,
        type: type,
      );
    }
  }

  // Добавьте этот вспомогательный метод в класс ApiService
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
        if (value.contains('T')) {
          return DateTime.parse(value);
        } else {
          return DateTime.parse(value.replaceAll(' ', 'T'));
        }
      } catch (_) {
        return null;
      }
    }
    return null;
  }




  static Future<bool> deleteMessage(int messageId) async {
    final headers = await _getHeaders();
    final url = Uri.parse(ApiConfig.deleteMessageEndpoint(messageId));

    ApiConfig.logRequest('POST', url.toString());

    try {
      final response = await http.post(url, headers: headers);
      final result = await _handleResponse(response);

      if (result['success'] == true) {
        return true;
      }

      // Fallback: если на сервере нет POST /messages/{id}/delete,
      // пробуем стандартный REST-метод DELETE /messages/{id}
      final errText = (result['error'] ?? result['message'] ?? '').toString();
      final statusCode = result['statusCode'];

      final looksLikeNoRoute = (statusCode == 404) ||
          errText.contains('No route was found') ||
          errText.contains('rest_no_route');

      if (looksLikeNoRoute) {
        final deleteUrl = Uri.parse(ApiConfig.deleteMessageEndpointRest(messageId));
        ApiConfig.logRequest('DELETE', deleteUrl.toString());

        final deleteResponse = await http.delete(deleteUrl, headers: headers);
        final deleteResult = await _handleResponse(deleteResponse);

        if (deleteResult['success'] == true) {
          return true;
        }

        print('[ERROR] Ошибка удаления сообщения $messageId (DELETE fallback): '
            '${deleteResult['error'] ?? deleteResult['message'] ?? deleteResult}');
        return false;
      }

      print('[ERROR] Ошибка удаления сообщения $messageId: ${result['error'] ?? result['message'] ?? result}');
      return false;
    } catch (e) {
      print('[ERROR] Ошибка удаления сообщения $messageId: $e');
      return false;
    }
  }
}