// Константы для UnifiedPush (ntfy).
// Замените на ваш реальный URL push-сервера (например https://push.ваш-домен.ru).

class PushConstants {
  PushConstants._();

  /// Базовый URL ntfy-сервера (без завершающего слэша).
  /// Должен быть доступен по HTTPS с устройства пользователя.
  static const String ntfyBaseUrl = 'https://chatnews.remont-gazon.ru';

  /// Префикс топика для пользователя: подписка на user_{userId}.
  /// userId — ID пользователя (chat_user post ID в WordPress).
  static String topicForUser(int userId) => 'user_$userId';
}
