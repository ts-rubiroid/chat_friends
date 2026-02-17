# Chat Friends — Документация приложения для AI-агентов

**Версия:** 1.0  
**Статус:** MVP готов к расширению  
**Backend:** WordPress + ACF (Advanced Custom Fields)

---

## 1. Назначение документа

Документация описывает мобильное приложение «Чат Друзей» (Flutter) и его интеграцию с WordPress-бэкендом. Цель — чтобы любой AI-агент мог быстро войти в контекст и начать разработку нового функционала.

---

## 2. Краткое описание приложения

Корпоративный мессенджер для закрытого круга пользователей. Реализовано:

- Регистрация и вход по телефону и паролю
- Личные и групповые чаты
- Текстовые сообщения, изображения, файлы (PDF, DOC и др.)
- Просмотр и скачивание файлов, сохранение изображений в галерею
- Отправка и предпросмотр видео и аудио файлов в чате
- Уведомления о новых сообщениях: звук, вибрация, системное уведомление; при свёрнутом/закрытом приложении — фоновая проверка (~15 мин) с минимальным уведомлением «Чат Друзей»; по тапу открывается список чатов

---

## 3. Структура проекта Flutter

```
lib/
├── main.dart                    # Точка входа, роутинг по токену
├── utils/
│   ├── api.dart                 # ApiConfig: baseUrl, endpoints, хелперы URL
│   └── local_unread_helper.dart # Локальный статус непрочитанных сообщений
├── services/
│   ├── api_service.dart         # Все HTTP-запросы к WP API
│   ├── media_storage.dart       # Кэш URL медиа для сообщений (SharedPreferences)
│   ├── download_service.dart    # Скачивание файлов, сохранение в галерею
│   ├── notification_service.dart      # Уведомления: звук, вибрация, показ уведомления, переход в список чатов по тапу
│   ├── background_fetch_service.dart # Фоновая проверка новых сообщений (свёрнуто/закрыто приложение), минимальное уведомление
│   └── background_notification_task.dart # Сохранение lastKnownMessageIds и currentUserId для фоновой проверки
├── models/
│   ├── user.dart
│   ├── chat.dart
│   └── message.dart
├── screens/
│   ├── login_screen.dart        # Вход + проверка обновлений приложения
│   ├── register_screen.dart     # Регистрация + загрузка аватара
│   ├── chats_screen.dart        # Список чатов (личные/групповые вкладки)
│   ├── create_chat_screen.dart  # Создание личного или группового чата
│   ├── chat_screen.dart         # Экран чата (сообщения, вложения)
│   ├── image_viewer_screen.dart # Просмотр изображения на весь экран
│   └── profile_screen.dart      # Профиль текущего пользователя
└── widgets/
    └── chat_list_item.dart      # Элемент списка чатов (аватар, превью, unread)
```

---

## 4. Модели данных

### 4.1 User (`lib/models/user.dart`)

| Поле       | Тип         | Описание                                  |
|------------|-------------|-------------------------------------------|
| id         | int         | ID пользователя (post_id chat_user в WP) |
| phone      | String?     | Телефон                                  |
| firstName  | String?     | Имя                                      |
| lastName   | String?     | Фамилия                                  |
| middleName | String?     | Отчество                                 |
| nickname   | String?     | Никнейм                                  |
| position   | String?     | Должность (позиция/роль в компании)      |
| avatar     | String?     | URL аватара (полный или относительный)   |
| createdAt  | DateTime?   | Дата регистрации                         |

Важно: `avatar` может приходить как полный URL или относительный путь. Метод `avatarUrl` преобразует его в рабочий URL.

### 4.2 Chat (`lib/models/chat.dart`)

| Поле        | Тип          | Описание                                      |
|-------------|--------------|-----------------------------------------------|
| id          | int          | ID чата (post_id chat в WP)                  |
| name        | String       | Название чата                                |
| avatar      | String?      | URL аватара чата (для групповых)             |
| isGroup     | bool         | true = групповой, false = личный             |
| createdAt   | DateTime?    | Дата создания                                |
| userIds     | List<int>?   | ID участников                                |
| members     | List<User>?  | Объекты участников                           |
| lastMessage | Message?     | Последнее сообщение                          |
| unreadCount | int          | Количество непрочитанных (серверное)         |
| creator     | User?        | Создатель чата                               |

### 4.3 Message (`lib/models/message.dart`)

| Поле      | Тип       | Описание                                           |
|-----------|-----------|----------------------------------------------------|
| id        | int       | ID сообщения (в WP может быть message_id)         |
| chatId    | int       | ID чата                                            |
| senderId  | int       | ID отправителя (post_id chat_user)                |
| text      | String?   | Текст сообщения                                    |
| image     | String?   | URL изображения                                    |
| file      | String?   | URL файла (документ, PDF и т.д.)                  |
| type      | String?   | 'text', 'image', 'file'                            |
| fileName  | String?   | Имя файла                                          |
| fileType  | String?   | MIME-тип                                           |
| fileSize  | int?      | Размер в байтах                                    |
| createdAt | DateTime? | Время отправки                                     |

Особенность: WordPress иногда возвращает `message_id` вместо `id`. `Message.fromJson` учитывает оба варианта.

---

## 5. API и интеграция с WordPress

### 5.1 Конфигурация (`lib/utils/api.dart`)

- **baseUrl:** `https://chat.remont-gazon.ru`
- **apiBase:** `$baseUrl/wp-json`
- **uploadsUrl:** `$baseUrl/wp-content/uploads`

### 5.2 Endpoints

| Метод        | Endpoint                                | Описание                                          |
|--------------|-----------------------------------------|---------------------------------------------------|
| POST         | /chat/v1/auth/login                     | Вход (phone, password)                            |
| POST         | /chat/v1/auth/register                  | Регистрация (phone, password, avatar и др.)       |
| GET          | /chat-api/v1/me                         | Текущий пользователь                              |
| GET          | /chat-api/v1/users                      | Список пользователей (без текущего)               |
| POST         | /chat-api/v1/users/update               | Обновление профиля (имя, фамилия, отчество, ник, должность, аватар) |
| POST         | /chat-api/v1/users/delete               | Удаление (soft-delete) профиля пользователя       |
| GET          | /chat-api/v1/chats                      | Список чатов                                      |
| GET          | /chat-api/v1/chats/{id}                 | Детали чата                                       |
| POST         | /chat-api/v1/chats/create               | Создание чата                                     |
| GET          | /chat-api/v1/chats/{id}/creator         | Создатель чата                                    |
| GET          | /chat-api/v1/messages?chat_id=X         | Сообщения чата                                    |
| POST         | /chat-api/v1/messages/send              | Отправка сообщения                                |
| POST         | /chat-api/v1/messages/{id}/delete       | Удаление сообщения (стандартный вариант)          |
| DELETE       | /chat-api/v1/messages/{id}              | Удаление сообщения (альтернативный REST-вариант)  |
| POST         | /chat-api/v1/upload                     | Загрузка файла (multipart/form-data)              |

### 5.3 Аутентификация

- **Формат токена:** `user_{id}_{hash}` (например: `user_259_e7f4d02c3f703f50ca87d790133e04f8`)
- **Хранение:** SharedPreferences (ключ `token`)
- **Заголовок:** `Authorization: Bearer {token}`

### 5.4 Загрузка файлов

1. **Изображения и файлы (включая видео/аудио) в сообщениях:**  
   - `ApiService.uploadFile(File)` → POST `/chat-api/v1/upload` (multipart, поле `file`).  
   - На стороне WordPress в `chat_api_upload` разрешены, как минимум, следующие MIME-типы:  
     - **Изображения:** `image/jpeg`, `image/png`, `image/gif`  
     - **Документы/текст:** `application/pdf`, `text/plain`  
     - **Видео:** `video/mp4`, `video/quicktime`, `video/webm`  
     - **Аудио:** `audio/mpeg`, `audio/mp4`, `audio/aac`, `audio/wav`, `audio/ogg`, `audio/webm`  
   - Успешный ответ: `{ success: true, file: { id, url, name, type, size, uploaded_at } }`.  
   - Затем `ApiService.sendMessageWithFile(chatId, text, file, type)` — отправка с `image_url` (для `type = 'image'`) или `file_url` (для `type = 'file'`).  
   - **Особенность:** видео и аудио в текущей версии всегда отправляются как `type = 'file'`; разграничение типов (`video`/`audio`/`document`) происходит на клиенте по `fileType` (MIME) и/или расширению файла.

2. **Аватар при регистрации:**  
   - `ApiService.uploadAvatar(File)` → тот же `/upload`.  
   - URL аватара передаётся в `register` в поле `avatar`.

### 5.5 Специфика WordPress / ACF

- Пользователи — CPT `chat_user`, поля в ACF (phone, password, avatar, first_name, last_name, middle_name, nickname, position, created_at, user_id).
- Чаты — CPT `chat`, поля: is_group, members, created_at, avatar.
- Сообщения — CPT `chat_message`, поля: chat, sender, text, image, file, created_at.
- Поле `avatar` (тип Image) хранит attachment ID; при приёме URL бэкенд преобразует его в ID через `attachment_url_to_postid()`.

---

## 6. Сервисы

### 6.1 ApiService (`lib/services/api_service.dart`)

Основные методы:

- `register(phone, password, userData)` — регистрация
- `login(phone, password)` — вход
- `getCurrentUser()` — текущий пользователь
- `getAllUsers()` — список пользователей
- `getChats()` — список чатов
- `getChatDetail(chatId)` — детали чата
- `getChatCreator(chatId)` — создатель чата
- `createChat(name, isGroup, {participants})` — создание чата
- `getMessages(chatId)` — сообщения чата
- `sendTextMessage(chatId, text)` — текстовое сообщение
- `sendMessageWithFile(chatId, text, file, type)` — сообщение с файлом (image/file)  
  - для `type = 'image'` сервер ожидает `image_url` и сохраняет значение в поле `image`;  
  - для `type = 'file'` сервер ожидает `file_url` и сохраняет значение в поле `file` (это может быть документ, видео или аудио файл).  
- `uploadFile(file, {fileName})` — загрузка файла (любой поддерживаемый тип: изображение, документ, видео или аудио).  
- `uploadAvatar(imageFile)` — загрузка аватара (при регистрации и при редактировании профиля).
- `updateProfile(data)` — обновление профиля текущего пользователя (имя, фамилия, отчество, никнейм, должность, аватар; телефон менять нельзя).
- `deleteProfile()` — удаление (soft-delete) профиля текущего пользователя, с последующим выходом из приложения.
- `deleteMessage(messageId)` — удаление сообщения текущего пользователя (если он отправитель или master).

### 6.2 MediaStorage (`lib/services/media_storage.dart`)

Хранит в SharedPreferences URL изображений и файлов по `messageId`, если сервер не вернул их в ответе. Используется в `_createMessageWithLocalMedia` при парсинге сообщений.

### 6.3 DownloadService (`lib/services/download_service.dart`)

- `downloadFile(url, fileName, {onProgress})` — скачивание в папку Download
- `downloadImageToGallery(url)` — сохранение изображения в галерею
- `openFile(filePath)` — открытие файла системным приложением

### 6.4 LocalUnreadHelper (`lib/utils/local_unread_helper.dart`)

Локальный учёт непрочитанных сообщений (серверный unread_count ненадёжен):

- `saveChatState(chatId, lastText, messageCount)` — при уходе из чата
- `hasUnreadMessages(chatId, currentText, lastMessageTime, currentMessageCount)` — проверка непрочитанных

### 6.5 NotificationService (`lib/services/notification_service.dart`)

Уведомления о новых сообщениях:

- **Инициализация:** `init(navigatorKey)` в `main()` — создание канала (Android), запрос разрешений, обработка запуска по тапу на уведомление.
- **Показ уведомления:** `showNewMessageNotification(chat, message)` — системное уведомление с заголовком (имя чата), текстом сообщения (или «Фото»/«Видео»/«Аудио»/«Файл»), звуком и вибрацией. Не показывается, если пользователь уже в этом чате (`currentChatId`).
- **Тап по уведомлению:** переход на экран общего списка чатов (`popUntil(isFirst)`). При запуске приложения по уведомлению выставляется `pendingOpenChatsList`, ChatsScreen сбрасывает флаг при отображении.

### 6.6 BackgroundFetchService (`lib/services/background_fetch_service.dart`)

Фоновая проверка новых сообщений при свёрнутом или закрытом приложении:

- **Регистрация:** `initBackgroundFetch()` в `main()` — регистрация headless-задачи для Android.
- **Запуск:** `configureAndStartBackgroundFetch()` вызывается из ChatsScreen после успешной загрузки чатов (интервал ~15 минут, задаётся ОС).
- **Логика:** в headless-задаче и в callback при свёрнутом приложении — чтение токена и `lastKnownMessageIds`/`currentUserId` из SharedPreferences, запрос `ApiService.getChats()`, сравнение `lastMessageId`/`lastMessage.id` с сохранёнными; при новых сообщениях от других пользователей — показ одного минимального уведомления («Чат Друзей» / «Новое сообщение») со звуком и вибрацией, сохранение обновлённых id через `saveLastKnownMessageIds` (background_notification_task).

### 6.7 BackgroundNotificationTask (`lib/services/background_notification_task.dart`)

Сохранение состояния для уведомлений (используется фоновой проверкой):

- `persistNotificationState(lastKnownIds, currentUserId)` — вызывается из ChatsScreen после загрузки данных и после каждого опроса новых сообщений (таймер 5 сек).
- Ключи SharedPreferences: `notify_last_known_ids` (JSON map chatId → lastMessageId), `notify_current_user_id`.

---

## 7. Экраны и навигация

### 7.1 Роутинг

- **main.dart:** При старте проверяется `ApiService.getToken()`. Если токен есть → `ChatsScreen`, иначе → `LoginScreen`.
- **LoginScreen:** Вход, регистрация, проверка обновлений APK.
- **ChatsScreen:** Список чатов (вкладки «Личные» и «Групповые»), FAB → CreateChatScreen, иконка профиля → ProfileScreen.
- **CreateChatScreen:** Выбор типа чата и участников, создание через `ApiService.createChat()`, возврат Chat.
- **ChatScreen:** Сообщения, отправка текста и файлов (включая видео/аудио), polling каждые 5 секунд. При появлении новых сообщений от других пользователей вызывается `NotificationService.showNewMessageNotification`.
- **Уведомления:** При открытом приложении — таймер в ChatsScreen каждые 5 сек вызывает `_checkNewMessages()` (getChats, сравнение lastMessageId), при новых сообщениях не из текущего чата — показ уведомления. При свёрнутом/закрытом приложении — BackgroundFetch ~каждые 15 мин проверяет чаты и показывает минимальное уведомление со звуком и вибрацией. По тапу на любое уведомление открывается экран списка чатов.

### 7.2 Потоки данных

- **Регистрация:** RegisterScreen → uploadAvatar (если выбран) → register(avatar: url) → login → ChatsScreen.
- **Создание чата:** ChatsScreen FAB → CreateChatScreen → createChat → ChatScreen с новым чатом.
- **Отправка файла/медиа:**  
  1. Пользователь в `ChatScreen` открывает меню вложений (кнопка слева от поля ввода) и выбирает:  
     - фото из галереи,  
     - фото с камеры,  
     - видео из галереи,  
     - аудио файл,  
     - произвольный файл.  
  2. Клиент формирует локальный предпросмотр (миниатюра/плитка/аудиоплеер) и ожидает подтверждения отправки.  
  3. При отправке:  
     - вызывается `ApiService.uploadFile(file)` (POST `/chat-api/v1/upload`),  
     - далее `ApiService.sendMessageWithFile(chatId, text, file, type)` (для видео/аудио используется `type = 'file'`).  
  4. Параллельно `MediaStorage.saveMediaForMessage(messageId, ...)` сохраняет локальные ссылки и метаданные, чтобы при последующих запросах сообщений можно было восстановить превью даже при неполном ответе сервера.
 - **Профиль пользователя:**  
   1. Пользователь на `ChatsScreen` нажимает иконку профиля → открывается `ProfileScreen`.  
   2. На экране профиля можно:
      - просмотреть аватар, ФИО, никнейм, должность, телефон, дату регистрации;
      - изменить ФИО (фамилия, имя, отчество), никнейм, должность;
      - выбрать новый аватар из галереи (через `ApiService.uploadAvatar` + `updateProfile`);
      - выйти из аккаунта (кнопка «Выйти»);
      - удалить свой профиль (soft-delete в WordPress, перемещение `chat_user` в корзину через `/chat-api/v1/users/delete`).  
   3. После удаления профиля выполняется `logout`, и пользователь попадает на экран входа.

---

## 8. Зависимости (pubspec.yaml)

- `http` — REST-запросы
- `shared_preferences` — токен, MediaStorage, LocalUnreadHelper
- `image_picker` — выбор изображений (галерея, камера)
- `file_picker` — выбор файлов
- `cached_network_image` — кэширование сетевых изображений
- `pull_to_refresh` — обновление списка чатов
- `package_info_plus` — версия приложения для проверки обновлений
- `path_provider` — директории для файлов
- `permission_handler` — разрешения хранилища
- `photo_view` — масштабирование изображений (ImageViewerScreen)
- `dio` — скачивание файлов (DownloadService)
- `open_filex` — открытие файлов
- `image_gallery_saver_plus` — сохранение изображений в галерею

- `video_player` — воспроизведение видео (экран `VideoViewerScreen`, превью видео-сообщений)
- `just_audio` — воспроизведение аудио (виджет `AudioPlayerBubble` для аудио-сообщений)
- `flutter_local_notifications` — системные уведомления о новых сообщениях (звук, вибрация, канал Android)
- `vibration` — вибрация при новом сообщении
- `background_fetch` — периодическая фоновая проверка новых сообщений при свёрнутом/закрытом приложении (~15 мин)

---

## 9. Особенности реализации

1. **Polling:** ChatScreen обновляет сообщения каждые 5 секунд. WebSocket не используется.
2. **Локальные непрочитанные:** LocalUnreadHelper сравнивает хэш текста и количество сообщений с сохранённым состоянием при выходе из чата.
3. **Медиа в сообщениях:** Если сервер не возвращает image/file в ответе, используется MediaStorage по messageId.
4. **Обновление приложения:** LoginScreen проверяет update.json на сервере и предлагает скачать новый APK.
5. **Уведомления:** При новом сообщении (в приложении — опрос 5 сек; в фоне — background_fetch ~15 мин) показывается системное уведомление со звуком и вибрацией. По тапу открывается список чатов. Состояние для фоновой проверки хранится в SharedPreferences (lastKnownMessageIds, currentUserId).

---

## 10. Планируемые доработки

1. ~~**Отправка аудио и видео** — загрузка через `/upload`, сохранение в полях message (например, audio_url, video_url или обобщённое file с type). Добавить предпросмотр аудио/видео в чате (плеер, превью).~~  
   **РЕАЛИЗОВАНО:**  
   - Загрузка аудио и видео через существующий endpoint `/chat-api/v1/upload` с расширенным списком MIME-типов.  
   - Сохранение ссылок на медиа в полях `image`/`file` (для видео/аудио используется `file` + `fileType`).  
   - В `ChatScreen` добавлено единое меню вложений (фото, камера, видео, аудио, файл) и предпросмотр выбранного файла перед отправкой.  
   - Отправленные изображения получают полноэкранный просмотр (`ImageViewerScreen`), видео — экран просмотра (`VideoViewerScreen`), аудио — встроенный плеер (`AudioPlayerBubble`).  
2. **Внешний вид UI** — оформление интерфейса: темы, цветовые схемы, отступы, типографика, анимации.

**Реализовано (уведомления):**
- Локальные уведомления о новых сообщениях (звук, вибрация, системное уведомление).
- При свёрнутом/закрытом приложении — фоновая проверка через `background_fetch` (~15 мин), при новых сообщениях — минимальное уведомление «Чат Друзей» / «Новое сообщение» со звуком и вибрацией.
- По тапу на уведомление открывается экран общего списка чатов.
- Бейдж на иконке приложения не используется (из-за несовместимости плагина с текущим AGP).

---

## 11. Справочная информация

- **Документация WP API:** см. `api-documentation-v3.md` в корне проекта.
- **Файлы WordPress-темы:** папка `wpfiles/` (chat-auth.php, chat-api-complete.php и др.) — для справки, не часть Flutter-сборки.
- **Правила проекта:** `.cursorrules` — запрет на Firebase/Supabase и др.

---

*Документ подготовлен для AI-агентов. При добавлении функционала обновляйте этот файл.*
