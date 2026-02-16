/**
 * Прокси для OData 1С: принимает запросы по HTTP(S), пересылает их на сервер 1С
 * и возвращает ответ. Нужен, чтобы приложение могло обращаться к 1С через HTTPS
 * (прокси стоит за nginx с Let's Encrypt), когда провайдер 1С не даёт HTTPS.
 *
 * Запуск: node server.js
 * Переменные окружения: см. config.example.env и README.
 */

const http = require('http');

const PORT = parseInt(process.env.PORT || '3080', 10);
const ONEC_BACKEND_BASE = (process.env.ONEC_BACKEND_BASE || '').replace(/\/$/, '');
const ONEC_USERNAME = process.env.ONEC_USERNAME || 'Администратор';
const ONEC_PASSWORD = process.env.ONEC_PASSWORD || '';

if (!ONEC_BACKEND_BASE) {
  console.error('Укажите ONEC_BACKEND_BASE (URL сервера 1С без завершающего слэша).');
  process.exit(1);
}

const AUTH_HEADER = Buffer.from(`${ONEC_USERNAME}:${ONEC_PASSWORD}`, 'utf8').toString('base64');

function forward(req, res) {
  const path = req.url || '/';
  const backendUrl = ONEC_BACKEND_BASE + path;

  const headers = { ...req.headers };
  delete headers.host;
  headers['Authorization'] = `Basic ${AUTH_HEADER}`;

  const opts = {
    method: req.method,
    headers,
  };

  const bodyChunks = [];
  req.on('data', (chunk) => bodyChunks.push(chunk));
  req.on('end', () => {
    const body = bodyChunks.length ? Buffer.concat(bodyChunks) : undefined;
    if (body) opts.body = body;

    fetch(backendUrl, opts)
      .then(async (backendRes) => {
        const headers = Object.fromEntries(backendRes.headers.entries());
        res.writeHead(backendRes.status, headers);
        const buf = Buffer.from(await backendRes.arrayBuffer());
        res.end(buf);
      })
      .catch((err) => {
        console.error('Proxy error:', err.message);
        res.writeHead(502, { 'Content-Type': 'text/plain; charset=utf-8' });
        res.end('Ошибка прокси: не удалось связаться с 1С. Проверьте доступность сервера 1С с этой машины.');
      });
  });
}

const server = http.createServer(forward);
server.listen(PORT, () => {
  console.log(`Прокси OData 1С слушает порт ${PORT}`);
  console.log(`Бэкенд: ${ONEC_BACKEND_BASE}`);
  console.log('Ожидаются запросы с путём вида /odata/standard.odata/...');
});
