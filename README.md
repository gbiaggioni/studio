# 🆘 ¡ATENCIÓN! SI ESTÁS EN UN BUCLE DE ERRORES, LEE ESTA SECCIÓN PRIMERO 🆘
## El problema es un error de `git pull` en tu servidor.

Has estado viendo el mismo error una y otra vez (`La configuración de la base de datos es incompleta` o `Cannot find module './types'`) porque tu servidor **NO ESTÁ DESCARGANDO LAS CORRECCIONES**.

El log de tu servidor lo confirma con este error:
`error: Your local changes to the following files would be overwritten by merge`

**SOLUCIÓN DEFINITIVA (ejecutar en tu servidor):**
Sigue estos dos comandos en orden para forzar la actualización y romper el bucle.

1.  **Descarta los cambios locales conflictivos que están bloqueando todo:**
    ```bash
    git reset --hard HEAD
    ```
2.  **Vuelve a intentar la descarga del código bueno:**
    ```bash
    git pull origin master
    ```
Una vez que el comando `git pull` funcione **sin errores**, y **SOLO ENTONCES**, sigue la "Guía Definitiva de Despliegue" de 4 pasos que está justo debajo.

---
## Error `connect ECONNREFUSED 172.17.0.1:3306` 🚨
Si en tus logs de Docker (`sudo docker logs qreasy-container`) ves este error, significa que **la aplicación funciona y el contenedor está bien construido**. El problema es que tu servidor de base de datos (MariaDB/MySQL) está configurado por seguridad para **rechazar** conexiones que no vengan de `localhost`. Debes cambiar esto.

1.  **Conéctate a tu servidor** y abre el archivo de configuración de MariaDB/MySQL. La ubicación puede variar, pero suele estar en `/etc/mysql/mariadb.conf.d/50-server.cnf`.
    ```bash
    sudo nano /etc/mysql/mariadb.conf.d/50-server.cnf
    ```

2.  **Busca la línea `bind-address`**. Lo más probable es que ponga `127.0.0.1`.
    ```cnf
    # Línea original
    bind-address = 127.0.0.1
    ```

3.  **Comenta esa línea o cámbiala a `0.0.0.0`** para que acepte conexiones de cualquier interfaz (incluida la de Docker).
    ```cnf
    # Línea modificada (opción 1: comentada)
    # bind-address = 127.0.0.1
    
    # Línea modificada (opción 2: cambiada)
    bind-address = 0.0.0.0
    ```

4.  **Guarda el archivo** (`Ctrl+X`, luego `Y`, luego `Enter` en nano).

5.  **Reinicia el servicio de la base de datos** para que aplique los cambios:
    ```bash
    sudo systemctl restart mariadb 
    # O si usas MySQL: sudo systemctl restart mysql
    ```
6.  Finalmente, **reinicia el contenedor de la aplicación** (siguiendo los 4 pasos de abajo).

¡Listo! El error `ECONNREFUSED` debería haber desaparecido.

---

## 🚀 Guía Definitiva de Despliegue en 4 Pasos

Para desplegar o actualizar la aplicación, sigue **siempre** estos 4 pasos en orden.

### Paso 1: Detén y Elimina el Contenedor Antiguo (¡MUY IMPORTANTE!)
Cada vez que quieras actualizar, **DEBES** ejecutar esto primero para evitar conflictos.
```bash
sudo docker stop qreasy-container
sudo docker rm qreasy-container
```
*(Es normal si estos comandos dan un error de "No such container", significa que no había uno corriendo).*

### Paso 2: Genera un archivo `.env.local` Perfecto
Este script evita cualquier error manual. **DEBES** ejecutarlo antes de construir la imagen.
1.  Asegúrate de estar en el directorio correcto: `cd /home/esquel.org.ar/qr`
2.  **Dale permisos de ejecución al script:**
    ```bash
    chmod +x configure-env.sh
    ```
3.  **Ejecuta el script asistente:**
    ```bash
    ./configure-env.sh
    ```
    El script te pedirá los datos de tu base de datos y la URL de tu sitio. **RECUERDA USAR `172.17.0.1` COMO HOST DE LA BASE DE DATOS.**

### Paso 3: Reconstruye la Imagen de Docker
Este comando empaqueta la aplicación para producción.
```bash
sudo docker build -t qreasy-app .
```

### Paso 4: Inicia el Nuevo Contenedor
Con todo listo, inicia el nuevo contenedor. Este comando ahora incluye el flag `--env-file` que es **crucial** para que la aplicación funcione.
```bash
sudo docker run -d --restart unless-stopped --name qreasy-container -p 3001:3000 --env-file ./.env.local qreasy-app
```
Después de estos 4 pasos, la aplicación en `https://qr.esquel.org.ar` debería funcionar.

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
