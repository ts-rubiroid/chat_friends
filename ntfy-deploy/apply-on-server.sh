#!/bin/bash
# Запускать на сервере (root): bash apply-on-server.sh
# Прокидывает chatnews.remont-gazon.ru на ntfy (127.0.0.1:2586).
# Имя 00-ntfy нужно, чтобы этот виртуальный хост обрабатывался раньше default (certbot).

set -e
CONF="/etc/nginx/sites-available/00-ntfy"
cat > "$CONF" << 'NGINX_EOF'
server {
    listen 80;
    server_name chatnews.remont-gazon.ru;
    location / {
        return 301 https://$server_name$request_uri;
    }
}

server {
    listen 443 ssl http2;
    server_name chatnews.remont-gazon.ru;

    ssl_certificate     /etc/letsencrypt/live/chatnews.remont-gazon.ru/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/chatnews.remont-gazon.ru/privkey.pem;

    location / {
        proxy_pass http://127.0.0.1:2586;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 86400;
    }
}
NGINX_EOF

ln -sf "$CONF" /etc/nginx/sites-enabled/00-ntfy
nginx -t && systemctl reload nginx
echo "OK. Nginx перезагружен. Проверьте: https://chatnews.remont-gazon.ru/"
