
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

**Ejemplo para producción con el dominio `esquel.org.ar` y desplegada en el subdirectorio `/studio`:**
```env
# .env.local (PRODUCCIÓN)

# Configuración de la Base de Datos de Producción
DB_HOST=localhost
DB_USER=el_usuario_de_tu_bd
DB_PASSWORD=la_contraseña_de_tu_bd
DB_NAME=el_nombre_de_tu_bd

# ⚠️ ¡IMPORTANTE!
# URL base para generar las URLs cortas con HTTPS y el subdirectorio.
# DEBE INCLUIR el /studio al final.
NEXT_PUBLIC_BASE_URL=https://esquel.org.ar/studio
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
    Pega el contenido relevante para producción, **asegurándote de que `NEXT_PUBLIC_BASE_URL` incluya `/studio`**.

4.  **Construye la aplicación para producción:**
    Este paso aplica la configuración `basePath` y optimiza la app.
    ```bash
    npm run build
    ```

### Paso 4: Establecer Permisos (¡Crucial!)
El servidor web (OpenLiteSpeed) y el gestor de procesos (PM2) necesitan permisos para leer y ejecutar los archivos de tu proyecto. Este es un paso crítico.

Ejecuta estos comandos desde la raíz del proyecto (`/home/esquel.org.ar/public_html/studio`):

```bash
# Asigna permisos correctos a las carpetas (755: rwx r-x r-x)
sudo find . -type d -exec chmod 755 {} \;

# Asigna permisos de solo lectura a la mayoría de los archivos (644: rw- r-- r--)
sudo find . -type f -exec chmod 644 {} \;

# ¡IMPORTANTE! Devuelve el permiso de ejecución al script de Next.js.
# El comando anterior elimina este permiso, pero es necesario para que PM2 pueda iniciar la aplicación.
sudo chmod +x node_modules/.bin/next
```

### Paso 5: Ejecutar la Aplicación con PM2

1.  **Inicia la aplicación desde el directorio correcto:**
    Asegúrate de estar en `/home/esquel.org.ar/public_html/studio` y ejecuta:
    ```bash
    # Inicia la app con el nombre 'qreasy'. El puerto 3001 se define en el script 'start' de package.json.
    pm2 start npm --name "qreasy" -- start
    ```

2.  **Guarda la configuración de PM2:**
    ```bash
    pm2 save
    pm2 startup
    ```
    Ejecuta el comando que te proporcione `pm2 startup` para asegurar que la app se reinicie con el servidor.

### Paso 6: Configurar el Proxy en CyberPanel (¡Solución Definitiva!)

Este es el paso final para conectar tu dominio con la aplicación. **Vamos a usar el método correcto y más robusto para tu servidor.**

1.  En tu panel de CyberPanel, ve a `Websites` -> `List Websites` -> `Manage` (para tu dominio `esquel.org.ar`).
2.  Busca la sección `Configuraciones` y haz clic en **`vHost Conf`**.
3.  **Borra cualquier contenido que haya** y pega el siguiente bloque de código **exactamente como está**:

    ```
    # 1. DEFINE THE EXTERNAL APPLICATION
    # This tells OpenLiteSpeed about your Node.js app running on port 3001.
    extprocessor qreasy-app {
      type                    node
      address                 127.0.0.1:3001
      maxConns                100
      pcKeepAliveTimeout      60
      initTimeout             60
      retryTimeout            0
      respBuffer              0
    }
    
    # 2. CREATE A PROXY CONTEXT
    # This is the correct way to map a subfolder to your application.
    # It tells the server: "any request to /studio/ should be sent to qreasy-app".
    # This is better than a rewrite rule because it handles pathing correctly.
    context /studio/ {
      type                    proxy
      handler                 qreasy-app
      addDefaultCharset       off
    }
    ```
    
4.  Haz clic en **"Guardar"**.
5.  **Importante**: Vuelve a la página de `Manage` de tu dominio y ve a `Rewrite Rules`. **Asegúrate de que el cuadro de texto de las reglas de reescritura esté completamente vacío** y guarda los cambios para evitar conflictos.

### Paso 7: Reiniciar el Servidor Web (¡El Paso Final y Crucial!)

Para que todos estos cambios en la configuración se apliquen, **es absolutamente necesario que reinicies el servidor web**.

Abre la terminal de tu servidor y ejecuta:
```bash
sudo systemctl restart lsws
```

¡Y listo! Ahora, cuando visites `https://esquel.org.ar/studio/`, debería funcionar correctamente.

---

### 🔄 Cómo Actualizar la Aplicación con Cambios de GitHub
Cuando realices cambios en tu código y los subas a GitHub, sigue estos pasos para actualizar la aplicación en tu servidor:

1.  **Conéctate a tu servidor por SSH.**
2.  **Navega al directorio de tu proyecto:**
    ```bash
    cd /home/esquel.org.ar/public_html/studio
    ```
3.  **Descarga los últimos cambios desde GitHub:**
    ```bash
    git pull origin master
    ```

4.  **Instala las dependencias (si hubo cambios en `package.json`):**
    ```bash
    npm install
    ```
5.  **Reconstruye la aplicación para producción:**
    Este paso es **crucial** para que tus cambios se apliquen.
    ```bash
    npm run build
    ```
6.  **Reaplica los permisos por si se añadieron nuevos archivos:**
    ```bash
    sudo find . -type d -exec chmod 755 {} \;
    sudo find . -type f -exec chmod 644 {} \;
    sudo chmod +x node_modules/.bin/next
    ```

7.  **Reinicia la aplicación con PM2:**
    PM2 cargará la nueva versión sin tiempo de inactividad.
    ```bash
    pm2 restart qreasy
    ```
8.  **Verifica el estado:**
    Asegúrate de que la aplicación esté `online`.
    ```bash
    pm2 list
    ```
---

### 🚨 Guía de Diagnóstico y Solución de Problemas (Checklist Final)

Si después de seguir todos los pasos aún tienes problemas, sigue esta lista de verificación en orden.

#### Paso A: Verifica que la Aplicación Esté Realmente Corriendo

1.  **Ejecuta `pm2 list` en la terminal de tu servidor**:
    -   ¿El estado (`status`) de `qreasy` es `online`?
        -   **Si es `online`**: ¡Perfecto! La aplicación funciona. El problema está en la comunicación con el servidor web. Ve al **Paso B**.
        -   **Si es `errored`**: La aplicación no puede arrancar. Lee los registros con `pm2 logs qreasy` para ver el error (probablemente una conexión fallida a la base de datos). Revisa tu archivo `.env.local`.

#### Paso B: Verifica la Conexión Directa a la Aplicación

Si PM2 muestra `online`, vamos a confirmar que responde localmente.

1.  **Ejecuta este comando en la terminal de tu servidor**:
    ```bash
    curl -I http://127.0.0.1:3001/studio/
    ```
    -   **Si obtienes una respuesta `HTTP/1.1 200 OK`**: ¡FELICIDADES! Tu aplicación funciona perfectamente. El problema está 100% en la configuración del servidor web (Paso C).
    -   **Si obtienes `Connection refused`**: Es muy raro si PM2 dice `online`, pero podría indicar un firewall interno.

#### Paso C: Verifica la Configuración del Servidor Web (CyberPanel/OpenLiteSpeed)

Este es el paso final y el más común.

1.  **Revisa la `vHost Conf`**: Asegúrate de que el contenido en `Manage` -> `vHost Conf` sea **exactamente** el del **Paso 6** y que no haya nada más.
2.  **Revisa las `Rewrite Rules`**: Ve a `Manage` -> `Rewrite Rules` y asegúrate de que el cuadro de texto esté **completamente vacío**.
3.  **Guarda y REINICIA el Servidor Web (¡EL PASO MÁS IMPORTANTE!)**:
    -   Después de guardar los cambios, ejecuta este comando en la terminal. **Sin este paso, los cambios no se aplican.**
    ```bash
    sudo systemctl restart lsws
    ```
4.  **Prueba en el navegador**:
    -   Abre una nueva pestaña en modo incógnito (para evitar la caché) y visita `https://esquel.org.ar/studio/`. Si ves errores 404 en la consola, es casi seguro que el reinicio de `lsws` no se completó correctamente o las reglas no se guardaron.
