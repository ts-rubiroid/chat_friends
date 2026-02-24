# Развёртывание ntfy для «Чат друзей»

Файлы в этой папке копируются на **BeGet VPS** (см. STEP-BY-STEP.md).

## Что здесь лежит

| Файл | Назначение |
|------|------------|
| `server.yml` | Конфиг ntfy (base-url: chatnews.remont-gazon.ru) |
| `docker-compose.yml` | Запуск ntfy в Docker |
| `nginx-ntfy.conf` | Конфиг Nginx для HTTPS и proxy на ntfy |
| `apply-on-server.sh` | Скрипт: применить Nginx-конфиг на сервере одной командой |

## Применить Nginx на сервере (одна команда)

После того как certbot уже выдал сертификат для chatnews.remont-gazon.ru, на сервере выполните:

**Вариант A — с вашего ПК (если есть OpenSSH):** скопировать скрипт на сервер и запустить:

```bash
scp ntfy-deploy/apply-on-server.sh root@155.212.191.117:/tmp/
ssh root@155.212.191.117 "bash /tmp/apply-on-server.sh"
```

**Вариант B — прямо на сервере:** в SSH-сессии скопировать содержимое файла `apply-on-server.sh` в буфер обмена, на сервере выполнить:

```bash
nano /tmp/apply-on-server.sh
```

Вставить содержимое, сохранить (Ctrl+O, Enter, Ctrl+X), затем:

```bash
bash /tmp/apply-on-server.sh
```

В конце должно вывестись «OK» и открыться https://chatnews.remont-gazon.ru/ (страница ntfy).
