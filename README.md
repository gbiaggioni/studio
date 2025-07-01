# 🆘 ¡ATENCIÓN! LA SOLUCIÓN DEFINITIVA ESTÁ AQUÍ 🆘
## Si ves un error de "Configuración de la base de datos incompleta" o "Internal Server Error", LEE ESTA SECCIÓN.

El problema casi siempre es doble:
1.  Un error en el archivo de entorno `.env.local` (causado por un script incompatible que ya corregimos).
2.  Un conflicto con un contenedor Docker antiguo que no se eliminó.

**Sigue estos 4 pasos en orden en tu servidor para solucionarlo de una vez por todas:**

### Paso 1: Detén y Elimina el Contenedor Antiguo (¡MUY IMPORTANTE!)

Cada vez que quieras actualizar, **DEBES** ejecutar esto primero para evitar conflictos de nombres.

```bash
sudo docker stop qreasy-container
sudo docker rm qreasy-container
```
*(Es normal si estos comandos dan un error de "No such container", significa que no había uno corriendo).*

### Paso 2: Genera un archivo `.env.local` perfecto

Hemos creado un script que evita cualquier error manual.

1.  Asegúrate de estar en el directorio correcto: `cd /home/esquel.org.ar/qr`
2.  **Dale permisos de ejecución al script:**
    ```bash
    chmod +x configure-env.sh
    ```
3.  **Ejecuta el script asistente:**
    ```bash
    ./configure-env.sh
    ```
    El script te pedirá los datos de tu base de datos y la URL de tu sitio, y creará un archivo `.env.local` limpio y sin errores.

### Paso 3: Reconstruye la imagen de Docker

1.  Desde `/home/esquel.org.ar/qr`, reconstruye la imagen para asegurarte de que tiene el último código.
    ```bash
    sudo docker build -t qreasy-app .
    ```

### Paso 4: Inicia el Nuevo Contenedor

1.  Con el archivo `.env.local` perfecto y la imagen reconstruida, inicia el nuevo contenedor:
    ```bash
    sudo docker run -d --restart unless-stopped --name qreasy-container -p 3001:3000 --env-file ./.env.local qreasy-app
    ```

Después de estos pasos, la aplicación en `https://qr.esquel.ar` debería funcionar. Si no, revisa los logs con `sudo docker logs qreasy-container` y comprueba la configuración del Reverse Proxy en el Paso 4 de la guía de despliegue más abajo.

---

# QREasy - Gestor de Códigos QR con Docker

QREasy es una aplicación web moderna y sencilla para crear, gestionar y compartir códigos QR. Esta versión está configurada para un despliegue robusto y simplificado usando Docker.

## ✨ Características Principales

-   **Creación de Códigos QR:** Genera códigos QR dinámicamente a partir de cualquier URL.
-   **Gestión Completa:** Edita, copia, imprime y elimina tus códigos QR fácilmente.
-   **URL Corta Única:** Cada QR obtiene una URL única para redirección (ej. `qr.esquel.ar/r/xyz123`).
-   **Responsivo y Moderno:** Interfaz adaptable a cualquier dispositivo.

## 🚀 Stack Tecnológico

-   **Framework:** Next.js (App Router)
-   **Lenguaje:** TypeScript
-   **Estilo:** Tailwind CSS & ShadCN UI
-   **Base de Datos:** MariaDB / MySQL
-   **Contenerización:** Docker
---

## 🧪 Diagnóstico de la Base de Datos

Si sigues teniendo problemas después de seguir los 4 pasos principales, puedes verificar la conexión a la base de datos directamente desde la terminal de tu servidor.

1.  **Asegúrate de tener las credenciales correctas** en el archivo `.env.local` ejecutando el Paso 2 de la guía principal de nuevo.

2.  **Instala las dependencias necesarias** para el script de prueba (solo necesitas hacerlo una vez):
    ```bash
    npm install
    ```

3.  **Ejecuta el script de prueba:**
    ```bash
    node check-db.js
    ```
    El script usará las credenciales de tu archivo `.env.local` e intentará conectarse. Te dará un mensaje de **¡ÉXITO!** o te mostrará un **ERROR** detallado que nos ayudará a encontrar el problema exacto (IP incorrecta, contraseña inválida, firewall, etc.).

---

## 🚀 Guía Definitiva de Despliegue con Docker en CyberPanel

Esta es la guía recomendada para desplegar **QREasy** en tu servidor.

### Prerrequisitos

*   **Acceso SSH a tu servidor:** Necesitas poder conectarte como `root`.
*   **Dominio Configurado:** Tu dominio (ej. `qr.esquel.ar`) debe estar creado en CyberPanel y apuntando a la IP de tu servidor.
*   **Repositorio Git:** Debes tener este proyecto en un repositorio de GitHub.

---

### Paso 1: Conectarse al Servidor e Instalar Docker

1.  Conéctate a tu servidor a través de SSH como `root`.
2.  Instala Docker (si no lo has hecho ya).
    ```bash
    # Comandos de instalación de Docker...
    sudo apt-get update
    sudo apt-get install -y apt-transport-https ca-certificates curl software-properties-common
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -
    sudo add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable"
    sudo apt-get update
    sudo apt-get install -y docker-ce docker-ce-cli containerd.io
    sudo systemctl status docker
    ```

---

### Paso 2: Clonar el Proyecto y Configurar

1.  Clona tu proyecto desde GitHub en el directorio correcto.
    ```bash
    # Navega al directorio padre y elimina la carpeta antigua si existe
    cd /home/esquel.org.ar
    sudo rm -rf qr

    # Clona tu repositorio.
    git clone https://github.com/gbiaggioni/studio.git qr

    # Navega al nuevo directorio del proyecto
    cd /home/esquel.org.ar/qr
    ```

2.  **Configura las Variables de Entorno:**
    *   Sigue las instrucciones de la sección `🆘 ¡ATENCIÓN! LA SOLUCIÓN DEFINITIVA ESTÁ AQUÍ 🆘` al principio de este archivo.

3.  **¡Paso Crítico! Corrige los Permisos de los Archivos:**
    *   Como clonaste el repositorio siendo `root`, devuelve la propiedad al usuario de CyberPanel (`esque9858` en tu caso).
        ```bash
        sudo chown -R esque9858:esque9858 /home/esquel.org.ar/qr
        ```

---

### Paso 3: Construir y Ejecutar el Contenedor Docker

1.  **Iniciar sesión en Docker Hub (si es necesario):**
    *   Si al construir ves un error `429 Too Many Requests`, ejecuta `sudo docker login`.

2.  **Construir y Ejecutar:**
    *   Sigue las instrucciones de la sección `🆘 ¡ATENCIÓN! ... 🆘` al principio de este archivo.

---

### Paso 4: Configurar CyberPanel como Reverse Proxy

1.  Entra en tu panel de CyberPanel.
2.  Ve a `Websites` -> `List Websites` y busca tu dominio. Haz clic en `Manage`.
3.  Desplázate hacia abajo hasta la sección **Rewrite Rules**.
4.  Pega **exactamente** esto:
    ```
    RewriteEngine On
    RewriteRule ^(.*)$ http://127.0.0.1:3001/$1 [P,L]
    ```
5.  Haz clic en **"Save Rewrite Rules"**.

---

### Paso 5: Configurar el Firewall y Reiniciar

1.  **Abre el puerto en el firewall:**
    ```bash
    sudo ufw allow 3001/tcp
    ```
2.  **Reinicia el servidor web (¡Muy Importante!):**
    ```bash
    sudo systemctl restart lsws
    ```
¡Listo! `https://tu-dominio.com` debería mostrar tu aplicación.

---

### Paso 6: Mantenimiento - Cómo Actualizar la Aplicación

1.  Conéctate al servidor: `cd /home/esquel.org.ar/qr`
2.  Trae los últimos cambios: `git pull origin main` (o `master` si es tu rama principal)
3.  Reconstruye y reinicia el contenedor siguiendo los pasos 1, 3 y 4 de la sección `🆘 ¡ATENCIÓN! ... 🆘`.
4.  Opcional: Limpia imágenes de Docker antiguas: `sudo docker image prune -a`
