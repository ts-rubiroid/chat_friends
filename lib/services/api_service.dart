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
        throw Exception('Ошибка парсинга JSON: $e');
      }
    } else {
      try {
        final errorData = json.decode(response.body);
        final errorMessage = errorData['message'] ?? 
                           errorData['error'] ?? 
                           'Ошибка ${response.statusCode}';
        throw Exception(errorMessage);
      } catch (_) {
        throw Exception('Ошибка ${response.statusCode}: ${response.body}');
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
    
    final body = {
      'phone': phone.trim(),
      'password': password.trim(),
      'first_name': (userData['first_name'] ?? '').trim(),
      'last_name': (userData['last_name'] ?? '').trim(),
      'nickname': (userData['nickname'] ?? '').trim(),
    };

    final middleName = (userData['middle_name'] ?? '').trim();
    if (middleName.isNotEmpty) {
      body['middle_name'] = middleName;
    }

    ApiConfig.logRequest('POST', url.toString(), body: body);
    
    try {
      final response = await http.post(
        url,
        headers: {'Content-Type': 'application/json'},
        body: json.encode(body),
      );

      final result = await _handleResponse(response);
      
      final token = result['token'];
      if (token != null && token is String) {
        await saveToken(token);
        print('[API] Токен сохранен после регистрации: ${token.substring(0, min(20, token.length))}...');
      } else {
        print('[API] Внимание: токен не найден в ответе регистрации');
      }
      
      return result;
    } catch (e) {
      print('[API] Ошибка регистрации: $e');
      rethrow;
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
      return []; // Возвращаем пустой список вместо ошибки
    }
  }

  static Future<Chat> getChatDetail(int chatId) async {
    final headers = await _getHeaders();
    final url = Uri.parse(ApiConfig.chatDetail(chatId));

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


  // Создать чат - ИСПРАВЛЕННАЯ ВЕРСИЯ (убрана попытка получить детали)
  static Future<Chat> createChat(String name, bool isGroup,
      {List<int>? participants}) async {
    final headers = await _getHeaders();
    final url = Uri.parse(ApiConfig.createChatEndpoint);

    Map<String, dynamic> body;
    
    if (isGroup) {
      if (participants == null || participants.isEmpty) {
        throw Exception('Для группового чата нужны участники');
      }
      
      body = {
        'name': name.trim(),
        'is_group': true,
        'user_ids': participants,
      };
    } else {
      if (participants == null || participants.isEmpty) {
        throw Exception('Для личного чата нужен ID другого пользователя');
      }
      
      body = {
        'user_id': participants.first,
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
      
      // WordPress возвращает успех, даже если чат "уже существует"
      if (result is Map<String, dynamic> && result['success'] == true) {
        // ВАЖНО: НЕ вызываем getChatDetail()! Это вызывает ошибку 404.
        // Бэкенд подтвердил, что чат есть (создан или уже существовал).
        
        // Если в ответе есть ID чата, создаем минимальный объект
        int? chatId;
        if (result['chat_id'] != null) {
          chatId = result['chat_id'] is int ? result['chat_id'] : int.tryParse(result['chat_id'].toString());
        }
        
        if (chatId != null) {
          // Создаем временный объект Chat для возврата
          return Chat(
            id: chatId,
            name: isGroup ? name.trim() : 'Чат', // Имя можно будет обновить позже из списка
            avatar: null,
            isGroup: isGroup,
            createdAt: DateTime.now(),
            userIds: participants,
            lastMessageId: null,
          );
        }
        
        // Если ID нет, но успех есть — чат создан/существует.
        // Просто сообщаем об успехе, UI должен обновить список.
        throw Exception('Чат успешно создан или уже существует. Обновите список чатов.');
      }
      
      throw Exception('Не удалось создать чат. Ответ: $result');
      
    } catch (e) {
      print('[ERROR] Ошибка создания чата: $e');
      
      // Различаем "ошибку создания" и "успех, но с 404 на getChatDetail"
      if (e.toString().contains('Чат успешно создан')) {
        // Это наше "успешное" исключение — пробрасываем как есть
        rethrow;
      }
      
      // Если ошибка связана с 404 от getChatDetail (старый код), сообщаем об успехе
      if (e.toString().contains('404') && e.toString().contains('chat_id')) {
        throw Exception('Чат создан или уже существует! Вернитесь в список чатов.');
      }
      
      // Любая другая ошибка
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
      
      // Обрабатываем ответ WordPress
      if (result is Map<String, dynamic>) {
        if (result.containsKey('success') && result['success'] == true) {
          if (result.containsKey('message') && result['message'] is Map<String, dynamic>) {
            return Message.fromJson(result['message'] as Map<String, dynamic>);
          } else if (result.containsKey('data') && result['data'] is Map<String, dynamic>) {
            return Message.fromJson(result['data'] as Map<String, dynamic>);
          }
        }
        // Если данные возвращаются напрямую
        if (result.containsKey('id')) {
          return Message.fromJson(result);
        }
      }
      
      throw Exception('Не удалось отправить сообщение. Ответ: $result');
    } catch (e) {
      print('[ERROR] Ошибка отправки сообщения: $e');
      rethrow;
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

  static Future<String> uploadAvatar(File imageFile) async {
    final token = await getToken();
    if (token == null) throw Exception('Требуется авторизация');

    final url = Uri.parse(ApiConfig.uploadAvatarEndpoint);
    
    var request = http.MultipartRequest('POST', url);
    request.headers['Authorization'] = 'Bearer $token';
    
    var multipartFile = await http.MultipartFile.fromPath(
      'avatar',
      imageFile.path,
      filename: 'avatar_${DateTime.now().millisecondsSinceEpoch}.${imageFile.path.split('.').last}',
    );
    request.files.add(multipartFile);
    
    final response = await request.send();
    final responseBody = await response.stream.bytesToString();
    
    if (response.statusCode >= 200 && response.statusCode < 300) {
      final result = json.decode(responseBody);
      return result['url'] ?? result['file_url'] ?? result['data']['url'];
    } else {
      throw Exception('Ошибка загрузки: $responseBody');
    }
  }
}