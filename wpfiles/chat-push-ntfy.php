<?php
/**
 * Push-уведомления через ntfy (UnifiedPush).
 * При сохранении сообщения (chat_message) отправляем push всем участникам чата, кроме отправителя.
 * Подключить в functions.php: require_once __DIR__ . '/chat-push-ntfy.php';
 */

// URL вашего ntfy-сервера (без завершающего слэша), например https://push.ваш-домен.ru
if (!defined('CHAT_FRIENDS_NTFY_BASE_URL')) {
    define('CHAT_FRIENDS_NTFY_BASE_URL', 'https://chatnews.remont-gazon.ru');
}

add_action('chat_friends_message_sent', 'chat_friends_ntfy_push_on_message_sent', 10, 4);

/**
 * После отправки сообщения через API — push получателям через ntfy.
 *
 * @param int $message_id ID поста (сообщения)
 * @param int $chat_id ID чата (post ID типа chat)
 * @param int $sender_id ID отправителя (chat_user post ID)
 * @param array $data массив с ключами text, image_url, file_url, created_at
 */
function chat_friends_ntfy_push_on_message_sent($message_id, $chat_id, $sender_id, $data) {
    $chat_id = (int) $chat_id;
    $sender_id = (int) $sender_id;
    if ($chat_id <= 0 || $message_id <= 0) {
        return;
    }

    $members = get_field('members', $chat_id);
    if (!is_array($members)) {
        $members = [];
    }
    $members = array_map('intval', $members);
    $recipients = array_values(array_diff($members, [$sender_id]));
    if (empty($recipients)) {
        return;
    }

    $text = isset($data['text']) && is_string($data['text']) ? $data['text'] : '';
    $created_at = isset($data['created_at']) && is_string($data['created_at'])
        ? $data['created_at']
        : current_time('c');
    $image_url = isset($data['image_url']) ? $data['image_url'] : null;
    $file_url = isset($data['file_url']) ? $data['file_url'] : null;

    $type = 'text';
    if (!empty($image_url)) {
        $type = 'image';
    } elseif (!empty($file_url)) {
        $type = 'file';
    }

    $message_payload = [
        'chatId'    => $chat_id,
        'messageId' => $message_id,
        'senderId'  => $sender_id,
        'text'      => $text,
        'type'      => $type,
        'timestamp' => $created_at,
    ];

    $base_url = rtrim(CHAT_FRIENDS_NTFY_BASE_URL, '/');
    foreach ($recipients as $user_id) {
        if ($user_id <= 0) {
            continue;
        }
        $topic = 'user_' . $user_id;
        $url = $base_url . '/' . $topic;

        $body = json_encode([
            'title'   => 'Чат Друзей',
            'message' => $message_payload,
        ], JSON_UNESCAPED_UNICODE);

        wp_remote_post($url, [
            'blocking'  => false,
            'timeout'   => 5,
            'headers'   => [
                'Content-Type' => 'application/json; charset=utf-8',
                'Title'        => 'Чат Друзей',
                'Priority'     => '5',
                'Tags'         => 'megaphone',
            ],
            'body'      => $body,
        ]);

        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log(sprintf('[chat_friends_ntfy] Push sent to topic %s for message %d', $topic, $message_id));
        }
    }
}
