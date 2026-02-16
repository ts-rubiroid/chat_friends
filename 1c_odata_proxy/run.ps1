# Запуск прокси OData 1С.
# Запускайте этот скрипт из любой папки (двойной клик или в терминале: .\run.ps1).
# Настройки — в переменных ниже или в config.example.env.

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

$env:ONEC_BACKEND_BASE = if ($env:ONEC_BACKEND_BASE) { $env:ONEC_BACKEND_BASE } else { "http://172.22.0.62/1R82821/1R82821_AVTOSERV30_73qj8uuuxp" }
$env:ONEC_USERNAME     = if ($env:ONEC_USERNAME) { $env:ONEC_USERNAME } else { "Администратор" }
$env:ONEC_PASSWORD     = if ($env:ONEC_PASSWORD) { $env:ONEC_PASSWORD } else { "" }
$env:PORT              = if ($env:PORT) { $env:PORT } else { "3080" }

Write-Host "Прокси 1С: папка $PSScriptRoot"
Write-Host "Бэкенд: $env:ONEC_BACKEND_BASE"
node server.js
