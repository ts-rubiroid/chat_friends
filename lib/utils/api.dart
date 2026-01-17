// API Configuration for WordPress Chat Backend
class ApiConfig {
  // Base URL - основной домен WordPress сайта
  static const String baseUrl = 'https://chat.remont-gazon.ru';
  
  // WordPress uploads directory - для аватаров и изображений
  static const String uploadsUrl = '$baseUrl/wp-content/uploads';
  
  // API Version prefix
  static const String apiVersion = 'chat-api/v1';
  
  // Full API base URL
  static const String apiBaseUrl = '$baseUrl/wp-json/$apiVersion';
  
  // Authentication endpoints
  static const String loginEndpoint = '$baseUrl/wp-json/chat/v1/auth/login';
  static const String registerEndpoint = '$baseUrl/wp-json/chat/v1/auth/register';
  static const String logoutEndpoint = '$baseUrl/wp-json/chat/v1/auth/logout';
  
  // User endpoints
  static const String meEndpoint = '$apiBaseUrl/me';
  static const String usersEndpoint = '$apiBaseUrl/users';
  static const String updateProfileEndpoint = '$apiBaseUrl/users/update';
  
  // Chat endpoints
  static const String chatsEndpoint = '$apiBaseUrl/chats';
  static const String createChatEndpoint = '$apiBaseUrl/chats/create';
  static const String chatDetailEndpoint = '$apiBaseUrl/chats'; // + /{id}
  static const String updateChatEndpoint = '$apiBaseUrl/chats/update'; // + /{id}
  static const String deleteChatEndpoint = '$apiBaseUrl/chats/delete'; // + /{id}
  
  // Message endpoints
  static const String messagesEndpoint = '$apiBaseUrl/messages';
  static const String sendMessageEndpoint = '$apiBaseUrl/messages/send';
  static const String chatMessagesEndpoint = '$apiBaseUrl/messages/chat'; // + /{chatId}
  static const String deleteMessageEndpoint = '$apiBaseUrl/messages/delete'; // + /{id}
  
  // File upload endpoints (если есть)
  static const String uploadAvatarEndpoint = '$apiBaseUrl/upload/avatar';
  static const String uploadChatImageEndpoint = '$apiBaseUrl/upload/chat-image';
  
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