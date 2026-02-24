# Отправка тестового push в топик пользователя ntfy (Чат Друзей).
# Использование: .\send-push-test.ps1 [-UserId 368] [-ChatId 1] [-MessageId 999] [-SenderId 2] [-Text "Текст"]
param(
    [int]$UserId = 368,
    [int]$ChatId = 1,
    [int]$MessageId = 999,
    [int]$SenderId = 2,
    [string]$Text = "Проверка push $(Get-Date -Format 'HH:mm:ss')"
)

$payload = @{
    title   = "Чат Друзей"
    message = @{
        chatId    = $ChatId
        messageId = $MessageId
        senderId  = $SenderId
        text      = $Text
        type      = "text"
        timestamp = (Get-Date).ToUniversalTime().ToString("yyyy-MM-ddTHH:mm:ssZ")
    }
} | ConvertTo-Json -Depth 5 -Compress

$uri = "https://chatnews.remont-gazon.ru/user_$UserId"
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
