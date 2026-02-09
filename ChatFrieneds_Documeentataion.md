📱 Chat Friends App - Обновленная документация
🏗️ Структура проекта (полная)
text
lib/
├── main.dart
├── utils/api.dart
├── services/api_service.dart
├── models/                  # User, Chat, Message
├── screens/
│   ├── login_screen.dart   ✅
│   ├── chats_screen.dart   ✅ (pull_to_refresh + сортировка)
│   ├── chat_screen.dart    ✅ (polling + файлы)
│   ├── create_chat_screen.dart  # Предполагается
│   └── profile_screen.dart      # Предполагается
└── widgets/
    └── chat_list_item.dart ✅ (используется в chats_screen)
🔧 Ключевые особенности
1. Чат-лист (chats_screen.dart)
Библиотека: pull_to_refresh для обновления

Сортировка:

Сначала чаты с непрочитанными
По времени последнего сообщения/создания
Навигация:

Тап по чату → ChatScreen

FAB → CreateChatScreen

Иконка профиля → ProfileScreen

2. Модели данных
dart
User:      id, phone, first_name, last_name, nickname, avatar
Chat:      id, name, is_group, members[], unread_count, last_message
Message:   id, chat_id, sender_id, text, image, file, created_at
3. Аутентификация
Token формат: user_{id}_{hash}

Хранение: SharedPreferences через ApiService.saveToken()

Заголовки: Authorization: Bearer $token

4. WP API Конфигурация
text
Base: https://chat.remont-gazon.ru/wp-json
Auth: /chat/v1/auth/login
Chats: /chat-api/v1/chats
Messages: /chat-api/v1/messages?chat_id=XXX
Upload: /chat-api/v1/upload       # Для загрузки файлов
⚠️ Проблемы для интеграции
1. Загрузка файлов (СРОЧНО)
dart
// СЕЙЧАС (не работает с WP):
ApiService.sendMessageWithFile() → multipart на /messages/send

// НУЖНО (WP схема):
1. ApiService.uploadFile() → POST /upload → получить URL
2. ApiService.sendTextMessage(..., file_url: полученный_URL)
2. Отсутствуют в ApiService
dart
// Обязательно добавить:
Future<int> getUnreadCount(int? chatId)     // GET /messages/unread-count
Future<void> markMessagesAsRead(int chatId) // POST /messages/mark-read
Future<Chat> addMembers(int chatId, List<int> userIds) // POST /chats/{id}/add-members
3. Поля в моделях
✅ User и Chat модели совместимы с WP

⚠️ Message: Нужно обрабатывать message_id (может прийти вместо id)

4. ChatScreen polling
Сейчас: Каждые 5 секунд

WP: Можно оставить, но лучше добавить WebSocket

🔄 Поток данных
Аутентификация
text
LoginScreen → ApiService.login() → /auth/login
↓
Сохранить токен → SharedPreferences
↓
Авто-добавление в заголовки
Создание чата
text
ChatsScreen FAB → CreateChatScreen → ApiService.createChat()
↓
Возвращает Chat → Открываем ChatScreen
↓
Обновляем список чатов (_loadData)
Отправка сообщения
text
ChatScreen → text/file → 
├── Текст → ApiService.sendTextMessage()
└── Файл → ApiService.uploadFile() → sendTextMessage(file_url: ...)

📊 Тестовые данные WP
dart
phone: '+79161234567'
password: '123456'
token: 'user_259_e7f4d02c3f703f50ca87d790133e04f8'
chat_id: 260
user_id: 259

✅ Что работает сейчас
✅ Структура проекта

✅ Модели данных (совместимы)

✅ Экран входа (логин + обновления)

✅ Список чатов (сортировка + обновление)

✅ Экран чата (сообщения + базовый UI)

✅ Навигация между экранами

