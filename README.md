
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

Esta guía contiene los pasos finales y probados para desplegar la aplicación en tu entorno. Sigue cada paso meticulosamente.

### Paso 1 al 5: Preparación del Servidor (Si ya lo hiciste, puedes omitirlos)
Asegúrate de haber completado los siguientes pasos iniciales al menos una vez:
1.  **Conexión SSH** e instalación de Node.js y PM2.
2.  **Configuración de la Base de Datos** en CyberPanel.
3.  **Despliegue del código** con `git clone` en la carpeta `studio`.
4.  **Configuración de `.env.local`** para producción.
5.  **Construcción de la aplicación** con `npm run build`.
6.  **Inicio de la aplicación con PM2** usando `pm2 start npm --name "qreasy" -- start` y `pm2 save`. Verifica que esté en línea con `pm2 list`.

### Paso 6: Ajustar Permisos de la Carpeta (¡Paso Crucial!)
Este es el paso más importante para evitar errores `403` o `404`. Le da al servidor web (LiteSpeed) los permisos necesarios para acceder a los archivos de tu proyecto.

1.  **Conéctate a tu servidor por SSH.**
2.  Navega a la carpeta que contiene tu proyecto (un nivel por encima de `studio`).
    ```bash
    cd /home/esquel.org.ar/public_html/
    ```
3.  Ejecuta los siguientes dos comandos para establecer el propietario y los permisos correctos. **Es vital que el propietario sea el usuario de tu sitio (`esque9858`), no `root`.**
    
    ```bash
    # Este comando cambia el propietario de todos los archivos al usuario correcto.
    sudo chown -R esque9858:esque9858 studio

    # Este comando asegura que los permisos sean los adecuados (lectura y ejecución).
    sudo chmod -R 755 studio
    ```

### Paso 7: Configurar `vHost Conf` (La Clave Final)
Esta configuración le dice al servidor cómo encontrar y comunicarse con tu aplicación Node.js.

1.  En tu panel de CyberPanel, ve a `Websites` -> `List Websites` -> `Manage` (para tu dominio).
2.  En la sección `Configuraciones`, haz clic en **`Rewrite Rules`** y **asegúrate de que esté completamente vacía**. Guarda los cambios.
3.  Ahora, en la misma sección, haz clic en **`vHost Conf`**.
4.  **Borra todo el contenido** y pega **solamente** el siguiente bloque. Este código define tu aplicación y le dice al servidor cómo redirigir el tráfico hacia ella.

   ```
   extprocessor qreasy-app {
     type                    node
     address                 127.0.0.1:3001
     maxConns                100
     pcKeepAliveTimeout      60
     initTimeout             60
     retryTimeout            0
     respBuffer              0
   }

   rewrite  {
     enable                  1
     autoLoadHtaccess        0
     RewriteRule ^/studio/(.*)$ http://127.0.0.1:3001/studio/$1 [P,L]
   }
   ```
5.  **Guarda los cambios.**

### Paso 8: Reiniciar el Servidor Web (¡El Paso Final!)
Para que todos estos cambios en la configuración y los permisos se apliquen, **es absolutamente necesario que reinicies el servidor web**.
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
    git pull origin main
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
