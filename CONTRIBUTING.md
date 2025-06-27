# Guía de Contribución para QREasy

¡Gracias por tu interés en contribuir a QREasy! Estamos emocionados de recibir ayuda de la comunidad. Esta guía te proporcionará todo lo que necesitas saber para contribuir al proyecto.

## Cómo Contribuir

Aceptamos contribuciones a través de Pull Requests (PRs). Aquí tienes los pasos generales:

1.  **Haz un Fork:** Crea un "fork" de este repositorio en tu propia cuenta de GitHub.
2.  **Clona tu Fork:** Clona tu fork a tu máquina local: `git clone https://github.com/TU_USUARIO/qreasy.git`
3.  **Crea una Rama:** Crea una nueva rama para tus cambios: `git checkout -b mi-nueva-funcionalidad`
4.  **Realiza tus Cambios:** Implementa tu nueva característica o corrección de error.
5.  **Confirma tus Cambios:** Haz commit de tus cambios con un mensaje claro: `git commit -m "Añadir nueva funcionalidad asombrosa"`
6.  **Empuja tus Cambios:** Sube tus cambios a tu fork: `git push origin mi-nueva-funcionalidad`
7.  **Abre un Pull Request:** Ve al repositorio original y abre un Pull Request desde tu rama a la rama `main` del proyecto.

## Configuración del Entorno de Desarrollo

Para trabajar en el proyecto localmente, necesitarás:
- Node.js (versión LTS recomendada)
- Una base de datos MariaDB o MySQL disponible localmente.

1.  **Instalar Dependencias:**
    Desde la raíz del proyecto, ejecuta:
    ```bash
    npm install
    ```

2.  **Configurar Base de Datos:**
    - Crea una base de datos en tu instancia local de MariaDB/MySQL (ej. `qreasy_db`).
    - Ejecuta el contenido del script `sql/schema.sql` en tu base de datos para crear la tabla `qr_codes`.

3.  **Configurar Variables de Entorno:**
    - Copia el archivo `.env.example` a un nuevo archivo llamado `.env.local`.
    - Abre `.env.local` y rellena las credenciales de tu base de datos local.

4.  **Ejecutar el Servidor de Desarrollo:**
    ```bash
    npm run dev
    ```
    La aplicación estará disponible en `http://localhost:9002`. Si todo está configurado correctamente, podrás crear, ver y eliminar códigos QR.


## Estándares de Codificación

-   **Estilo de Código:** Usamos Prettier y ESLint para mantener un estilo de código consistente. Asegúrate de ejecutar el linter antes de hacer commit.
-   **Nombres de Variables y Funciones:** Utiliza nombres descriptivos y en inglés (`camelCase` para variables y funciones, `PascalCase` para componentes de React).
-   **Comentarios:** Añade comentarios solo cuando el código no sea autoexplicativo.

## Scripts Útiles

-   `npm run dev`: Inicia el servidor de desarrollo.
-   `npm run build`: Construye la aplicación para producción.
-   `npm run lint`: Ejecuta el linter para encontrar errores de estilo.
-   `npm run typecheck`: Ejecuta el compilador de TypeScript para verificar tipos.

## Proceso de Pull Request

1.  **Asegúrate de que la build no falle:** Todos los Pull Requests deben pasar las comprobaciones de CI (linting, type checking y build).
2.  **Describe tus cambios:** En la descripción del PR, explica claramente qué problema resuelves o qué funcionalidad añades. Si resuelve un "issue" existente, menciónalo con `Closes #123`.
3.  **Espera la revisión:** Un mantenedor del proyecto revisará tu PR. Puede que te pidan hacer algunos cambios.

¡Gracias de nuevo por tu contribución!
