
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
-   **Gestor de Procesos:** [PM2](https://pm2.keymetrics.io/)

---

## 🚀 Despliegue y Mantenimiento en Servidor

Esta guía contiene los pasos para desplegar, actualizar y diagnosticar la aplicación en tu servidor.

### Primera Vez (Despliegue Inicial Limpio)

Este proceso de "reinicio limpio" debe ejecutarse **como `root`** y es la solución definitiva para cualquier error grave o para la configuración inicial.

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
    pm2 start server.js --name "qreasy" --uid esque9858 --gid esque9858

    # 6. Guardar la nueva y correcta configuración de PM2.
    pm2 save
    ```
4.  Verifica que todo funciona con `pm2 list` y `pm2 logs qreasy`. La aplicación debería aparecer como "online" con un PID asignado.

### 🔄 Cómo Actualizar la Aplicación (Automatizado)

Para **todas las futuras actualizaciones**, simplemente ejecuta el script `update.sh`. Este script automatiza el proceso de reinicio limpio de forma segura.

1.  **Conéctate a tu servidor por SSH** como `root`.
2.  **Navega al directorio del proyecto:**
    ```bash
    cd /home/esquel.org.ar/public_html/studio
    ```
3.  **Ejecuta el script de actualización:**
    ```bash
    bash ./update.sh
    ```
    *¡Y eso es todo! El script se encargará de limpiar PM2, descargar cambios, reinstalar dependencias, reconstruir, arreglar permisos y reiniciar la aplicación correctamente.*

---

## 🩺 Solución de Problemas y Diagnóstico (Health Check)

Si la aplicación no funciona, antes de intentar cualquier otra cosa, ejecuta el script de diagnóstico. Te dará un informe detallado de qué componente está fallando.

1.  **Conéctate a tu servidor por SSH** como `root`.
2.  **Navega al directorio del proyecto.**
3.  **Ejecuta el script:**
    ```bash
    bash ./health-check.sh
    ```
4.  El script te indicará con [OK] o [ERROR] el estado de cada componente y te dará pistas sobre cómo solucionarlo.

### Checklist Final del Servidor Web (LiteSpeed / CyberPanel)

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
**Importante:** Después de guardar el `vHost Conf`, recuerda **reiniciar el servidor web** para que los cambios surtan efecto.
```bash
sudo systemctl restart lsws
```
