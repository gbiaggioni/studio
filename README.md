
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
-   **Contenerización:** [Docker](https://www.docker.com/)

---

## 🐳 Dockerizando QREasy

La forma recomendada de desplegar esta aplicación es a través de Docker. Esto garantiza un entorno consistente y un despliegue robusto.

### 1. Construir la Imagen de Docker

Desde la raíz del proyecto (donde se encuentra el `Dockerfile`), ejecuta el siguiente comando. Esto creará una imagen llamada `qreasy`.

```bash
docker build -t qreasy .
```

### 2. Ejecutar el Contenedor

Una vez construida la imagen, puedes iniciar un contenedor. Es **crucial** pasar las variables de entorno necesarias para la conexión a la base de datos y la configuración de la URL base.

```bash
docker run -p 3001:3001 \
  -e DB_HOST='la_ip_o_host_de_tu_db' \
  -e DB_USER='tu_usuario_de_db' \
  -e DB_PASSWORD='tu_contraseña_de_db' \
  -e DB_NAME='el_nombre_de_tu_db' \
  -e NEXT_PUBLIC_BASE_URL='https://esquel.org.ar/studio' \
  --name qreasy-container \
  -d --restart unless-stopped \
  qreasy
```

**Desglose del comando:**
-   `-p 3001:3001`: Mapea el puerto 3001 de tu servidor al puerto 3001 dentro del contenedor.
-   `-e VARIABLE='valor'`: Pasa cada variable de entorno necesaria.
-   `--name qreasy-container`: Le da un nombre fácil de recordar a tu contenedor.
-   `-d`: Ejecuta el contenedor en segundo plano (detached mode).
-   `--restart unless-stopped`: Configura el contenedor para que se reinicie automáticamente si se detiene, excepto si lo detienes tú manualmente.
-   `qreasy`: El nombre de la imagen que quieres usar.

Con esto, la aplicación estará corriendo dentro de un contenedor Docker y accesible a través del puerto 3001 de tu servidor.

---

## 🚀 Despliegue y Mantenimiento en Servidor (Método Alternativo sin Docker)

Esta guía contiene los pasos para desplegar la aplicación directamente en el servidor usando PM2.

### 🔄 Cómo Actualizar o Reparar la Aplicación (Método Recomendado)

Para **todas las futuras actualizaciones** o si la aplicación deja de funcionar por cualquier motivo, simplemente ejecuta el script `update.sh`. Este script realiza un **"reinicio limpio"** que automatiza todo el proceso de forma segura.

1.  **Conéctate a tu servidor por SSH** como `root`.
2.  **Navega al directorio del proyecto:**
    ```bash
    cd /home/esquel.org.ar/public_html/studio
    ```
3.  **Ejecuta el script de actualización y reinicio limpio:**
    ```bash
    sh ./update.sh
    ```
    *¡Y eso es todo! El script se encargará de limpiar PM2, descargar los últimos cambios de GitHub, reinstalar dependencias, reconstruir la aplicación, arreglar permisos y reiniciarla correctamente.*

### 🛠️ Despliegue Inicial o Reinicio Limpio Manual

Este proceso debe ejecutarse **como `root`** y solo es necesario la primera vez que despliegas el proyecto o si el script `update.sh` falla por alguna razón extrema.

1.  **Conéctate a tu servidor por SSH** como `root`.
2.  **Navega al directorio de tu proyecto:**
    ```bash
    cd /home/esquel.org.ar/public_html/studio
    ```
3.  **Ejecuta los siguientes comandos uno por uno, en este orden exacto:**

    ```bash
    # 1. ¡Paso Crucial! Detener, eliminar y borrar la configuración corrupta de PM2.
    pm2 stop qreasy
    pm2 delete qreasy
    pm2 save --force

    # 2. Descargar los últimos cambios desde GitHub.
    # Usamos fetch y reset para forzar la actualización y evitar conflictos.
    git fetch origin
    git reset --hard origin/master

    # 3. Instalar dependencias y construir la aplicación.
    npm install
    npm run build

    # 4. ¡Paso Crucial! Cambiar la propiedad de todos los archivos al usuario del sitio.
    chown -R esque9858:esque9858 /home/esquel.org.ar/public_html/studio

    # 5. Iniciar la aplicación desde cero con el comando correcto y limpio.
    # Se usa 'npm' para ejecutar el script 'start' definido en package.json.
    pm2 start npm --name "qreasy" --uid esque9858 --gid esque9858 -- run start

    # 6. Guardar la nueva y correcta configuración de PM2.
    pm2 save
    ```
4.  Verifica que todo funciona con `pm2 list` y `pm2 logs qreasy`. La aplicación debería aparecer como "online" con un PID asignado.

---

## 🩺 Solución de Problemas y Diagnóstico (Health Check)

Si la aplicación no funciona, antes de intentar cualquier otra cosa, ejecuta el script de diagnóstico. Te dará un informe detallado de qué componente está fallando.

1.  **Conéctate a tu servidor por SSH** como `root`.
2.  **Navega al directorio del proyecto.**
3.  **Ejecuta el script:**
    ```bash
    sh ./health-check.sh
    ```
4.  El script te indicará con [OK] o [ERROR] el estado de cada componente y te dará pistas sobre cómo solucionarlo.

---
## ⚙️ Configuración del Servidor Web (LiteSpeed / CyberPanel)

Si el `health-check.sh` muestra que la aplicación está corriendo en el puerto 3001 pero no puedes acceder desde el dominio, el problema casi siempre está en la configuración del servidor web.

#### 1. Rewrite Rules
Asegúrate de que la sección **`Rewrite Rules`** en la configuración de tu sitio en CyberPanel esté **completamente vacía**.

#### 2. vHost Conf
Esta es la configuración final, correcta y robusta para tu `vHost Conf`. Ve a `Websites` -> `List Websites` -> `Manage` (para tu dominio) -> `vHost Conf` y reemplaza todo el contenido con este bloque:

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
  env                     LSAPI_CHILD_PROCESSES=10
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
**Importante:** Después de guardar el `vHost Conf`, recuerda **reiniciar el servidor web** para que los cambios surtan efecto.
```bash
sudo systemctl restart lsws
```
