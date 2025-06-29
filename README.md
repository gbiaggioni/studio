
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

---

## 🚀 Instrucciones Finales y Definitivas de Despliegue en DonWeb Cloud Server (con CyberPanel)

Esta guía contiene los pasos finales, consolidados y probados para desplegar la aplicación. Sigue cada paso meticulosamente. El objetivo es asegurar que todos los archivos y procesos pertenezcan al usuario correcto (`esque9858`) para eliminar cualquier conflicto de permisos.

### Paso 1: Conexión y Limpieza (Como `root`)

1.  **Conéctate a tu servidor por SSH** como `root`.
2.  **Navega a la carpeta de la aplicación:**
    ```bash
    cd /home/esquel.org.ar/public_html/studio
    ```
3.  **Detén y elimina cualquier proceso de PM2 anterior.** Esto es crucial para empezar de cero.
    ```bash
    pm2 stop qreasy
    pm2 delete qreasy
    pm2 save --force
    ```
4.  **Limpia los artefactos de construcción antiguos.**
    ```bash
    rm -rf node_modules .next
    ```

### Paso 2: Instalación, Construcción y Corrección de Permisos (Todo como `root`)

**Explicación:** Ejecutaremos `npm install` y `npm run build` como `root`, ya que es el único usuario que puede. Esto creará las carpetas `node_modules` y `.next` como propiedad de `root`. Inmediatamente después, cambiaremos su propiedad a `esque9858` para evitar el estado `errored` en PM2.

1.  **Instala las dependencias (como `root`):**
    ```bash
    npm install
    ```
2.  **Construye la aplicación (como `root`):**
    ```bash
    npm run build
    ```
3.  **¡Paso Crucial! Cambia la propiedad de los nuevos archivos** al usuario del sitio.
    ```bash
    chown -R esque9858:esque9858 /home/esquel.org.ar/public_html/studio
    ```
    *Esto asegura que todos los archivos, incluyendo los recién creados `node_modules` y `.next`, pertenezcan al usuario correcto.*

### Paso 3: Configurar el Servidor Web (vHost Conf)

Esta configuración unificada le dice al servidor cómo encontrar y comunicarse con tu aplicación Node.js de forma robusta, utilizando el método de `contexto` que es el más fiable.

1.  En tu panel de CyberPanel, ve a `Websites` -> `List Websites` -> `Manage` (para tu dominio).
2.  En la sección `Configuraciones`, haz clic en **`Rewrite Rules`** y **asegúrate de que esté completamente vacía**. Guarda los cambios.
3.  Ahora, en la misma sección, haz clic en **`vHost Conf`**.
4.  **Borra todo el contenido** y pega **el siguiente bloque completo**. Este bloque contiene tu configuración existente de PHP y SSL, con las adiciones necesarias para la aplicación Node.js.

   ```
   docRoot                   $VH_ROOT/public_html
   vhDomain                  $VH_NAME
   vhAliases                 www.$VH_NAME
   adminEmails               gbiaggioni@gmail.com
   enableGzip                1
   enableIpGeo               1
   
   index  {
     useServer               0
     indexFiles              index.php, index.html
   }
   
   errorlog $VH_ROOT/logs/$VH_NAME.error_log {
     useServer               0
     logLevel                WARN
     rollingSize             10M
   }
   
   accesslog $VH_ROOT/logs/$VH_NAME.access_log {
     useServer               0
     logFormat               "%h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\""
     logHeaders              5
     rollingSize             10M
     keepDays                10
     compressArchive         1
   }
   
   scripthandler  {
     add                     lsapi:esque9858 php
   }
   
   extprocessor esque9858 {
     type                    lsapi
     address                 UDS://tmp/lshttpd/esque9858.sock
     maxConns                10
     env                     LSAPI_CHILDREN=10
     initTimeout             600
     retryTimeout            0
     persistConn             1
     pcKeepAliveTimeout      1
     respBuffer              0
     autoStart               1
     path                    /usr/local/lsws/lsphp83/bin/lsphp
     extUser                 esque9858
     extGroup                esque9858
     memSoftLimit            2047M
     memHardLimit            2047M
     procSoftLimit           400
     procHardLimit           500
   }
   
   extprocessor qreasy-app {
     type                    node
     address                 127.0.0.1:3001
     maxConns                100
     pcKeepAliveTimeout      60
     initTimeout             60
     retryTimeout            0
     respBuffer              0
     autoStart               0
   }
   
   context /studio/ {
     type                    proxy
     handler                 qreasy-app
     addDefaultCharset       off
   }
   
   context /.well-known/acme-challenge {
     location                /usr/local/lsws/Example/html/.well-known/acme-challenge
     allowBrowse             1
   
     rewrite  {
       enable                  0
     }
     addDefaultCharset       off
   
     phpIniOverride  {
   
     }
   }
   
   vhssl  {
     keyFile                 /etc/letsencrypt/live/esquel.org.ar/privkey.pem
     certFile                /etc/letsencrypt/live/esquel.org.ar/fullchain.pem
     certChain               1
     sslProtocol             24
     enableECDHE             1
     renegProtection         1
     sslSessionCache         1
     enableSpdy              15
     enableStapling           1
     ocspRespMaxAge           86400
   }
   ```
5.  **Guarda los cambios.**

### Paso 4: Iniciar la Aplicación y Finalizar (Como `root`)

1.  **Como `root`, desde la carpeta de la aplicación**, inicia la aplicación con PM2. Esta vez, usamos la sintaxis correcta para que el flag `--uid` sea reconocido por PM2, asegurando que el proceso se ejecute como el usuario `esque9858`. Esto es fundamental.
    ```bash
    pm2 start npm --name "qreasy" --uid esque9858 --gid esque9858 -- start
    ```
2.  **Guarda la lista de procesos de PM2** para que se reinicie automáticamente:
    ```bash
    pm2 save
    ```
3.  **Verifica que la aplicación está en línea** con `pm2 list`. **Ahora debería mostrar a `esque9858` como el usuario** y el estado `online`.
4.  **Reinicia el servidor web (El Paso Final!)** Para que todos los cambios se apliquen.
    ```bash
    sudo systemctl restart lsws
    ```

¡Y listo! Ahora, cuando visites `https://esquel.org.ar/studio/`, debería funcionar correctamente.

---

### 🔄 Cómo Actualizar la Aplicación con Cambios de GitHub

Cuando realices cambios en tu código y los subas a GitHub, sigue este nuevo procedimiento simplificado **(ejecutado siempre como `root`)**:

1.  **Conéctate a tu servidor por SSH** como `root`.
2.  **Navega al directorio de tu proyecto:**
    ```bash
    cd /home/esquel.org.ar/public_html/studio
    ```
3.  **Descarga los últimos cambios desde GitHub:**
    ```bash
    git pull origin main
    ```
4.  **Instala las dependencias (si hubo cambios en `package.json`):**
    ```bash
    npm install
    ```
5.  **Reconstruye la aplicación para producción:**
    ```bash
    npm run build
    ```
6.  **Asegura que los nuevos archivos tengan los permisos correctos:**
    ```bash
    chown -R esque9858:esque9858 /home/esquel.org.ar/public_html/studio
    ```
7.  **Reinicia la aplicación con PM2:**
    ```bash
    pm2 restart qreasy
    ```
¡Eso es todo! La nueva versión estará en línea.
    

