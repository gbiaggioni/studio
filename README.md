# QREasy - Gestor de Códigos QR

QREasy es una aplicación web moderna y sencilla para crear, gestionar y compartir códigos QR. Ha sido desarrollada con un stack tecnológico actual, enfocándose en el rendimiento, la escalabilidad y una experiencia de usuario fluida.

## ✨ Características Principales

-   **Creación de Códigos QR:** Genera códigos QR dinámicamente a partir de cualquier URL de destino.
-   **Etiquetado Personalizado:** Asigna un nombre o etiqueta a cada código QR para una fácil identificación.
-   **Galería de Códigos:** Visualiza todos tus códigos QR en una interfaz de tarjeta limpia y organizada.
-   **URL Corta Única:** Cada código QR obtiene una URL corta y única (ej. `tu-dominio.com/r/xyz123`) para la redirección.
-   **Gestión Completa:**
    -   Copia la URL corta al portapapeles con un solo clic.
    -   Imprime códigos QR individuales directamente desde la aplicación.
    -   Elimina códigos QR específicos o todos a la vez con diálogos de confirmación.
-   **Responsivo:** Diseño completamente adaptable para funcionar en computadoras de escritorio, tabletas y dispositivos móviles.

## 🚀 Stack Tecnológico

Este proyecto está construido con tecnologías modernas y robustas:

-   **Framework:** [Next.js](https://nextjs.org/) (usando el App Router para un rendimiento óptimo)
-   **Lenguaje:** [TypeScript](https://www.typescriptlang.org/)
-   **Estilo:** [Tailwind CSS](https://tailwindcss.com/) para un diseño basado en utilidades.
-   **Componentes UI:** [ShadCN UI](https://ui.shadcn.com/) para componentes accesibles y reutilizables.
-   **Validación de Formularios:** [Zod](https://zod.dev/) para una validación de esquemas segura y tipada.
-   **Hooks de Formularios:** [React Hook Form](https://react-hook-form.com/)
-   **Base de Datos (Mock):** La versión actual utiliza un almacén en memoria para una demostración rápida. Está diseñado para ser fácilmente reemplazable por una base de datos de producción como **MariaDB**, **PostgreSQL** o **Firebase Firestore**.

## 📦 Instalación y Uso Local

Sigue estos pasos para ejecutar el proyecto en tu entorno local. Esto es válido tanto para **Windows** como para **Linux/macOS**.

### Prerrequisitos

-   [Node.js](https://nodejs.org/) (versión LTS recomendada, ej. 20.x)
-   [Git](https://git-scm.com/)

### Pasos de Instalación

1.  **Clona el repositorio:**
    Abre tu terminal y clona el proyecto desde GitHub.

    ```bash
    git clone https://github.com/gbiaggioni/qreasy-app.git
    ```

2.  **Navega al directorio del proyecto:**
    ```bash
    cd qreasy-app
    ```

3.  **Instala las dependencias:**
    Usa `npm` para instalar todos los paquetes necesarios.

    ```bash
    npm install
    ```

4.  **Ejecuta el servidor de desarrollo:**
    Inicia la aplicación en modo de desarrollo.

    ```bash
    npm run dev
    ```

5.  **Abre la aplicación:**
    La aplicación estará disponible en tu navegador en la siguiente dirección:
    [http://localhost:9002](http://localhost:9002)

### Scripts Disponibles

-   `npm run dev`: Inicia el servidor de desarrollo con `turbopack` para recargas rápidas.
-   `npm run build`: Construye la aplicación para un entorno de producción.
-   `npm run start`: Inicia la aplicación en modo de producción (requiere una `build` previa).
-   `npm run lint`: Ejecuta el linter para revisar la calidad del código.
-   `npm run typecheck`: Valida los tipos de TypeScript en el proyecto.

## 🔧 Configuración

El proyecto puede requerir un archivo de variables de entorno para su correcta configuración, especialmente para la generación de las URLs cortas.

1.  Crea un archivo llamado `.env.local` en la raíz del proyecto.
2.  Añade la siguiente variable:

    ```env
    # URL base para generar las URLs cortas.
    # En desarrollo, esta es la URL local. En producción, debe ser tu dominio público.
    NEXT_PUBLIC_BASE_URL=http://localhost:9002
    ```