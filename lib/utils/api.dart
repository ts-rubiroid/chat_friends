// lib/utils/api.dart
// API Configuration for WordPress Chat Backend (Production Ready)

class ApiConfig {
  // Base URL - основной домен WordPress сайта
  static const String baseUrl = 'https://chat.remont-gazon.ru';
  
  // WordPress uploads directory - для аватаров и изображений
  static const String uploadsUrl = '$baseUrl/wp-content/uploads';
  
  // Full API base URL
  static const String apiBase = '$baseUrl/wp-json';

  // === API NAMESPACES (согласно документации) ===
  static const String authApi = '$apiBase/chat/v1/auth';
  static const String chatApi = '$apiBase/chat-api/v1';

  // === АУТЕНТИФИКАЦИЯ (authApi) ===
  static const String loginEndpoint = '$authApi/login';
  static const String registerEndpoint = '$authApi/register';

  // === ОСНОВНЫЕ ENDPOINTS (chatApi) ===
  
  // Пользователи
  static const String meEndpoint = '$chatApi/me';
  static const String usersEndpoint = '$chatApi/users';
  static const String updateProfileEndpoint = '$chatApi/users/update';
  
  // Чаты
  static const String chatsEndpoint = '$chatApi/chats';
  static const String createChatEndpoint = '$chatApi/chats/create';
  static String chatAddMembersEndpoint(int chatId) => '$chatApi/chats/$chatId/add-members';
  static String chatUpdateEndpoint(int chatId) => '$chatApi/chats/$chatId/update';
  
  // Сообщения
  static const String messagesEndpoint = '$chatApi/messages';
  static const String sendMessageEndpoint = '$chatApi/messages/send';
  
  // Загрузка файлов
  static const String uploadAvatarEndpoint = '$chatApi/upload/avatar';
  
  // === ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ДЛЯ ПОСТРОЕНИЯ URL ===
  static String chatDetailEndpoint(int chatId) => '$chatsEndpoint/$chatId';
  static String getMessagesUrl(int chatId, {int page = 1, int perPage = 50}) => 
      '$messagesEndpoint?chat_id=$chatId&page=$page&per_page=$perPage';
  
  // Удаление (если реализовано на бэкенде)
  static String deleteChatEndpoint(int chatId) => '$chatApi/chats/$chatId/delete';
  static String deleteMessageEndpoint(int messageId) => '$chatApi/messages/$messageId/delete';
  
  // === ДОПОЛНИТЕЛЬНЫЕ УТИЛИТЫ ===
  
  // Полный URL для аватара
  static String getAvatarUrl(String? avatarPath) {
    if (avatarPath == null || avatarPath.isEmpty || avatarPath == 'false' || avatarPath == 'null') {
      return 'https://ui-avatars.com/api/?name=User&background=random';
    }
    
    // Если путь уже полный URL
    if (avatarPath.startsWith('http')) {
      return avatarPath;
    }
    
    // Если путь относительный
    return '$uploadsUrl/$avatarPath';
  }

  // Полный URL для файлов сообщений (изображения, документы)
  static String getFileUrl(String? filePath) {
    if (filePath == null || filePath.isEmpty) return '';
    
    if (filePath.startsWith('http')) {
      return filePath;
    }
    
    return '$uploadsUrl/$filePath';
  }
  
  // Формирование заголовков с токеном
  static Map<String, String> getHeaders({String? token}) {
    Map<String, String> headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    
    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }
    
    return headers;
  }
  
  // Проверка успешности статуса
  static bool isSuccess(int statusCode) => statusCode >= 200 && statusCode < 300;
  
  // Обработка ошибок API
  static String getErrorMessage(dynamic error) {
    if (error is Map<String, dynamic>) {
      return error['message'] ?? error['error'] ?? 'Произошла ошибка';
    } else if (error is String) {
      return error;
    } else {
      return 'Непредвиденная ошибка';
    }
  }
  
  // Логирование запросов (только для дебага)
  static void logRequest(String method, String url, {dynamic body}) {
    print('[$method] $url');
    if (body != null) {
      print('Body: $body');
    }
  }
}