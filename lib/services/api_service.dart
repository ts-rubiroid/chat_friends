import 'dart:convert';
import 'dart:io';
import 'dart:math';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:chat_friends/utils/api.dart';
import 'package:chat_friends/models/user.dart';
import 'package:chat_friends/models/chat.dart';
import 'package:chat_friends/models/message.dart';

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
        // Не бросаем исключение, возвращаем пустой Map
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
    return User.fromJson(result);
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
    // ИСПРАВЛЕНИЕ: Используем правильный endpoint с query параметром
    final url = Uri.parse('${ApiConfig.messagesEndpoint}?chat_id=$chatId&page=1&per_page=50');

    ApiConfig.logRequest('GET', url.toString());
    
    try {
      final response = await http.get(url, headers: headers);
      final result = await _handleResponse(response);
      
      final List messagesData = result is List ? result : (result['data'] ?? result['messages'] ?? []);
      return messagesData.map((json) => Message.fromJson(json)).toList();
    } catch (e) {
      print('[ERROR] Ошибка получения сообщений: $e');
      return [];
    }
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


  static Future<Message> sendMessageWithFile(
      int chatId, String text, File file, String type) async {
    final token = await getToken();
    if (token == null) throw Exception('Требуется авторизация');

    final url = Uri.parse(ApiConfig.sendMessageEndpoint);
    
    try {
      var request = http.MultipartRequest('POST', url);

      request.headers.addAll({
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      });
      
      request.fields['chat_id'] = chatId.toString();
      request.fields['text'] = text;
      request.fields['type'] = type;
      
      var multipartFile = await http.MultipartFile.fromPath(
        'file',
        file.path,
        filename: '${type}_${DateTime.now().millisecondsSinceEpoch}.${file.path.split('.').last}',
      );
      request.files.add(multipartFile);
      
      ApiConfig.logRequest('POST', url.toString(), 
          body: {'chat_id': chatId, 'text': text, 'type': type});
      
      final response = await request.send();
      final responseBody = await response.stream.bytesToString();
      
      if (response.statusCode >= 200 && response.statusCode < 300) {
        final result = json.decode(responseBody);
        return Message.fromJson(result);
      } else {
        throw Exception('Ошибка ${response.statusCode}: $responseBody');
      }
    } catch (e) {
      print('[API] Ошибка отправки файла: $e');
      rethrow;
    }
  }

  static Future<bool> deleteMessage(int messageId) async {
    print('[WARNING] deleteMessage не реализован на бэкенде');
    return false;
  }

  // === ЗАГРУЗКА ФАЙЛОВ ===

  static Future<String?> uploadAvatar(File imageFile) async {
    try {
      print('📤 Загрузка аватара БЕЗ токена...');
      
      var request = http.MultipartRequest(
        'POST',
        Uri.parse('${ApiConfig.baseUrl}/wp-json/chat-api/v1/upload'),
      );
      
      // БЕЗ заголовка Authorization!
      
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
}