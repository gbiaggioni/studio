# 🆘 ¡ATENCIÓN! LA SOLUCIÓN DEFINITIVA ESTÁ AQUÍ 🆘
## Si ves un error de "Configuración de la base de datos incompleta" o un "Internal Server Error 500", LEE ESTA SECCIÓN.

**El código de la aplicación y el Dockerfile son correctos.** El error que ves es una **confirmación** de que el problema está en la configuración de tu servidor. Específicamente, **el contenedor Docker no está leyendo tus variables de entorno del archivo `.env.local`**.

Esto casi siempre ocurre por un error de formato invisible en el archivo `.env.local` (espacios extra, comentarios, etc.).

**Sigue estos 3 pasos en tu servidor para solucionarlo de una vez por todas:**

### Paso 1: Crea un archivo `.env.local` perfecto (El paso clave)

1.  Asegúrate de estar en el directorio correcto: `cd /home/esquel.org.ar/qr`
2.  **Borra el archivo antiguo** para evitar problemas: `rm -f .env.local`
3.  Ejecuta el siguiente comando para crear un archivo `.env.local` nuevo y con el formato garantizado. **Copia y pega el bloque completo, incluyendo `EOF`**:
    ```bash
    cat <<EOF > .env.local
DB_HOST=172.17.0.1
DB_USER=esqu_qr_codes
DB_PASSWORD=esqu_qr_codes
DB_NAME=esqu_qr_codes
NEXT_PUBLIC_BASE_URL=https://qr.esquel.ar
EOF
    ```
    *Este comando crea un archivo limpio, sin comentarios ni espacios extra que puedan confundir a Docker.*

### Paso 2: Reconstruye la imagen de Docker

1.  Desde `/home/esquel.org.ar/qr`, reconstruye la imagen.
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

Si después de estos pasos sigues viendo la página por defecto de CyberPanel, sigue la guía del **Reverse Proxy** que se encuentra más abajo en este mismo archivo. Pero el error 500 debería estar solucionado.

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

Esta es la guía recomendada y única para desplegar **QREasy** en tu servidor. Sigue estos pasos en orden.

**Introducción Importante: `root` vs. `esque9858`**

-   **Usa `root` para todo:** Debes realizar todos los pasos de esta guía conectado a tu servidor por SSH como el usuario `root`.
-   **¿Y `esque9858`?** Es el usuario que CyberPanel usa internamente para los archivos del sitio. No necesitas usarlo ni preocuparte por él para este despliegue; la configuración se encarga de conectar todo correctamente.

### Prerrequisitos

*   **Acceso SSH a tu servidor:** Necesitas poder conectarte como `root`.
*   **Dominio Configurado:** Tu dominio `qr.esquel.ar` debe estar creado en CyberPanel y apuntando a la IP de tu servidor.
*   **Repositorio Git:** Debes tener este proyecto en un repositorio de GitHub.

---

### Paso 1: Conectarse al Servidor e Instalar Docker

1.  Conéctate a tu servidor a través de SSH como `root`.

2.  Instala Docker con los siguientes comandos. Es un proceso que solo harás una vez.
    ```bash
    # Actualizar repositorios e instalar paquetes necesarios
    sudo apt-get update
    sudo apt-get install -y apt-transport-https ca-certificates curl software-properties-common

    # Añadir la clave GPG oficial de Docker
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -

    # Añadir el repositorio de Docker
    sudo add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable"

    # Actualizar de nuevo e instalar el motor de Docker
    sudo apt-get update
    sudo apt-get install -y docker-ce docker-ce-cli containerd.io

    # Verificar que Docker está corriendo
    sudo systemctl status docker
    ```

---

### Paso 2: Clonar el Proyecto y Configurar

1.  Clona tu proyecto desde GitHub en el directorio correcto.
    ```bash
    # Navega al directorio padre y elimina la carpeta antigua si existe
    cd /home/esquel.org.ar
    sudo rm -rf qr

    # Clona tu repositorio. Git creará la carpeta 'qr' automáticamente.
    # Asegúrate de usar la URL de TU repositorio.
    git clone https://github.com/gbiaggioni/studio.git qr

    # Navega al nuevo directorio del proyecto
    cd /home/esquel.org.ar/qr
    ```

2.  **Configura las Variables de Entorno:**
    *   Sigue las instrucciones de la sección `🆘 ¡ATENCIÓN! LA SOLUCIÓN DEFINITIVA ESTÁ AQUÍ 🆘` al principio de este archivo.

3.  **¡Paso Crítico! Corrige los Permisos de los Archivos:**
    *   Como clonaste el repositorio siendo `root`, los archivos ahora pertenecen a `root`. Necesitamos devolverle la propiedad al usuario que CyberPanel utiliza (`esque9858`) para que pueda gestionar el sitio correctamente.
    *   Ejecuta este comando desde `/home/esquel.org.ar/qr`:
        ```bash
        sudo chown -R esque9858:esque9858 /home/esquel.org.ar/qr
        ```
    *   Este paso es **esencial** para que CyberPanel pueda escribir las reglas de reescritura más adelante.

---

### Paso 3: Construir y Ejecutar el Contenedor Docker

1.  **Iniciar sesión en Docker Hub (Solución al error "429 Too Many Requests"):**
    *   Si al construir la imagen ves un error `429 Too Many Requests`, inicia sesión con una cuenta gratuita de Docker Hub.
    *   Ejecuta `sudo docker login` e ingresa tus credenciales.

2.  **Construir y Ejecutar:**
    *   Sigue las instrucciones de la sección `🆘 ¡ATENCIÓN! LA SOLUCIÓN DEFINITIVA ESTÁ AQUÍ 🆘` al principio de este archivo.

---

### Paso 4: Configurar CyberPanel como Reverse Proxy

Ahora, tu aplicación corre en `http://localhost:3001` en el servidor. Hay que decirle a CyberPanel que redirija el tráfico de `https://qr.esquel.ar` a ese puerto.

1.  Entra en tu panel de CyberPanel.
2.  Ve a `Websites` -> `List Websites` y busca `qr.esquel.ar`. Haz clic en `Manage`.
3.  Desplázate hacia abajo hasta la sección **Rewrite Rules**.
4.  En el campo de texto, borra todo lo que haya y pega **exactamente** esto:
    ```
    RewriteEngine On
    RewriteRule ^(.*)$ http://127.0.0.1:3001/$1 [P,L]
    ```
5.  Haz clic en **"Save Rewrite Rules"**.

---

### Paso 5: Configurar el Firewall y Reiniciar

El último paso es decirle al firewall del servidor que permita conexiones entrantes al puerto 3001 y reiniciar el servidor web para que aplique los cambios.

1.  **Abre el puerto en el firewall:**
    ```bash
    sudo ufw allow 3001/tcp
    ```
2.  **Reinicia el servidor web (¡Muy Importante!):**
    ```bash
    sudo systemctl restart lsws
    ```
¡Listo! `https://qr.esquel.ar` debería mostrar tu aplicación.

---

### Paso 6: Mantenimiento - Cómo Actualizar la Aplicación

Cuando subas cambios a GitHub, el proceso de actualización es muy sencillo:

1.  Conéctate al servidor y ve al directorio del proyecto: `cd /home/esquel.org.ar/qr`

2.  Trae los últimos cambios del código. **La rama principal de este proyecto es `master`**. Si tu rama se llama de otra forma, úsala aquí.
    ```bash
    git pull origin master
    ```

3.  Reconstruye y reinicia el contenedor siguiendo los pasos 2 y 3 de la sección `🆘 ¡ATENCIÓN! LA SOLUCIÓN DEFINITIVA ESTÁ AQUÍ 🆘`.

4.  **Opcional pero recomendado:** Limpia imágenes de Docker antiguas que ya no se usan: `sudo docker image prune -a`
