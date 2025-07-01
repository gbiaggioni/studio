# 🆘 ¡ATENCIÓN! LA SOLUCIÓN DEFINITIVA ESTÁ AQUÍ 🆘
## Si ves un error de "Configuración de la base de datos incompleta" o un "Internal Server Error 500", LEE ESTA SECCIÓN.

El problema casi siempre es que el contenedor Docker no está leyendo correctamente las credenciales del archivo `.env.local`. Para solucionarlo de una vez por todas, he creado un script que lo hace por ti y he mejorado el diagnóstico.

**Sigue estos 4 pasos en tu servidor para solucionarlo:**

### Paso 1: Genera un archivo `.env.local` perfecto

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

### Paso 2: Reconstruye la imagen de Docker

1.  Desde `/home/esquel.org.ar/qr`, reconstruye la imagen para asegurarte de que tiene el último código de diagnóstico.
    ```bash
    sudo docker build -t qreasy-app .
    ```

### Paso 3: Reinicia el contenedor con el comando correcto

1.  Detén y elimina el contenedor antiguo:
    ```bash
    sudo docker stop qreasy-container
    sudo docker rm qreasy-container
    ```
2.  Inicia el nuevo contenedor, asegurándote de que lea el nuevo archivo de entorno perfecto:
    ```bash
    sudo docker run -d --restart unless-stopped --name qreasy-container -p 3001:3000 --env-file ./.env.local qreasy-app
    ```

### Paso 4: Revisa los logs para la prueba definitiva

1.  Espera unos segundos y luego revisa los logs del contenedor:
    ```bash
    sudo docker logs qreasy-container
    ```
2.  **Busca una sección que empiece con `--- [QREASY_DOCKER_DEBUG] Imprimiendo variables de entorno ---`**.
3.  **Comprueba si tus variables `DB_HOST`, `DB_USER`, `DB_PASSWORD`, y `DB_NAME` aparecen en esa lista.**
    *   **Si NO aparecen**, el problema sigue siendo el archivo `.env.local` o los permisos. Vuelve al Paso 1.
    *   **Si SÍ aparecen**, el problema está en otro lugar (muy improbable), pero ahora tenemos la prueba.

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
2.  Trae los últimos cambios: `git pull origin master`
3.  Reconstruye y reinicia el contenedor siguiendo los pasos 2 y 3 de la sección `🆘 ¡ATENCIÓN! ... 🆘`.
4.  Opcional: Limpia imágenes de Docker antiguas: `sudo docker image prune -a`
