# 🔧 QR Manager - Sistema de Gestión de Códigos QR

Una aplicación web completa que permite a administradores generar códigos QR que apuntan a carpetas específicas dentro del servidor web y redirigen automáticamente a URLs externas.

## ✨ Características

- 🔐 **Sistema de login protegido** para administradores
- 📱 **Generación automática de códigos QR** con API externa
- 🎯 **Redirecciones dinámicas** a URLs externas
- 📁 **Creación automática de carpetas** en el servidor
- 🗃️ **Almacenamiento en archivos JSON** (sin base de datos)
- 🎨 **Interfaz moderna** con Bootstrap 5
- ✏️ **Edición de destinos** después de crear el QR
- 🗑️ **Eliminación de redirecciones** con limpieza automática
- 📊 **Panel de administración** intuitivo

## 📋 Requisitos

- **Servidor web** con Apache (con mod_rewrite habilitado)
- **PHP 7.4** o superior
- **Permisos de escritura** en el directorio de la aplicación

## 🚀 Instalación

### 1. Subir archivos al servidor

Sube todos los archivos de la aplicación a tu servidor web. Puedes colocarlos en:
- Directorio raíz: `public_html/qr-manager/`
- Subdirectorio: `public_html/tu-sitio/qr-manager/`

### 2. Configurar permisos

```bash
# Dar permisos de escritura a las carpetas necesarias
chmod 755 qr-manager/
chmod 777 qr-manager/qr/
chmod 666 qr-manager/users.json
chmod 666 qr-manager/redirects.json
```

### 3. Configurar dominio

Edita el archivo `config.php` y cambia la línea:

```php
define('BASE_URL', 'http://localhost/qr-manager'); // Cambiar por tu dominio
```

Por tu dominio real:

```php
define('BASE_URL', 'https://tudominio.com/qr-manager');
```

### 4. Verificar instalación

Accede a tu dominio: `https://tudominio.com/qr-manager/`

Deberías ver la página de login.

## 👤 Acceso por defecto

- **Usuario:** admin
- **Contraseña:** password

> ⚠️ **Importante**: Cambia la contraseña por defecto después de la primera instalación.

## 📚 Uso de la aplicación

### 1. Iniciar sesión

1. Accede a la URL de la aplicación
2. Ingresa las credenciales de administrador
3. Haz clic en "Iniciar Sesión"

### 2. Crear una redirección QR

1. En el panel de administración, completa el formulario:
   - **URL de Destino**: La URL completa a la que quieres redirigir (ej: `https://youtube.com/watch?v=xyz`)
   - **ID Personalizado** (opcional): Un identificador único (ej: `mi-video-promocional`)

2. Haz clic en "Crear Código QR"

3. El sistema:
   - Genera un ID único (si no proporcionaste uno)
   - Crea una carpeta: `/qr/abc123/`
   - Crea un archivo `index.php` en esa carpeta con la redirección
   - Genera el código QR automáticamente

### 3. Usar el código QR

- **URL del QR**: `https://tudominio.com/qr-manager/qr/abc123`
- **Función**: Al escanear el QR o acceder a la URL, redirige automáticamente a la URL de destino

### 4. Gestionar redirecciones

- **Ver todas**: La tabla muestra todas las redirecciones creadas
- **Ver QR grande**: Haz clic en el ícono de lupa para ver el QR en tamaño completo
- **Editar destino**: Haz clic en el ícono de lápiz para cambiar la URL de destino
- **Eliminar**: Haz clic en el ícono de papelera para eliminar (borra la carpeta y entrada del JSON)

### 5. Editar redirecciones existentes

1. En la tabla de redirecciones, haz clic en el botón de edición (ícono de lápiz)
2. Se abrirá un modal mostrando:
   - **ID del QR**: No se puede modificar
   - **URL actual**: Para referencia
   - **Nueva URL**: Campo para ingresar el nuevo destino
3. Ingresa la nueva URL de destino
4. Haz clic en "Actualizar Destino"
5. El sistema actualiza automáticamente:
   - El archivo `index.php` en la carpeta del QR
   - La entrada en `redirects.json`
   - Registra quién y cuándo hizo el cambio

## 📁 Estructura de archivos

```
qr-manager/
├── index.php          # Página de login
├── admin.php          # Panel de administración
├── logout.php         # Cerrar sesión
├── config.php         # Configuración y funciones
├── users.json         # Usuarios administradores
├── redirects.json     # Redirecciones creadas
├── .htaccess          # Configuración Apache
├── README.md          # Documentación
└── qr/                # Carpetas de redirección
    ├── abc123/
    │   └── index.php
    ├── def456/
    │   └── index.php
    └── ...
```

## 🔧 Configuración avanzada

### Cambiar contraseña de administrador

1. Ve a un generador de hash PHP online o usa esta línea de comando:
```php
echo password_hash('tu_nueva_contraseña', PASSWORD_DEFAULT);
```

2. Edita `users.json` y reemplaza el hash en el campo `password`

### Agregar más usuarios

Edita `users.json` y agrega nuevos usuarios:

```json
[
    {
        "id": 1,
        "username": "admin",
        "password": "$2y$10$...",
        "role": "admin",
        "created_at": "2024-01-01"
    },
    {
        "id": 2,
        "username": "manager",
        "password": "$2y$10$...",
        "role": "admin",
        "created_at": "2024-01-01"
    }
]
```

### Personalizar dominio base

En `config.php`, puedes cambiar:

```php
define('BASE_URL', 'https://qr.tuempresa.com');
define('QR_URL', BASE_URL . '/');
```

Esto haría que las URLs de QR sean más cortas: `https://qr.tuempresa.com/abc123`

## 🛠️ Solución de problemas

### Error: "No se puede crear la carpeta"

**Causa**: Permisos insuficientes
**Solución**: 
```bash
chmod 777 qr-manager/qr/
```

### Error: "No se puede guardar en JSON"

**Causa**: Permisos de archivos
**Solución**:
```bash
chmod 666 qr-manager/*.json
```

### Los códigos QR no se muestran

**Causa**: Bloqueo de la API externa
**Solución**: Verifica que tu servidor permita conexiones salientes a `api.qrserver.com`

### Error 500 al acceder

**Causa**: Configuración de Apache
**Solución**: Verifica que mod_rewrite esté habilitado

## 🔒 Seguridad

- Los archivos JSON están protegidos mediante `.htaccess`
- Las contraseñas se almacenan hasheadas con `password_hash()`
- Validación de URLs y sanitización de inputs
- Protección contra acceso directo a carpetas

## 📈 Próximas características

- [ ] Estadísticas de uso de QR
- [ ] Fechas de expiración para redirecciones
- [ ] API REST para integración
- [ ] Códigos QR personalizados con logos
- [ ] Export/Import de redirecciones

## 🤝 Soporte

Si encuentras algún problema o tienes sugerencias, puedes:

1. Verificar la sección "Solución de problemas"
2. Revisar los logs de error de tu servidor
3. Verificar los permisos de archivos y carpetas

## 📄 Licencia

Este proyecto es de código abierto y libre para uso personal y comercial.

---

**¡Listo para usar! 🚀**

Tu sistema de gestión de códigos QR está configurado y funcionando.