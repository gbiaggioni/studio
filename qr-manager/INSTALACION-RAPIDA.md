# 🚀 Instalación Rápida - QR Manager

## ⚡ Pasos Mínimos para Funcionar

### 1. Subir archivos
Sube toda la carpeta `qr-manager/` a tu servidor web.

### 2. Configurar permisos
```bash
chmod 777 qr-manager/qr/
chmod 666 qr-manager/users.json
chmod 666 qr-manager/redirects.json
```

### 3. Configurar dominio
Edita `config.php` línea 5:
```php
define('BASE_URL', 'https://tudominio.com/qr-manager');
```

### 4. Acceder
- **URL**: `https://tudominio.com/qr-manager/`
- **Usuario**: `admin`
- **Contraseña**: `password`

## ✅ Verificar Instalación

Ejecuta: `https://tudominio.com/qr-manager/test-setup.php`

## 🎯 Primer Uso

1. Entra al panel de administración
2. Llena el formulario:
   - **URL de destino**: `https://youtube.com/watch?v=dQw4w9WgXcQ`
   - **ID personalizado**: `mi-video` (opcional)
3. Haz clic en "Crear Código QR"
4. Tu QR apuntará a: `https://tudominio.com/qr-manager/qr/mi-video`
5. Al escanear redirige a YouTube

## ✏️ Editar Redirecciones

1. En la tabla, haz clic en el **ícono de lápiz** (botón amarillo)
2. Cambia la URL de destino en el modal
3. Haz clic en "Actualizar Destino"
4. El QR seguirá igual, pero ahora redirige al nuevo destino

## 🔧 Archivos Principales

| Archivo | Función |
|---------|---------|
| `index.php` | Página de login |
| `admin.php` | Panel de administración |
| `config.php` | Configuración |
| `users.json` | Usuarios administradores |
| `redirects.json` | Redirecciones creadas |
| `qr/[id]/index.php` | Archivos de redirección |

## ⚠️ Importante

- Cambia la contraseña después del primer login
- Elimina `test-setup.php` después de verificar
- Los archivos JSON están protegidos por `.htaccess`

---

**¡Ya está listo para usar! 🎉**