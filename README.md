# QREasy - Gestor de Códigos QR

QREasy es una aplicación web moderna y sencilla para crear, gestionar y compartir códigos QR. Ha sido desarrollada con un stack tecnológico actual, enfocándose en el rendimiento, la escalabilidad y una experiencia de usuario fluida.

## ✨ Características Principales

-   **Creación de Códigos QR:** Genera códigos QR dinámicamente a partir de cualquier URL de destino.
-   **Etiquetado Personalizado:** Asigna un nombre o etiqueta a cada código QR para una fácil identificación.
-   **Galería de Códigos:** Visualiza todos tus códigos QR en una interfaz de tarjeta limpia y organizada.
-   **URL Corta Única:** Cada código QR obtiene una URL corta y única (ej. `esquel.ar/r/xyz123`) para la redirección.
-   **Gestión Completa:**
    -   Edita la URL de destino o el nombre de un QR sin necesidad de reimprimirlo.
    -   Copia la URL corta al portapapeles con un solo clic.
    -   Imprime códigos QR individuales directamente desde la aplicación, optimizados para A4.
    -   Elimina códigos QR específicos o todos a la vez con diálogos de confirmación.
-   **Responsivo:** Diseño completamente adaptable para funcionar en computadoras de escritorio, tabletas y dispositivos móviles.
-   **Listo para Producción:** Conexión a base de datos MariaDB/MySQL y documentación de despliegue completa.

## 🚀 Stack Tecnológico

Este proyecto está construido con tecnologías modernas y robustas:

-   **Framework:** [Next.js](https://nextjs.org/) (usando el App Router para un rendimiento óptimo)
-   **Lenguaje:** [TypeScript](https://www.typescriptlang.org/)
-   **Estilo:** [Tailwind CSS](https://tailwindcss.com/) para un diseño basado en utilidades.
-   **Componentes UI:** [ShadCN UI](https://ui.shadcn.com/) para componentes accesibles y reutilizables.
-   **Validación de Formularios:** [Zod](https://zod.dev/) para una validación de esquemas segura y tipada.
-   **Hooks de Formularios:** [React Hook Form](https://react-hook-form.com/)
-   **Base de Datos:** [MariaDB](https://mariadb.org/) / [MySQL](https://www.mysql.com/) con el driver `mysql2`.

## 📦 Instalación y Uso Local

Sigue estos pasos para ejecutar el proyecto en tu entorno local. Esto es válido tanto para **Windows** como para **Linux/macOS**.

### Prerrequisitos

-   [Node.js](https://nodejs.org/) (versión LTS recomendada, ej. 20.x)
-   [Git](https://git-scm.com/)
-   Una base de datos MariaDB o MySQL accesible localmente (puedes usar Docker, XAMPP, WAMP, etc.).

### Pasos de Instalación

1.  **Clona el repositorio:**
    Abre tu terminal y clona el proyecto desde GitHub.

    ```bash
    git clone https://github.com/tu-usuario-de-github/esquel.ar.git
    ```

2.  **Navega al directorio del proyecto:**
    ```bash
    cd esquel.ar
    ```

3.  **Instala las dependencias:**
    Usa `npm` para instalar todos los paquetes necesarios.

    ```bash
    npm install
    ```

4.  **Configura la base de datos:**
    -   Crea una base de datos en tu instancia de MariaDB/MySQL (ej. `qreasy_db`).
    -   Ejecuta el script `sql/schema.sql` en tu base de datos para crear la tabla `qr_codes`. Puedes hacerlo desde phpMyAdmin, DBeaver, o la línea de comandos de `mysql`.

5.  **Configura las variables de entorno:**
    -   Crea una copia del archivo `.env.example` y renómbrala a `.env.local`.
    -   Edita `.env.local` y rellena los datos de conexión a tu base de datos local y la URL base para el desarrollo.
    
    **Ejemplo de `.env.local` para desarrollo:**
    ```env
    # Configuración de la Base de Datos Local
    DB_HOST=127.0.0.1
    DB_USER=root
    DB_PASSWORD=tu_contraseña_local
    DB_NAME=qreasy_db
    
    # URL base para generar las URLs cortas en desarrollo
    NEXT_PUBLIC_BASE_URL=http://localhost:9002
    ```
    *Nota: Si no se configura este archivo, la aplicación se iniciará pero no podrá conectarse a la base de datos. Verás una lista vacía de códigos QR y recibirás errores al intentar crear, editar o eliminar.*

6.  **Ejecuta el servidor de desarrollo:**
    Inicia la aplicación en modo de desarrollo.

    ```bash
    npm run dev
    ```

7.  **Abre la aplicación:**
    La aplicación estará disponible en tu navegador en la siguiente dirección:
    [http://localhost:9002](http://localhost:9002)

### Probando en Dispositivos Móviles

Cuando ejecutas la aplicación en modo de desarrollo, las URLs cortas se generan usando `http://localhost:9002`. Tu computadora entiende que `localhost` se refiere a sí misma, pero otros dispositivos en tu red (como tu teléfono móvil) no lo saben.

Para probar la redirección escaneando un código QR desde tu teléfono, necesitas que la URL corta use la **dirección IP local** de tu computadora.

1.  **Averigua tu IP Local:**
    *   **En Windows:** Abre `cmd` y escribe `ipconfig`. Busca la dirección "IPv4 Address".
    *   **En macOS/Linux:** Abre una terminal y escribe `ip addr` o `ifconfig`. Busca la dirección `inet`.
    *   Tu IP se verá como `192.168.1.100` (es un ejemplo).

2.  **Actualiza tu `.env.local` temporalmente:**
    Cambia `NEXT_PUBLIC_BASE_URL` para que use tu IP local, por ejemplo:
    `NEXT_PUBLIC_BASE_URL=http://192.168.1.100:9002`

3.  **Reinicia tu servidor de desarrollo** (`Ctrl+C` y `npm run dev`) para que tome la nueva configuración. Ahora los códigos QR que generes apuntarán a la dirección correcta para probar desde tu teléfono.

*Nota: El script `npm run dev` ya está configurado para aceptar conexiones desde tu red local.*

### Scripts Disponibles

-   `npm run dev`: Inicia el servidor de desarrollo con `turbopack` para recargas rápidas.
-   `npm run build`: Construye la aplicación para un entorno de producción.
-   `npm run start`: Inicia la aplicación en modo de producción (requiere una `build` previa).
-   `npm run lint`: Ejecuta el linter para revisar la calidad del código.
-   `npm run typecheck`: Valida los tipos de TypeScript en el proyecto.

## 🔧 Configuración del Dominio y Variables de Entorno

La aplicación utiliza variables de entorno para gestionar la configuración de la base de datos y el dominio, lo cual es esencial para separar los entornos de desarrollo y producción.

### Variables de Entorno Requeridas

Crea un archivo llamado `.env.local` en la raíz del proyecto (este archivo **no** debe subirse a GitHub). Puedes copiar el archivo `.env.example` como plantilla. Contendrá los siguientes valores:

-   `DB_HOST`: La dirección del servidor de la base de datos (ej. `localhost`).
-   `DB_USER`: El usuario de la base de datos.
-   `DB_PASSWORD`: La contraseña del usuario.
-   `DB_NAME`: El nombre de la base de datos.
-   `NEXT_PUBLIC_BASE_URL`: La URL base completa (incluyendo `http://` o `https://`) que se usará para generar las URLs cortas.

**Ejemplo para producción con el dominio `esquel.ar`:**
```env
# .env.local

# Configuración de la Base de Datos de Producción
DB_HOST=localhost
DB_USER=el_usuario_de_tu_bd
DB_PASSWORD=la_contraseña_de_tu_bd
DB_NAME=el_nombre_de_tu_bd

# URL base para generar las URLs cortas con HTTPS
NEXT_PUBLIC_BASE_URL=https://esquel.ar
```

## 🚀 Despliegue en DonWeb Cloud Server (con CyberPanel)

Esta guía describe cómo desplegar la aplicación en un servidor cloud de DonWeb que utiliza la imagen de **CyberPanel**, compatible tanto con **Ubuntu 20.04** como con **Ubuntu 22.04**.

El proceso es prácticamente idéntico para ambas versiones del sistema operativo, ya que la estrategia consiste en ejecutar la aplicación Next.js como un proceso independiente usando **PM2** y configurar **OpenLiteSpeed** como un proxy inverso para dirigir el tráfico del dominio a la aplicación.

### Prerrequisitos

-   Un Cloud Server de DonWeb con la imagen de CyberPanel.
-   Acceso SSH al servidor (necesitarás la IP, el usuario `root` y la contraseña).
-   El dominio `esquel.ar` apuntando a la IP de tu servidor.

### Paso 1: Conexión y Preparación del Servidor

1.  **Conéctate por SSH:**
    ```bash
    ssh root@<IP_DE_TU_SERVIDOR>
    ```

2.  **Instala Node.js y PM2:**
    La imagen de CyberPanel no incluye Node.js. Instala la versión LTS (Recomendada):
    ```bash
    # Instala NVM (Node Version Manager) para gestionar versiones de Node.js
    curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash

    # Carga NVM en la sesión actual
    export NVM_DIR="$HOME/.nvm"
    [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
    [ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"

    # Instala la última versión LTS de Node.js
    nvm install --lts

    # Instala PM2, un gestor de procesos para mantener la app corriendo
    npm install pm2 -g
    ```

### Paso 2: Configuración de la Base de Datos

1.  **Accede a CyberPanel:** `https://<IP_DE_TU_SERVIDOR>:8090`
2.  Ve a `Database` -> `Create Database` para crear una nueva base de datos y un usuario. Anota el nombre de la base de datos, el nombre de usuario y la contraseña.
3.  Ve a `Database` -> `phpMyAdmin` y accede con las credenciales del usuario que acabas de crear.
4.  Una vez dentro de phpMyAdmin, selecciona tu base de datos en el panel izquierdo.
5.  Ve a la pestaña `SQL`.
6.  Copia el contenido del archivo `sql/schema.sql` de este repositorio y pégalo en el cuadro de texto.
7.  Haz clic en **"Go"** para ejecutar el script. Esto creará la tabla `qr_codes` necesaria.

### Paso 3: Desplegar el Código de la Aplicación

1.  **Clona el repositorio desde GitHub:**
    Navega a la carpeta de tu sitio web (CyberPanel la crea por defecto) y clona el proyecto.
    ```bash
    # Navega al directorio raíz de tu sitio
    cd /home/esquel.ar/public_html

    # Clona el proyecto (el punto al final clona en el directorio actual)
    git clone https://github.com/tu-usuario-de-github/esquel.ar.git .
    ```

2.  **Instala las dependencias:**
    ```bash
    npm install
    ```

3.  **Configura las variables de entorno para producción:**
    Crea el archivo `.env.local` con la configuración de tu base de datos y dominio.
    ```bash
    nano .env.local
    ```
    Pega el siguiente contenido, reemplazando los valores con los que creaste en el Paso 2:
    ```env
    DB_HOST=localhost
    DB_USER=el_usuario_de_tu_bd
    DB_PASSWORD=la_contraseña_de_tu_bd
    DB_NAME=el_nombre_de_tu_bd
    NEXT_PUBLIC_BASE_URL=https://esquel.ar
    ```

4.  **Construye la aplicación para producción:**
    ```bash
    npm run build
    ```

### Paso 4: Ejecutar la Aplicación con PM2

1.  **Inicia la aplicación:**
    Desde la carpeta del proyecto (`/home/esquel.ar/public_html`), ejecuta:
    ```bash
    # Inicia la app en el puerto 3000 con el nombre 'qreasy'
    pm2 start npm --name "qreasy" -- start -p 3000
    ```

2.  **Verifica que esté corriendo:**
    ```bash
    pm2 list
    ```
    Deberías ver la app `qreasy` con el estado `online`.

3.  **Guarda la lista de procesos y configúrala para el arranque del sistema:**
    ```bash
    pm2 save
    pm2 startup
    ```
    Copia y ejecuta el comando que te proporcione `pm2 startup`. Esto asegura que tu aplicación se reinicie automáticamente si el servidor se reinicia.

### Paso 5: Configurar OpenLiteSpeed como Proxy Inverso

1.  **Accede a tu panel de CyberPanel.**
2.  Ve a `Websites` -> `List Websites` y haz clic en `Manage` en `esquel.ar`.
3.  Desplázate hacia abajo hasta la sección **"Rewrite Rules"**.
4.  Pega las siguientes reglas y guarda los cambios:

    ```
    # Estas reglas le dicen a OpenLiteSpeed que envíe todo el tráfico
    # a tu aplicación Next.js que corre en el puerto 3000.
    REWRITERULE ^(.*)$ http://127.0.0.1:3000/$1 [P,L]
    ```

5.  **Reinicia el servidor web** para aplicar los cambios. Desde la terminal:
    ```bash
    sudo systemctl restart lsws
    ```

### Paso 6: Configurar SSL (HTTPS)

CyberPanel lo hace muy fácil.
1.  En el panel de gestión de tu sitio (`Manage`), ve a la sección **"SSL"**.
2.  Selecciona `esquel.ar` y haz clic en **"Issue SSL"**. CyberPanel se encargará de obtener e instalar un certificado gratuito de Let's Encrypt.

¡Y eso es todo! Tu aplicación QREasy ahora debería estar funcionando en `https://esquel.ar`, conectada a una base de datos persistente y servida de forma segura a través de HTTPS.
