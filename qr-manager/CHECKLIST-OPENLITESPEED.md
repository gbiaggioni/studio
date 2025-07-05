# ✅ CHECKLIST DE INSTALACIÓN RÁPIDA - OPENLITESPEED

## 🚀 INSTALACIÓN EN 4 PASOS SIMPLES

### 📋 **PASO 1: PREPARAR ARCHIVOS**
- [ ] ✅ Subir todos los archivos del `qr-manager/` a tu servidor
- [ ] ✅ Ubicación recomendada: `/home/tu-dominio.com/public_html/qr-manager/`
- [ ] ✅ Verificar que tienes acceso SSH o File Manager

### 🔧 **PASO 2: EJECUTAR INSTALACIÓN AUTOMÁTICA**
```bash
cd /home/tu-dominio.com/public_html/qr-manager/
./install-cyberpanel.sh
```
- [ ] ✅ Script ejecutado sin errores
- [ ] ✅ Permisos configurados automáticamente
- [ ] ✅ Archivos de configuración creados

### ⚙️ **PASO 3: CONFIGURAR CYBERPANEL**

#### 🔹 **A. Configuración PHP**
- **Ubicación**: CyberPanel → Websites → [Tu sitio] → PHP
- [ ] ✅ Versión PHP 7.4 o superior seleccionada
- [ ] ✅ `max_execution_time = 300`
- [ ] ✅ `memory_limit = 256M`
- [ ] ✅ `post_max_size = 10M`
- [ ] ✅ `upload_max_filesize = 10M`
- [ ] ✅ `allow_url_fopen = On`

#### 🔹 **B. Headers de Seguridad**
- **Ubicación**: CyberPanel → Websites → [Tu sitio] → Headers
- [ ] ✅ `X-Frame-Options: SAMEORIGIN`
- [ ] ✅ `X-Content-Type-Options: nosniff`
- [ ] ✅ `X-XSS-Protection: 1; mode=block`
- [ ] ✅ `Referrer-Policy: strict-origin-when-cross-origin`

#### 🔹 **C. SSL/HTTPS**
- **Ubicación**: CyberPanel → SSL → [Tu sitio]
- [ ] ✅ SSL activado (Let's Encrypt recomendado)
- [ ] ✅ HTTPS redirect forzado
- [ ] ✅ Certificado válido y renovación automática

#### 🔹 **D. Compresión**
- **Ubicación**: CyberPanel → Websites → [Tu sitio] → Caching
- [ ] ✅ Gzip compression activado
- [ ] ✅ Cache para archivos estáticos habilitado

### 🌐 **PASO 4: CONFIGURAR DOMINIO Y VERIFICAR**

#### 🔹 **A. Editar Configuración**
```php
// Editar archivo: config.php (línea 4)
define('BASE_URL', 'https://TU-DOMINIO.com/qr-manager');
```
- [ ] ✅ Dominio actualizado correctamente
- [ ] ✅ HTTPS si tienes SSL (recomendado)

#### 🔹 **B. Verificación Automática**
- **URL**: `https://tu-dominio.com/qr-manager/verify-installation.php`
- [ ] ✅ Todas las verificaciones en verde ✅
- [ ] ✅ Sin errores rojos ❌

#### 🔹 **C. Prueba de Acceso**
- **URL**: `https://tu-dominio.com/qr-manager/`
- **Credenciales**: `admin` / `password`
- [ ] ✅ Login funciona correctamente
- [ ] ✅ Panel de administración se carga

#### 🔹 **D. Prueba de Funcionalidad**
1. **Crear QR de prueba**:
   - [ ] ✅ ID: `test` 
   - [ ] ✅ URL: `https://google.com`
   - [ ] ✅ Se crea carpeta `/qr/test/`

2. **Probar redirección**:
   - [ ] ✅ `https://tu-dominio.com/qr-manager/qr/test` redirige a Google

3. **Verificar protección**:
   - [ ] ✅ `https://tu-dominio.com/qr-manager/users.json` muestra error 403

---

## 🔍 VERIFICACIÓN FINAL

### ✅ **Funcionalidades Críticas**
- [ ] ✅ **Login** y gestión de usuarios
- [ ] ✅ **Crear QRs** con ID personalizado  
- [ ] ✅ **Redirección** automática funciona
- [ ] ✅ **Analytics** captura datos
- [ ] ✅ **Protección** de archivos JSON
- [ ] ✅ **Templates** predefinidos disponibles
- [ ] ✅ **Personalización** visual funcional
- [ ] ✅ **Exportación** de reportes (CSV/PDF)

### 🎯 **Rendimiento OpenLiteSpeed**
- [ ] ✅ Velocidad de carga rápida (< 2 segundos)
- [ ] ✅ Respuesta del servidor rápida
- [ ] ✅ QRs se generan instantáneamente
- [ ] ✅ Analytics se procesan sin demora

---

## 🚨 SOLUCIÓN RÁPIDA DE PROBLEMAS

### ❌ **Error 403 en toda la aplicación**
```bash
chmod 755 /path/to/qr-manager/
chmod 644 index.php
```

### ❌ **No se pueden escribir archivos JSON**  
```bash
chmod 666 *.json
```

### ❌ **Las sesiones no funcionan**
- Verificar en CyberPanel → PHP que las sesiones estén habilitadas
- Comprobar permisos de `/tmp/` en el servidor

### ❌ **Los QRs no se muestran**
- Verificar `allow_url_fopen = On` en CyberPanel → PHP
- Comprobar conectividad externa del servidor

### ❌ **SSL no funciona**
- Renovar certificado en CyberPanel → SSL
- Verificar que el dominio esté correctamente apuntado

---

## 🎉 ¡INSTALACIÓN COMPLETADA!

### 🚀 **Acceso a tu QR Manager**
```
URL: https://tu-dominio.com/qr-manager/
Usuario: admin
Contraseña: password
```

### 📚 **Documentación Adicional**
- `INSTALACION-CYBERPANEL-OPENLITESPEED.md` - Guía detallada
- `RESUMEN-OPENLITESPEED-COMPATIBLE.md` - Especificaciones técnicas
- `README.md` - Manual de usuario completo

### 🏆 **Beneficios Obtenidos**
- ⚡ **6x más velocidad** que Apache
- 💾 **50% menos memoria** utilizada  
- 🔐 **Seguridad** empresarial
- 📊 **Analytics** profesional
- 🎨 **Personalización** completa

---

## 🛡️ SEGURIDAD POST-INSTALACIÓN

### 🔐 **Recomendaciones Inmediatas**
- [ ] ✅ Cambiar contraseña por defecto (`admin` / `password`)
- [ ] ✅ Crear usuarios adicionales si necesario
- [ ] ✅ Configurar backup automático de archivos JSON
- [ ] ✅ Revisar logs periódicamente

### 🔄 **Mantenimiento Recomendado**
- [ ] ✅ Actualizar certificados SSL (automático con Let's Encrypt)
- [ ] ✅ Limpiar logs antiguos mensualmente
- [ ] ✅ Verificar funcionamiento cada 3 meses
- [ ] ✅ Backup de archivos JSON semanalmente

---

## ✅ CONFIRMACIÓN FINAL

**¿Todo está funcionando correctamente?**

- [ ] ✅ **SÍ** - ¡Perfecto! Tu QR Manager está listo para usar
- [ ] ❌ **NO** - Consulta la documentación detallada o revisa los logs

**🎯 ¡Tu sistema QR Manager está optimizado para OpenLiteSpeed y listo para producción!**