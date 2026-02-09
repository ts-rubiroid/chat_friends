<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Полная система аутентификации для чата
 */

add_action('rest_api_init', function() {
    
    // 1. Login (вход)
    register_rest_route('chat/v1', '/auth/login', [
        'methods' => 'POST',
        'callback' => 'chat_auth_login',
        'permission_callback' => '__return_true',
    ]);
    
    // 2. Register (регистрация)
    register_rest_route('chat/v1', '/auth/register', [
        'methods' => 'POST',
        'callback' => 'chat_auth_register',
        'permission_callback' => '__return_true',
    ]);
    
    // 3. Validate token (проверка токена)
    register_rest_route('chat/v1', '/auth/validate', [
        'methods' => 'POST',
        'callback' => 'chat_auth_validate',
        'permission_callback' => '__return_true',
    ]);
    
    // 4. Get current user (получить текущего пользователя)
    register_rest_route('chat/v1', '/me', [
        'methods' => 'GET',
        'callback' => 'chat_get_me',
        'permission_callback' => 'chat_check_auth',
    ]);
});

// Проверка авторизации по токену
function chat_check_auth($request) {
    $headers = $request->get_headers();
    $auth_header = $headers['authorization'] ?? [];
    
    if (empty($auth_header)) {
        return false;
    }
    
    $auth_value = is_array($auth_header) ? $auth_header[0] : $auth_header;
    
    if (strpos($auth_value, 'Bearer ') === 0) {
        $token = substr($auth_value, 7);
        return chat_validate_token_internal($token);
    }
    
    return false;
}

// Внутренняя функция проверки токена
function chat_validate_token_internal($token) {
    if (strpos($token, 'master_') === 0) {
        return 999; // Master user ID
    }
    
    if (strpos($token, 'user_') === 0) {
        $parts = explode('_', $token);
        if (count($parts) >= 2 && is_numeric($parts[1])) {
            return intval($parts[1]);
        }
    }
    
    return false;
}

// Функция входа
function chat_auth_login($request) {
    $params = $request->get_params();
    
    // Валидация
    if (empty($params['phone']) || empty($params['password'])) {
        return new WP_Error('missing_data', 'Телефон и пароль обязательны', ['status' => 400]);
    }
    
    // Мастер-пароль (для админа и быстрого доступа)
    if ($params['password'] === '123123') {
        return [
            'success' => true,
            'message' => 'Авторизация успешна (мастер-пароль)',
            'token' => 'master_' . md5($params['phone'] . time()),
            'user' => [
                'id' => 999,
                'phone' => $params['phone'],
                'first_name' => 'Master',
                'last_name' => 'User',
                'nickname' => 'master',
                'avatar' => '',
                'avatar_url' => '',
                'created_at' => current_time('mysql'),
            ]
        ];
    }
    
    // Поиск пользователя по телефону
    $args = [
        'post_type' => 'chat_user',
        'posts_per_page' => 1,
        'meta_query' => [[
            'key' => 'phone',
            'value' => $params['phone'],
            'compare' => '='
        ]]
    ];
    
    $users = get_posts($args);
    
    if (empty($users)) {
        return new WP_Error('user_not_found', 'Пользователь не найден', ['status' => 404]);
    }
    
    $user_post = $users[0];
    $stored_password = get_field('password', $user_post->ID);
    
    // Простая проверка пароля (пока без хеширования)
    if (!$stored_password || $stored_password !== $params['password']) {
        return new WP_Error('wrong_password', 'Неверный пароль', ['status' => 401]);
    }
    
    // Генерация токена
    $token = 'user_' . $user_post->ID . '_' . md5($user_post->ID . time() . 'chat_secret_2026');
    
    return [
        'success' => true,
        'message' => 'Авторизация успешна',
        'token' => $token,
        'user' => [
            'id' => $user_post->ID,
            'phone' => get_field('phone', $user_post->ID),
            'first_name' => get_field('first_name', $user_post->ID),
            'last_name' => get_field('last_name', $user_post->ID),
            'nickname' => get_field('nickname', $user_post->ID),
            'avatar' => get_field('avatar', $user_post->ID),
            'avatar_url' => get_field('avatar', $user_post->ID),
            'created_at' => get_field('created_at', $user_post->ID) ?: $user_post->post_date,
        ]
    ];
}

// Функция регистрации
    function chat_auth_register($request) {
        $params = $request->get_params();
        
        // Валидация
        $required = ['phone', 'password', 'first_name'];
        foreach ($required as $field) {
            if (empty($params[$field])) {
                return new WP_Error('missing_field', "Поле $field обязательно", ['status' => 400]);
            }
        }
        
        // Проверка, существует ли уже такой телефон
        $args = [
            'post_type' => 'chat_user',
            'posts_per_page' => 1,
            'meta_query' => [[
                'key' => 'phone',
                'value' => $params['phone'],
                'compare' => '='
            ]]
        ];
        
        $existing = get_posts($args);
        
        if (!empty($existing)) {
            return new WP_Error('phone_exists', 'Пользователь с таким телефоном уже существует', ['status' => 409]);
        }
        
        // Создание пользователя
        $user_data = [
            'post_type' => 'chat_user',
            'post_title' => $params['first_name'] . ' ' . ($params['last_name'] ?? ''),
            'post_status' => 'publish',
        ];
        
        $user_id = wp_insert_post($user_data);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // Упрощенное сохранение полей
        $fields_to_save = [
            'phone' => $params['phone'],
            'password' => $params['password'],
            'first_name' => $params['first_name'],
            'last_name' => $params['last_name'] ?? '',
            'nickname' => $params['nickname'] ?? '',
            'created_at' => current_time('Y-m-d H:i:s'),
            'user_id' => $user_id
        ];
        
        // Сохраняем avatar если есть
        // ВАЖНО:
        // - Flutter присылает ПОЛНЫЙ URL загруженного файла (из /chat-api/v1/upload)
        // - ACF Image-поле "avatar" хранит ВНУТРЕННИЙ attachment ID
        // Поэтому:
        // 1) по URL находим attachment ID через attachment_url_to_postid()
        // 2) в ACF-поле "avatar" сохраняем ИМЕННО ID
        // 3) дополнительный URL кладём в отдельный meta-ключ avatar_url (для отладки/совместимости)
        $avatar_value = $params['avatar'] ?? '';
        if (!empty($avatar_value) && $avatar_value !== 'false') {
            $avatar_url = esc_url_raw($avatar_value);
            $avatar_id = 0;

            if (function_exists('attachment_url_to_postid')) {
                $avatar_id = attachment_url_to_postid($avatar_url);
            }

            if ($avatar_id) {
                // Правильный путь для ACF Image поля – сохраняем ID вложения
                $fields_to_save['avatar'] = $avatar_id;
            } else {
                // На всякий случай сохраняем URL – ACF иногда умеет сам привязать его к ID
                $fields_to_save['avatar'] = $avatar_url;
            }

            // Дополнительно сохраняем явный URL в отдельное meta-поле
            update_post_meta($user_id, 'avatar_url', $avatar_url);
        }
        
        // Сохраняем все поля через ACF (в т.ч. avatar)
        foreach ($fields_to_save as $key => $value) {
            update_field($key, $value, $user_id);
        }
        
        // Генерация токена
        $token = 'user_' . $user_id . '_' . md5($user_id . time() . 'chat_secret_2026');
        
        // Получаем сохраненный avatar для ответа (через ACF)
        $saved_avatar = get_field('avatar', $user_id);

        // Если ACF вернул ID (для Image-поля) — преобразуем в URL
        if (!empty($saved_avatar) && is_numeric($saved_avatar)) {
            $attachment_url = wp_get_attachment_url(intval($saved_avatar));
            if ($attachment_url) {
                $saved_avatar = $attachment_url;
            }
        }

        // Фолбэк: если по какой-то причине ACF ничего не вернул, пробуем meta avatar_url
        if (empty($saved_avatar)) {
            $meta_avatar_url = get_post_meta($user_id, 'avatar_url', true);
            if (!empty($meta_avatar_url)) {
                $saved_avatar = $meta_avatar_url;
            }
        }
        
        return [
            'success' => true,
            'message' => 'Регистрация успешна',
            'token' => $token,
            'user' => [
                'id' => $user_id,
                'phone' => $params['phone'],
                'first_name' => $params['first_name'],
                'last_name' => $params['last_name'] ?? '',
                'nickname' => $params['nickname'] ?? '',
                'avatar' => $saved_avatar ?: '',
                'avatar_url' => $saved_avatar ?: '',
                'created_at' => current_time('Y-m-d H:i:s'),
            ]
        ];
    }

// Функция проверки токена
function chat_auth_validate($request) {
    $params = $request->get_params();
    $token = $params['token'] ?? '';
    
    $user_id = chat_validate_token_internal($token);
    
    if ($user_id) {
        return [
            'valid' => true,
            'user_id' => $user_id,
            'message' => 'Токен действителен'
        ];
    }
    
    return [
        'valid' => false,
        'message' => 'Неверный токен'
    ];
}

// Функция получения текущего пользователя
function chat_get_me($request) {
    $headers = $request->get_headers();
    $auth_header = $headers['authorization'] ?? [];
    
    if (empty($auth_header)) {
        return new WP_Error('no_token', 'Токен не предоставлен', ['status' => 401]);
    }
    
    $auth_value = is_array($auth_header) ? $auth_header[0] : $auth_header;
    
    if (strpos($auth_value, 'Bearer ') !== 0) {
        return new WP_Error('invalid_token', 'Неверный формат токена', ['status' => 401]);
    }
    
    $token = substr($auth_value, 7);
    $user_id = chat_validate_token_internal($token);
    
    if (!$user_id) {
        return new WP_Error('invalid_token', 'Неверный токен', ['status' => 401]);
    }
    
    // Если это мастер-пользователь
    if ($user_id === 999) {
        return [
            'id' => 999,
            'phone' => 'master',
            'first_name' => 'Master',
            'last_name' => 'User',
            'nickname' => 'master',
            'avatar' => '',
            'avatar_url' => '',
            'created_at' => current_time('mysql'),
        ];
    }
    
    // Получение данных пользователя
    $user_post = get_post($user_id);
    
    if (!$user_post || $user_post->post_type !== 'chat_user') {
        return new WP_Error('user_not_found', 'Пользователь не найден', ['status' => 404]);
    }
    
    // Если get_field не работает, пробуем через get_post_meta
    if (empty($avatar_value) || $avatar_value === false || $avatar_value === 'false') {
        $avatar_value = get_post_meta($user_id, 'avatar', true);
        
        // Если все еще нет значения, пробуем через старый метод
        if (empty($avatar_value) || $avatar_value === false) {
            $avatar_value = '';
        }
    }



    // Отладочная информация
    error_log("Chat API: Получен аватар для пользователя $user_id: " . ($avatar_value ?: '(пусто)'));

    return [
        'id' => $user_id,
        'phone' => get_field('phone', $user_id),
        'first_name' => get_field('first_name', $user_id),
        'last_name' => get_field('last_name', $user_id),
        'nickname' => get_field('nickname', $user_id),
        'avatar' => $avatar_value,
        'avatar_url' => $avatar_value,
        'created_at' => get_field('created_at', $user_id) ?: $user_post->post_date,
    ];


}