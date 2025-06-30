# QREasy - Gestor de Códigos QR con Docker

QREasy es una aplicación web moderna y sencilla para crear, gestionar y compartir códigos QR. Esta versión está configurada para un despliegue robusto y simplificado usando Docker.

## ✨ Características Principales

-   **Creación de Códigos QR:** Genera códigos QR dinámicamente a partir de cualquier URL.
-   **Gestión Completa:** Edita, copia, imprime y elimina tus códigos QR fácilmente.
-   **URL Corta Única:** Cada QR obtiene una URL única para redirección (ej. `qr.esquel.org.ar/r/xyz123`).
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
*   **Dominio Configurado:** Tu dominio `qr.esquel.org.ar` debe estar creado en CyberPanel y apuntando a la IP de tu servidor.
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

    # Nota: Es normal ver una advertencia como "apt-key is deprecated". Puedes continuar de forma segura.

    # Actualizar de nuevo e instalar el motor de Docker
    sudo apt-get update
    sudo apt-get install -y docker-ce docker-ce-cli containerd.io

    # Verificar que Docker está corriendo
    sudo systemctl status docker
    ```

---

### 🚨 Paso 1.5: Solución de Problemas (Si Docker no se inicia)

Si el comando `sudo systemctl status docker` muestra un estado `failed` o `inactive`, es muy probable que tengas un conflicto con la configuración de `systemd` en tu VPS.

1.  **Ejecuta el script de reparación:** Este script ajustará la configuración de inicio de Docker para que sea compatible con tu entorno.
    ```bash
    # Primero, asegúrate de haber clonado el proyecto en el Paso 2.
    # Navega al directorio del proyecto.
    cd /home/esquel.org.ar/qr

    # Luego, da permisos de ejecución al script
    sudo chmod +x fix-docker-start.sh

    # Finalmente, ejecuta el script
    sudo ./fix-docker-start.sh
    ```
2.  El script intentará reiniciar Docker y al final mostrará su estado. Si ves `active (running)`, el problema está resuelto y puedes continuar.

---

### Paso 2: Clonar el Proyecto y Configurar

1.  Clona tu proyecto desde GitHub en el directorio correcto.
    ```bash
    # Crea el directorio si no existe
    mkdir -p /home/esquel.org.ar/qr
    
    # Clona tu repositorio
    git clone https://github.com/TU_USUARIO/qreasy.git /home/esquel.org.ar/qr # <- Reemplaza con la URL de tu repo
    
    # Navega al nuevo directorio
    cd /home/esquel.org.ar/qr
    ```

2.  **Configura las Variables de Entorno (¡Paso Crítico!):**
    *   Copia el archivo de ejemplo:
        ```bash
        cp .env.example .env.local
        ```
    *   Abre el nuevo archivo para editarlo (por ejemplo, con `nano .env.local`).
    *   Modifica el contenido con **tus credenciales reales**. Debería quedar así:
        ```env
        # Credenciales de la Base de Datos
        DB_HOST=127.0.0.1  # O la IP de tu BD si es externa
        DB_USER=tu_usuario_de_bd
        DB_PASSWORD=tu_contraseña_de_bd
        DB_NAME=el_nombre_de_tu_bd

        # URL pública de la aplicación
        NEXT_PUBLIC_BASE_URL=https://qr.esquel.org.ar
        ```
    *   Guarda los cambios (`Ctrl+X`, luego `Y`, y `Enter`).

---

### Paso 3: Construir y Ejecutar el Contenedor Docker

1.  **Construir la imagen:** Desde la raíz del proyecto (`/home/esquel.org.ar/qr`), ejecuta:
    ```bash
    sudo docker build -t qreasy-app .
    ```
    *(Esto puede tardar unos minutos la primera vez).*

2.  **Ejecutar el contenedor:** Este comando inicia tu aplicación.
    ```bash
    sudo docker run -d --restart unless-stopped \
      --name qreasy-container \
      -p 3001:3001 \
      --env-file ./.env.local \
      qreasy-app
    ```
    -   `-d`: Ejecuta en segundo plano.
    -   `--restart unless-stopped`: Reinicia el contenedor automáticamente.
    -   `--name qreasy-container`: Le da un nombre fácil de recordar.
    -   `-p 3001:3001`: Mapea el puerto 3001 del servidor al puerto 3001 del contenedor.
    -   `--env-file ./.env.local`: Pasa tus credenciales de forma segura al contenedor.

3.  **Verificar que está corriendo:**
    -   Para ver los contenedores activos: `sudo docker ps` (Deberías ver `qreasy-container`).
    -   Para ver los logs de la aplicación: `sudo docker logs qreasy-container`.

---

### Paso 4: Configurar CyberPanel como Reverse Proxy

Ahora, tu aplicación corre en `http://localhost:3001`. Hay que decirle a CyberPanel que redirija el tráfico de `https://qr.esquel.org.ar` a ese puerto.

1.  Entra en tu panel de CyberPanel.
2.  Ve a `Websites` -> `List Websites` y busca `qr.esquel.org.ar`. Haz clic en `Manage`.
3.  Desplázate hacia abajo hasta la sección **Rewrite Rules**.
4.  En el campo de texto, borra todo lo que haya y pega esto:
    ```
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    RewriteRule ^(.*)$ http://127.0.0.1:3001/$1 [P,L]
    ```
5.  Haz clic en **"Save Rewrite Rules"**.

¡Listo! `https://qr.esquel.org.ar` debería mostrar tu aplicación.

---

### Mantenimiento: Cómo Actualizar la Aplicación

Cuando subas cambios a GitHub, el proceso de actualización es muy sencillo:

1.  Conéctate al servidor y ve al directorio del proyecto: `cd /home/esquel.org.ar/qr`
2.  Detén y elimina el contenedor antiguo:
    ```bash
    sudo docker stop qreasy-container
    sudo docker rm qreasy-container
    ```
3.  Trae los últimos cambios del código: `git pull origin main`
4.  Reconstruye la imagen de Docker: `sudo docker build -t qreasy-app .`
5.  Vuelve a ejecutar el contenedor con el mismo comando de siempre:
    ```bash
    sudo docker run -d --restart unless-stopped --name qreasy-container -p 3001:3001 --env-file ./.env.local qreasy-app
    ```

---

## Anexo: Cómo Desinstalar Docker (Si fuera necesario)

**Advertencia:** Esto eliminará Docker y todos sus datos (imágenes, contenedores).

1.  **Detener servicios:**
    ```bash
    sudo systemctl stop docker.service
    sudo systemctl stop docker.socket
    ```
2.  **Desinstalar paquetes:**
    ```bash
    sudo apt-get purge -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    ```
3.  **Eliminar directorios residuales:**
    ```bash
    sudo rm -rf /var/lib/docker
    sudo rm -rf /var/lib/containerd
    ```
4.  **Limpiar sistema:**
    ```bash
    sudo apt-get autoremove -y --purge
    sudo apt-get clean
    ```
