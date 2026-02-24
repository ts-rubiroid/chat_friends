<?php
/**
 * Дочерняя тема для Chat Friends
 * Отключаем вывод PHP-уведомлений в браузер (WP 6.7 + ACF загружают перевод до init),
 * иначе «headers already sent» и белый экран админки. Лог по-прежнему в debug.log.
 */
if (function_exists('ini_set')) {
    @ini_set('display_errors', '0');
}

add_action('after_setup_theme', function() {
    // Загружаем текстовый домен для перевода
    load_theme_textdomain('chat-friends', get_stylesheet_directory() . '/languages');
});

// Подключаем стили родительской темы
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
});

// 1. Подключаем регистрацию CPT
require_once get_stylesheet_directory() . '/cpt-registration.php';

// 2. Подключаем аутентификацию
require_once get_stylesheet_directory() . '/chat-auth.php';

// 3. Подключаем ПОЛНЫЙ API (все endpoints в одном файле)
require_once get_stylesheet_directory() . '/chat-api-complete.php';

// 4. Push-уведомления через ntfy (UnifiedPush)
require_once get_stylesheet_directory() . '/chat-push-ntfy.php';



// Создаем Custom Post Types
add_action('init', function() {
    
    // 1. CPT для Пользователей (User Profiles)
    register_post_type('chat_user', [
        'label' => __('Пользователи чата', 'chat-friends'),
        'labels' => [
            'name' => __('Пользователи', 'chat-friends'),
            'singular_name' => __('Пользователь', 'chat-friends'),
            'add_new' => __('Добавить пользователя', 'chat-friends'),
            'add_new_item' => __('Добавить нового пользователя', 'chat-friends'),
            'edit_item' => __('Редактировать пользователя', 'chat-friends'),
            'new_item' => __('Новый пользователь', 'chat-friends'),
            'view_item' => __('Просмотреть пользователя', 'chat-friends'),
            'search_items' => __('Найти пользователя', 'chat-friends'),
            'not_found' => __('Пользователи не найдены', 'chat-friends'),
        ],
        'public' => true,
        'show_in_rest' => true, // ВАЖНО: включаем REST API
        'rest_base' => 'chat-users',
        'has_archive' => false,
        'supports' => ['title', 'custom-fields'],
        'menu_icon' => 'dashicons-admin-users',
        'capability_type' => 'post',
        'capabilities' => [
            'create_posts' => 'create_users', // Только админ может создавать
        ],
        'map_meta_cap' => true,
    ]);
    
    // 2. CPT для Чатов (Chats)
    register_post_type('chat', [
        'label' => __('Чаты', 'chat-friends'),
        'labels' => [
            'name' => __('Чаты', 'chat-friends'),
            'singular_name' => __('Чат', 'chat-friends'),
            'add_new' => __('Создать чат', 'chat-friends'),
            'add_new_item' => __('Создать новый чат', 'chat-friends'),
            'edit_item' => __('Редактировать чат', 'chat-friends'),
            'new_item' => __('Новый чат', 'chat-friends'),
            'view_item' => __('Просмотреть чат', 'chat-friends'),
            'search_items' => __('Найти чат', 'chat-friends'),
            'not_found' => __('Чаты не найдены', 'chat-friends'),
        ],
        'public' => true,
        'show_in_rest' => true, // ВАЖНО: включаем REST API
        'rest_base' => 'chats',
        'has_archive' => false,
        'supports' => ['title', 'custom-fields'],
        'menu_icon' => 'dashicons-format-chat',
    ]);
    
    // 3. CPT для Сообщений (Messages)
    register_post_type('chat_message', [
        'label' => __('Сообщения', 'chat-friends'),
        'labels' => [
            'name' => __('Сообщения', 'chat-friends'),
            'singular_name' => __('Сообщение', 'chat-friends'),
            'add_new' => __('Написать сообщение', 'chat-friends'),
            'add_new_item' => __('Написать новое сообщение', 'chat-friends'),
            'edit_item' => __('Редактировать сообщение', 'chat-friends'),
            'new_item' => __('Новое сообщение', 'chat-friends'),
            'view_item' => __('Просмотреть сообщение', 'chat-friends'),
            'search_items' => __('Найти сообщение', 'chat-friends'),
            'not_found' => __('Сообщения не найдены', 'chat-friends'),
        ],
        'public' => true,
        'show_in_rest' => true, // ВАЖНО: включаем REST API
        'rest_base' => 'chat-messages',
        'has_archive' => false,
        'supports' => ['title', 'custom-fields'],
        'menu_icon' => 'dashicons-email-alt',
    ]);
    
    // Регистрируем таксономию для связи сообщений с чатами
    register_taxonomy('chat_room', 'chat_message', [
        'label' => __('Чат комнаты', 'chat-friends'),
        'labels' => [
            'name' => __('Чат комнаты', 'chat-friends'),
            'singular_name' => __('Чат комната', 'chat-friends'),
        ],
        'public' => true,
        'show_in_rest' => true,
        'hierarchical' => false,
        'show_admin_column' => true,
    ]);
});

// Добавляем ACF поля в REST API ответы
add_action('rest_api_init', function() {
    
    // Функция для добавления ACF полей
    $add_acf_to_rest = function($object, $field_name, $request) {
        return get_field($field_name, $object['id']);
    };
    
    // 1. Для пользователей чата (chat_user)
    register_rest_field('chat_user', 'acf', [
        'get_callback' => function($object, $field_name, $request) {
            $acf_fields = get_fields($object['id']);
            return $acf_fields ?: [];
        },
        'update_callback' => null,
        'schema' => null,
    ]);
    
    // Также регистрируем отдельные поля для удобства
    // Добавляем сюда и новое поле "position" (должность)
    $user_fields = [
        'phone',
        'password',
        'avatar',
        'first_name',
        'last_name',
        'middle_name',
        'nickname',
        'position',
        'created_at',
        'user_id',
    ];
    foreach ($user_fields as $field) {
        register_rest_field('chat_user', $field, [
            'get_callback' => $add_acf_to_rest,
            'update_callback' => null,
            'schema' => null,
        ]);
    }
    
    // 2. Для чатов (chat) - подготовим поля
    register_rest_field('chat', 'acf', [
        'get_callback' => function($object, $field_name, $request) {
            return get_fields($object['id']) ?: [];
        },
        'update_callback' => null,
        'schema' => null,
    ]);
    
    // 3. Для сообщений (chat_message)
    register_rest_field('chat_message', 'acf', [
        'get_callback' => function($object, $field_name, $request) {
            return get_fields($object['id']) ?: [];
        },
        'update_callback' => null,
        'schema' => null,
    ]);
});

// Включаем дополнительные параметры в REST API
add_filter('rest_prepare_chat_user', function($response, $post, $request) {
    // Добавляем ссылку на аватар
    $avatar = get_field('avatar', $post->ID);
    if ($avatar) {
        $response->data['avatar_url'] = $avatar;
    }
    
    // Добавляем полное имя
    $first_name = get_field('first_name', $post->ID);
    $last_name = get_field('last_name', $post->ID);
    $response->data['full_name'] = trim($first_name . ' ' . $last_name);
    
    return $response;
}, 10, 3);

// CORS заголовки (в самом конце)
add_action('rest_api_init', function() {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function($value) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce');
        return $value;
    });
}, 15);

// Скрываем чувствительные поля из REST API
add_filter('rest_prepare_chat_user', function($response, $post, $request) {
    // Удаляем пароль из ответа API
    if (isset($response->data['acf']['password'])) {
        unset($response->data['acf']['password']);
    }
    
    // Также удаляем из верхнего уровня, если есть
    if (isset($response->data['password'])) {
        unset($response->data['password']);
    }
    
    return $response;
}, 10, 3);

// Фильтр для запросов REST API (скрываем пароль при создании/обновлении)
add_filter('rest_insert_chat_user', function($post, $request, $creating) {
    $params = $request->get_params();
    
    if (isset($params['password'])) {
        // Здесь позже добавим хеширование пароля
        update_field('password', $params['password'], $post->ID);
    }
    
    return $post;
}, 10, 3);

// Добавляем поля сообщений в REST API
add_action('rest_api_init', function() {
    // Поля для сообщений на верхнем уровне
    $message_fields = ['message_id', 'chat', 'sender', 'text', 'image', 'file', 'created_at'];
    
    foreach ($message_fields as $field) {
        register_rest_field('chat_message', $field, [
            'get_callback' => function($object, $field_name, $request) {
                return get_field($field_name, $object['id']);
            },
            'update_callback' => null,
            'schema' => null,
        ]);
    }
    
    // Добавляем URL для изображений и файлов
    add_filter('rest_prepare_chat_message', function($response, $post, $request) {
        $image = get_field('image', $post->ID);
        $file = get_field('file', $post->ID);
        
        if ($image) {
            $response->data['image_url'] = $image;
        }
        
        if ($file) {
            $response->data['file_url'] = $file;
        }
        
        // Добавляем ID связанного чата и отправителя
        $chat_id = get_field('chat', $post->ID);
        $sender_id = get_field('sender', $post->ID);
        
        if ($chat_id) {
            $response->data['chat_id'] = $chat_id;
        }
        
        if ($sender_id) {
            $response->data['sender_id'] = $sender_id;
        }
        
        return $response;
    }, 10, 3);
});

// Автоматически генерируем заголовок для сообщений
add_filter('wp_insert_post_data', function($data, $postarr) {
    if ($data['post_type'] === 'chat_message' && empty($data['post_title'])) {
        $sender_id = $postarr['acf']['sender'] ?? '';
        $chat_id = $postarr['acf']['chat'] ?? '';
        $text = wp_trim_words($postarr['acf']['text'] ?? '', 5, '...');
        
        $data['post_title'] = sprintf('Сообщение от %s в чате %s: %s', 
            $sender_id, $chat_id, $text);
    }
    return $data;
}, 10, 2);

// Исправляем формат полей для сообщений (chat и sender должны быть числами, а не массивами)
add_filter('rest_prepare_chat_message', function($response, $post, $request) {
    // Получаем ACF поля
    $chat = get_field('chat', $post->ID);
    $sender = get_field('sender', $post->ID);
    
    // Если chat - массив, берем первый элемент
    if (is_array($chat) && !empty($chat)) {
        $response->data['chat'] = intval($chat[0]);
        $response->data['acf']['chat'] = intval($chat[0]);
        $response->data['chat_id'] = intval($chat[0]);
    } elseif (is_numeric($chat)) {
        $response->data['chat'] = intval($chat);
        $response->data['chat_id'] = intval($chat);
    }
    
    // Если sender - массив, берем первый элемент
    if (is_array($sender) && !empty($sender)) {
        $response->data['sender'] = intval($sender[0]);
        $response->data['acf']['sender'] = intval($sender[0]);
        $response->data['sender_id'] = intval($sender[0]);
    } elseif (is_numeric($sender)) {
        $response->data['sender'] = intval($sender);
        $response->data['sender_id'] = intval($sender);
    }
    
    return $response;
}, 10, 3);

// Также исправляем members в чатах (если это массив)
add_filter('rest_prepare_chat', function($response, $post, $request) {
    $members = get_field('members', $post->ID);
    if (is_array($members)) {
        $response->data['members'] = array_map('intval', $members);
        $response->data['acf']['members'] = array_map('intval', $members);
    }
    return $response;
}, 10, 3);

// Добавляем функцию хеширования паролей
add_action('acf/save_post', function($post_id) {
    // Проверяем, что это наш CPT пользователя
    if (get_post_type($post_id) === 'chat_user') {
        $password = get_field('password', $post_id);
        
        // Если пароль установлен и не хеширован
        if ($password && !wp_hash_password($password)) {
            // Хешируем пароль
            $hashed_password = wp_hash_password($password);
            update_field('password', $hashed_password, $post_id);
        }
    }
}, 10);

// Безопасное удаление пользователей чата
add_action('before_delete_post', function($post_id) {
    if (get_post_type($post_id) !== 'chat_user') {
        return;
    }
    
    // 1. Удаляем пользователя из всех чатов
    $chats = get_posts([
        'post_type' => 'chat',
        'posts_per_page' => -1,
        'meta_query' => [[
            'key' => 'members',
            'value' => $post_id,
            'compare' => 'LIKE'
        ]]
    ]);
    
    foreach ($chats as $chat) {
        $members = get_field('members', $chat->ID);
        if (is_array($members)) {
            $new_members = array_values(array_diff($members, [$post_id]));
            update_field('members', $new_members, $chat->ID);
            
            // Если в чате остался 1 человек - удаляем чат
            if (count($new_members) <= 1) {
                wp_delete_post($chat->ID, true);
            }
        }
    }
    
    // 2. Помечаем сообщения
    $messages = get_posts([
        'post_type' => 'chat_message',
        'posts_per_page' => -1,
        'meta_query' => [[
            'key' => 'sender',
            'value' => $post_id,
            'compare' => '='
        ]]
    ]);
    
    foreach ($messages as $message) {
        update_field('sender', 0, $message->ID); // 0 = удаленный
        update_post_meta($message->ID, '_deleted_sender', $post_id);
    }
    
    error_log("Chat user {$post_id} was deleted. Cleaned up references.");
}, 10, 1);




add_filter('rest_pre_serve_request', function($served, $result, $request, $server) {
    // Устанавливаем правильный Content-Type
    header('Content-Type: application/json; charset=' . get_option('blog_charset'));
    return $served;
}, 10, 4);