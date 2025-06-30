
# QREasy - Gestor de Códigos QR

QREasy es una aplicación web moderna y sencilla para crear, gestionar y compartir códigos QR. Ha sido desarrollada con un stack tecnológico actual, enfocándose en el rendimiento, la escalabilidad y una experiencia de usuario fluida.

## ✨ Características Principales

-   **Creación de Códigos QR:** Genera códigos QR dinámicamente a partir de cualquier URL de destino.
-   **Etiquetado Personalizado:** Asigna un nombre o etiqueta a cada código QR para una fácil identificación.
-   **Galería de Códigos:** Visualiza todos tus códigos QR en una interfaz de tarjeta limpia y organizada.
-   **URL Corta Única:** Cada código QR obtiene una URL corta y única (ej. `qr.esquel.org.ar/r/xyz123`) para la redirección.
-   **Gestión Completa:**
    -   Edita la URL de destino o el nombre de un QR sin necesidad de reimprimirlo.
    -   Copia la URL corta al portapapeles con un solo clic.
    -   Imprime códigos QR individuales directamente desde la aplicación, optimizados para A4.
    -   Elimina códigos QR específicos o todos a la vez con diálogos de confirmación.
-   **Responsivo:** Diseño completamente adaptable para funcionar en computadoras de escritorio, tabletas y dispositivos móviles.
-   **Listo para Producción:** Conexión a base de datos MariaDB/MySQL y despliegue con Docker.

## 🚀 Stack Tecnológico

-   **Framework:** [Next.js](https://nextjs.org/) (App Router)
-   **Lenguaje:** [TypeScript](https://www.typescriptlang.org/)
-   **Estilo:** [Tailwind CSS](https://tailwindcss.com/)
-   **Componentes UI:** [ShadCN UI](https://ui.shadcn.com/)
-   **Base de Datos:** [MariaDB](https://mariadb.org/) / [MySQL](https://www.mysql.com/)
-   **Contenerización:** [Docker](https://www.docker.com/)

---

## 🚀 Despliegue con Docker en CyberPanel (Método Recomendado)

Esta es la guía definitiva y recomendada para desplegar **QREasy** en tu servidor con CyberPanel. Docker simplifica el proceso, garantiza un entorno consistente y es mucho más robusto que los métodos manuales.

**Importante:** Los antiguos scripts (`update.sh`, `health-check.sh`) y el archivo `server.js` quedan **obsoletos** con este método y no deben usarse.

### Prerrequisitos

*   **Acceso SSH a tu servidor:** Necesitas poder conectarte como `root` o un usuario con privilegios `sudo`.
*   **Dominio Configurado:** Tu dominio `qr.esquel.org.ar` debe estar creado en CyberPanel y apuntando a la IP de tu servidor.

---

### Paso 1: Conectarse al Servidor e Instalar Docker

1.  Conéctate a tu servidor a través de SSH.

2.  Instala Docker. Estos comandos funcionan para la mayoría de sistemas basados en Debian/Ubuntu:
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

### Paso 2: Clonar el Proyecto

1.  Navega a un directorio adecuado, como `/home`. Clona tu proyecto desde GitHub:
    ```bash
    cd /home
    git clone https://github.com/TU_USUARIO/qreasy.git # <- Reemplaza esto con la URL de tu repo
    cd qreasy
    ```

---

### Paso 3: Configurar las Variables de Entorno

Este es un paso crítico. La aplicación necesita saber cómo conectarse a tu base de datos.

1.  Dentro del directorio del proyecto (`/home/qreasy`), copia el archivo de ejemplo:
    ```bash
    cp .env.example .env.local
    ```

2.  Abre el nuevo archivo `.env.local` para editarlo (por ejemplo, con `nano`):
    ```bash
    nano .env.local
    ```

3.  Modifica el contenido con **tus credenciales reales**. Debería quedar así:
    ```env
    # Credenciales de la Base de Datos
    DB_HOST=127.0.0.1  # O la IP/host de tu base de datos si es externa
    DB_USER=tu_usuario_de_bd
    DB_PASSWORD=tu_contraseña_de_bd
    DB_NAME=el_nombre_de_tu_bd

    # URL pública de la aplicación
    NEXT_PUBLIC_BASE_URL=https://qr.esquel.org.ar
    ```
    *   **Importante:** Guarda los cambios (`Ctrl+X`, luego `Y`, y `Enter`).

---

### Paso 4: Construir y Ejecutar el Contenedor Docker

1.  **Construir la imagen:** Desde la raíz del proyecto (`/home/qreasy`), ejecuta:
    ```bash
    sudo docker build -t qreasy-app .
    ```
    *(Esto puede tardar unos minutos la primera vez que se ejecuta).*

2.  **Ejecutar el contenedor:** Este comando inicia tu aplicación.
    ```bash
    sudo docker run -d --restart unless-stopped \
      --name qreasy-container \
      -p 3001:3001 \
      --env-file ./.env.local \
      qreasy-app
    ```
    *   `-d`: Ejecuta en segundo plano.
    *   `--restart unless-stopped`: Reinicia el contenedor automáticamente si se detiene.
    *   `--name qreasy-container`: Le da un nombre fácil de recordar al contenedor.
    *   `-p 3001:3001`: Mapea el puerto 3001 del servidor al puerto 3001 del contenedor.
    *   `--env-file ./.env.local`: **Pasa todas las variables de tu archivo `.env.local` al contenedor de forma segura.**

3.  **Verificar que está corriendo:**
    ```bash
    sudo docker ps
    ```
    Deberías ver `qreasy-container` en la lista. Para ver los logs de la aplicación en cualquier momento:
    ```bash
    sudo docker logs qreasy-container
    ```

---

### Paso 5: Configurar CyberPanel como Reverse Proxy

Ahora mismo, tu aplicación está corriendo en `http://localhost:3001`. Necesitamos decirle a CyberPanel que cuando alguien visite `https://qr.esquel.org.ar`, debe redirigir el tráfico a ese puerto.

1.  Entra en tu panel de CyberPanel.
2.  Ve a `Websites` -> `List Websites` y busca `qr.esquel.org.ar`. Haz clic en `Manage`.
3.  Desplázate hacia abajo hasta la sección **Rewrite Rules** y haz clic en el desplegable para seleccionar la plantilla `Proxy`.
4.  En el campo `Address` escribe `127.0.0.1:3001`.
5.  Haz clic en **"Save Rewrite Rules"**.

¡Y listo! Ahora `https://qr.esquel.org.ar` debería mostrar tu aplicación QREasy corriendo desde Docker.

---

### Mantenimiento: Cómo Actualizar la Aplicación

Cuando hagas cambios en tu código y los subas a GitHub, el proceso de actualización es muy sencillo:

1.  Conéctate al servidor y ve al directorio del proyecto:
    ```bash
    cd /home/qreasy
    ```
2.  Detén y elimina el contenedor antiguo:
    ```bash
    sudo docker stop qreasy-container
    sudo docker rm qreasy-container
    ```
3.  Trae los últimos cambios del código desde GitHub:
    ```bash
    git pull origin main
    ```
4.  Reconstruye la imagen de Docker con los nuevos cambios:
    ```bash
    sudo docker build -t qreasy-app .
    ```
5.  Vuelve a ejecutar el contenedor con el mismo comando que usaste para el despliegue inicial:
    ```bash
    sudo docker run -d --restart unless-stopped \
      --name qreasy-container \
      -p 3001:3001 \
      --env-file ./.env.local \
      qreasy-app
    ```
