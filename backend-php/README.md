# Backend PHP — Gestión de licencias docentes

Requisitos: PHP 8+, extensiones `json`, `zip` y acceso a red saliente.

## Configuración

1. Copiar `.env.example` a `.env` y completar:

```
cp .env.example .env
```

2. Servir la carpeta `backend-php/` en un servidor web. Con Apache, apuntar `/api` a `backend-php/api/` (incluye `.htaccess`). Con PHP embebido:

```
php -S 0.0.0.0:8080 -t backend-php
```

> Luego acceder a `http://localhost:8080/api` con encabezado `Authorization: Bearer <API_TOKEN>`.

## Endpoints

- GET `/api/docentes` — lista
- GET `/api/cursos` — lista
- GET `/api/horarios?docente_id=DOC-001` — por docente
- POST `/api/licencias` — crear/simular `{ docente_id, desde, hasta, motivo, simulate?: true }`
- GET `/api/licencias?docente_id=...` — historial
- POST `/api/notificar/prueba` — test `{ canal, to, mensaje }`
- GET `/api/backup` — descarga ZIP con `data/` y `logs/`

## Datos

Los JSON se guardan en `backend-php/data/`. Ejemplos iniciales provistos.

## Seguridad

- Autenticación por `Bearer` token (variable `API_TOKEN`).
- CORS configurable (`ALLOWED_ORIGINS`).
- Rate limit básico (`RATE_LIMIT_*`).

## Notificaciones

Por defecto se simula el envío. Para habilitar entrega real configure credenciales y defina `DELIVER_NOTIFICATIONS=true` en `.env`.

---

# Frontend React (Vite + Tailwind)

1. Instalar dependencias:

```
cd frontend
npm i
```

2. Ejecutar en desarrollo:

```
npm run dev
```

3. Variables: usar `VITE_API_BASE_URL` si el backend no está bajo `/api`.

Accesibilidad: navegación por teclado, alto contraste con botón en el header, labels ARIA en formularios/tablas.

## Cambiar a variante Flutter

Si prefiere Flutter Web, cree un proyecto Flutter con vistas equivalentes (Login, Cargar Licencia, Historial, Catálogo) usando `go_router` y `riverpod`. El backend no cambia. Documente `VITE_API_BASE_URL` equivalente en Flutter (`--dart-define=API_BASE_URL`).