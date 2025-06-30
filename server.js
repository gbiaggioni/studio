
// server.js
// Este servidor personalizado nos da un control explícito sobre el arranque
// y nos permite registrar cualquier error fatal que ocurra durante el inicio.

const { createServer } = require('http');
const { parse } = require('url');
const next = require('next');

// Agregamos logs para verificar el entorno
console.log('>>> [QREasy] server.js starting...');
console.log(`>>> [QREasy] NODE_ENV: ${process.env.NODE_ENV}`);

const dev = process.env.NODE_ENV !== 'production';
const hostname = '0.0.0.0'; // Escuchar en todas las interfaces
const port = 3001; // Puerto interno que usará LiteSpeed para el proxy

// Cuando se usa middleware, `hostname` y `port` deben proporcionarse a continuación
// El flag `dir` es crucial para que Next.js encuentre el directorio .next
const app = next({ dev, hostname, port, dir: __dirname });
const handle = app.getRequestHandler();

app.prepare().then(() => {
  console.log('>>> [QREasy] Next.js App Prepared Successfully.');
  createServer(async (req, res) => {
    try {
      const parsedUrl = parse(req.url, true);
      await handle(req, res, parsedUrl);
    } catch (err) {
      console.error('>>> [QREasy] Error handling request:', err);
      res.statusCode = 500;
      res.end('internal server error');
    }
  })
    .once('error', (err) => {
      console.error('>>> [QREasy] Fatal Server Creation Error:', err);
      process.exit(1);
    })
    .listen(port, () => {
      console.log(`>>> [QREasy] Ready on http://${hostname}:${port}`);
    });
}).catch(err => {
    // Este bloque es el más importante. Capturará errores durante la inicialización.
    console.error('>>> [QREasy] Fatal Error during app.prepare():', err.stack || err);
    process.exit(1); // Salir del proceso si hay un error fatal
});

process.on('SIGINT', () => {
  console.log('>>> [QREasy] Received SIGINT. Shutting down gracefully...');
  process.exit(0);
});

process.on('SIGTERM', () => {
  console.log('>>> [QREasy] Received SIGTERM. Shutting down gracefully...');
  process.exit(0);
});
