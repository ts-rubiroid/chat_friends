📱 Flutter Integration Guide: Chat Friends App with WordPress Backend
🚀 Основная информация
Base URL: https://chat.remont-gazon.ru/wp-json
Статус API: ✅ Продакшен готов (28.01.2026)

🔐 Аутентификация
1. Вход в систему
dart
Future<Map<String, dynamic>> login(String phone, String password) async {
  final response = await http.post(
    Uri.parse('https://chat.remont-gazon.ru/wp-json/chat/v1/auth/login'),
    headers: {'Content-Type': 'application/json'},
    body: jsonEncode({
      'phone': phone,
      'password': password
    }),
  );
  return jsonDecode(response.body);
}
Ответ: Содержит token для последующих запросов

2. Сохранение токена
dart
class AuthService {
  final FlutterSecureStorage _storage = FlutterSecureStorage();
  
  Future<void> saveToken(String token) async {
    await _storage.write(key: 'auth_token', value: token);
  }
  
  Future<String?> getToken() async {
    return await _storage.read(key: 'auth_token');
  }
}
3. Проверка токена
dart
bool isValidToken(String token) {
  // Формат токена: user_{id}_{hash}
  return token.startsWith('user_') && token.split('_').length == 3;
}
📦 Модели данных
Пользователь (User)
dart
class User {
  final int id;
  final String phone;
  final String firstName;
  final String lastName;
  final String? nickname;
  final String? avatar;
  
  String get fullName => '$firstName $lastName'.trim();
  String get initials => '${firstName.isNotEmpty ? firstName[0] : ''}${lastName.isNotEmpty ? lastName[0] : ''}';
}
Чат (Chat)
dart
class Chat {
  final int id;
  final String name;
  final String? avatar;
  final bool isGroup;
  final int membersCount;
  final DateTime createdAt;
  final Message? lastMessage;
}
Сообщение (Message)
dart
class Message {
  final int id;
  final int chatId;
  final int senderId;
  final String text;
  final String? imageUrl;
  final String? fileUrl;
  final DateTime createdAt;
  final User sender;
}
🔧 API Сервис
Конфигурация
dart
class ApiConstants {
  static const String baseUrl = 'https://chat.remont-gazon.ru/wp-json';
  static const String chatApi = '$baseUrl/chat-api/v1';
  
  static Map<String, String> getHeaders(String token) {
    return {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer $token',
    };
  }
}
Основной сервис
dart
class ApiService {
  String? _token;
  
  Future<void> init() async {
    _token = await AuthService().getToken();
  }
  
  // Получить список чатов
  Future<List<Chat>> getChats() async {
    final response = await http.get(
      Uri.parse('${ApiConstants.chatApi}/chats'),
      headers: ApiConstants.getHeaders(_token!),
    );
    // Парсинг ответа
  }
  
  // Отправить сообщение
  Future<Message> sendMessage(int chatId, String text, {String? imageUrl, String? fileUrl}) async {
    final response = await http.post(
      Uri.parse('${ApiConstants.chatApi}/messages/send'),
      headers: ApiConstants.getHeaders(_token!),
      body: jsonEncode({
        'chat_id': chatId,
        'text': text,
        'image_url': imageUrl,
        'file_url': fileUrl,
      }),
    );
    // Парсинг ответа
  }
  
  // Получить сообщения чата
  Future<List<Message>> getMessages(int chatId, {int page = 1, int perPage = 50}) async {
    final response = await http.get(
      Uri.parse('${ApiConstants.chatApi}/messages?chat_id=$chatId&page=$page&per_page=$perPage'),
      headers: ApiConstants.getHeaders(_token!),
    );
    // Парсинг ответа
  }
  
  // Загрузить файл
  Future<String> uploadFile(File file) async {
    var request = http.MultipartRequest(
      'POST',
      Uri.parse('${ApiConstants.chatApi}/upload'),
    );
    
    request.headers['Authorization'] = 'Bearer $_token';
    request.files.add(await http.MultipartFile.fromPath('file', file.path));
    
    final response = await request.send();
    final responseData = await response.stream.bytesToString();
    final json = jsonDecode(responseData);
    
    return json['file']['url']; // URL загруженного файла
  }
}
📱 Основные экраны приложения
1. Экран входа (LoginScreen)
Поля: телефон, пароль

Кнопка "Войти"

Кнопка "Зарегистрироваться"

Кнопка "Проверить обновление"

2. Список чатов (ChatsScreen)
ListView чатов пользователя

Индикатор непрочитанных сообщений

Поиск чатов

Создание нового чата

3. Экран чата (ChatScreen)
ListView сообщений (обратный порядок)

Поле ввода текста

Кнопка отправки

Кнопка прикрепления файлов/изображений

Индикатор "прочитано"

4. Профиль (ProfileScreen)
Аватар пользователя

Имя, фамилия, никнейм

Кнопка выхода

📎 Загрузка файлов
Поддерживаемые типы:
Изображения: JPEG, PNG, GIF

Документы: PDF, TXT

Макс. размер: 10MB

Пример использования:
dart
// Выбор файла
final file = await ImagePicker().pickImage(source: ImageSource.gallery);

// Загрузка на сервер
final fileUrl = await ApiService().uploadFile(File(file.path));

// Отправка сообщения с файлом
await ApiService().sendMessage(chatId, 'Смотри файл', fileUrl: fileUrl);
🔄 Механизм обновлений
Конфигурация на сервере:
text
https://chat.remont-gazon.ru/update/
├── update.json          // Метаданные версии
└── app-release.apk     // APK файл
update.json:
json
{
  "version": "1.1.0",
  "build_number": 5,
  "apk_url": "https://chat.remont-gazon.ru/update/app-release.apk",
  "release_notes": "Исправлены ошибки, улучшена производительность",
  "force_update": false
}
Проверка обновлений в приложении:
dart
Future<void> checkForUpdates() async {
  final response = await http.get(
    Uri.parse('https://chat.remont-gazon.ru/update/update.json'),
  );
  
  final updateInfo = jsonDecode(response.body);
  final currentVersion = packageInfo.version;
  
  if (isNewVersion(updateInfo['version'], currentVersion)) {
    // Показать диалог обновления
    // Начать загрузку APK
    // После загрузки - установка
  }
}
🛠️ Важные нюансы интеграции
1. Авторизация в каждом запросе
dart
// Все запросы к API (кроме /auth/*) требуют заголовка:
'Authorization': 'Bearer user_259_e7f4d02c3f703f50ca87d790133e04f8'
2. Пагинация сообщений
По умолчанию: 50 сообщений на странице

Максимум: 100 сообщений

Реализовать бесконечный скролл

3. Состояние "прочитано"
dart
// Отметить все сообщения в чате как прочитанные
await http.post(
  Uri.parse('${ApiConstants.chatApi}/messages/mark-read'),
  headers: ApiConstants.getHeaders(_token!),
  body: jsonEncode({'chat_id': chatId}),
);
4. Создание чатов
Личный чат: {"user_id": 259, "is_group": false}

Групповой чат: требует минимум 1 участника кроме создателя

🐛 Обработка ошибок
Основные HTTP ошибки:
401: Неверный токен → перелогин

403: Нет доступа → показать сообщение пользователю

404: Не найден ресурс → проверить ID

500: Ошибка сервера → записать в лог

Пример обработки:
dart
try {
  final response = await ApiService().getChats();
  // Обработка успешного ответа
} catch (e) {
  if (e is http.Response && e.statusCode == 401) {
    // Токен устарел - показать экран входа
    Navigator.pushReplacement(context, LoginScreen());
  } else {
    // Показать пользователю сообщение об ошибке
    showErrorDialog(context, e.toString());
  }
}
📊 Мониторинг и отладка
Логирование запросов:
dart
class LoggingInterceptor extends http.BaseClient {
  final http.Client _innerClient = http.Client();
  
  @override
  Future<http.StreamedResponse> send(http.BaseRequest request) async {
    print('[${request.method}] ${request.url}');
    print('Headers: ${request.headers}');
    
    final response = await _innerClient.send(request);
    
    print('[${response.statusCode}] ${response.reasonPhrase}');
    return response;
  }
}
Тестовые данные для разработки:
dart
const testCredentials = {
  'phone': '+79161234567',
  'password': '123456',
  'token': 'user_259_e7f4d02c3f703f50ca87d790133e04f8',
  'user_id': 259,
  'chat_id': 260,
};
✅ Чек-лист интеграции
Обязательные шаги:
Добавить зависимость http: ^1.1.0 и flutter_secure_storage: ^9.0.0

Создать модели User, Chat, Message

Реализовать ApiService с методами авторизации

Настроить хранение токена в FlutterSecureStorage

Реализовать обработку ошибок авторизации (401)

Добавить заголовок Authorization во все запросы

Реализовать загрузку файлов через multipart/form-data

Настроить проверку обновлений приложения

Опциональные улучшения:
Кэширование данных (чаты, сообщения)

WebSocket для реального времени

Push-уведомления через Firebase

Локальная база данных для офлайн-режима

📞 Контакты для проблем с API
Base URL: https://chat.remont-gazon.ru/wp-json

Тестировщик API: https://chat.remont-gazon.ru/chat-test.html

Статус: Все endpoints протестированы и работают

Примечание: Эта документация содержит только информацию, необходимую для интеграции Flutter приложения с существующим WordPress бэкендом. Полная документация API доступна в основном файле api-documentation-v3.md.