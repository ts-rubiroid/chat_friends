# Тестирование push-уведомлений (UnifiedPush / ntfy)

Схема: **WordPress** при отправке сообщения шлёт POST на **ntfy** (топик `user_{userId}`). Приложение подписано на топик своего пользователя и при получении показывает уведомление и по тапу открывает чат.

---

## Предусловия

1. **ntfy** доступен: https://chatnews.remont-gazon.ru (проверка: в браузере открывается страница).
2. **В приложении**: пользователь авторизован (ID пользователя = ID поста типа `chat_user` в WordPress, например 368).
3. **На устройстве**: установлен дистрибьютор UnifiedPush (например приложение **ntfy**), в настройках указан сервер `https://chatnews.remont-gazon.ru`.
4. Приложение один раз открыто после входа — выполняется подписка на топик `user_368` (или другой ID).

---

## Формат тела push (JSON)

Сервер (WordPress) отправляет в ntfy тело вида:

```json
{
  "title": "Чат Друзей",
  "message": {
    "chatId": 1,
    "messageId": 123,
    "senderId": 2,
    "text": "Текст сообщения",
    "type": "text",
    "timestamp": "2026-02-23T12:00:00+00:00"
  }
}
```

В ответе ntfy поле `message` может отображаться в упрощённом виде — на клиент приходит **исходное тело запроса**, приложение парсит JSON и извлекает `message.chatId`, `message.text` и т.д.

---

## Тест 1: Push при закрытом/фоновом приложении

**Цель:** убедиться, что при отправке в топик пользователя приходит системное уведомление и по тапу открывается нужный чат.

1. Закройте приложение или сверните его (не на экране).
2. В PowerShell выполните (подставьте свой `USER_ID`, например 368, и при необходимости `chatId`, `messageId`, `senderId`):

```powershell
$userId = 368
$chatId = 1
$messageId = 100
$senderId = 2
$body = @{
  title   = "Чат Друзей"
  message = @{
    chatId    = $chatId
    messageId = $messageId
    senderId  = $senderId
    text      = "Тест уведомления"
    type      = "text"
    timestamp = (Get-Date).ToUniversalTime().ToString("yyyy-MM-ddTHH:mm:ssZ")
  }
} | ConvertTo-Json -Depth 5 -Compress

Invoke-RestMethod -Uri "https://chatnews.remont-gazon.ru/user_$userId" `
  -Method Post `
  -ContentType "application/json; charset=utf-8" `
  -Headers @{ Title = "Чат Друзей" } `
  -Body ([System.Text.Encoding]::UTF8.GetBytes($body))
```

Или одной строкой через `curl.exe` (JSON в одну строку без переносов):

```powershell
curl.exe -X POST "https://chatnews.remont-gazon.ru/user_368" -H "Content-Type: application/json; charset=utf-8" -H "Title: Чат Друзей" -d '{"title":"Чат Друзей","message":{"chatId":1,"messageId":100,"senderId":2,"text":"Тест","type":"text","timestamp":"2026-02-23T12:00:00Z"}}'
```

3. **Ожидание:** на устройстве появляется уведомление «Чат Друзей» с текстом «Тест» (или «Новое сообщение», если текст пустой).
4. **Тап по уведомлению:** приложение открывается и переходит в чат с `chatId = 1` (если такой чат есть в списке).

---

## Тест 2: Push при открытом приложении (foreground)

**Цель:** в foreground системное уведомление не показывается, но список чатов обновляется.

1. Откройте приложение и оставайтесь на экране списка чатов (или в другом чате, не в чате с `chatId = 1`).
2. Отправьте тот же запрос в топик пользователя (как в тесте 1).
3. **Ожидание:** системное уведомление не появляется; при следующем опросе/обновлении список чатов может обновиться (если настроен колбэк по push в foreground).

---

## Тест 3: Реальное сообщение из чата (WordPress → ntfy)

**Цель:** при отправке сообщения через API/приложение push уходит получателям автоматически.

1. Пользователь A отправляет сообщение в чат, где есть пользователь B (ID пользователя B, например, 368).
2. В WordPress срабатывает хук `chat_friends_message_sent`, скрипт `chat-push-ntfy.php` для каждого получателя (кроме отправителя) делает `wp_remote_post` на `https://chatnews.remont-gazon.ru/user_{id}` с телом из `title` + `message` (chatId, messageId, senderId, text, type, timestamp).
3. **Ожидание:** у пользователя B приходит push и показывается уведомление (если приложение в фоне).

Проверка логов WordPress: в `wp-content/debug.log` при включённом `WP_DEBUG_LOG` могут быть строки вида `[chat_friends_ntfy] Push sent to topic user_368 for message ...`.

---

## Тест 4: Тап по уведомлению — открытие чата

1. Получите push (тест 1 или 3), не открывая приложение по тапу ранее.
2. Тапните по уведомлению.
3. **Ожидание:** приложение открывается на экране списка чатов и автоматически переходит в чат из уведомления (по `chatId` из payload).

---

## Скрипт для быстрой проверки (PowerShell)

Сохраните как `scripts/send-push-test.ps1` или выполните по шагам. Подставьте свой `USER_ID` и при необходимости параметры чата/сообщения.

```powershell
# Параметры (замените на свои)
$userId    = 368
$chatId    = 1
$messageId = 999
$senderId  = 2
$text      = "Проверка push $(Get-Date -Format 'HH:mm:ss')"

$payload = @{
  title   = "Чат Друзей"
  message = @{
    chatId    = $chatId
    messageId = $messageId
    senderId  = $senderId
    text      = $text
    type      = "text"
    timestamp = (Get-Date).ToUniversalTime().ToString("yyyy-MM-ddTHH:mm:ssZ")
  }
} | ConvertTo-Json -Depth 5 -Compress

$uri = "https://chatnews.remont-gazon.ru/user_$userId"
Write-Host "POST $uri" -ForegroundColor Cyan
Write-Host "Body: $payload" -ForegroundColor Gray

try {
  $r = Invoke-RestMethod -Uri $uri -Method Post `
    -ContentType "application/json; charset=utf-8" `
    -Headers @{ Title = "Чат Друзей" } `
    -Body ([System.Text.Encoding]::UTF8.GetBytes($payload))
  Write-Host "OK: $r" -ForegroundColor Green
} catch {
  Write-Host "Error: $_" -ForegroundColor Red
}
```

---

## Частые проблемы

| Симптом | Что проверить |
|--------|----------------|
| Уведомление не приходит | Подписка на топик (приложение открыто после входа, в логах «registerTopic: user_368»). В ntfy указан правильный сервер. Нет блокировок по сети. |
| «Не удалось распарсить JSON» | Тело запроса должно быть валидный JSON с полем `message` и внутри `chatId`, `messageId`, `senderId`, `text`, `type`, `timestamp`. |
| По тапу не открывается чат | В payload локального уведомления передаётся `chatId`; приложение по нему ищет чат в списке и открывает. Убедитесь, что чат с таким ID есть у пользователя. |
| Push из WordPress не уходит | В админке проверьте, что у чата (ACF) заполнено поле «members». В debug.log смотреть записи `[chat_friends_ntfy]`. |

---

## Краткий чек-лист

- [ ] ntfy доступен по HTTPS, приложение ntfy на устройстве указывает на этот сервер.
- [ ] Пользователь в приложении авторизован, топик подписан (после входа был экран чатов).
- [ ] Тест 1: curl/PowerShell → уведомление в фоне, тап → открытие нужного чата.
- [ ] Тест 2: тот же запрос в foreground → без дублирования уведомления.
- [ ] Тест 3: отправка сообщения в чат из приложения → push получателю.
- [ ] Тест 4: тап по уведомлению открывает конкретный чат.
