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

  // Регистрация пользователя - ИСПРАВЛЕННАЯ ВЕРСИЯ
  static Future<Map<String, dynamic>> register(
      String phone, String password, Map<String, dynamic> userData) async {
    final url = Uri.parse(ApiConfig.registerEndpoint);
    
    // Создаем тело запроса точно как ожидает WordPress backend
    final body = {
      'phone': phone.trim(),
      'password': password.trim(),
      'first_name': (userData['first_name'] ?? '').trim(),
      'last_name': (userData['last_name'] ?? '').trim(),
      'nickname': (userData['nickname'] ?? '').trim(),
    };

    // Добавляем middle_name только если он есть
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
      
      // WordPress возвращает токен в result['token']
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

  // Логин
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
    
    // WordPress API может возвращать токен в разных полях
    final token = result['token'] ?? result['jwt_token'] ?? result['data']['token'];
    if (token != null) {
      await saveToken(token);
    }
    
    return result;
  }

  // === ПОЛЬЗОВАТЕЛИ ===


// Получить текущего пользователя - ИСПРАВЛЕННАЯ ВЕРСИЯ
static Future<User> getCurrentUser() async {
  final headers = await _getHeaders();
  final url = Uri.parse(ApiConfig.meEndpoint);

  ApiConfig.logRequest('GET', url.toString());
  
  try {
    final response = await http.get(url, headers: headers);
    
    print('[DEBUG] Status code: ${response.statusCode}');
    
    // Используем нашу функцию обработки ответа
    final Map<String, dynamic> result = await _handleResponse(response);
    
    print('[DEBUG] Parsed result from /me: $result');
    
    // WordPress возвращает данные в формате: {success: true, user: {...}}
    if (result.containsKey('success') && result['success'] == true) {
      if (result.containsKey('user') && result['user'] is Map<String, dynamic>) {
        // Данные в поле 'user'
        print('[DEBUG] Извлекаем данные из result[\'user\']');
        return User.fromJson(result['user'] as Map<String, dynamic>);
      } else if (result.containsKey('data') && result['data'] is Map<String, dynamic>) {
        // Данные в поле 'data' (альтернативный вариант)
        print('[DEBUG] Извлекаем данные из result[\'data\']');
        return User.fromJson(result['data'] as Map<String, dynamic>);
      }
    } else if (result.containsKey('id')) {
      // Данные возвращаются напрямую
      print('[DEBUG] Данные возвращены напрямую');
      return User.fromJson(result);
    }
    
    // Если не удалось распарсить - выбрасываем исключение
    throw Exception('Неверный формат ответа от API: $result');
  } catch (e) {
    print('[ERROR] Ошибка в getCurrentUser: $e');
    rethrow;
  }
}

// Получить всех пользователей - ИСПРАВЛЕННАЯ ВЕРСЯ
static Future<List<User>> getAllUsers() async {
  final headers = await _getHeaders();
  final url = Uri.parse(ApiConfig.usersEndpoint);

  ApiConfig.logRequest('GET', url.toString());
  
  try {
    final response = await http.get(url, headers: headers);
    
    // Явно указываем тип для _handleResponse
    final Map<String, dynamic> result = await _handleResponse(response);
    
    print('[DEBUG] Ответ от /users: $result');
    
    // Обрабатываем разные форматы ответа
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
      // Если ответ сам по себе список
      final List<dynamic> rawList = result as List<dynamic>;
      usersList = rawList.cast<Map<String, dynamic>>();
    }
    
    print('[DEBUG] Найдено пользователей: ${usersList.length}');
    
    return usersList.map((json) => User.fromJson(json)).toList();
  } catch (e) {
    print('[ERROR] Ошибка в getAllUsers: $e');
    rethrow;
  }
}




  // Обновить профиль
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

  // Получить все чаты пользователя
  static Future<List<Chat>> getChats() async {
    final headers = await _getHeaders();
    final url = Uri.parse(ApiConfig.chatsEndpoint);

    ApiConfig.logRequest('GET', url.toString());
    
    final response = await http.get(url, headers: headers);
    final result = await _handleResponse(response);
    
    final List chatsData = result is List ? result : (result['data'] ?? result['chats'] ?? []);
    return chatsData.map((json) => Chat.fromJson(json)).toList();
  }

  // Получить детали чата
  static Future<Chat> getChatDetail(int chatId) async {
    final headers = await _getHeaders();
    final url = Uri.parse(ApiConfig.chatDetail(chatId));

    ApiConfig.logRequest('GET', url.toString());
    
    final response = await http.get(url, headers: headers);
    final result = await _handleResponse(response);
    
    return Chat.fromJson(result);
  }

  // Создать чат

  static Future<Chat> createChat(String name, bool isGroup,
      {List<int>? userIds}) async {
    final headers = await _getHeaders();
    final url = Uri.parse(ApiConfig.createChatEndpoint);

    // Временно убираем currentUser, пока не работает
    // final currentUser = await getCurrentUser();
    
    final body = {
      'name': name,
      'is_group': isGroup,
      'user_ids': userIds ?? [], // WordPress может ожидать пустой массив
    };

    ApiConfig.logRequest('POST', url.toString(), body: body);
    
    final response = await http.post(
      url,
      headers: headers,
      body: json.encode(body),
    );

    final result = await _handleResponse(response);
    print('[DEBUG] Ответ создания чата: $result');
    return Chat.fromJson(result);
  }

  // Обновить чат
  static Future<Chat> updateChat(int chatId, Map<String, dynamic> data) async {
    final headers = await _getHeaders();
    final url = Uri.parse(ApiConfig.updateChat(chatId));

    ApiConfig.logRequest('PUT', url.toString(), body: data);
    
    final response = await http.put(
      url,
      headers: headers,
      body: json.encode(data),
    );

    final result = await _handleResponse(response);
    return Chat.fromJson(result);
  }

  // Удалить чат
  static Future<bool> deleteChat(int chatId) async {
    final headers = await _getHeaders();
    final url = Uri.parse(ApiConfig.deleteChat(chatId));

    ApiConfig.logRequest('DELETE', url.toString());
    
    final response = await http.delete(url, headers: headers);
    final result = await _handleResponse(response);
    
    return result['success'] == true || result['deleted'] == true;
  }

  // === СООБЩЕНИЯ ===

  // Получить сообщения чата
  static Future<List<Message>> getMessages(int chatId) async {
    final headers = await _getHeaders();
    // Используем правильный endpoint с query параметром
    final url = Uri.parse(ApiConfig.chatMessages(chatId));

    ApiConfig.logRequest('GET', url.toString());
    
    final response = await http.get(url, headers: headers);
    final result = await _handleResponse(response);
    
    final List messagesData = result is List ? result : (result['data'] ?? result['messages'] ?? []);
    return messagesData.map((json) => Message.fromJson(json)).toList();
  }

  // Отправить текстовое сообщение
  static Future<Message> sendTextMessage(int chatId, String text) async {
    final headers = await _getHeaders();
    final url = Uri.parse(ApiConfig.sendMessageEndpoint);

    final body = {
      'chat_id': chatId,
      'text': text,
      'type': 'text',
    };

    ApiConfig.logRequest('POST', url.toString(), body: body);
    
    final response = await http.post(
      url,
      headers: headers,
      body: json.encode(body),
    );

    final result = await _handleResponse(response);
    return Message.fromJson(result);
  }

  // Отправить сообщение с файлом/изображением (мультипарт)
  static Future<Message> sendMessageWithFile(
      int chatId, String text, File file, String type) async {
    final token = await getToken();
    if (token == null) throw Exception('Требуется авторизация');

    final url = Uri.parse(ApiConfig.sendMessageEndpoint);
    
    try {
      var request = http.MultipartRequest('POST', url);
      request.headers['Authorization'] = 'Bearer $token';
      
      // Основные поля
      request.fields['chat_id'] = chatId.toString();
      request.fields['text'] = text;
      request.fields['type'] = type; // 'image' или 'file'
      
      // Файл
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

  // Удалить сообщение
  static Future<bool> deleteMessage(int messageId) async {
    final headers = await _getHeaders();
    final url = Uri.parse(ApiConfig.deleteMessage(messageId));

    ApiConfig.logRequest('DELETE', url.toString());
    
    final response = await http.delete(url, headers: headers);
    final result = await _handleResponse(response);
    
    return result['success'] == true || result['deleted'] == true;
  }

  // === ЗАГРУЗКА ФАЙЛОВ ===

  // Загрузить аватар
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