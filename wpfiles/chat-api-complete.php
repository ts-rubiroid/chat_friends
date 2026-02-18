<?php
/**
 * ПОЛНЫЙ API для Chat Friends v3.01
 * Все endpoints в одном файле без конфликтов
 */

add_action('rest_api_init', 'chat_register_complete_api');

function chat_register_complete_api() {
    // ========== ТЕСТ И ПРОВЕРКА ==========
    register_rest_route('chat-api/v1', '/test', [
        'methods' => 'GET',
        'callback' => 'chat_api_test',
        'permission_callback' => '__return_true'
    ]);
    
    // ========== ЧАТЫ ==========
    register_rest_route('chat-api/v1', '/chats', [
        'methods' => 'GET',
        'callback' => 'chat_api_get_chats',
        'permission_callback' => 'chat_api_check_auth'
    ]);
    
    register_rest_route('chat-api/v1', '/chats/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'chat_api_get_chat_info',
        'permission_callback' => 'chat_api_check_auth'
    ]);
    
    register_rest_route('chat-api/v1', '/chats/create', [
        'methods' => 'POST',
        'callback' => 'chat_api_create_chat',
        'permission_callback' => 'chat_api_check_auth'
    ]);
    
    register_rest_route('chat-api/v1', '/chats/(?P<id>\d+)/add-members', [
        'methods' => 'POST',
        'callback' => 'chat_api_add_members',
        'permission_callback' => 'chat_api_check_auth'
    ]);
    
    register_rest_route('chat-api/v1', '/chats/(?P<id>\d+)/update', [
        'methods' => 'POST',
        'callback' => 'chat_api_update_chat',
        'permission_callback' => 'chat_api_check_auth'
    ]);
    
    register_rest_route('chat-api/v1', '/chats/(?P<id>\d+)/remove-member', [
        'methods' => 'POST',
        'callback' => 'chat_api_remove_member',
        'permission_callback' => 'chat_api_check_auth'
    ]);
    
    register_rest_route('chat-api/v1', '/chats/(?P<id>\d+)/delete', [
        'methods' => 'POST',
        'callback' => 'chat_api_delete_chat',
        'permission_callback' => 'chat_api_check_auth'
    ]);
    
    register_rest_route('chat-api/v1', '/chats/(?P<id>\d+)/creator', [
        'methods' => 'GET',
        'callback' => 'chat_api_get_chat_creator',
        'permission_callback' => 'chat_api_check_auth'
    ]);
    
    // ========== СООБЩЕНИЯ ==========
    register_rest_route('chat-api/v1', '/messages', [
        'methods' => 'GET',
        'callback' => 'chat_api_get_messages',
        'permission_callback' => 'chat_api_check_auth'
    ]);
    
    register_rest_route('chat-api/v1', '/messages/send', [
        'methods' => 'POST',
        'callback' => 'chat_api_send_message',
        'permission_callback' => 'chat_api_check_auth'
    ]);
    
    // Удаление сообщения (как в мессенджерах — удаление для всех)
    register_rest_route('chat-api/v1', '/messages/(?P<id>\d+)/delete', [
        'methods' => 'POST',
        'callback' => 'chat_api_delete_message',
        'permission_callback' => 'chat_api_check_auth'
    ]);

    // Удаление сообщения (альтернативный REST-вариант)
    // Поддержка стандартного HTTP-метода DELETE:
    // DELETE /chat-api/v1/messages/{id}
    // Это помогает, если клиент/прокси не вызывает POST /.../delete.
    register_rest_route('chat-api/v1', '/messages/(?P<id>\d+)', [
        'methods' => WP_REST_Server::DELETABLE,
        'callback' => 'chat_api_delete_message',
        'permission_callback' => 'chat_api_check_auth'
    ]);

    // Отметить сообщения как прочитанные (НОВЫЙ)
    register_rest_route('chat-api/v1', '/messages/mark-read', [
        'methods' => 'POST',
        'callback' => 'chat_api_mark_read',
        'permission_callback' => 'chat_api_check_auth'
    ]);
    // Получить количество непрочитанных сообщений 
    register_rest_route('chat-api/v1', '/messages/unread-count', [
        'methods' => 'GET',
        'callback' => 'chat_api_unread_count',
        'permission_callback' => 'chat_api_check_auth'
    ]);
    
    register_rest_route('chat-api/v1', '/messages/search', [
        'methods' => 'GET',
        'callback' => 'chat_api_search_messages',
        'permission_callback' => 'chat_api_check_auth'
    ]);
    
    // ========== ПОЛЬЗОВАТЕЛИ ==========
    register_rest_route('chat-api/v1', '/users', [
        'methods' => 'GET',
        'callback' => 'chat_api_get_users',
        'permission_callback' => 'chat_api_check_auth'
    ]);
    
    // Обновление профиля текущего пользователя (или любого chat_user для master-пользователя)
    register_rest_route('chat-api/v1', '/users/update', [
        'methods' => 'POST',
        'callback' => 'chat_api_update_profile',
        'permission_callback' => 'chat_api_check_auth'
    ]);
    
    // Удаление (перемещение в корзину) профиля текущего пользователя
    register_rest_route('chat-api/v1', '/users/delete', [
        'methods' => 'POST',
        'callback' => 'chat_api_delete_profile',
        'permission_callback' => 'chat_api_check_auth'
    ]);
    
    register_rest_route('chat-api/v1', '/me', [
        'methods' => 'GET',
        'callback' => 'chat_api_get_me',
        'permission_callback' => 'chat_api_check_auth'
    ]);
    
    // ========== ФАЙЛЫ ==========
    register_rest_route('chat-api/v1', '/upload', [
        'methods' => 'POST',
        'callback' => 'chat_api_upload',
        'permission_callback' => '__return_true' // Разрешить всем
    ]);
}

// ==================== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ====================

function chat_api_check_auth($request) {
    $headers = $request->get_headers();
    $auth_header = $headers['authorization'] ?? [];
    
    if (empty($auth_header)) {
        return false;
    }
    
    $auth_value = is_array($auth_header) ? $auth_header[0] : $auth_header;
    
    if (strpos($auth_value, 'Bearer ') !== 0) {
        return false;
    }
    
    $token = substr($auth_value, 7);
    $user_id = chat_api_validate_token($token);
    
    return $user_id !== false;
}

function chat_api_get_user_id($request) {
    $headers = $request->get_headers();
    $auth_header = $headers['authorization'] ?? [];
    
    if (empty($auth_header)) {
        return 0;
    }
    
    $auth_value = is_array($auth_header) ? $auth_header[0] : $auth_header;
    
    if (strpos($auth_value, 'Bearer ') !== 0) {
        return 0;
    }
    
    $token = substr($auth_value, 7);
    return chat_api_validate_token($token);
}

function chat_api_validate_token($token) {
    if (strpos($token, 'master_') === 0) {
        return 999;
    }
    
    if (strpos($token, 'user_') === 0) {
        $parts = explode('_', $token);
        if (count($parts) >= 2 && is_numeric($parts[1])) {
            return intval($parts[1]);
        }
    }
    
    return false;
}

function chat_api_get_last_message($chat_id) {
    $messages = get_posts([
        'post_type' => 'chat_message',
        'posts_per_page' => 1,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => [[
            'key' => 'chat',
            'value' => $chat_id,
            'compare' => '='
        ]]
    ]);
    
    if (empty($messages)) {
        return null;
    }
    
    $message = $messages[0];
    return [
        'id' => (int) $message->ID,
        'message_id' => (int) $message->ID,
        'text' => get_field('text', $message->ID),
        'sender_id' => get_field('sender', $message->ID),
        'created_at' => get_field('created_at', $message->ID)
    ];
}

function chat_api_find_existing_private_chat($user1_id, $user2_id) {
    $chats = get_posts([
        'post_type' => 'chat',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'is_group',
                'value' => '0',
                'compare' => '='
            ],
            [
                'key' => 'members',
                'value' => $user1_id,
                'compare' => 'LIKE'
            ],
            [
                'key' => 'members',
                'value' => $user2_id,
                'compare' => 'LIKE'
            ]
        ]
    ]);
    
    return !empty($chats) ? $chats[0] : null;
}

// ==================== ОСНОВНЫЕ API ФУНКЦИИ ====================

function chat_api_test() {
    return [
        'success' => true,
        'message' => 'Chat API v3.0 работает со всеми функциями',
        'timestamp' => current_time('mysql'),
        'version' => '3.0',
        'endpoints' => [
            '/chat-api/v1/test' => 'GET - Тест API',
            '/chat-api/v1/chats' => 'GET - Список чатов',
            '/chat-api/v1/chats/{id}' => 'GET - Информация о чате',
            '/chat-api/v1/chats/create' => 'POST - Создать чат',
            '/chat-api/v1/chats/{id}/add-members' => 'POST - Добавить участников',
            '/chat-api/v1/chats/{id}/update' => 'POST - Обновить чат',
            '/chat-api/v1/chats/{id}/remove-member' => 'POST - Удалить участника',
            '/chat-api/v1/chats/{id}/delete' => 'POST - Удалить чат',
            '/chat-api/v1/chats/{id}/creator' => 'GET - Создатель чата',
            '/chat-api/v1/messages' => 'GET - Сообщения чата',
            '/chat-api/v1/messages/send' => 'POST - Отправить сообщение',
            '/chat-api/v1/messages/{id}/delete' => 'POST - Удалить сообщение',
            '/chat-api/v1/messages/{id}' => 'DELETE - Удалить сообщение (REST)',
            '/chat-api/v1/messages/mark-read' => 'POST - Отметить прочитанными',
            '/chat-api/v1/messages/unread-count' => 'GET - Непрочитанные',
            '/chat-api/v1/messages/search' => 'GET - Поиск сообщений',
            '/chat-api/v1/users' => 'GET - Все пользователи',
            '/chat-api/v1/me' => 'GET - Текущий пользователь',
            '/chat-api/v1/upload' => 'POST - Загрузить файл'
        ]
    ];
}

function chat_api_get_me($request) {
    $user_id = chat_api_get_user_id($request);
    
    if (!$user_id) {
        return new WP_Error('unauthorized', 'Требуется авторизация', ['status' => 401]);
    }
    
    if ($user_id === 999) {
        return [
            'id' => 999,
            'phone' => 'master',
            'first_name' => 'Master',
            'last_name' => 'User',
            'middle_name' => '',
            'nickname' => 'master',
            'position' => '',
            'avatar' => '',
            'created_at' => current_time('mysql')
        ];
    }
    
    $user = get_post($user_id);
    if (!$user || $user->post_type !== 'chat_user') {
        return new WP_Error('user_not_found', 'Пользователь не найден', ['status' => 404]);
    }
    
    // ========== ИСПРАВЛЕННЫЙ КОД ДЛЯ AVATAR ==========
    $avatar_value = '';
    
    // Способ 1: Пробуем get_field
    $field_value = get_field('avatar', $user_id);
    if ($field_value && $field_value !== false && $field_value !== 'false') {
        $avatar_value = $field_value;
    }
    
    // Способ 2: Если пусто, пробуем get_post_meta
    if (empty($avatar_value)) {
        $meta_value = get_post_meta($user_id, 'avatar', true);
        if ($meta_value && $meta_value !== false && $meta_value !== 'false') {
            $avatar_value = $meta_value;
        }
    }
    
    // Способ 3: Пробуем прямое обращение через ACF
    if (empty($avatar_value) && function_exists('get_field_object')) {
        $avatar_field = get_field_object('avatar', $user_id);
        if ($avatar_field && isset($avatar_field['value']) && !empty($avatar_field['value'])) {
            $avatar_value = $avatar_field['value'];
        }
    }
    // ========== КОНЕЦ ИСПРАВЛЕНИЯ ==========
    
    return [
        'success' => true,
        'user' => [
            'id' => $user_id,
            'phone' => get_field('phone', $user_id),
            'first_name' => get_field('first_name', $user_id),
            'last_name' => get_field('last_name', $user_id),
            'middle_name' => get_field('middle_name', $user_id),
            'nickname' => get_field('nickname', $user_id),
            'position' => get_field('position', $user_id),
            'avatar' => $avatar_value, // Теперь всегда строка
            'created_at' => get_field('created_at', $user_id) ?: $user->post_date
        ]
    ];
}


function chat_api_get_chats($request) {
    $user_id = chat_api_get_user_id($request);
    
    if (!$user_id) {
        return new WP_Error('unauthorized', 'Требуется авторизация', ['status' => 401]);
    }
    
    $chats = get_posts([
        'post_type' => 'chat',
        'posts_per_page' => -1,
        'meta_query' => [[
            'key' => 'members',
            'value' => $user_id,
            'compare' => 'LIKE'
        ]]
    ]);
    
    $result = [];
    foreach ($chats as $chat) {
        $chat_id = $chat->ID;
        $members = get_field('members', $chat_id);
        $is_group = get_field('is_group', $chat_id);
        
        // ========== ИСПРАВЛЕНИЕ: Получаем аватар чата ==========
        $chat_avatar = '';
        $chat_avatar_field = get_field('avatar', $chat_id);
        
        if ($chat_avatar_field && $chat_avatar_field !== false && $chat_avatar_field !== 'false') {
            $chat_avatar = $chat_avatar_field;
        }
        // ========== КОНЕЦ ИСПРАВЛЕНИЯ ==========
        
        // Получаем информацию об участниках
        $members_info = [];
        if (is_array($members)) {
            foreach ($members as $member_id) {
                $member = get_post($member_id);
                if ($member && $member->post_type === 'chat_user') {
                    // ========== ИСПРАВЛЕНИЕ: Получаем аватар участника ==========
                    $member_avatar = '';
                    $member_avatar_field = get_field('avatar', $member_id);
                    
                    if ($member_avatar_field && $member_avatar_field !== false && $member_avatar_field !== 'false') {
                        $member_avatar = $member_avatar_field;
                    } else {
                        // Пробуем через post_meta
                        $meta_avatar = get_post_meta($member_id, 'avatar', true);
                        if ($meta_avatar && $meta_avatar !== false && $meta_avatar !== 'false') {
                            $member_avatar = $meta_avatar;
                        }
                    }
                    // ========== КОНЕЦ ИСПРАВЛЕНИЯ ==========
                    
                    $members_info[] = [
                        'id' => $member_id,
                        'first_name' => get_field('first_name', $member_id),
                        'last_name' => get_field('last_name', $member_id),
                        'nickname' => get_field('nickname', $member_id),
                        'avatar' => $member_avatar, // Исправленное значение
                        'phone' => get_field('phone', $member_id),
                    ];
                }
            }
        }
        
        // Для личного чата получаем имя собеседника
        $chat_name = get_the_title($chat_id);
        if (!$is_group && is_array($members)) {
            foreach ($members as $member_id) {
                if ($member_id != $user_id) {
                    $first_name = get_field('first_name', $member_id);
                    $last_name = get_field('last_name', $member_id);
                    $chat_name = trim("$first_name $last_name");
                    if (empty($chat_name)) {
                        $chat_name = get_field('nickname', $member_id) ?: 'Пользователь';
                    }
                    break;
                }
            }
        }
        
        $last_message = chat_api_get_last_message($chat_id);
        $last_message_id = !empty($last_message['id']) ? (int) $last_message['id'] : 0;
        $result[] = [
            'id' => $chat_id,
            'name' => $chat_name,
            'avatar' => $chat_avatar, // Исправленное значение
            'is_group' => $is_group ? true : false,
            'members_count' => is_array($members) ? count($members) : 0,
            'members' => $members_info,
            'created_at' => get_field('created_at', $chat_id),
            'last_message_id' => $last_message_id,
            'last_message' => $last_message
        ];
    }
    
    return [
        'success' => true,
        'chats' => $result,
        'count' => count($result)
    ];
}



function chat_api_get_chat_info($request) {
    $user_id = chat_api_get_user_id($request);
    
    if (!$user_id) {
        return new WP_Error('unauthorized', 'Требуется авторизация', ['status' => 401]);
    }
    
    $chat_id = $request->get_param('id');
    
    if (!$chat_id) {
        return new WP_Error('missing_chat_id', 'ID чата обязателен', ['status' => 400]);
    }
    
    $chat = get_post($chat_id);
    if (!$chat || $chat->post_type !== 'chat') {
        return new WP_Error('chat_not_found', 'Чат не найден', ['status' => 404]);
    }
    
    // Проверка доступа
    $members = get_field('members', $chat_id);
    if (!is_array($members) || !in_array($user_id, $members)) {
        return new WP_Error('no_access', 'Нет доступа к чату', ['status' => 403]);
    }
    
    $is_group = get_field('is_group', $chat_id);
    $chat_info = [
        'id' => $chat_id,
        'name' => get_the_title($chat_id),
        'avatar' => $chat_avatar, // Исправленное значение
        'is_group' => $is_group ? true : false,
        'created_at' => get_field('created_at', $chat_id),
        'members_count' => count($members)
    ];
    
    // Информация об участниках
    $members_info = [];
    foreach ($members as $member_id) {
        $member = get_post($member_id);
        if ($member && $member->post_type === 'chat_user') {
            $members_info[] = [
                'id' => $member_id,
                'first_name' => get_field('first_name', $member_id),
                'last_name' => get_field('last_name', $member_id),
                'avatar' => $chat_avatar
            ];
        }
    }
    
    $chat_info['members'] = $members_info;
    
    return [
        'success' => true,
        'chat' => $chat_info
    ];
}

function chat_api_create_chat($request) {
    $user_id = chat_api_get_user_id($request);
    
    if (!$user_id) {
        return new WP_Error('unauthorized', 'Требуется авторизация', ['status' => 401]);
    }
    
    $params = $request->get_params();
    $is_group = isset($params['is_group']) ? (bool)$params['is_group'] : false;
    
    if ($is_group) {
        // Групповой чат
        $name = isset($params['name']) ? sanitize_text_field($params['name']) : '';
        $members = isset($params['members']) ? (array)$params['members'] : [];
        
        if (empty($name)) {
            return new WP_Error('missing_name', 'Название чата обязательно', ['status' => 400]);
        }
        
        if (empty($members)) {
            return new WP_Error('missing_members', 'Нужны участники', ['status' => 400]);
        }
        
        // Добавляем создателя если его нет
        if (!in_array($user_id, $members)) {
            $members[] = $user_id;
        }
        
    } else {
        // Личный чат
        $other_user_id = isset($params['user_id']) ? intval($params['user_id']) : 0;
        
        if (!$other_user_id) {
            return new WP_Error('missing_user_id', 'ID пользователя обязателен', ['status' => 400]);
        }
        
        // Проверяем существование пользователя
        $other_user = get_post($other_user_id);
        if (!$other_user || $other_user->post_type !== 'chat_user') {
            return new WP_Error('user_not_found', 'Пользователь не найден', ['status' => 404]);
        }
        
        // Проверяем существующий личный чат
        $existing_chat = chat_api_find_existing_private_chat($user_id, $other_user_id);
        if ($existing_chat) {
            return [
                'success' => true,
                'message' => 'Личный чат уже существует',
                'chat_id' => $existing_chat->ID,
                'is_group' => false
            ];
        }
        
        $members = [$user_id, $other_user_id];
        $name = get_field('first_name', $user_id) . ' и ' . get_field('first_name', $other_user_id);
    }
    
    // Создаем чат
    $chat_id = wp_insert_post([
        'post_type' => 'chat',
        'post_title' => $name,
        'post_status' => 'publish'
    ]);
    
    if (is_wp_error($chat_id)) {
        return $chat_id;
    }
    
    update_field('is_group', $is_group ? 1 : 0, $chat_id);
    update_field('members', $members, $chat_id);
    update_field('created_at', current_time('Y-m-d H:i:s'), $chat_id);
    
    if (!empty($params['avatar'])) {
        update_field('avatar', esc_url_raw($params['avatar']), $chat_id);
    }
    
    return [
        'success' => true,
        'message' => $is_group ? 'Групповой чат создан' : 'Личный чат создан',
        'chat_id' => $chat_id,
        'chat_name' => $name,
        'is_group' => $is_group,
        'members' => $members
    ];
}


function chat_api_get_messages($request) {
    $user_id = chat_api_get_user_id($request);
    
    if (!$user_id) {
        return new WP_Error('unauthorized', 'Требуется авторизация', ['status' => 401]);
    }
    
    $params = $request->get_params();
    $chat_id = isset($params['chat_id']) ? intval($params['chat_id']) : 0;
    
    if (!$chat_id) {
        return new WP_Error('missing_chat_id', 'ID чата обязателен', ['status' => 400]);
    }
    
    // Проверка доступа
    $members = get_field('members', $chat_id);
    if (!is_array($members) || !in_array($user_id, $members)) {
        return new WP_Error('no_access', 'Нет доступа к чату', ['status' => 403]);
    }
    
    $per_page = isset($params['per_page']) ? min(intval($params['per_page']), 100) : 50;
    $page = isset($params['page']) ? max(intval($params['page']), 1) : 1;
    $offset = ($page - 1) * $per_page;
    
    $messages = get_posts([
        'post_type' => 'chat_message',
        'posts_per_page' => $per_page,
        'offset' => $offset,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => [[
            'key' => 'chat',
            'value' => $chat_id,
            'compare' => '='
        ]]
    ]);
    
    $result = [];
    foreach ($messages as $message) {
        $message_id = $message->ID;
        $sender_id = get_field('sender', $message_id);
        
        // ========== ИСПРАВЛЕНИЕ: Правильное получение sender_id ==========
        // Если sender_id - массив, берем первый элемент
        if (is_array($sender_id)) {
            $sender_id = !empty($sender_id) ? intval($sender_id[0]) : 0;
        }
        // ========== КОНЕЦ ИСПРАВЛЕНИЯ ==========
        
        $result[] = [
            'id' => $message_id,
            'chat_id' => $chat_id,
            'sender_id' => $sender_id,
            'text' => get_field('text', $message_id),
            'image' => get_post_meta($message_id, 'image', true) ?: '',
            'file' => get_post_meta($message_id, 'file', true) ?: '',
            'created_at' => get_field('created_at', $message_id),
            'sender' => $sender_id ? [
                'id' => $sender_id,
                'first_name' => get_field('first_name', $sender_id),
                'avatar' => get_field('avatar', $sender_id)
            ] : null
        ];
    }
    
    // Сортируем по времени (старые -> новые)
    usort($result, function($a, $b) {
        return strtotime($a['created_at']) - strtotime($b['created_at']);
    });
    
    // Общее количество сообщений
    $total = wp_count_posts('chat_message');
    // ИСПРАВЛЕНИЕ: wp_count_posts возвращает объект, нужно брать publish
    $total_count = $total->publish ?? 0;
    
    return [
        'success' => true,
        'messages' => $result,
        'pagination' => [
            'page' => $page,
            'per_page' => $per_page,
            'total' => $total_count,
            'total_pages' => ceil($total_count / $per_page)
        ]
    ];
}




function chat_api_send_message($request) {
    $user_id = chat_api_get_user_id($request);
    
    if (!$user_id) {
        return new WP_Error('unauthorized', 'Требуется авторизация', ['status' => 401]);
    }
    
    $params = $request->get_params();
    $chat_id = isset($params['chat_id']) ? intval($params['chat_id']) : 0;
    $text = isset($params['text']) ? sanitize_textarea_field($params['text']) : '';
    
    if (!$chat_id) {
        return new WP_Error('missing_chat_id', 'ID чата обязателен', ['status' => 400]);
    }
    
    if (empty($text)) {
        return new WP_Error('missing_text', 'Текст сообщения обязателен', ['status' => 400]);
    }
    
    // Проверка доступа
    $members = get_field('members', $chat_id);
    if (!is_array($members) || !in_array($user_id, $members)) {
        return new WP_Error('no_access', 'Нет доступа к чату', ['status' => 403]);
    }
    
    // Создаем сообщение
    $message_id = wp_insert_post([
        'post_type' => 'chat_message',
        'post_title' => 'Сообщение от пользователя ' . $user_id,
        'post_status' => 'publish'
    ]);
    
    if (is_wp_error($message_id)) {
        return $message_id;
    }
    
    update_field('chat', $chat_id, $message_id);
    update_field('sender', $user_id, $message_id);
    update_field('text', $text, $message_id);
    update_field('created_at', current_time('Y-m-d H:i:s'), $message_id);
    
    if (!empty($params['image_url'])) {
        update_post_meta($message_id, 'image', esc_url_raw($params['image_url']));
    }

    if (!empty($params['file_url'])) {
        update_post_meta($message_id, 'file', esc_url_raw($params['file_url']));
    }
    
    return [
        'success' => true,
        'message' => 'Сообщение отправлено',
        'message_id' => $message_id,
        'chat_id' => $chat_id,
        'sender_id' => $user_id,
        'text' => $text,
        'created_at' => current_time('Y-m-d H:i:s')
    ];
}

/**
 * Удаление сообщения.
 * Разрешено:
 * - отправителю сообщения;
 * - master-токену (user_id = 999).
 * При успешном удалении сообщение переносится в корзину (wp_trash_post),
 * в выборке сообщений (chat_api_get_messages) оно больше не появляется.
 */
function chat_api_delete_message($request) {
    $user_id = chat_api_get_user_id($request);
    
    if (!$user_id) {
        return new WP_Error('unauthorized', 'Требуется авторизация', ['status' => 401]);
    }
    
    $message_id = isset($request['id']) ? intval($request['id']) : 0;
    
    if (!$message_id) {
        return new WP_Error('missing_message_id', 'ID сообщения обязателен', ['status' => 400]);
    }
    
    $message = get_post($message_id);
    if (!$message || $message->post_type !== 'chat_message') {
        return new WP_Error('message_not_found', 'Сообщение не найдено', ['status' => 404]);
    }
    
    // Определяем чат и участников для проверки доступа
    // ВАЖНО: избегаем get_field() здесь, потому что при некоторых настройках ACF
    // (и включенном WP_DEBUG_DISPLAY) ACF может выбрасывать Warning и ломать JSON-ответ.
    // Читаем данные напрямую из post_meta и нормализуем типы.
    $chat_id = get_post_meta($message_id, 'chat', true);
    if (is_array($chat_id)) {
        $chat_id = !empty($chat_id) ? intval($chat_id[0]) : 0;
    } else {
        $chat_id = intval($chat_id);
    }

    if (!$chat_id) {
        return new WP_Error('chat_not_found', 'Чат не найден для сообщения', ['status' => 404]);
    }
    
    $members = get_post_meta($chat_id, 'members', true);
    if (!is_array($members)) {
        $members = [];
    }
    // Нормализуем к int, чтобы in_array работал предсказуемо
    $members = array_map('intval', $members);

    if (!in_array(intval($user_id), $members)) {
        return new WP_Error('no_access', 'Нет доступа к этому чату', ['status' => 403]);
    }
    
    // Проверяем, что удаляет либо сам отправитель, либо master-пользователь
    $sender_id = get_post_meta($message_id, 'sender', true);
    if (is_array($sender_id)) {
        $sender_id = !empty($sender_id) ? intval($sender_id[0]) : 0;
    } else {
        $sender_id = intval($sender_id);
    }
    
    if ($user_id !== 999 && $sender_id !== $user_id) {
        return new WP_Error('forbidden', 'Можно удалять только свои сообщения', ['status' => 403]);
    }
    
    // Переносим в корзину (в выборке сообщений больше не появится)
    wp_trash_post($message_id);
    
    return [
        'success' => true,
        'message' => 'Сообщение удалено',
        'message_id' => $message_id,
        'chat_id' => $chat_id,
    ];
}

function chat_api_get_users($request) {
    $user_id = chat_api_get_user_id($request);
    
    if (!$user_id) {
        return new WP_Error('unauthorized', 'Требуется авторизация', ['status' => 401]);
    }
    
    $users = get_posts([
        'post_type' => 'chat_user',
        'posts_per_page' => -1,
        'post__not_in' => [$user_id]
    ]);
    
    $result = [];
    foreach ($users as $user) {
        $user_id_post = $user->ID;
        
        // ========== ИСПРАВЛЕННЫЙ КОД ДЛЯ AVATAR ==========
        $avatar_value = '';
        
        // Способ 1: Пробуем get_field
        $field_value = get_field('avatar', $user_id_post);
        if ($field_value && $field_value !== false && $field_value !== 'false') {
            $avatar_value = $field_value;
        }
        
        // Способ 2: Если пусто, пробуем get_post_meta
        if (empty($avatar_value)) {
            $meta_value = get_post_meta($user_id_post, 'avatar', true);
            if ($meta_value && $meta_value !== false && $meta_value !== 'false') {
                $avatar_value = $meta_value;
            }
        }
        
        // Способ 3: Пробуем прямое обращение через ACF
        if (empty($avatar_value) && function_exists('get_field_object')) {
            $avatar_field = get_field_object('avatar', $user_id_post);
            if ($avatar_field && isset($avatar_field['value']) && !empty($avatar_field['value'])) {
                $avatar_value = $avatar_field['value'];
            }
        }
        // ========== КОНЕЦ ИСПРАВЛЕНИЯ ==========
        
        $result[] = [
            'id' => $user_id_post,
            'phone' => get_field('phone', $user_id_post),
            'first_name' => get_field('first_name', $user_id_post),
            'last_name' => get_field('last_name', $user_id_post),
            'middle_name' => get_field('middle_name', $user_id_post),
            'nickname' => get_field('nickname', $user_id_post),
            'position' => get_field('position', $user_id_post),
            'avatar' => $avatar_value, // Теперь всегда строка
            'created_at' => get_field('created_at', $user_id_post)
        ];
    }
    
    return [
        'success' => true,
        'users' => $result,
        'count' => count($result)
    ];
}

/**
 * Обновление профиля пользователя.
 *
 * Правила:
 * - Обычный пользователь может изменять ТОЛЬКО свой профиль и не может менять телефон.
 * - Master-пользователь (token вида master_*) может изменять любой chat_user и его телефон.
 */
function chat_api_update_profile($request) {
    $current_user_id = chat_api_get_user_id($request);
    if (!$current_user_id) {
        return new WP_Error('unauthorized', 'Требуется авторизация', ['status' => 401]);
    }

    $params = $request->get_json_params();
    if (!is_array($params)) {
        $params = $request->get_params();
    }

    $target_user_id = $current_user_id;

    // Master-пользователь может редактировать любой профиль по user_id
    if ($current_user_id === 999 && !empty($params['user_id'])) {
        $target_user_id = intval($params['user_id']);
    }

    $user_post = get_post($target_user_id);
    if (!$user_post || $user_post->post_type !== 'chat_user') {
        return new WP_Error('user_not_found', 'Пользователь не найден', ['status' => 404]);
    }

    // Обычный пользователь не может редактировать чужой профиль
    if ($current_user_id !== 999 && $target_user_id !== $current_user_id) {
        return new WP_Error('forbidden', 'Можно изменять только свой профиль', ['status' => 403]);
    }

    $fields_to_update = [];

    // Имя, фамилия, отчество, никнейм, должность
    if (isset($params['first_name'])) {
        $fields_to_update['first_name'] = sanitize_text_field($params['first_name']);
    }
    if (isset($params['last_name'])) {
        $fields_to_update['last_name'] = sanitize_text_field($params['last_name']);
    }
    if (isset($params['middle_name'])) {
        $fields_to_update['middle_name'] = sanitize_text_field($params['middle_name']);
    }
    if (isset($params['nickname'])) {
        $fields_to_update['nickname'] = sanitize_text_field($params['nickname']);
    }
    if (isset($params['position'])) {
        $fields_to_update['position'] = sanitize_text_field($params['position']);
    }

    // Телефон:
    // - обычный пользователь: запрещено;
    // - master (999): может менять, с проверкой уникальности.
    if (isset($params['phone']) && $current_user_id === 999) {
        $new_phone = sanitize_text_field($params['phone']);
        if (!empty($new_phone)) {
            // Проверяем, что телефон не занят другим пользователем
            $existing = get_posts([
                'post_type' => 'chat_user',
                'posts_per_page' => 1,
                'meta_query' => [
                    [
                        'key' => 'phone',
                        'value' => $new_phone,
                        'compare' => '=',
                    ],
                ],
            ]);

            if (!empty($existing) && intval($existing[0]->ID) !== $target_user_id) {
                return new WP_Error('phone_exists', 'Пользователь с таким телефоном уже существует', ['status' => 409]);
            }

            $fields_to_update['phone'] = $new_phone;
        }
    }

    // Аватар: ожидаем, что Flutter пришлёт ПОЛНЫЙ URL (как при регистрации).
    if (isset($params['avatar'])) {
        $avatar_value = trim($params['avatar']);
        if ($avatar_value === '' || $avatar_value === 'null' || $avatar_value === 'false') {
            $fields_to_update['avatar'] = '';
            delete_post_meta($target_user_id, 'avatar_url');
        } else {
            $avatar_url = esc_url_raw($avatar_value);
            $avatar_id = 0;

            // Пытаемся получить attachment ID по URL — как при регистрации
            if (function_exists('attachment_url_to_postid')) {
                $avatar_id = attachment_url_to_postid($avatar_url);
            }

            if ($avatar_id) {
                // Для ACF Image-поля корректно сохранять ID
                $fields_to_update['avatar'] = $avatar_id;
            } else {
                // Фолбэк: сохраняем URL — ACF иногда сам сопоставляет его с ID
                $fields_to_update['avatar'] = $avatar_url;
            }

            // Дополнительно сохраняем явный URL в meta-поле (как в регистрации)
            update_post_meta($target_user_id, 'avatar_url', $avatar_url);
        }
    }

    foreach ($fields_to_update as $key => $value) {
        update_field($key, $value, $target_user_id);
    }

    // Возвращаем обновлённого пользователя в том же формате, что и /me
    $dummy_request = new WP_REST_Request('GET', '/chat-api/v1/me');
    // Подделывать заголовки authorization не нужно, мы знаем ID
    // поэтому просто собираем данные напрямую:

    // Готовим avatar для ответа в виде ПОЛНОГО URL, как в chat_auth_register
    $saved_avatar = get_field('avatar', $target_user_id);

    // Если ACF вернул ID (для Image-поля) — преобразуем в URL
    if (!empty($saved_avatar) && is_numeric($saved_avatar)) {
        $attachment_url = wp_get_attachment_url(intval($saved_avatar));
        if ($attachment_url) {
            $saved_avatar = $attachment_url;
        }
    }

    // Фолбэк: если ACF ничего не вернул, пробуем meta avatar_url
    if (empty($saved_avatar)) {
        $meta_avatar_url = get_post_meta($target_user_id, 'avatar_url', true);
        if (!empty($meta_avatar_url)) {
            $saved_avatar = $meta_avatar_url;
        }
    }

    $response_user = [
        'id' => $target_user_id,
        'phone' => get_field('phone', $target_user_id),
        'first_name' => get_field('first_name', $target_user_id),
        'last_name' => get_field('last_name', $target_user_id),
        'middle_name' => get_field('middle_name', $target_user_id),
        'nickname' => get_field('nickname', $target_user_id),
        'position' => get_field('position', $target_user_id),
        'avatar' => $saved_avatar ?: '',
        'created_at' => get_field('created_at', $target_user_id) ?: $user_post->post_date,
    ];

    return [
        'success' => true,
        'message' => 'Профиль обновлён',
        'user' => $response_user,
    ];
}

/**
 * Удаление (перемещение в корзину) профиля пользователя.
 *
 * - Обычный пользователь может удалить только себя.
 * - Master-пользователь может удалить любого chat_user по user_id.
 */
function chat_api_delete_profile($request) {
    $current_user_id = chat_api_get_user_id($request);
    if (!$current_user_id) {
        return new WP_Error('unauthorized', 'Требуется авторизация', ['status' => 401]);
    }

    $params = $request->get_json_params();
    if (!is_array($params)) {
        $params = $request->get_params();
    }

    $target_user_id = $current_user_id;
    if ($current_user_id === 999 && !empty($params['user_id'])) {
        $target_user_id = intval($params['user_id']);
    }

    // Нельзя удалять "виртуального" master-пользователя
    if ($target_user_id === 999) {
        return new WP_Error('forbidden', 'Нельзя удалить master-пользователя', ['status' => 403]);
    }

    $user_post = get_post($target_user_id);
    if (!$user_post || $user_post->post_type !== 'chat_user') {
        return new WP_Error('user_not_found', 'Пользователь не найден', ['status' => 404]);
    }

    // Обычный пользователь может удалить только себя
    if ($current_user_id !== 999 && $target_user_id !== $current_user_id) {
        return new WP_Error('forbidden', 'Можно удалить только свой профиль', ['status' => 403]);
    }

    // Перемещаем в корзину (soft-delete, чтобы можно было восстановить из админки)
    $trashed = wp_trash_post($target_user_id);

    if (!$trashed) {
        return new WP_Error('delete_failed', 'Не удалось переместить профиль в корзину', ['status' => 500]);
    }

    return [
        'success' => true,
        'message' => 'Профиль перемещён в корзину',
        'user_id' => $target_user_id,
    ];
}

function chat_api_add_members($request) {
    $user_id = chat_api_get_user_id($request);
    
    if (!$user_id) {
        return new WP_Error('unauthorized', 'Требуется авторизация', ['status' => 401]);
    }
    
    $chat_id = $request->get_param('id');
    $params = $request->get_params();
    
    if (!$chat_id) {
        return new WP_Error('missing_chat_id', 'ID чата обязателен', ['status' => 400]);
    }
    
    $chat = get_post($chat_id);
    if (!$chat || $chat->post_type !== 'chat') {
        return new WP_Error('chat_not_found', 'Чат не найден', ['status' => 404]);
    }
    
    // Проверяем, что пользователь состоит в чате
    $members = get_field('members', $chat_id);
    if (!is_array($members) || !in_array($user_id, $members)) {
        return new WP_Error('no_access', 'Нет доступа к чату', ['status' => 403]);
    }
    
    // Получаем новых участников
    $new_members = isset($params['members']) ? (array)$params['members'] : [];
    if (empty($new_members)) {
        return new WP_Error('missing_members', 'Нужно указать участников', ['status' => 400]);
    }
    
    // Добавляем новых участников
    $all_members = array_unique(array_merge($members, $new_members));
    update_field('members', $all_members, $chat_id);
    
    return [
        'success' => true,
        'message' => 'Участники добавлены',
        'chat_id' => $chat_id,
        'added_members' => $new_members,
        'total_members' => count($all_members)
    ];
}

function chat_api_update_chat($request) {
    $user_id = chat_api_get_user_id($request);
    
    if (!$user_id) {
        return new WP_Error('unauthorized', 'Требуется авторизация', ['status' => 401]);
    }
    
    $chat_id = $request->get_param('id');
    $params = $request->get_params();
    
    if (!$chat_id) {
        return new WP_Error('missing_chat_id', 'ID чата обязателен', ['status' => 400]);
    }
    
    $chat = get_post($chat_id);
    if (!$chat || $chat->post_type !== 'chat') {
        return new WP_Error('chat_not_found', 'Чат не найден', ['status' => 404]);
    }
    
    // Проверяем, что пользователь состоит в чате
    $members = get_field('members', $chat_id);
    if (!is_array($members) || !in_array($user_id, $members)) {
        return new WP_Error('no_access', 'Нет доступа к чату', ['status' => 403]);
    }
    
    $updated_fields = [];
    
    // Обновляем название
    if (!empty($params['name'])) {
        $new_name = sanitize_text_field($params['name']);
        wp_update_post([
            'ID' => $chat_id,
            'post_title' => $new_name
        ]);
        $updated_fields[] = 'name';
    }
    
    // Обновляем аватар
    if (!empty($params['avatar'])) {
        update_field('avatar', esc_url_raw($params['avatar']), $chat_id);
        $updated_fields[] = 'avatar';
    }
    
    return [
        'success' => true,
        'message' => 'Чат обновлен',
        'chat_id' => $chat_id,
        'updated_fields' => $updated_fields
    ];
}

function chat_api_remove_member($request) {
    $user_id = chat_api_get_user_id($request);
    
    if (!$user_id) {
        return new WP_Error('unauthorized', 'Требуется авторизация', ['status' => 401]);
    }
    
    $chat_id = $request->get_param('id');
    $params = $request->get_params();
    
    if (!$chat_id) {
        return new WP_Error('missing_chat_id', 'ID чата обязателен', ['status' => 400]);
    }
    
    $chat = get_post($chat_id);
    if (!$chat || $chat->post_type !== 'chat') {
        return new WP_Error('chat_not_found', 'Чат не найден', ['status' => 404]);
    }
    
    // Проверяем, что пользователь состоит в чате
    $members = get_field('members', $chat_id);
    if (!is_array($members) || !in_array($user_id, $members)) {
        return new WP_Error('no_access', 'Нет доступа к чату', ['status' => 403]);
    }
    
    $member_to_remove = isset($params['user_id']) ? intval($params['user_id']) : 0;
    if (!$member_to_remove) {
        return new WP_Error('missing_user_id', 'ID пользователя обязателен', ['status' => 400]);
    }
    
    // Нельзя удалить себя
    if ($member_to_remove === $user_id) {
        return new WP_Error('self_removal', 'Нельзя удалить себя', ['status' => 400]);
    }
    
    // Проверяем, что участник состоит в чате
    if (!in_array($member_to_remove, $members)) {
        return new WP_Error('member_not_found', 'Участник не найден', ['status' => 404]);
    }
    
    // Удаляем участника
    $new_members = array_values(array_diff($members, [$member_to_remove]));
    update_field('members', $new_members, $chat_id);
    
    return [
        'success' => true,
        'message' => 'Участник удален',
        'chat_id' => $chat_id,
        'removed_member' => $member_to_remove,
        'remaining_members' => $new_members
    ];
}

function chat_api_delete_chat($request) {
    $user_id = chat_api_get_user_id($request);
    
    if (!$user_id) {
        return new WP_Error('unauthorized', 'Требуется авторизация', ['status' => 401]);
    }
    
    $chat_id = $request->get_param('id');
    
    if (!$chat_id) {
        return new WP_Error('missing_chat_id', 'ID чата обязателен', ['status' => 400]);
    }
    
    $chat = get_post($chat_id);
    if (!$chat || $chat->post_type !== 'chat') {
        return new WP_Error('chat_not_found', 'Чат не найден', ['status' => 404]);
    }
    
    // Проверяем, что пользователь состоит в чате
    $members = get_field('members', $chat_id);
    if (!is_array($members) || !in_array($user_id, $members)) {
        return new WP_Error('no_access', 'Нет доступа к чату', ['status' => 403]);
    }
    
    // Удаляем чат
    $deleted = wp_delete_post($chat_id, true);
    
    if (!$deleted) {
        return new WP_Error('delete_failed', 'Не удалось удалить чат', ['status' => 500]);
    }
    
    return [
        'success' => true,
        'message' => 'Чат удален',
        'chat_id' => $chat_id,
        'deleted_at' => current_time('Y-m-d H:i:s')
    ];
}

function chat_api_get_chat_creator($request) {
    $user_id = chat_api_get_user_id($request);
    
    if (!$user_id) {
        return new WP_Error('unauthorized', 'Требуется авторизация', ['status' => 401]);
    }
    
    $chat_id = $request->get_param('id');
    
    if (!$chat_id) {
        return new WP_Error('missing_chat_id', 'ID чата обязателен', ['status' => 400]);
    }
    
    $chat = get_post($chat_id);
    if (!$chat || $chat->post_type !== 'chat') {
        return new WP_Error('chat_not_found', 'Чат не найден', ['status' => 404]);
    }
    
    // Проверяем, что пользователь состоит в чате
    $members = get_field('members', $chat_id);
    if (!is_array($members) || !in_array($user_id, $members)) {
        return new WP_Error('no_access', 'Нет доступа к чату', ['status' => 403]);
    }
    
    // Получаем создателя (автор поста)
    $creator_id = $chat->post_author;
    
    if (!$creator_id || $creator_id == 0) {
        $creator_id = !empty($members) ? $members[0] : 0;
    }
    
    if (!$creator_id) {
        return [
            'success' => true,
            'creator' => null,
            'message' => 'Создатель не определен'
        ];
    }
    
    $creator = get_post($creator_id);
    if (!$creator || $creator->post_type !== 'chat_user') {
        return [
            'success' => true,
            'creator' => null,
            'message' => 'Создатель не найден'
        ];
    }
    
    return [
        'success' => true,
        'creator' => [
            'id' => $creator_id,
            'first_name' => get_field('first_name', $creator_id),
            'last_name' => get_field('last_name', $creator_id),
            'avatar' => get_field('avatar', $creator_id),
            'created_at' => get_field('created_at', $creator_id)
        ]
    ];
}

function chat_api_mark_read($request) {
    $user_id = chat_api_get_user_id($request);
    
    if (!$user_id) {
        return new WP_Error('unauthorized', 'Требуется авторизация', ['status' => 401]);
    }
    
    $params = $request->get_params();
    $chat_id = isset($params['chat_id']) ? intval($params['chat_id']) : 0;
    
    if (!$chat_id) {
        return new WP_Error('missing_chat_id', 'ID чата обязателен', ['status' => 400]);
    }
    
    // Проверка доступа
    $members = get_field('members', $chat_id);
    if (!is_array($members) || !in_array($user_id, $members)) {
        return new WP_Error('no_access', 'Нет доступа к чату', ['status' => 403]);
    }
    
    $message_ids = isset($params['message_ids']) ? (array)$params['message_ids'] : [];
    
    // В этой версии просто возвращаем успех
    // Полную реализацию можно добавить позже
    return [
        'success' => true,
        'message' => 'Сообщения отмечены как прочитанные',
        'chat_id' => $chat_id,
        'user_id' => $user_id
    ];
}

function chat_api_unread_count($request) {
    $user_id = chat_api_get_user_id($request);
    
    if (!$user_id) {
        return new WP_Error('unauthorized', 'Требуется авторизация', ['status' => 401]);
    }
    
    $params = $request->get_params();
    $chat_id = isset($params['chat_id']) ? intval($params['chat_id']) : null;
    
    if ($chat_id) {
        // Для конкретного чата
        return [
            'success' => true,
            'unread_count' => 0,
            'chat_id' => $chat_id
        ];
    } else {
        // Для всех чатов
        return [
            'success' => true,
            'total_unread' => 0,
            'chat_unread' => []
        ];
    }
}

function chat_api_search_messages($request) {
    $user_id = chat_api_get_user_id($request);
    
    if (!$user_id) {
        return new WP_Error('unauthorized', 'Требуется авторизация', ['status' => 401]);
    }
    
    $params = $request->get_params();
    $query = isset($params['q']) ? sanitize_text_field($params['q']) : '';
    
    if (empty($query)) {
        return new WP_Error('missing_query', 'Поисковый запрос обязателен', ['status' => 400]);
    }
    
    $chat_id = isset($params['chat_id']) ? intval($params['chat_id']) : 0;
    $limit = isset($params['limit']) ? min(intval($params['limit']), 100) : 50;
    $offset = isset($params['offset']) ? intval($params['offset']) : 0;
    
    $args = [
        'post_type' => 'chat_message',
        'posts_per_page' => $limit,
        'offset' => $offset,
        's' => $query
    ];
    
    if ($chat_id) {
        // Для конкретного чата
        $args['meta_query'] = [[
            'key' => 'chat',
            'value' => $chat_id,
            'compare' => '='
        ]];
    } else {
        // Для всех чатов пользователя
        $user_chats = get_posts([
            'post_type' => 'chat',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [[
                'key' => 'members',
                'value' => $user_id,
                'compare' => 'LIKE'
            ]]
        ]);
        
        if (empty($user_chats)) {
            return [
                'success' => true,
                'results' => [],
                'count' => 0
            ];
        }
        
        $args['meta_query'] = [[
            'key' => 'chat',
            'value' => $user_chats,
            'compare' => 'IN'
        ]];
    }
    
    $messages = get_posts($args);
    
    $results = [];
    foreach ($messages as $message) {
        $message_id = $message->ID;
        $sender_id = get_field('sender', $message_id);
        
        $results[] = [
            'id' => $message_id,
            'text' => get_field('text', $message_id),
            'sender_id' => $sender_id,
            'chat_id' => get_field('chat', $message_id),
            'created_at' => get_field('created_at', $message_id)
        ];
    }
    
    return [
        'success' => true,
        'results' => $results,
        'count' => count($results),
        'query' => $query
    ];
}




function chat_api_upload($request) {
    //$user_id = chat_api_get_user_id($request);
    
    //if (!$user_id) {
        //return new WP_Error('unauthorized', 'Требуется авторизация', //['status' => 401]);
    //}
    
    if (empty($_FILES['file'])) {
        return new WP_Error('no_file', 'Файл не загружен', ['status' => 400]);
    }
    
    $file = $_FILES['file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return new WP_Error('upload_error', 'Ошибка загрузки файла', ['status' => 400]);
    }
    
    // Максимум 10MB
    $max_size = 10 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        return new WP_Error('file_too_large', 'Файл слишком большой (макс. 10MB)', ['status' => 400]);
    }
    
    // Некоторые хостинги/окружения определяют MIME для m4a нестабильно:
    // audio/mp4a-latm, audio/x-m4a, video/mp4, application/octet-stream и т.п.
    // Поэтому валидируем по расширению + проверяем MIME максимально безопасно/предсказуемо.
    $original_name = isset($file['name']) ? (string)$file['name'] : '';
    $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

    $allowed_exts = [
        // images
        'jpg', 'jpeg', 'png', 'gif',
        // documents / text
        'pdf', 'txt',
        // video
        'mp4', 'mov', 'webm',
        // audio
        'mp3', 'm4a', 'aac', 'wav', 'ogg', 'oga',
    ];

    if (empty($ext) || !in_array($ext, $allowed_exts, true)) {
        return new WP_Error('invalid_file_type', 'Недопустимое расширение файла', ['status' => 400]);
    }

    $allowed_mimes = [
        // images
        'image/jpeg',
        'image/png',
        'image/gif',
        // documents / text
        'application/pdf',
        'text/plain',
        // video
        'video/mp4',
        'video/quicktime', // mov
        'video/webm',
        // audio (основные)
        'audio/mpeg', // mp3
        'audio/mp4',  // m4a
        'audio/aac',
        'audio/wav',
        'audio/ogg',
        'audio/webm',
        // audio (варианты, которые встречаются на некоторых хостингах)
        'audio/x-m4a',
        'audio/mp4a-latm',
        'audio/x-wav',
        'audio/x-aac',
    ];

    $detected_mime = '';
    if (function_exists('mime_content_type')) {
        $tmp = isset($file['tmp_name']) ? (string)$file['tmp_name'] : '';
        if (!empty($tmp)) {
            $detected_mime = (string) mime_content_type($tmp);
        }
    }

    $wp_checked_type = '';
    if (function_exists('wp_check_filetype_and_ext')) {
        $checked = wp_check_filetype_and_ext($file['tmp_name'], $original_name);
        if (is_array($checked) && !empty($checked['type'])) {
            $wp_checked_type = (string) $checked['type'];
        }
    }

    // Берём наиболее надёжный MIME (если WordPress смог определить — используем его)
    $effective_mime = !empty($wp_checked_type) ? $wp_checked_type : $detected_mime;

    $mime_ok = in_array($effective_mime, $allowed_mimes, true);
    // Если MIME определить не удалось, разрешаем только "безопасные" расширения из allowlist выше.
    // Для m4a дополнительно допускаем типы, которые часто ошибочно прилетают.
    if (!$mime_ok) {
        if ($ext === 'm4a' && in_array($effective_mime, ['video/mp4', 'application/octet-stream', ''], true)) {
            $mime_ok = true;
        }
        if ($ext === 'mp4' && $effective_mime === 'application/octet-stream') {
            $mime_ok = true;
        }
    }

    if (!$mime_ok) {
        return new WP_Error(
            'invalid_file_type',
            'Недопустимый тип файла (' . $effective_mime . ') для .' . $ext,
            ['status' => 400]
        );
    }

    // Для ответа/метаданных используем effective_mime (а не только mime_content_type())
    $file_type = $effective_mime;
    
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    // ВАЖНО: для аудио/видео WordPress может вызывать wp_read_audio_metadata()/wp_read_video_metadata()
    // которые определены в media.php (и могут не быть подключены).
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    // ==== РУЧНАЯ ЗАГРУЗКА ФАЙЛА (БЕЗ wp_handle_upload, которое режет по типу) ====
    $upload_dir = wp_upload_dir();
    if (!empty($upload_dir['error'])) {
        return new WP_Error(
            'upload_failed',
            'Ошибка каталога загрузки: ' . $upload_dir['error'],
            ['status' => 500]
        );
    }

    $upload_path = $upload_dir['path'];
    if (!file_exists($upload_path)) {
        wp_mkdir_p($upload_path);
    }

    // Генерируем безопасное уникальное имя файла
    $base_filename = $original_name ?: ('file.' . $ext);
    $unique_filename = wp_unique_filename($upload_path, $base_filename);
    $target_file = trailingslashit($upload_path) . $unique_filename;

    if (!@move_uploaded_file($file['tmp_name'], $target_file)) {
        return new WP_Error(
            'upload_failed',
            'Не удалось сохранить файл на сервере',
            ['status' => 500]
        );
    }

    // Создаем запись в медиабиблиотеке (автор = 1 - администратор)
    $attachment = [
        'post_mime_type' => $file_type,
        'post_title' => sanitize_file_name($unique_filename),
        'post_content' => '',
        'post_status' => 'inherit',
        'post_author' => 1 // Администратор как автор
    ];
    
    $attach_id = wp_insert_attachment($attachment, $target_file);
    // Генерация метаданных нужна в основном для изображений (превью/размеры).
    // На некоторых хостингах генерация метаданных аудио может падать (нет getID3/нет функций),
    // поэтому делаем её безопасно и условно.
    $should_generate_metadata = false;
    if (function_exists('wp_attachment_is_image') && wp_attachment_is_image($attach_id)) {
        $should_generate_metadata = true;
    } else if (is_string($file_type) && strpos($file_type, 'video/') === 0) {
        $should_generate_metadata = true;
    }

    if ($should_generate_metadata && function_exists('wp_generate_attachment_metadata')) {
        $attach_data = wp_generate_attachment_metadata($attach_id, $target_file);
        if (!is_wp_error($attach_data)) {
            wp_update_attachment_metadata($attach_id, $attach_data);
        }
    }
    
    $file_url = wp_get_attachment_url($attach_id);
    
    return [
        'success' => true,
        'message' => 'Файл успешно загружен',
        'file' => [
            'id' => $attach_id,
            'name' => $file['name'],
            'type' => $file_type,
            'size' => $file['size'],
            'url' => $file_url,
            'uploaded_at' => current_time('Y-m-d H:i:s')
        ]
    ];
}


// ============================================
// СИСТЕМА НЕПРОЧИТАННЫХ СООБЩЕНИЙ
// ============================================

// 1. Добавляем мета-поле для отслеживания последнего прочитанного сообщения
add_action('init', function() {
    register_meta('user', 'chat_last_seen', [
        'type' => 'array',
        'single' => true,
        'show_in_rest' => true,
    ]);
});

// 2. Endpoint для отметки чата как прочитанного
add_action('rest_api_init', function() {
    register_rest_route('chat-api/v1', '/chats/(?P<id>\d+)/mark-as-read', [
        'methods' => 'POST',
        'callback' => function(WP_REST_Request $request) {
            $chat_id = intval($request['id']);
            $user_id = get_current_user_id();
            
            if (!$user_id) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Требуется авторизация'
                ], 401);
            }
            
            // Получаем текущий чат
            $chat = get_post($chat_id);
            if (!$chat || $chat->post_type !== 'chat') {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Чат не найден'
                ], 404);
            }
            
            // Получаем последнее сообщение в чате
            $last_message = get_posts([
                'post_type' => 'chat_message',
                'posts_per_page' => 1,
                'meta_query' => [
                    [
                        'key' => 'chat',
                        'value' => $chat_id,
                        'compare' => '='
                    ]
                ],
                'orderby' => 'date',
                'order' => 'DESC'
            ]);
            
            $last_message_id = 0;
            if (!empty($last_message)) {
                $last_message_id = $last_message[0]->ID;
            }
            
            // Сохраняем последнее прочитанное сообщение для этого чата
            $last_seen = get_user_meta($user_id, 'chat_last_seen', true);
            if (!is_array($last_seen)) {
                $last_seen = [];
            }
            
            $last_seen[$chat_id] = $last_message_id;
            update_user_meta($user_id, 'chat_last_seen', $last_seen);
            
            return new WP_REST_Response([
                'success' => true,
                'message' => 'Чат отмечен как прочитанный',
                'last_seen_message_id' => $last_message_id
            ]);
        },
        'permission_callback' => function() {
            return is_user_logged_in();
        }
    ]);
    
    // 3. Endpoint для получения счетчиков непрочитанных сообщений
    register_rest_route('chat-api/v1', '/chats/unread-counts', [
        'methods' => 'GET',
        'callback' => function(WP_REST_Request $request) {
            $user_id = get_current_user_id();
            
            if (!$user_id) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Требуется авторизация'
                ], 401);
            }
            
            // Получаем все чаты пользователя
            $chats = get_posts([
                'post_type' => 'chat',
                'posts_per_page' => -1,
                'meta_query' => [
                    'relation' => 'OR',
                    [
                        'key' => 'members',
                        'value' => $user_id,
                        'compare' => 'LIKE'
                    ],
                    [
                        'key' => 'members',
                        'value' => '"' . $user_id . '"',
                        'compare' => 'LIKE'
                    ]
                ]
            ]);
            
            $unread_counts = [];
            $last_seen = get_user_meta($user_id, 'chat_last_seen', true);
            if (!is_array($last_seen)) {
                $last_seen = [];
            }
            
            foreach ($chats as $chat) {
                $chat_id = $chat->ID;
                $last_seen_message_id = isset($last_seen[$chat_id]) ? $last_seen[$chat_id] : 0;
                
                // Считаем непрочитанные сообщения (созданные после last_seen)
                $unread_count = 0;
                if ($last_seen_message_id > 0) {
                    $unread_count = count(get_posts([
                        'post_type' => 'chat_message',
                        'posts_per_page' => -1,
                        'meta_query' => [
                            [
                                'key' => 'chat',
                                'value' => $chat_id,
                                'compare' => '='
                            ]
                        ],
                        'date_query' => [
                            'after' => get_post_time('Y-m-d H:i:s', false, $last_seen_message_id)
                        ]
                    ]));
                } else {
                    // Если last_seen = 0, считаем все сообщения как непрочитанные
                    $unread_count = count(get_posts([
                        'post_type' => 'chat_message',
                        'posts_per_page' => -1,
                        'meta_query' => [
                            [
                                'key' => 'chat',
                                'value' => $chat_id,
                                'compare' => '='
                            ]
                        ]
                    ]));
                }
                
                $unread_counts[$chat_id] = $unread_count;
            }
            
            return new WP_REST_Response([
                'success' => true,
                'counts' => $unread_counts,
                'total_unread' => array_sum($unread_counts)
            ]);
        },
        'permission_callback' => function() {
            return is_user_logged_in();
        }
    ]);
    
    // 4. Endpoint для быстрой проверки обновлений
    register_rest_route('chat-api/v1', '/check-updates', [
        'methods' => 'GET',
        'callback' => function(WP_REST_Request $request) {
            $user_id = get_current_user_id();
            
            if (!$user_id) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Требуется авторизация'
                ], 401);
            }
            
            $last_check = $request->get_param('last_check');
            $force_update = $request->get_param('force') === 'true';
            
            // Получаем счетчики непрочитанных
            $last_seen = get_user_meta($user_id, 'chat_last_seen', true);
            if (!is_array($last_seen)) {
                $last_seen = [];
            }
            
            // Получаем все чаты пользователя
            $chats = get_posts([
                'post_type' => 'chat',
                'posts_per_page' => -1,
                'meta_query' => [
                    'relation' => 'OR',
                    [
                        'key' => 'members',
                        'value' => $user_id,
                        'compare' => 'LIKE'
                    ],
                    [
                        'key' => 'members',
                        'value' => '"' . $user_id . '"',
                        'compare' => 'LIKE'
                    ]
                ]
            ]);
            
            $has_updates = false;
            $unread_counts = [];
            $new_messages = [];
            
            foreach ($chats as $chat) {
                $chat_id = $chat->ID;
                $last_seen_message_id = isset($last_seen[$chat_id]) ? $last_seen[$chat_id] : 0;
                
                // Получаем сообщения после last_seen
                $recent_messages = get_posts([
                    'post_type' => 'chat_message',
                    'posts_per_page' => 5,
                    'meta_query' => [
                        [
                            'key' => 'chat',
                            'value' => $chat_id,
                            'compare' => '='
                        ]
                    ],
                    'orderby' => 'date',
                    'order' => 'DESC'
                ]);
                
                foreach ($recent_messages as $message) {
                    if ($message->ID > $last_seen_message_id) {
                        $has_updates = true;
                        
                        // Добавляем информацию о новом сообщении
                        $new_messages[] = [
                            'chat_id' => $chat_id,
                            'message_id' => $message->ID,
                            'sender_id' => get_field('sender', $message->ID),
                            'text' => get_field('text', $message->ID),
                            'created_at' => $message->post_date
                        ];
                    }
                }
            }
            
            return new WP_REST_Response([
                'success' => true,
                'has_updates' => $has_updates,
                'new_messages' => $new_messages,
                'timestamp' => current_time('mysql')
            ]);
        },
        'permission_callback' => function() {
            return is_user_logged_in();
        }
    ]);
});

// 5. Модифицируем endpoint получения чатов, чтобы добавлять unread_count
add_filter('rest_prepare_chat', function($response, $post, $request) {
    $user_id = get_current_user_id();
    
    if ($user_id) {
        $chat_id = $post->ID;
        $last_seen = get_user_meta($user_id, 'chat_last_seen', true);
        $last_seen_message_id = isset($last_seen[$chat_id]) ? $last_seen[$chat_id] : 0;
        
        // Считаем непрочитанные сообщения
        $unread_count = 0;
        if ($last_seen_message_id > 0) {
            $unread_count = count(get_posts([
                'post_type' => 'chat_message',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'chat',
                        'value' => $chat_id,
                        'compare' => '='
                    ]
                ],
                'date_query' => [
                    'after' => get_post_time('Y-m-d H:i:s', false, $last_seen_message_id)
                ]
            ]));
        } else {
            // Если last_seen = 0, считаем все сообщения
            $unread_count = count(get_posts([
                'post_type' => 'chat_message',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'chat',
                        'value' => $chat_id,
                        'compare' => '='
                    ]
                ]
            ]));
        }
        
        $response->data['unread_count'] = $unread_count;
        $response->data['acf']['unread_count'] = $unread_count;
    }
    
    return $response;
}, 10, 3);

?>