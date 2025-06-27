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
-   `npm run start`: Inicia la aplicación en modo de producción (requiere una `build` previa). El puerto se define en el script `start`.
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

**Ejemplo para producción con el dominio `esquel.org.ar`:**
```env
# .env.local

# Configuración de la Base de Datos de Producción
DB_HOST=localhost
DB_USER=el_usuario_de_tu_bd
DB_PASSWORD=la_contraseña_de_tu_bd
DB_NAME=el_nombre_de_tu_bd

# URL base para generar las URLs cortas con HTTPS
NEXT_PUBLIC_BASE_URL=https://esquel.org.ar
```

## 🚀 Despliegue en DonWeb Cloud Server (con CyberPanel)

Esta guía describe cómo desplegar la aplicación en un servidor cloud de DonWeb que utiliza la imagen de **CyberPanel**.

### Paso 1: Conexión y Preparación del Servidor
Antes de desplegar, asegúrate de que tu servidor tenga todo lo necesario.
1.  **Conéctate a tu servidor por SSH:**
    ```bash
    ssh root@<IP_DE_TU_SERVIDOR>
    ```
2.  **Instala Node.js:** Es posible que la imagen de CyberPanel no incluya Node.js. La forma más sencilla de instalarlo es usando los scripts de NodeSource. Ejecuta los siguientes comandos para instalar Node.js 20.x:
    ```bash
    # Para sistemas basados en CentOS/AlmaLinux (como los de DonWeb)
    sudo dnf install -y nodejs
    
    # Para sistemas basados en Debian/Ubuntu
    # curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
    # sudo apt-get install -y nodejs
    ```
    Verifica la instalación con `node -v` y `npm -v`.
3.  **Instala PM2 globalmente:** PM2 es un gestor de procesos que mantendrá tu aplicación de Next.js corriendo.
    ```bash
    npm install pm2 -g
    ```

### Paso 2: Configuración de la Base de Datos
1.  **Inicia sesión en tu panel de CyberPanel.**
2.  Navega a `Bases de Datos` -> `Crear Base de Datos`.
3.  Selecciona tu sitio web (`esquel.org.ar`) en el desplegable.
4.  Asigna un **nombre** para la base de datos (ej. `esquel_qreasy`), un **usuario** y una **contraseña segura**. Guárdalos, los necesitarás para el archivo `.env.local`.
5.  Una vez creada, ve a `Bases de Datos` -> `phpMyAdmin` para administrarla.
6.  Dentro de phpMyAdmin, selecciona la base de datos que acabas de crear en el panel izquierdo.
7.  Ve a la pestaña `SQL`.
8.  Copia el contenido completo del archivo `sql/schema.sql` de tu proyecto y pégalo en el cuadro de texto.
9.  Haz clic en **"Continuar"** o **"Go"** para ejecutar el script y crear la tabla `qr_codes`.

### Paso 3: Desplegar el Código de la Aplicación

1.  **Clona el repositorio desde GitHub:**
    Navega a la carpeta donde deseas instalar el proyecto. En tu caso, es un subdirectorio.
    ```bash
    # Navega al directorio raíz de tu sitio
    cd /home/esquel.org.ar/public_html
    
    # Clona el proyecto en una carpeta llamada 'studio'
    git clone https://github.com/tu-usuario-de-github/esquel.ar.git studio
    
    # Entra en el directorio del proyecto
    cd studio
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
    Pega el contenido relevante para producción.

4.  **Construye la aplicación para producción:**
    ```bash
    npm run build
    ```

### Paso 4: Ejecutar la Aplicación con PM2

1.  **Inicia la aplicación desde el directorio correcto:**
    Asegúrate de estar en `/home/esquel.org.ar/public_html/studio` y ejecuta:
    ```bash
    # Inicia la app con el nombre 'qreasy'. El puerto se define en el script 'start' de package.json.
    pm2 start npm --name "qreasy" -- start
    ```

2.  **Guarda la configuración de PM2:**
    ```bash
    pm2 save
    pm2 startup
    ```
    Ejecuta el comando que te proporcione `pm2 startup` para asegurar que la app se reinicie con el servidor.

### Paso 5: Configurar Proxy Inverso y Forzar HTTPS

En CyberPanel, las reglas de reescritura se gestionan en el panel de administración del sitio. **No uses archivos `.htaccess`**.

1.  **Ve a CyberPanel:** Navega a `Websites` -> `List Websites` -> `Manage` para tu dominio.
2.  **Configura SSL:** En la sección "SSL", haz clic en "Issue SSL" para instalar un certificado y habilitar HTTPS. Asegúrate de que `NEXT_PUBLIC_BASE_URL` en tu `.env.local` use `https://`.
3.  **Añade Reglas de Proxy en "Rewrite Rules":**
    Desplázate a la sección **"Rewrite Rules"** y pega el siguiente bloque de código. Este se encarga de forzar HTTPS y de redirigir correctamente las peticiones a tu aplicación Next.js.
    
    ```
    RewriteEngine On
    
    # 1. Forzar HTTPS (si CyberPanel no lo hace automáticamente)
    RewriteCond %{HTTPS} !=on
    RewriteRule ^/?(.*) https://%{SERVER_NAME}/$1 [R,L]
    
    # 2. Proxy para la aplicación Next.js que se ejecuta en un subdirectorio
    # Esto captura cualquier petición a /studio/ y la reenvía a tu app en el puerto 3001,
    # manteniendo el /studio/ en la ruta para que Next.js funcione correctamente.
    RewriteRule ^/studio(/.*)?$ http://127.0.0.1:3001/studio$1 [P,L]
    ```

4.  **Guardar y Reiniciar:**
    -   Haz clic en "Save Rewrite Rules".
    -   Para que los cambios se apliquen de inmediato, reinicia el servidor web desde la terminal:
        ```bash
        sudo systemctl restart lsws
        ```
---

### 🔄 Cómo Actualizar la Aplicación con Cambios de GitHub
Cuando realices cambios en tu código y los subas a GitHub, sigue estos pasos para actualizar la aplicación en tu servidor:

1.  **Conéctate a tu servidor por SSH.**
2.  **Navega al directorio de tu proyecto:**
    ```bash
    cd /home/esquel.org.ar/public_html/studio
    ```
3.  **Descarga los últimos cambios desde GitHub:**
    Aquí es donde puede ocurrir un error si tienes cambios locales.
    ```bash
    git pull origin master
    ```
    *Si recibes un error sobre que tus cambios locales serían sobreescritos, consulta la sección de solución de problemas a continuación.*

4.  **Instala las dependencias (si hubo cambios en `package.json`):**
    ```bash
    npm install
    ```
5.  **Reconstruye la aplicación para producción:**
    Este paso es **crucial** para que tus cambios se apliquen.
    ```bash
    npm run build
    ```
6.  **Reinicia la aplicación con PM2:**
    PM2 cargará la nueva versión sin tiempo de inactividad.
    ```bash
    pm2 restart qreasy
    ```
7.  **Verifica el estado:**
    Asegúrate de que la aplicación esté `online`.
    ```bash
    pm2 list
    ```
---

### 🚨 Solución de Problemas

#### Error de `git pull`: "Your local changes to the following files would be overwritten"

Este error ocurre porque tienes cambios en archivos de tu servidor (como `package.json` o `package-lock.json`) que no están en GitHub. La solución es descartar esos cambios locales y forzar al servidor a usar la versión de GitHub.

1.  **Navega al directorio de tu proyecto:**
    ```bash
    cd /home/esquel.org.ar/public_html/studio
    ```
2.  **Resetea tus archivos locales:** Este comando descarta todos tus cambios locales y deja el directorio de trabajo limpio.
    ```bash
    git reset --hard HEAD
    ```
3.  **Descarga los cambios de GitHub:** Ahora que no hay conflictos, `git pull` funcionará. Usa el nombre de tu rama principal (`master`).
    ```bash
    git pull origin master
    ```
4.  **Continúa con el proceso de actualización normal:**
    ```bash
    npm install
    npm run build
    pm2 restart qreasy
    ```

#### Error de `git pull`: "fatal: couldn't find remote ref main"

Este error significa que la rama principal en tu repositorio de GitHub se llama `master` y no `main`. Simplemente reemplaza `main` por `master` en el comando:
```bash
git pull origin master
```

#### Estado 'Errored' en PM2
Si `pm2 list` muestra tu aplicación `qreasy` con el estado `errored`, significa que la aplicación no puede iniciarse. La causa más probable es que PM2 la está ejecutando desde el directorio equivocado o con un comando incorrecto.

Sigue estos pasos **exactos** en la terminal de tu servidor para corregirlo:

1.  **Detén y elimina el proceso antiguo:**
    Esto limpiará la configuración incorrecta de PM2.
    ```bash
    pm2 stop qreasy
    pm2 delete qreasy
    ```

2.  **Navega al directorio correcto del proyecto:**
    Asegúrate de estar en la carpeta donde se encuentra tu archivo `package.json`.
    ```bash
    cd /home/esquel.org.ar/public_html/studio
    ```

3.  **Inicia la aplicación nuevamente con PM2 (Comando Simplificado):**
    Este comando le dice a PM2 que use el `npm start` de tu `package.json` actual.
    ```bash
    pm2 start npm --name "qreasy" -- start
    ```

4.  **Verifica el estado:**
    Ahora `pm2 list` debería mostrar el estado como `online`.
    ```bash
    pm2 list
    ```
    Si sigue fallando, revisa los registros para ver el error específico:
    ```bash
    pm2 logs qreasy
    ```

5.  **Guarda la nueva configuración correcta:**
    Una vez que el estado sea `online`, guarda la lista de procesos para que PM2 la recuerde después de un reinicio del servidor.
    ```bash
    pm2 save
    ```

#### Error de Puerto en Uso (EADDRINUSE)
Si en los registros (`pm2 logs qreasy`) ves un error como `Error: listen EADDRINUSE: address already in use :::3001`, significa que otro proceso ya está ocupando el puerto 3001 y tu aplicación no puede iniciarse.

Sigue estos pasos en la terminal de tu servidor para solucionarlo:

1.  **Detén y elimina todos los procesos de PM2:**
    Esto asegura que no haya instancias antiguas o duplicadas intentando ejecutarse.
    ```bash
    pm2 stop all
    pm2 delete all
    ```

2.  **Encuentra y detén el proceso que ocupa el puerto:**
    Averigua qué proceso está usando el puerto 3001.
    ```bash
    sudo lsof -i :3001
    ```
    Este comando te mostrará una lista de procesos. Fíjate en la columna `PID` (Process ID). Si ves algún proceso, detenlo usando su PID. Por ejemplo, si el PID es `12345`:
    ```bash
    sudo kill -9 12345
    ```
    *Nota: Si el comando `lsof` no está disponible, puedes instalarlo con `sudo yum install lsof` en CentOS/AlmaLinux o `sudo apt-get install lsof` en Debian/Ubuntu.*

3.  **Reinicia la aplicación con PM2:**
    Ahora que el puerto está libre, navega al directorio de tu proyecto y reinicia la aplicación.
    ```bash
    cd /home/esquel.org.ar/public_html/studio
    pm2 start npm --name "qreasy" -- start
    ```

4.  **Verifica los registros y el estado:**
    Comprueba que la aplicación se haya iniciado correctamente.
    ```bash
    pm2 logs qreasy  # Deberías ver un mensaje de que el servidor se inició en el puerto 3001
    pm2 list         # Debería mostrar el estado como 'online'
    ```

5.  **Guarda la configuración de PM2:**
    Una vez que todo funcione, guarda la lista de procesos para que se reinicie correctamente con el servidor.
    ```bash
    pm2 save
    ```
