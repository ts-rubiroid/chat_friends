<?php
/**
 * Регистрация Custom Post Types
 */

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
        'show_in_rest' => true,
        'rest_base' => 'chat-users',
        'has_archive' => false,
        'supports' => ['title', 'custom-fields'],
        'menu_icon' => 'dashicons-admin-users',
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
        'show_in_rest' => true,
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
        'show_in_rest' => true,
        'rest_base' => 'chat-messages',
        'has_archive' => false,
        'supports' => ['title', 'custom-fields'],
        'menu_icon' => 'dashicons-email-alt',
    ]);
});

// Добавляем ACF поля в REST API
add_action('rest_api_init', function() {
    
    // Функция для добавления ACF полей
    $add_acf_to_rest = function($object, $field_name, $request) {
        return get_field($field_name, $object['id']);
    };
    
    // Для пользователей чата
    register_rest_field('chat_user', 'acf', [
        'get_callback' => function($object, $field_name, $request) {
            $acf_fields = get_fields($object['id']);
            return $acf_fields ?: [];
        },
        'update_callback' => null,
        'schema' => null,
    ]);
    
    // Для чатов
    register_rest_field('chat', 'acf', [
        'get_callback' => function($object, $field_name, $request) {
            return get_fields($object['id']) ?: [];
        },
        'update_callback' => null,
        'schema' => null,
    ]);
    
    // Для сообщений
    register_rest_field('chat_message', 'acf', [
        'get_callback' => function($object, $field_name, $request) {
            return get_fields($object['id']) ?: [];
        },
        'update_callback' => null,
        'schema' => null,
    ]);
});

// Скрываем пароль из REST API
add_filter('rest_prepare_chat_user', function($response, $post, $request) {
    if (isset($response->data['acf']['password'])) {
        unset($response->data['acf']['password']);
    }
    return $response;
}, 10, 3);