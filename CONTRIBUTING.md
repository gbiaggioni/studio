# Guía de Contribución para QREasy

¡Gracias por tu interés en contribuir a QREasy! Estamos emocionados de recibir ayuda de la comunidad. Esta guía te proporcionará todo lo que necesitas saber para contribuir al proyecto.

## Cómo Contribuir

Aceptamos contribuciones a través de Pull Requests (PRs). Aquí tienes los pasos generales:

1.  **Haz un Fork:** Crea un "fork" de este repositorio en tu propia cuenta de GitHub.
2.  **Clona tu Fork:** Clona tu fork a tu máquina local.
3.  **Crea una Rama:** Crea una nueva rama para tus cambios: `git checkout -b mi-nueva-funcionalidad`
4.  **Realiza tus Cambios:** Implementa tu nueva característica o corrección de error.
5.  **Confirma tus Cambios:** Haz commit de tus cambios con un mensaje claro.
6.  **Empuja tus Cambios:** Sube tus cambios a tu fork.
7.  **Abre un Pull Request:** Ve al repositorio original y abre un Pull Request desde tu rama a la rama `main` del proyecto.

## Configuración del Entorno de Desarrollo (con Docker)

Para trabajar en el proyecto localmente, es **obligatorio** usar Docker para asegurar un entorno consistente.

1.  **Prerrequisitos:**
    *   [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado en tu computadora.
    *   Una base de datos MariaDB/MySQL accesible (puede estar en otro contenedor Docker o en tu máquina local).

2.  **Configurar Variables de Entorno:**
    *   Copia el archivo `.env.example` a un nuevo archivo llamado `.env.local`.
    *   Abre `.env.local` y rellena las credenciales de tu base de datos y la URL base para el desarrollo local (ej. `http://localhost:3001`).

3.  **Construir y Ejecutar el Contenedor:**
    *   Desde la raíz del proyecto, construye la imagen:
        ```bash
        docker build -t qreasy-app .
        ```
    *   Ejecuta el contenedor:
        ```bash
        docker run -p 3001:3001 --env-file ./.env.local qreasy-app
        ```
    *   La aplicación estará disponible en `http://localhost:3001`.

## Proceso de Pull Request

1.  **Asegúrate de que la build no falle:** Todos los Pull Requests deben pasar las comprobaciones de CI (ver `.github/workflows/ci.yml`).
2.  **Describe tus cambios:** Explica claramente qué problema resuelves o qué funcionalidad añades.

¡Gracias de nuevo por tu contribución!
