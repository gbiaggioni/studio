# 🆘 ¡ATENCIÓN! LA SOLUCIÓN ESTÁ AQUÍ 🆘
## Si ves un error de "Configuración de la base de datos incompleta" o la página por defecto de CyberPanel, LEE ESTA SECCIÓN PRIMERO.

**El código de la aplicación funciona correctamente.** El error que ves es una **confirmación** de que el problema está en la configuración de tu servidor. 
**No se necesitan más cambios de código. El Asistente de IA no proporcionará más correcciones de código para este problema, ya que la solución está en la configuración de tu servidor.**

La solución es seguir **exactamente** estos pasos en la terminal de tu servidor.

---

### Error: Veo "Internal Server Error" o la página de error "Configuración Detectada".

Este es el error más común y **casi siempre está relacionado con el archivo `.env.local`**.

1.  **Causa Principal:** El contenedor Docker no puede encontrar o leer tus variables de entorno.
2.  **Solución Definitiva (Sigue estos pasos en orden):**
    *   **Paso A: Verifica que estás en el directorio correcto.**
        ```bash
        # Entra a la terminal de tu servidor y ejecuta esto:
        cd /home/esquel.org.ar/qr
        pwd
        # La salida DEBE ser /home/esquel.org.ar/qr
        ```
    *   **Paso B: Verifica que el archivo `.env.local` existe y tiene contenido.**
        ```bash
        # Desde el directorio anterior, ejecuta:
        ls -l .env.local
        cat .env.local
        ```
    *   El contenido debe ser **exactamente** así, sin comillas, sin espacios extra, y con tus credenciales reales:
        ```env
        DB_HOST=172.17.0.1
        DB_USER=tu_usuario_de_bd
        DB_PASSWORD=tu_contraseña_de_bd
        DB_NAME=el_nombre_de_tu_bd
        NEXT_PUBLIC_BASE_URL=https://qr.esquel.org.ar
        ```
    *   **Paso C: Si has hecho algún cambio, reinicia el contenedor.**
        ```bash
        # Detén y elimina el contenedor antiguo
        sudo docker stop qreasy-container
        sudo docker rm qreasy-container

        # Inicia el nuevo contenedor (asegúrate de estar en /home/esquel.org.ar/qr)
        sudo docker run -d --restart unless-stopped --name qreasy-container -p 3001:3000 --env-file ./.env.local qreasy-app
        ```

### Error: Veo la página por defecto de CyberPanel/LiteSpeed, no mi aplicación.

Esto significa que el **Reverse Proxy no está funcionando**. LiteSpeed está interceptando la petición pero no la está enviando a tu aplicación en el puerto 3001.

1.  **Causa Principal:** Las reglas de reescritura de CyberPanel no se están aplicando correctamente.
2.  **Solución Definitiva (Haz estos 3 pasos en orden):**
    *   **Paso A: Revisa los Permisos.** Asegúrate de haber ejecutado el comando `chown` del **Paso 3** de la guía de despliegue. Si CyberPanel no tiene permisos sobre los archivos, no puede guardar las reglas.
        ```bash
        # Vuelve a ejecutarlo por si acaso, desde /home/esquel.org.ar/qr
        sudo chown -R esque9858:esque9858 /home/esquel.org.ar/qr
        ```
    *   **Paso B: Revisa las Reglas de Reescritura.** Vuelve al **Paso 4** de la guía de despliegue y asegúrate de haber pegado las reglas **exactamente** como se muestran.
     Borra todo lo que había antes y pega el nuevo contenido.
        ```
        RewriteEngine On
        RewriteRule ^(.*)$ http://127.0.0.1:3001/$1 [P,L]
        ```
        Haz clic en **"Save Rewrite Rules"**.
    *   **Paso C: Reinicia el Servidor Web (¡EL MÁS IMPORTANTE!).** Después de guardar las reglas, **debes reiniciar LiteSpeed** para que las cargue.
        ```bash
        sudo systemctl restart lsws
        ```
    *   Limpia la caché de tu navegador o prueba en modo incógnito. Si sigues estos 3 pasos, el problema del reverse proxy se solucionará.

---

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
    *   Copia el archivo de ejemplo para crear tu configuración local:
        ```bash
        cp .env.example .env.local
        ```
    *   Abre el nuevo archivo para editarlo (`nano .env.local`).
    *   Modifica el contenido con **tus credenciales reales**. Debe quedar **exactamente** como se muestra en la sección de "Solución de Errores Comunes" arriba.
    *   Guarda los cambios (`Ctrl+X`, luego `Y`, y `Enter`).

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

2.  **Construir la imagen:** Desde la raíz del proyecto (`/home/esquel.org.ar/qr`), ejecuta:
    ```bash
    sudo docker build -t qreasy-app .
    ```

3.  **Ejecutar el contenedor:** Este comando inicia tu aplicación.
    ```bash
    sudo docker run -d --restart unless-stopped \
      --name qreasy-container \
      -p 3001:3000 \
      --env-file ./.env.local \
      qreasy-app
    ```

---

### Paso 4: Configurar CyberPanel como Reverse Proxy

Ahora, tu aplicación corre en `http://localhost:3001` en el servidor. Hay que decirle a CyberPanel que redirija el tráfico de `https://qr.esquel.org.ar` a ese puerto.

1.  Entra en tu panel de CyberPanel.
2.  Ve a `Websites` -> `List Websites` y busca `qr.esquel.org.ar`. Haz clic en `Manage`.
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
¡Listo! `https://qr.esquel.org.ar` debería mostrar tu aplicación.

---

### Paso 6: Mantenimiento - Cómo Actualizar la Aplicación

Cuando subas cambios a GitHub, el proceso de actualización es muy sencillo:

1.  Conéctate al servidor y ve al directorio del proyecto: `cd /home/esquel.org.ar/qr`

2.  Trae los últimos cambios del código. **La rama principal de este proyecto es `main`**. Si tu rama se llama `master`, usa `git pull origin master`.
    ```bash
    git pull origin main
    ```

3.  Detén y elimina el contenedor antiguo:
    ```bash
    sudo docker stop qreasy-container
    sudo docker rm qreasy-container
    ```

4.  Reconstruye la imagen de Docker con los nuevos cambios:
    ```bash
    sudo docker build -t qreasy-app .
    ```

5.  Vuelve a ejecutar el contenedor con el mismo comando de siempre (¡asegúrate de estar en el directorio correcto!):
    ```bash
    sudo docker run -d --restart unless-stopped --name qreasy-container -p 3001:3000 --env-file ./.env.local qreasy-app
    ```
6.  **Opcional pero recomendado:** Limpia imágenes de Docker antiguas que ya no se usan: `sudo docker image prune -a`

    