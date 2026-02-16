# Прокси OData 1С

Прокси принимает запросы от приложения «Каталог запчастей» и пересылает их на ваш сервер 1С. Нужен, когда провайдер 1С не даёт HTTPS: прокси ставится на машину с доступом к 1С (VPN или внутренняя сеть) и отдаёт трафик по HTTPS через nginx с Let's Encrypt.

## Требования

- Node.js 18 или новее (для встроенного `fetch`).
- Доступ с машины, где запущен прокси, до сервера 1С (по VPN или в той же сети).

## Настройка

1. Скопируйте `config.example.env` в `.env` или задайте переменные окружения вручную.
2. Укажите **ONEC_BACKEND_BASE** — полный URL вашей 1С до пути OData (без завершающего слэша), например:
   `http://172.22.0.62/1R82821/1R82821_AVTOSERV30_73qj8uuuxp`
3. Укажите **ONEC_USERNAME** и **ONEC_PASSWORD** (логин/пароль 1С). Прокси подставит их в каждый запрос к 1С.

## Запуск

**Важно:** команду `node server.js` нужно выполнять из папки, где лежит `server.js` (папка **1c_odata_proxy**). Иначе будет ошибка «Cannot find module ... server.js».

**Способ 1 — скрипт (удобно):** из любой папки в терминале выполните:
```powershell
cd C:\Users\USER\chat_friends_BeGet\1c_odata_proxy
.\run.ps1
```
Скрипт сам перейдёт в папку прокси и запустит сервер (настройки по умолчанию — при необходимости отредактируйте переменные в `run.ps1`).

**Способ 2 — вручную:** откройте терминал, перейдите в папку прокси, задайте переменные и запустите:
```powershell
cd C:\Users\USER\chat_friends_BeGet\1c_odata_proxy
$env:ONEC_BACKEND_BASE="http://172.22.0.62/1R82821/1R82821_AVTOSERV30_73qj8uuuxp"
$env:ONEC_USERNAME="Администратор"
$env:ONEC_PASSWORD=""
node server.js
```

На Linux/macOS:
```bash
cd /путь/к/1c_odata_proxy
export ONEC_BACKEND_BASE="http://172.22.0.62/1R82821/1R82821_AVTOSERV30_73qj8uuuxp"
export ONEC_USERNAME="Администратор"
export ONEC_PASSWORD=""
node server.js
```

Прокси слушает порт **3080** (или значение `PORT`). Запросы должны приходить с путём, как у 1С: `/odata/standard.odata/Catalog_...`, `/odata/standard.odata/InformationRegister_...` и т.д.

## Использование в приложении

В настройках приложения (экран «Настройки 1С») укажите **URL OData** в виде адреса прокси с тем же путём, что и у 1С:

- Если прокси доступен по интернету как `https://your-domain.com` (через nginx), то URL в приложении:
  **`https://your-domain.com/odata/standard.odata`**
- Если проверяете локально (ПК с прокси и эмулятор на одном компе):  
  **`http://localhost:3080/odata/standard.odata`**  
  или с телефона в той же Wi‑Fi: **`http://IP_ПК:3080/odata/standard.odata`**.

Логин и пароль в приложении можно оставить любыми (или пустыми): авторизацию к 1С добавляет прокси из своих переменных окружения.

## HTTPS (доступ из интернета)

Чтобы приложение работало из любой сети, прокси должен отдавать трафик по HTTPS. Варианты:

1. **Nginx перед прокси** (рекомендуется): на VPS или ПК с белым IP установите nginx, получите сертификат Let's Encrypt (certbot). Настройте nginx как reverse proxy на `http://127.0.0.1:3080`. Запросы к `https://your-domain.com/odata/...` nginx передаёт на прокси.
2. **Только для теста**: можно поднять прокси на VPS с доступом в 1С по VPN и обращаться по HTTP (тогда в приложении на Android возможны ограничения на cleartext).

### Пример конфига nginx (фрагмент)

```nginx
server {
    listen 443 ssl;
    server_name your-domain.com;
    ssl_certificate     /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    location / {
        proxy_pass http://127.0.0.1:3080;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

После настройки в приложении укажите URL: **`https://your-domain.com/odata/standard.odata`**.

## Безопасность

- Храните `.env` в безопасном месте и не коммитьте его в репозиторий.
- На проде используйте HTTPS (nginx + Let's Encrypt).
- По желанию ограничьте доступ к прокси по IP или добавьте свою авторизацию (например, ключ в заголовке) — для этого нужно доработать `server.js`.
