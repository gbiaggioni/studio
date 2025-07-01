# 🆘 ¡ATENCIÓN! LA SOLUCIÓN DEFINITIVA ESTÁ AQUÍ 🆘
## Si ves un error de "Internal Server Error" o la página no carga, LEE ESTA SECCIÓN.

El problema casi siempre es uno de estos dos, en este orden de probabilidad:
1.  **Error de `bind-address` en la Base de Datos (Error `ECONNREFUSED` en los logs).**
2.  Un error en el archivo de entorno `.env.local` (generalmente, usar `localhost` en vez de `172.17.0.1` como `DB_HOST`).

---

### 🚨 Solución para el error `connect ECONNREFUSED 172.17.0.1:3306` 🚨
Si en tus logs de Docker (`sudo docker logs qreasy-container`) ves este error, significa que **el código y la configuración de Docker son correctos**. El problema es que tu servidor de base de datos (MariaDB/MySQL) está configurado por seguridad para **rechazar** conexiones que no vengan de `localhost`. Debes cambiar esto.

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
Este script evita cualquier error manual.
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
Este comando empaqueta la aplicación con tu configuración.
```bash
sudo docker build -t qreasy-app .
```

### Paso 4: Inicia el Nuevo Contenedor
Con todo listo, inicia el nuevo contenedor.
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

## 🛠️ Herramientas de Diagnóstico

### Chequear la conexión a la Base de Datos
Si tienes dudas sobre si tus credenciales son correctas, puedes usar un script de prueba.

1.  **Instala las dependencias de desarrollo** (solo se hace una vez):
    ```bash
    npm install
    ```
2.  **Crea un archivo `.env.local`** con el asistente:
    ```bash
    ./configure-env.sh
    ```
3.  **Ejecuta el script de prueba:**
    ```bash
    node check-db.js
    ```
El script te dará un mensaje de ÉXITO o un ERROR detallado. **Nota:** Si usas `172.17.0.1` como host, el script fallará. Esto es **normal** porque esa IP solo es accesible desde dentro de un contenedor Docker. Para probar tus credenciales, puedes usar temporalmente `localhost` o `127.0.0.1` como host en el archivo `.env.local` solo para esta prueba.
