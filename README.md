# chat_friends

A new Flutter project.

## Getting Started

This project is a starting point for a Flutter application.

A few resources to get you started if this is your first Flutter project:

- [Lab: Write your first Flutter app](https://docs.flutter.dev/get-started/codelab)
- [Cookbook: Useful Flutter samples](https://docs.flutter.dev/cookbook)

For help getting started with Flutter development, view the
[online documentation](https://docs.flutter.dev/), which offers tutorials,
samples, guidance on mobile development, and a full API reference.



flutter clean
flutter pub get
flutter run

flutter clean
flutter pub get
flutter build apk --release


git reset --hard 50a84bff47fae6e3d4d380a6b0232f07cfb322b5

################### git reset --hard 0fd9f9bde1104e5c2bd1812613deffb7b69f8462

https://chatnews.remont-gazon.ru/update/version.txt



########################












https://chat.remont-gazon.ru

https://chat.remont-gazon.ru/admin/login.php


mskotoi7_seek vIVvV&H5sY3d



host:mskotoi7.beget.tech
useer:mskotoi7_seek
pass:X!M0aS



1. Ответы трех ссылок все:
{
  "code": "rest_forbidden",
  "message": "Извините, вам не разрешено выполнять данное действие.",
  "data": {
    "status": 401
  }
}

Ответ от: POST https://chat.remont-gazon.ru/wp-json/chat/v1/auth/login:

{
  "code": "rest_no_route",
  "message": "Подходящий маршрут для URL и метода запроса не найден.",
  "data": {
    "status": 404
  }
}


















=============================================

Сервер хостинга

server104.hosting.reg.ru

IP: 
37.140.192.76


Новый FTP-пользователь

u2891027_chat
Kaliningrad1972

BD
u2891027_chat
u2891027_chat
Kaliningrad1972
localhost













#############################################################

Прочитай все вниммательно до самого конца!

Ты: - Senior разработчик веб-приложений и специалист по созданию корпоративных чатов для общения узкого круга пользователей и интеграции этих чатов по API с сервисом BeGet. Ты в совершенстве владеешь flutter, Dart, JS, PHP, WordPress и API внедрениями. Ты уже сотни раз успешно создавал корпоративные чаты и знаешь в этой области абсолютно ВСЁ!

Я: новичек и не знаю кода. Надо: Чтобы я просто содавал файлы и "копи/пасте" сгенерированный тобой код.

КОНТЕКСТ ПРОЕКТА:
Мы завершили разработку продакшен-готового WordPress backend для корпоративного чата и успешно интегрировали Flutter приложение "Чат Друзей". 

- Домен: https://chat.remont-gazon.ru/
- WordPress с Custom Post Types: chat_user, chat, chat_message
- ACF поля для всех сущностей (полное соответствие Flutter моделям)
- Полный REST API протестирован и готов к продакшену


Документации по API WP Бэкенда в самом конце сообщения.


** ТЕКУЩАЯ СТРУКТУРА FLUTTER ПРИЛОЖЕНИЯ:
lib/
├── main.dart
├── utils/api.dart          # Base URL, headers
├── widgets/chat_list_item.dart
├── services/api_service.dart # ВСЕ API запросы
├── models/
│   ├── user.dart
│   ├── chat.dart
│   └── message.dart
└── screens/
    ├── login_screen.dart
    ├── register_screen.dart
    ├── chats_screen.dart
    ├── chat_screen.dart
    ├── create_chat_screen.dart
    └── profile_screen.dart


** ЧТО УЖЕ СДЕЛАНО:

Интеграция с WordPress REST API (/wp-json/chat-api/v1/)

Аутентификация через /chat/v1/auth/login, /chat/v1/auth/register

Работа с чатами: Дичные и Групповые (создание, список, участники)

Отправка/получение сообщений (исправлен парсинг message_id)

Время последнего сообщения.


** ТЕХНИЧЕСКИЕ ДЕТАЛИ:

Backend: WordPress + Custom Post Types (chat_user, chat, chat_message) + ACF

API endpoints протестированы и работают

Для загрузки файлов: ApiService.uploadAvatar() (существует)

Для аватарок: User.avatarUrl и User.initials уже реализованы

Пакеты: image_picker, cached_network_image (уже в pubspec.yaml)


** ТЕСТОВЫЕ ДАННЫЕ:

Телефон: +79161234567

Пароль: 123456

Токен: user_16_ff45665cfe7a518729aa934e8af457f0

Сервер: https://chat.remont-gazon.ru/




** ТЕКУЩАЯ ЗАДАЧА:

Зкран Списка чатов нуждается в доработке в первую очередь!

Исправляем элементы Групповых чатов в списке. Сей час там всё не правильно.

Вот каким должен быть UI для элементов именно Групповых чатов в общем списке:

    1. Групповые чаты:

        - Аватар: Аватар Создателя чата.
        
        - Заголовок: "[Название]" - Наззвание чата данное при создании.

        - Подзаголовок мелко: "Создан: [chate_creator - Никнейм или Имя]" - Ник (если не заполнен Ник то - Имя)" юзера создавшего этот чат (chate_creator - мы еще не создали)

        - Всамом низу ячейки, мелко: "Группа: [Никнеймы или Имена всех участников группы через запятую] участников".

        Цвет фона элементов Групповых чатов надо сделать заметно темнее чем Личных.

    2. Самые новые или не прочитанные чаты, которые в верху, должны быть с меткой - маленький ярко Зеленый кружок 10 на 10px.

Примерный план решения (если нужны файлы приложения или Бэенда - пришлю): 

Надо начать с WP. Добавить в тип записей Чаты новое ACF-поле - Создатель Чата (chate_creator). Это Поле типа выбора одного из всех Кастомных Пользователей чатов (Не системных WP!).

Затем добавить в код Бэкенда - Эндпоинт (Или несколько): chate_creator_info со всей информацией о Пользователе создавшем чат. Эндпоинт связанный с новым полем в Чатах chate_creator.

В Flutter приложении пусть это поле автоматически заполняется при создании новогго Группового и Личного чпата.

Это нужно чтобы добавить в UI вывод Никнейм или Имя и Аватар Создателя Именно Группового чата. Личные чаты вывдятся правильно - их не трогаем.

***********************************************************************

Мне надо чтобы ты внимательно проанализировал Документацию и файлы Бэкенда.
Сей час документация устарела и нуждается в обновлении. Напимер там не работют некоторые ссылки на страницы тестов. И возможны некоторые ошибки с Эндпоинтами. Изучи Бэкенд и Создай новый, обновленный, правильный файл Документации. Точныйи и понятный. Хорошо структурированны, со всеми тестами и структурами файлов. Для тестирования сделай отдельную новую html страницу.
Надо чтобы в доументации были описанны все доступные Ендпоинты. Проверить их.
Написать инструккциии как добавлять новые Ендпоинты. И подробно "для чайников" инструкцию по интеграции с Flutter приложением "Чат Друзей".

Прикрепил старую документацию.

Вот файл Бекенда chat-api-base.php

Вот файл Бекенда functions.php

************************************************************************

** ПРИНЦИП НАШЕЙ РАБОТЫ:

Ты присылаешь ОДИН шаг: файл + полный код + инструкции. Не надо длинных рассуждений и "воды"

Я выполняю, тестирую, сообщаю результат

Если все отлично работает - добавим изменения в Документацию.

Празднуем Успех проекта! И готовимся к Усовершенствованию Flutter приложения!

Работаем до полной готовности.

Только реальный, проверенный три раза код, без костылей.

Точно пиши полные пути, точные фрагменты и названия! Не надо длинных объяснений и рассуждений - коротко и понятно, без "воды".

Прежде чем менять файлы которые я еще не присылал - запроси их полное содержимое.

Вот Документация по WP API:

Какие сей час еще прислать файлы?


** НЕ ОТВЕЧАЙ. ОТВЕТИШЬ ПО МОЕЙ КОМАНДЕ - "Продолжим"










#############################################################

Прочитай все вниммательно до самого конца!

Ты: - Senior разработчик веб-приложений и специалист по созданию корпоративных чатов для общения узкого круга пользователей и интеграции этих чатов по API с сервисом BeGet. Ты в совершенстве владеешь flutter, Dart, JS, PHP, WordPress и API внедрениями. Ты уже сотни раз успешно создавал корпоративные чаты и знаешь в этой области абсолютно ВСЁ!

Я: новичек и не знаю кода. Надо: Чтобы я просто содавал файлы и "копи/пасте" сгенерированный тобой код.

КОНТЕКСТ ПРОЕКТА:
Мы завершили разработку продакшен-готового WordPress backend для корпоративного чата и успешно интегрировали Flutter приложение "Чат Друзей". Теперь будем усовершенствовать проект! 

- Домен: https://chat.remont-gazon.ru/
- WordPress с Custom Post Types: chat_user, chat, chat_message
- ACF поля для сущностей (соответствие Flutter моделям) - Требует доработки.
- Полный REST API протестирован и готов к продакшену


Документации по API WP Бэкенда прикрепил.


** ТЕКУЩАЯ СТРУКТУРА FLUTTER ПРИЛОЖЕНИЯ:
lib/
├── main.dart
├── utils/api.dart          # Base URL, headers
├── widgets/chat_list_item.dart
├── services/api_service.dart # ВСЕ API запросы
├── models/
│   ├── user.dart
│   ├── chat.dart
│   └── message.dart
└── screens/
    ├── login_screen.dart
    ├── register_screen.dart
    ├── chats_screen.dart
    ├── chat_screen.dart
    ├── create_chat_screen.dart
    └── profile_screen.dart


** ЧТО УЖЕ СДЕЛАНО:

Интеграция с WordPress REST API (/wp-json/chat-api/v1/)

Аутентификация через /chat/v1/auth/login, /chat/v1/auth/register

Работа с чатами: Дичные и Групповые (создание, список, участники)

Отправка/получение сообщений (исправлен парсинг message_id)

Время последнего сообщения.


** ТЕХНИЧЕСКИЕ ДЕТАЛИ:

Backend: WordPress + Custom Post Types (chat_user, chat, chat_message) + ACF

API endpoints протестированы и работают

Для загрузки файлов: ApiService.uploadAvatar() (существует)

Для аватарок: User.avatarUrl и User.initials уже реализованы

Пакеты: image_picker, cached_network_image (уже в pubspec.yaml)

Вот pubspec.yaml:

name: chat_friends
description: "Корпоративный чат для узкого круга"
version: 1.0.0+1

environment:
  sdk: '>=3.0.0 <4.0.0'

dependencies:
  flutter:
    sdk: flutter
  http: ^1.1.0
  shared_preferences: ^2.2.2
  image_picker: ^1.2.1
  file_picker: ^10.3.0
  cached_network_image: ^3.3.0
  intl: ^0.18.0
  pull_to_refresh: ^2.0.0
  package_info_plus: ^8.0.0
  permission_handler: ^11.2.1  
  path_provider: ^2.1.3  
  open_filex: ^4.7.0
  
dev_dependencies:
  flutter_test:
    sdk: flutter
  flutter_lints: ^2.0.0

flutter:
  uses-material-design: true
  assets:
    - assets/



** ТЕСТОВЫЕ ДАННЫЕ:

Телефон: +79161234567

Пароль: 123456

Токен: user_16_ff45665cfe7a518729aa934e8af457f0

Сервер: https://chat.remont-gazon.ru/




** ТЕКУЩАЯ ЗАДАЧА:

Проанализируй весь проект и первым делм давай исправим загрузку изображений для аватарок при регистрации. В файле экрана регистрации Польователя уже есть код для реализации загрузки картинок, но он не работает. Открывается галерея, выбираю картинку, она выбирается и встает на место, но не сохраняется при сохранении пользователя. Давай это исправим.
И посмотри в Документации Приложения - там есть информация по доработкам.



** ПРИНЦИП НАШЕЙ РАБОТЫ:

Ты присылаешь ОДИН шаг: файл + полный код + инструкции. Не надо длинных рассуждений и "воды"

Я выполняю, тестирую, сообщаю результат

Если все отлично работает - добавим изменения в Документацию.

Празднуем Успех проекта! И готовимся к Усовершенствованию Flutter приложения!

Работаем до полной готовности.

Только реальный, проверенный три раза код, без костылей.

Точно пиши полные пути, точные фрагменты и названия! Не надо длинных объяснений и рассуждений - коротко и понятно, без "воды".

Прежде чем менять файлы которые я еще не присылал - запроси их полное содержимое.

Прикрепил Документацию по WP API и Flutter Приложению.

Какие сей час еще прислать файлы?














##############################################################################



Ты: - Senior разработчик веб-приложений и специалист по созданию корпоративных чатов для общения узкого круга пользователей и интеграции этих чатов по API с сервисом BeGet. Ты в совершенстве владеешь flutter, Dart, JS, PHP, WordPress и API внедрениями.

КОНТЕКСТ ПРОЕКТА: Разрабатываем корпоративный чат "Чат Друзей" с Flutter frontend и WordPress backend.

ТЕКУЩЕЕ СОСТОЯНИЕ:

✅ Продакшен-готовый WordPress backend с Custom Post Types: chat_user, chat, chat_message

✅ Полный REST API протестирован и работает

✅ Аутентификация (логин/регистрация) с токенами формата user_{id}_{hash}

✅ Загрузка аватаров при регистрации работает

✅ Создание личных и групповых чатов

✅ Отправка/получение сообщений

✅ Аватары пользователей отображаются в чатах

СТРУКТУРА FLUTTER ПРОЕКТА:

text
lib/
├── main.dart
├── utils/api.dart
├── services/api_service.dart
├── models/
│   ├── user.dart
│   ├── chat.dart
│   └── message.dart
└── screens/
    ├── login_screen.dart
    ├── register_screen.dart
    ├── chats_screen.dart      # Текущий экран списка чатов
    ├── chat_screen.dart
    ├── create_chat_screen.dart
    └── profile_screen.dart
ТЕКУЩАЯ ЗАДАЧА: Усовершенствование экрана списка чатов

ЗАДАЧИ:

Разделить список чатов на 2 вкладки: "Личные" и "Групповые"

Для групповых чатов: вместо количества участников выводить список никнеймов/имен участников (мелкими именами под названием чата)

Экран группового чата: добавить вверху список всех участников группы с возможностью удалять/добавлять участников (меню с чекбоксами всех пользователей)

Непрочитанные сообщения: выделять чаты с непрочитанными сообщениями ярким зеленым индикатором

ЧТО УЖЕ РАБОТАЕТ В chats_screen.dart:

Отображение списка чатов (личных и групповых)

Сортировка по последнему сообщению

Pull-to-refresh

Навигация в чат

Отображение аватаров

API ENDPOINTS (WordPress):

GET /chat-api/v1/chats - список всех чатов пользователя (возвращает поле members с массивами участников)

GET /chat-api/v1/users - все пользователи

POST /chat-api/v1/chats/{id}/add-members - добавить участников

POST /chat-api/v1/chats/{id}/remove-member - удалить участника

НУЖНЫЕ ФАЙЛЫ ДЛЯ НАЧАЛА:

lib/screens/chats_screen.dart - текущая реализация списка чатов

lib/models/chat.dart - модель Chat для понимания структуры

lib/widgets/chat_list_item.dart - виджет элемента списка чатов

lib/services/api_service.dart - методы API для работы с чатами

ТЕСТОВЫЕ ДАННЫЕ:

Сервер: https://chat.remont-gazon.ru/

Токен: user_259_e7f4d02c3f703f50ca87d790133e04f8

Пользователь ID: 259

ПРИНЦИП РАБОТЫ:

Ты присылаешь ОДИН шаг: файл + полный код + инструкции

Я выполняю, тестирую, сообщаю результат

Только реальный проверенный код, без костылей

Точно и полностью пиши полные пути, фрагменты и названия

Не фантаируй, лиь бы угодить мне.

Проверяй трижды код перед тем как прислать.

Присылай только реальный, проверенный, работающий код.

Перед тем как вносить изменения, запрашивай нужные, не знакомые файлы проекта на анализ.

Начинаем с задачи №1: Разделение списка чатов на вкладки "Личные" и "Групповые". Пришли текущий chats_screen.dart.КОНТЕКСТ ПРОЕКТА: Разрабатываем корпоративный чат "Чат Друзей" с Flutter frontend и WordPress backend.

ТЕКУЩЕЕ СОСТОЯНИЕ:

✅ Продакшен-готовый WordPress backend с Custom Post Types: chat_user, chat, chat_message

✅ Полный REST API протестирован и работает

✅ Аутентификация (логин/регистрация) с токенами формата user_{id}_{hash}

✅ Загрузка аватаров при регистрации работает

✅ Создание личных и групповых чатов

✅ Отправка/получение сообщений

✅ Аватары пользователей отображаются в чатах

СТРУКТУРА FLUTTER ПРОЕКТА:

text
lib/
├── main.dart
├── utils/api.dart
├── services/api_service.dart
├── models/
│   ├── user.dart
│   ├── chat.dart
│   └── message.dart
└── screens/
    ├── login_screen.dart
    ├── register_screen.dart
    ├── chats_screen.dart      # Текущий экран списка чатов
    ├── chat_screen.dart
    ├── create_chat_screen.dart
    └── profile_screen.dart
ТЕКУЩАЯ ЗАДАЧА: Усовершенствование экрана списка чатов

ЗАДАЧИ:

Разделить список чатов на 2 вкладки: "Личные" и "Групповые"

Для групповых чатов: вместо количества участников выводить список никнеймов/имен участников (мелкими именами под названием чата)

Экран группового чата: добавить вверху список всех участников группы с возможностью удалять/добавлять участников (меню с чекбоксами всех пользователей)

Непрочитанные сообщения: выделять чаты с непрочитанными сообщениями ярким зеленым индикатором

ЧТО УЖЕ РАБОТАЕТ В chats_screen.dart:

Отображение списка чатов (личных и групповых)

Сортировка по последнему сообщению

Pull-to-refresh

Навигация в чат

Отображение аватаров

API ENDPOINTS (WordPress):

GET /chat-api/v1/chats - список всех чатов пользователя (возвращает поле members с массивами участников)

GET /chat-api/v1/users - все пользователи

POST /chat-api/v1/chats/{id}/add-members - добавить участников

POST /chat-api/v1/chats/{id}/remove-member - удалить участника

Вот список работающих ендпоинтов  из Документации :

{
  "success": true,
  "message": "Chat API v3.0 работает со всеми функциями",
  "timestamp": "2026-01-31 15:49:41",
  "version": "3.0",
  "endpoints": {
    "/chat-api/v1/test": "GET - Тест API",
    "/chat-api/v1/chats": "GET - Список чатов",
    "/chat-api/v1/chats/{id}": "GET - Информация о чате",
    "/chat-api/v1/chats/create": "POST - Создать чат",
    "/chat-api/v1/chats/{id}/add-members": "POST - Добавить участников",
    "/chat-api/v1/chats/{id}/update": "POST - Обновить чат",
    "/chat-api/v1/chats/{id}/remove-member": "POST - Удалить участника",
    "/chat-api/v1/chats/{id}/delete": "POST - Удалить чат",
    "/chat-api/v1/chats/{id}/creator": "GET - Создатель чата",
    "/chat-api/v1/messages": "GET - Сообщения чата",
    "/chat-api/v1/messages/send": "POST - Отправить сообщение",
    "/chat-api/v1/messages/mark-read": "POST - Отметить прочитанными",
    "/chat-api/v1/messages/unread-count": "GET - Непрочитанные",
    "/chat-api/v1/messages/search": "GET - Поиск сообщений",
    "/chat-api/v1/users": "GET - Все пользователи",
    "/chat-api/v1/me": "GET - Текущий пользователь",
    "/chat-api/v1/upload": "POST - Загрузить файл"
  }
}


НУЖНЫЕ ФАЙЛЫ ДЛЯ НАЧАЛА:

lib/screens/chats_screen.dart - текущая реализация списка чатов

lib/models/chat.dart - модель Chat для понимания структуры

lib/widgets/chat_list_item.dart - виджет элемента списка чатов

lib/services/api_service.dart - методы API для работы с чатами

ТЕСТОВЫЕ ДАННЫЕ:

Сервер: https://chat.remont-gazon.ru/

Токен: user_259_e7f4d02c3f703f50ca87d790133e04f8

Пользователь ID: 259

ПРИНЦИП РАБОТЫ:

Стремись к простоте при генерации кода.

Не надо длинных рассуждений в сообщениях - пиши коротко и понятно, без "воды"

Ты присылаешь ОДИН шаг: файл + полный код + инструкции

Я выполняю, тестирую, сообщаю результат

Только реальный проверенный код, без костылей

Точно пиши полные пути, фрагменты и названия

Начинаем с задачи №1: Разделение списка чатов на вкладки "Личные" и "Групповые".

 Вот текущий chats_screen.dart. Тчательно проанализируй его на ошибки и укажи как исправить, если найдеь! Код работающий. Важно не повредить его работоспособность!

 import 'package:flutter/material.dart';
import 'package:pull_to_refresh/pull_to_refresh.dart';
import 'package:chat_friends/services/api_service.dart';
import 'package:chat_friends/models/chat.dart';
import 'package:chat_friends/models/user.dart';
import 'package:chat_friends/widgets/chat_list_item.dart';
import 'package:chat_friends/screens/chat_screen.dart';
import 'package:chat_friends/screens/create_chat_screen.dart';
import 'package:chat_friends/screens/profile_screen.dart';

class ChatsScreen extends StatefulWidget {
  @override
  _ChatsScreenState createState() => _ChatsScreenState();
}

class _ChatsScreenState extends State<ChatsScreen> {
  List<Chat> _chats = [];
  List<User> _allUsers = [];
  User? _currentUser;
  bool _isLoading = true;
  final RefreshController _refreshController = RefreshController();

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    try {
      final chats = await ApiService.getChats();
      final allUsers = await ApiService.getAllUsers();
      final currentUser = await ApiService.getCurrentUser();
      
      chats.sort((a, b) {
        if (a.hasUnread && !b.hasUnread) return -1;
        if (!a.hasUnread && b.hasUnread) return 1;
        
        final aTime = a.lastMessage?.createdAt ?? a.createdAt ?? DateTime(1970);
        final bTime = b.lastMessage?.createdAt ?? b.createdAt ?? DateTime(1970);
        return bTime.compareTo(aTime);
      });

      setState(() {
        _chats = chats;
        _allUsers = allUsers;
        _currentUser = currentUser;
        _isLoading = false;
      });
    } catch (e) {
      print('Ошибка загрузки данных: $e');
      setState(() { _isLoading = false; });
    }
  }

  void _onRefresh() async {
    await _loadData();
    _refreshController.refreshCompleted();
  }

  @override
  Widget build(BuildContext context) {
    
    return Scaffold(
      appBar: AppBar(
        title: Text('Чаты'),
        actions: [
          IconButton(
            icon: Icon(Icons.person),
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(builder: (context) => ProfileScreen()),
              ).then((_) => _loadData());
            },
          ),
        ],
      ),
      body: _isLoading || _currentUser == null
          ? Center(child: CircularProgressIndicator())
          : SmartRefresher(
              controller: _refreshController,
              onRefresh: _onRefresh,
              child: _chats.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.chat_bubble_outline, size: 64, color: Colors.grey),
                          SizedBox(height: 16),
                          Text(
                            'Нет чатов',
                            style: TextStyle(fontSize: 18, color: Colors.grey),
                          ),
                          SizedBox(height: 8),
                          Text(
                            'Создайте первый чат',
                            style: TextStyle(color: Colors.grey),
                          ),
                        ],
                      ),
                    )
                  : ListView.builder(
                      itemCount: _chats.length,
                      itemBuilder: (context, index) {
                        final chat = _chats[index];
                        return ChatListItem(
                          chat: chat,
                          currentUser: _currentUser!,
                          allUsers: _allUsers,
                          onTap: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (context) => ChatScreen(chat: chat),
                              ),
                            ).then((_) => _loadData());
                          },
                        );
                      },
                    ),
            ),
      floatingActionButton: FloatingActionButton(
        onPressed: () async {
          // ОТКРЫВАЕМ ЭКРАН СОЗДАНИЯ И ЖДЕМ РЕЗУЛЬТАТ
          final result = await Navigator.push(
            context,
            MaterialPageRoute(builder: (context) => CreateChatScreen()),
          );
          
          // ЕСЛИ ВЕРНУЛСЯ СОЗДАННЫЙ ЧАТ - ОТКРЫВАЕМ ЕГО
          if (result is Chat) {
            print('[DEBUG] Получен созданный чат: ${result.id}');
            await _loadData(); // Обновляем список
            
            // НЕМНОГО ЖДЕМ, ЧТОБЫ СПИСОК ОБНОВИЛСЯ
            await Future.delayed(Duration(milliseconds: 300));
            
            // ОТКРЫВАЕМ СОЗДАННЫЙ ЧАТ
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (context) => ChatScreen(chat: result),
              ),
            ).then((_) => _loadData());
          } 
          // ЕСЛИ ЧАТ НЕ БЫЛ СОЗДАН (null) - ПРОСТО ОБНОВЛЯЕМ СПИСОК
          else if (result == null) {
            _loadData();
          }
        },
        child: Icon(Icons.add),
      ),
    );
  }
}

