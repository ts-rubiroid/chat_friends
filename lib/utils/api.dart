// API Configuration for WordPress Chat Backend
class ApiConfig {
  // Base URL - основной домен WordPress сайта
  static const String baseUrl = 'https://chat.remont-gazon.ru';
  
  // WordPress uploads directory - для аватаров и изображений
  static const String uploadsUrl = '$baseUrl/wp-content/uploads';
  

  // API Version prefix 


  // Full API base URL
  static const String apiBase = '$baseUrl/wp-json';

  static const String authApi = '$apiBase/chat/v1';
  static const String chatApi = '$apiBase/chat-api/v1';


  
  // static const String chatApi = '$apiBase/chat-api/v1';


  
  // Authentication endpoints  

  static const String loginEndpoint = '$authApi/auth/login';
  static const String registerEndpoint = '$authApi/auth/register';
  static const String logoutEndpoint = '$authApi/auth/logout';



  // User endpoints
  static const String meEndpoint = '$chatApi/me';
  static const String usersEndpoint = '$chatApi/users';
  static const String updateProfileEndpoint = '$chatApi/users/update';
  
  // Chat endpoints
  static const String chatsEndpoint = '$chatApi/chats';

  static const String createChatEndpoint = '$chatApi/chats/create';
  
  static const String chatDetailEndpoint = '$chatApi/chats'; // + /{id}
  static const String updateChatEndpoint = '$chatApi/chats/update'; // + /{id}
  static const String deleteChatEndpoint = '$chatApi/chats/delete'; // + /{id}
  
  // Message endpoints
  static const String messagesEndpoint = '$chatApi/messages';
  static const String sendMessageEndpoint = '$chatApi/messages/send';
  static const String chatMessagesEndpoint = '$chatApi/messages/chat'; // + /{chatId}
  static const String deleteMessageEndpoint = '$chatApi/messages/delete'; // + /{id}
  
  // File upload endpoints (если есть)
  static const String uploadAvatarEndpoint = '$chatApi/upload/avatar';
  static const String uploadChatImageEndpoint = '$chatApi/upload/chat-image';
  
  // Helper methods for constructing URLs
  static String chatDetail(int chatId) => '$chatsEndpoint/$chatId';
  static String chatMessages(int chatId) => '$chatMessagesEndpoint/$chatId';
  static String updateChat(int chatId) => '$updateChatEndpoint/$chatId';
  static String deleteChat(int chatId) => '$deleteChatEndpoint/$chatId';
  static String deleteMessage(int messageId) => '$deleteMessageEndpoint/$messageId';
  
  // Helper for avatar URL
  static String getAvatarUrl(String? avatarPath) {
    if (avatarPath == null || avatarPath.isEmpty) {
      return '$uploadsUrl/default-avatar.png'; // Замените на путь к дефолтному аватару
    }
    
    // Если путь уже полный URL
    if (avatarPath.startsWith('http')) {
      return avatarPath;
    }
    
    // Если путь относительный
    return '$uploadsUrl/$avatarPath';
  }
  
  // Helper for chat image URL
  static String getChatImageUrl(String? imagePath) {
    if (imagePath == null || imagePath.isEmpty) {
      return '$uploadsUrl/default-chat-image.png'; // Замените на путь к дефолтному изображению чата
    }
    
    // Если путь уже полный URL
    if (imagePath.startsWith('http')) {
      return imagePath;
    }
    
    // Если путь относительный
    return '$uploadsUrl/$imagePath';
  }
  
  // Headers configuration
  static Map<String, String> getHeaders({String? token}) {
    Map<String, String> headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    
    // Add Authorization header if token is provided
    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }
    
    return headers;
  }
  
  // Handle API errors
  static String getErrorMessage(dynamic error) {
    if (error is Map<String, dynamic>) {
      return error['message'] ?? 'An error occurred';
    } else if (error is String) {
      return error;
    } else {
      return 'An unexpected error occurred';
    }
  }
  
  // Check if response is successful
  static bool isSuccess(int statusCode) {
    return statusCode >= 200 && statusCode < 300;
  }
  
  // Debug helper
  static void logRequest(String method, String url, {dynamic body}) {
    print('[$method] $url');
    if (body != null) {
      print('Body: $body');
    }
  }
}