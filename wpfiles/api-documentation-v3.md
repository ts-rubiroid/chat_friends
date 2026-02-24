# 📚 ПОЛНАЯ ДОКУМЕНТАЦИЯ Chat API v3.0

## 🎯 Статус: **ПРОДАКШЕН ГОТОВ**

**Последнее обновление:** 17 февраля 2026 года  
**Версия:** 3.0  
**Статус:** ✅ Все endpoints работают

---

## 📋 ОГЛАВЛЕНИЕ
- [🚀 Быстрый старт](#быстрый-старт)
- [🔐 Аутентификация](#аутентификация)
- [💬 Чаты](#чаты)
- [📨 Сообщения](#сообщения)
- [👥 Пользователи](#пользователи)
- [📎 Файлы](#файлы)
- [🛠️ Flutter Integration](#flutter-integration)
- [🧪 Тестирование](#тестирование)
- [👨‍💼 Администрирование](#администрирование)
- [🔧 Поддержка](#поддержка)

---

## 🚀 БЫСТРЫЙ СТАРТ

### 🔗 Основные ссылки
- **Главная страница:** https://chat.remont-gazon.ru/
- **Тестировщик API:** https://chat.remont-gazon.ru/chat-test.html
- **Base URL:** `https://chat.remont-gazon.ru/wp-json`

### 📊 Тестовые данные (РЕАЛЬНЫЕ)
```json
{
  "base_url": "https://chat.remont-gazon.ru/wp-json",
  "test_token": "user_259_e7f4d02c3f703f50ca87d790133e04f8",
  "test_user_id": 259,
  "credentials": {
    "phone": "+79161234567",
    "password": "123456"
  },
  "test_chat_id": 260
}
✅ Проверка работы API
bash
# 1. Проверка API (работает)
curl -X GET "https://chat.remont-gazon.ru/wp-json/chat-api/v1/test"

# 2. Вход в систему (работает)
curl -X POST "https://chat.remont-gazon.ru/wp-json/chat/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"phone\":\"+79161234567\",\"password\":\"123456\"}"

# 3. Получить профиль (работает)
curl -X GET "https://chat.remont-gazon.ru/wp-json/chat-api/v1/me" \
  -H "Authorization: Bearer user_259_e7f4d02c3f703f50ca87d790133e04f8"
🔐 АУТЕНТИФИКАЦИЯ
📍 Base URL
text
https://chat.remont-gazon.ru/wp-json
1. 📱 Вход в систему ✅ РАБОТАЕТ
Endpoint: POST /chat/v1/auth/login

Параметры:

json
{
  "phone": "+79161234567",
  "password": "123456"
}
Пример запроса (CURL):

bash
curl -X POST "https://chat.remont-gazon.ru/wp-json/chat/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"phone\":\"+79161234567\",\"password\":\"123456\"}"
Ответ:

json
{
  "success": true,
  "message": "Авторизация успешна",
  "token": "user_259_e7f4d02c3f703f50ca87d790133e04f8",
  "user": {
    "id": 259,
    "phone": "+79161234567",
    "first_name": "Иван",
    "last_name": "Иванов",
    "nickname": "ivan",
    "avatar": "",
    "created_at": "2026-01-28 10:09:17"
  }
}
2. 📝 Регистрация ✅ РАБОТАЕТ
Endpoint: POST /chat/v1/auth/register

Параметры:

json
{
  "phone": "+79161234567",
  "password": "123456",
  "first_name": "Иван",
  "last_name": "Иванов",
  "nickname": "ivan"
}
Пример Flutter:

dart
Future<Map<String, dynamic>> registerUser() async {
  final response = await http.post(
    Uri.parse('${ApiConstants.baseUrl}/chat/v1/auth/register'),
    headers: {'Content-Type': 'application/json'},
    body: jsonEncode({
      'phone': '+79161234567',
      'password': '123456',
      'first_name': 'Иван',
      'last_name': 'Иванов',
      'nickname': 'ivan'
    }),
  );
  return jsonDecode(response.body);
}
3. 🔍 Проверка токена ✅ РАБОТАЕТ
Endpoint: POST /chat/v1/auth/validate

Параметры:

json
{
  "token": "user_259_e7f4d02c3f703f50ca87d790133e04f8"
}
Ответ:

json
{
  "valid": true,
  "user_id": 259,
  "message": "Токен действителен"
}
💬 ЧАТЫ ✅ ВСЕ РАБОТАЮТ
1. 📋 Получить все чаты пользователя
Endpoint: GET /chat-api/v1/chats

Заголовки:

text
Authorization: Bearer user_259_e7f4d02c3f703f50ca87d790133e04f8
Пример запроса:

bash
curl -X GET "https://chat.remont-gazon.ru/wp-json/chat-api/v1/chats" \
  -H "Authorization: Bearer user_259_e7f4d02c3f703f50ca87d790133e04f8"
Ответ:

json
{
  "success": true,
  "chats": [
    {
      "id": 260,
      "name": "Тестовый чат API",
      "avatar": null,
      "is_group": true,
      "members_count": 1,
      "created_at": "2026-01-28 12:30:00",
      "last_message": null
    }
  ],
  "count": 1
}
2. 🔍 Получить информацию о конкретном чате
Endpoint: GET /chat-api/v1/chats/{id}

Параметры:

id - ID чата (обязательно)

Пример:

bash
curl -X GET "https://chat.remont-gazon.ru/wp-json/chat-api/v1/chats/260" \
  -H "Authorization: Bearer user_259_e7f4d02c3f703f50ca87d790133e04f8"
Ответ:

json
{
  "success": true,
  "chat": {
    "id": 260,
    "name": "Тестовый чат API",
    "avatar": null,
    "is_group": true,
    "created_at": "2026-01-28 12:30:00",
    "members_count": 1,
    "members": [
      {
        "id": 259,
        "first_name": "Иван",
        "last_name": "Иванов",
        "avatar": null
      }
    ]
  }
}
3. 🤝 Создать личный чат (1 на 1)
Endpoint: POST /chat-api/v1/chats/create

Параметры:

json
{
  "user_id": 259,
  "is_group": false
}

4. 👥 Создать групповой чат
Endpoint: POST /chat-api/v1/chats/create

Параметры:

json
{
  "is_group": true,
  "name": "Рабочая группа",
  "members": [259],
  "avatar": "https://example.com/avatar.jpg"
}
Требования:

✅ Минимум 1 участник (создатель автоматически добавляется)

✅ Обязательное название чата

✅ avatar - опционально

5. ➕ Добавить участников в групповой чат
Endpoint: POST /chat-api/v1/chats/{id}/add-members

Параметры:

json
{
  "members": [260, 261]
}
6. ➖ Удалить участника из группового чата
Endpoint: POST /chat-api/v1/chats/{id}/remove-member

Параметры:

json
{
  "user_id": 260
}
Особенности:

❌ Нельзя удалить себя

⚠️ Если остаётся 1 участник - рекомендуется удалить чат

7. ✏️ Обновить информацию о чате
Endpoint: POST /chat-api/v1/chats/{id}/update

Параметры (опционально):

json
{
  "name": "Новое название",
  "avatar": "https://new-avatar.jpg"
}

8. 👑 Получить создателя группового чата
Endpoint: GET /chat-api/v1/chats/{id}/creator

Ответ:

json
{
  "success": true,
  "creator": {
    "id": 259,
    "first_name": "Иван",
    "last_name": "Иванов",
    "avatar": null,
    "created_at": "2026-01-28 10:09:17"
  }
}
9. 🗑️ Удалить чат
Endpoint: POST /chat-api/v1/chats/{id}/delete

Внимание: Удаляет чат и все сообщения в нём

📨 СООБЩЕНИЯ ✅ ВСЕ РАБОТАЮТ
1. 📩 Получить сообщения чата
Endpoint: GET /chat-api/v1/messages

Параметры запроса:

text
chat_id=260      # обязательно
page=1           # опционально (по умолчанию: 1)
per_page=50      # опционально (по умолчанию: 50, максимум: 100)
Пример:

bash
curl -X GET "https://chat.remont-gazon.ru/wp-json/chat-api/v1/messages?chat_id=260&per_page=10" \
  -H "Authorization: Bearer user_259_e7f4d02c3f703f50ca87d790133e04f8"
Ответ:

json
{
  "success": true,
  "messages": [
    {
      "id": 1,
      "chat_id": 260,
      "sender_id": 259,
      "text": "Привет!",
      "image": null,
      "file": null,
      "created_at": "2026-01-28 12:35:00",
      "sender": {
        "id": 259,
        "first_name": "Иван",
        "avatar": null
      }
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 1,
    "total_pages": 1
  }
}
2. 📤 Отправить сообщение
Endpoint: POST /chat-api/v1/messages/send

Параметры:

json
{
  "chat_id": 260,
  "text": "Привет! Как дела?",
  "image_url": "https://example.com/image.jpg",
  "file_url": "https://example.com/document.pdf"
}
Пример Flutter:

dart
Future<Map<String, dynamic>> sendMessage(int chatId, String text) async {
  final token = await storage.read(key: 'auth_token');
  final response = await http.post(
    Uri.parse('${ApiConstants.baseUrl}/chat-api/v1/messages/send'),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json'
    },
    body: jsonEncode({
      'chat_id': chatId,
      'text': text
    }),
  );
  return jsonDecode(response.body);
}
3. ✅ Отметить сообщения как прочитанные
Endpoint: POST /chat-api/v1/messages/mark-read

Варианты использования:

а) Все сообщения в чате:

json
{
  "chat_id": 260
}
б) Конкретные сообщения:

json
{
  "chat_id": 260,
  "message_ids": [1, 2, 3]
}
4. 🔢 Получить количество непрочитанных сообщений
Endpoint: GET /chat-api/v1/messages/unread-count

Параметры:

text
chat_id=260  # опционально, если не указан - для всех чатов
Ответ для конкретного чата:

json
{
  "success": true,
  "unread_count": 5,
  "chat_id": 260
}
Ответ для всех чатов:

json
{
  "success": true,
  "total_unread": 15,
  "chat_unread": {
    "260": {
      "chat_id": 260,
      "chat_name": "Тестовый чат",
      "unread_count": 5
    }
  }
}
5. 🔍 Поиск сообщений
Endpoint: GET /chat-api/v1/messages/search

Параметры:

text
q=привет                    # обязательно
chat_id=260                 # опционально
limit=50                    # опционально (макс. 100)
offset=0                    # опционально
Пример:

bash
curl -X GET "https://chat.remont-gazon.ru/wp-json/chat-api/v1/messages/search?q=привет&chat_id=260" \
  -H "Authorization: Bearer user_259_e7f4d02c3f703f50ca87d790133e04f8"
6. 🗑️ Удалить сообщение

Основной endpoint:

Endpoint: POST /chat-api/v1/messages/{id}/delete

Альтернативный REST-вариант:

Endpoint: DELETE /chat-api/v1/messages/{id}

Параметры:

- `id` — ID сообщения (обязательно, указывается в URL)

Правила доступа:

- Сообщение может удалить **только отправитель** или **master-пользователь** (токен вида `master_...`).
- Пользователь должен быть участником чата, к которому относится сообщение.

Поведение:

- При успешном удалении сообщение перемещается в корзину WordPress (`wp_trash_post`), и больше не попадает в выборку `/messages`.

Пример запроса (POST-вариант):

```bash
curl -X POST "https://chat.remont-gazon.ru/wp-json/chat-api/v1/messages/888/delete" \
  -H "Authorization: Bearer user_259_e7f4d02c3f703f50ca87d790133e04f8"
```

Ответ:

```json
{
  "success": true,
  "message": "Сообщение удалено",
  "message_id": 888,
  "chat_id": 260
}
```
👥 ПОЛЬЗОВАТЕЛИ ✅ РАБОТАЕТ
1. 👨‍👩‍👧‍👦 Получить всех пользователей
Endpoint: GET /chat-api/v1/users

Ответ не включает: Текущего пользователя

Пример ответа:

json
{
  "success": true,
  "users": [
    {
      "id": 259,
      "phone": "+79161234567",
      "first_name": "Иван",
      "last_name": "Иванов",
      "middle_name": "Иванович",
      "nickname": "ivan",
      "position": "Менеджер",
      "avatar": "https://chat.remont-gazon.ru/wp-content/uploads/2026/01/avatar.jpg",
      "created_at": "2026-01-28 10:09:17"
    }
  ],
  "count": 1
}
2. 👤 Получить текущего пользователя
Endpoint: GET /chat-api/v1/me

Пример ответа:

```json
{
  "success": true,
  "user": {
    "id": 259,
    "phone": "+79161234567",
    "first_name": "Иван",
    "last_name": "Иванов",
    "middle_name": "Иванович",
    "nickname": "ivan",
    "position": "Менеджер",
    "avatar": "https://chat.remont-gazon.ru/wp-content/uploads/2026/01/avatar.jpg",
    "created_at": "2026-01-28 10:09:17"
  }
}
```

Обновлённая Flutter-модель пользователя (упрощённый пример):

```dart
class User {
  final int id;
  final String phone;
  final String firstName;
  final String lastName;
  final String? middleName;
  final String? nickname;
  final String? position;
  final String? avatar;
  final String createdAt;
  
  User({
    required this.id,
    required this.phone,
    required this.firstName,
    required this.lastName,
    this.middleName,
    this.nickname,
    this.position,
    this.avatar,
    required this.createdAt,
  });
  
  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      phone: json['phone'] ?? '',
      firstName: json['first_name'] ?? '',
      lastName: json['last_name'] ?? '',
      middleName: json['middle_name'],
      nickname: json['nickname'],
      position: json['position'],
      avatar: json['avatar'],
      createdAt: json['created_at'] ?? '',
    );
  }
}
```

3. ✏️ Обновить профиль пользователя

Endpoint: POST /chat-api/v1/users/update

Назначение: обновление профиля текущего пользователя (или любого `chat_user` для master-пользователя).

Правила:

- Обычный пользователь может изменять **только свой** профиль.
- Обычный пользователь **не может** менять номер телефона.
- Master-пользователь (`master_...`) может изменять профиль любого `chat_user` и номер телефона (с проверкой уникальности).

Параметры (JSON-тело, все поля опциональны):

```json
{
  "first_name": "Иван",
  "last_name": "Иванов",
  "middle_name": "Иванович",
  "nickname": "ivan",
  "position": "Менеджер",
  "avatar": "https://chat.remont-gazon.ru/wp-content/uploads/2026/01/avatar.jpg"
}
```

Дополнительно для master-пользователя:

```json
{
  "user_id": 259,
  "phone": "+79161234567",
  "first_name": "Иван",
  "last_name": "Иванов",
  "position": "Руководитель отдела"
}
```

Пример ответа:

```json
{
  "success": true,
  "message": "Профиль обновлён",
  "user": {
    "id": 259,
    "phone": "+79161234567",
    "first_name": "Иван",
    "last_name": "Иванов",
    "middle_name": "Иванович",
    "nickname": "ivan",
    "position": "Руководитель отдела",
    "avatar": "https://chat.remонт-gazon.ru/wp-content/uploads/2026/01/avatar.jpg",
    "created_at": "2026-01-28 10:09:17"
  }
}
```

4. 🗑️ Удалить профиль пользователя

Endpoint: POST /chat-api/v1/users/delete

Назначение: перемещение `chat_user` в корзину WordPress (soft-delete профиля).

Правила:

- Обычный пользователь может удалить **только свой** профиль.
- Master-пользователь может удалить любой `chat_user` по `user_id`.
- Master-пользователь (виртуальный, `id = 999`) не может быть удалён.

Параметры:

- Без параметров — удалить профиль текущего пользователя:

```json
{}
```

- Для master-пользователя:

```json
{
  "user_id": 259
}
```

Ответ:

```json
{
  "success": true,
  "message": "Профиль перемещён в корзину",
  "user_id": 259
}
```
📎 ФАЙЛЫ ✅ РАБОТАЕТ
1. 📤 Загрузить файл
Endpoint: POST /chat-api/v1/upload

Требования:

✅ Метод: POST с multipart/form-data

✅ Поле: file

✅ Максимальный размер: 150MB

✅ Разрешенные типы: image/jpeg, image/png, image/gif, application/pdf, text/plain, application/vnd.android.package-archive

Пример Flutter:

dart
Future<Map<String, dynamic>> uploadFile(File file) async {
  final token = await storage.read(key: 'auth_token');
  
  var request = http.MultipartRequest(
    'POST',
    Uri.parse('${ApiConstants.baseUrl}/chat-api/v1/upload'),
  );
  
  request.headers['Authorization'] = 'Bearer $token';
  request.files.add(await http.MultipartFile.fromPath('file', file.path));
  
  final response = await request.send();
  final responseData = await response.stream.bytesToString();
  
  return jsonDecode(responseData);
}
Ответ:

json
{
  "success": true,
  "message": "Файл успешно загружен",
  "file": {
    "id": 45,
    "name": "document.pdf",
    "type": "application/pdf",
    "size": 2048576,
    "url": "https://chat.remont-gazon.ru/wp-content/uploads/2026/01/document.pdf",
    "uploaded_at": "2026-01-28 12:30:00"
  }
}
2. 📎 Использование загруженных файлов в сообщениях
json
{
  "chat_id": 260,
  "text": "Смотри документ",
  "file_url": "https://chat.remont-gazon.ru/wp-content/uploads/2026/01/document.pdf"
}
🛠️ FLUTTER INTEGRATION
📁 Структура проекта
text
lib/
├── main.dart
├── utils/
│   └── constants.dart      # Константы API
├── models/
│   ├── user.dart          # Модель пользователя
│   ├── chat.dart          # Модель чата
│   └── message.dart       # Модель сообщения
├── services/
│   └── api_service.dart   # Основной класс API
└── screens/
    ├── login_screen.dart
    ├── chats_screen.dart
    ├── chat_screen.dart
    └── profile_screen.dart
🧩 Ключевые пакеты
yaml
dependencies:
  http: ^1.1.0
  flutter_secure_storage: ^9.0.0
  image_picker: ^1.0.4
  cached_network_image: ^3.3.0
  provider: ^6.0.5
🔧 Константы API
Файл: lib/utils/constants.dart

dart
class ApiConstants {
  static const String baseUrl = 'https://chat.remont-gazon.ru/wp-json';
  static const String chatApi = '$baseUrl/chat-api/v1';
  static const String authApi = '$baseUrl/chat/v1';
  
  // Headers
  static Map<String, String> getHeaders(String? token) {
    final headers = <String, String>{
      'Content-Type': 'application/json',
    };
    
    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }
    
    return headers;
  }
}
💾 Хранение токена
dart
class AuthService {
  final FlutterSecureStorage _storage = FlutterSecureStorage();
  
  Future<void> saveToken(String token) async {
    await _storage.write(key: 'auth_token', value: token);
  }
  
  Future<String?> getToken() async {
    return await _storage.read(key: 'auth_token');
  }
  
  Future<void> clearToken() async {
    await _storage.delete(key: 'auth_token');
  }
}
🚀 Инициализация приложения
dart
void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Инициализация API сервиса
  await ApiService().init();
  
  runApp(MyApp());
}
🧪 ТЕСТИРОВАНИЕ
🌐 Веб-тестировщик
Доступно два варианта:

Главная страница: https://chat.remont-gazon.ru/

Автоматический редирект на тестер

Информация о системе

Проверка доступности API

Прямой доступ: https://chat.remont-gazon.ru/chat-test.html

Полный набор endpoints

Визуальное отображение ответов

Сохранение токена в LocalStorage

📟 CURL команды для проверки
bash
# 1. Проверка API (работает)
curl -X GET "https://chat.remont-gazon.ru/wp-json/chat-api/v1/test"

# 2. Вход в систему (работает)
curl -X POST "https://chat.remont-gazon.ru/wp-json/chat/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"phone\":\"+79161234567\",\"password\":\"123456\"}"

# 3. Получить чаты (работает)
curl -X GET "https://chat.remont-gazon.ru/wp-json/chat-api/v1/chats" \
  -H "Authorization: Bearer user_259_e7f4d02c3f703f50ca87d790133e04f8"

# 4. Отправить сообщение (работает)
curl -X POST "https://chat.remont-gazon.ru/wp-json/chat-api/v1/messages/send" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer user_259_e7f4d02c3f703f50ca87d790133e04f8" \
  -d "{\"chat_id\":260,\"text\":\"Тестовое сообщение\"}"

# 5. Получить пользователей (работает)
curl -X GET "https://chat.remont-gazon.ru/wp-json/chat-api/v1/users" \
  -H "Authorization: Bearer user_259_e7f4d02c3f703f50ca87d790133e04f8"
👨‍💼 АДМИНИСТРИРОВАНИЕ
🖥️ WordPress Admin панель
text
1. Пользователи чата:   /wp-admin/edit.php?post_type=chat_user
2. Чаты:               /wp-admin/edit.php?post_type=chat
3. Сообщения:          /wp-admin/edit.php?post_type=chat_message
4. ACF поля:           /wp-admin/edit.php?post_type=acf-field-group
🔄 Автоматическая очистка данных
При удалении пользователя происходит:

✅ Удаление из всех чатов

✅ Помечание сообщений как "от удалённого пользователя"

✅ Удаление чатов с 1 участником

✅ Очистка REST API ответов

💾 Резервное копирование
bash
# База данных через BeGet панель
/wp-admin → Экспорт базы данных

# Файлы
/wp-content/uploads/       # Загруженные файлы
/wp-content/themes/chat-friends/  # Тема с кодом бэкенда
🔧 ПОДДЕРЖКА
🐛 Частые ошибки и решения
Ошибка	Причина	Решение
401 Unauthorized	Неверный/отсутствующий токен	Обновить токен через /auth/login
403 Forbidden	Нет доступа к чату	Проверить membership пользователя
404 Not Found	Неправильный URL	Проверить наличие /wp-json/ в пути
400 Bad Request	Неверные параметры запроса	Проверить обязательные поля
500 Internal Error	Ошибка сервера	Проверить логи WordPress
📝 Логирование
php
// Включить debug режим в wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Логи будут в: /wp-content/debug.log
📊 Мониторинг
bash
# Проверка доступности API
curl -I "https://chat.remont-gazon.ru/wp-json/chat-api/v1/test"

# Проверка токена
curl -X POST "https://chat.remont-gazon.ru/wp-json/chat/v1/auth/validate" \
  -H "Content-Type: application/json" \
  -d "{\"token\":\"user_259_e7f4d02c3f703f50ca87d790133e04f8\"}"
✅ ЧЕК-ЛИСТ РАЗВЁРТЫВАНИЯ
Для Flutter разработчика ✅ ГОТОВО
Base URL: https://chat.remont-gazon.ru/wp-json

Токен получен и сохранён

Модели Chat, User, Message созданы

ApiService инициализирован

Обработка ошибок настроена

Загрузка файлов реализована

Отметка прочитанных сообщений работает

Для Backend разработчика ✅ ГОТОВО
WordPress установлен и настроен

Дочерняя тема активирована

Custom Post Types зарегистрированы

ACF поля созданы

Все endpoints работают

Безопасность настроена

Резервные копии настроены

Для тестировщика ✅ ГОТОВО
Все endpoints протестированы через HTML тестер

Аутентификация работает

Чаты создаются/удаляются корректно

Сообщения отправляются/получаются

Файлы загружаются

Ошибки обрабатываются корректно

🎉 ЗАВЕРШЕНИЕ ПРОЕКТА
🏆 Достижения
✅ Полностью рабочий бэкенд на WordPress

✅ 17 рабочих API endpoints

✅ Полная документация с реальными примерами

✅ Веб-тестировщик для проверки

✅ Flutter интеграция готова

✅ Продакшен-готовое решение


**Обновление приложения "Чат Друзей"

Система позволяет пользователям обновлять мобильное приложение без использования Google Play Store. Обновления загружаются напрямую с сервера разработчика.

Как работает
Для пользователя:
Проверка обновлений

На экране входа нажмите кнопку "Проверить обновление"

Приложение сравнит текущую версию с доступной на сервере

Установка обновления

Если есть новая версия → начнется автоматическая загрузка

После загрузки появится синий блок "Обновление скачано!"

Нажмите на этот блок для запуска установки

Разрешите установку из неизвестных источников (один раз)

Завершение

Следуйте стандартным шагам установки Android

После установки приложение запустится автоматически

Для разработчика:
Подготовка обновления

Соберите новый APK: flutter build apk

Загрузите app-release.apk на сервер в папку /update/

Обновите версию в файле update.json

Структура на сервере:

text
https://ваш-сайт.ru/update/
├── update.json         # Информация о версии
└── app-release.apk    # Файл обновления




📈 Следующие шаги
Развернуть Flutter приложение с обновленным ApiService

Протестировать на реальных устройствах

Добавить push-уведомления (Firebase)

Реализовать WebSocket для реального времени

Добавить аудио/видео звонки

📞 Контакты для поддержки
Домен: https://chat.remont-gazon.ru/

API Documentation: https://chat.remont-gazon.ru/api-documentation-v3-final.md

API Tester: https://chat.remont-gazon.ru/chat-test.html

Статус: ✅ ГОТОВ К ПРОДАКШЕНУ