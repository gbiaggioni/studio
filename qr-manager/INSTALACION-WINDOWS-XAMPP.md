# 🪟 GUÍA DE INSTALACIÓN: QR MANAGER EN WINDOWS + XAMPP

## 🎯 INSTALACIÓN COMPLETA PASO A PASO

Esta guía te llevará a instalar **QR Manager** en **Windows** usando **XAMPP** de forma sencilla y rápida.

---

## 📋 REQUISITOS PREVIOS

### ✅ **Sistema Operativo**
- Windows 7, 8, 10 o 11
- Mínimo 2GB RAM
- 1GB espacio libre en disco

### ✅ **XAMPP**
- XAMPP 7.4 o superior
- Descarga: https://www.apachefriends.org/

---

## 🚀 PASO 1: INSTALAR XAMPP

### **1.1. Descargar XAMPP**
1. Ve a: https://www.apachefriends.org/
2. Descarga **XAMPP para Windows**
3. Elige versión **PHP 7.4** o superior

### **1.2. Instalar XAMPP**
1. Ejecuta el instalador como **Administrador**
2. Instalar en: `C:\xampp` (recomendado)
3. Selecciona componentes:
   - ✅ Apache
   - ✅ MySQL (opcional)
   - ✅ PHP
   - ✅ phpMyAdmin (opcional)

### **1.3. Configurar XAMPP**
1. Abrir **XAMPP Control Panel**
2. Hacer clic en **"Start"** junto a **Apache**
3. Verificar que Apache esté corriendo (luz verde)

### **1.4. Probar Instalación**
1. Abrir navegador
2. Ir a: `http://localhost`
3. Debe aparecer la página de bienvenida de XAMPP

---

## 📁 PASO 2: INSTALAR QR MANAGER

### **2.1. Ubicar Archivos**
1. Navegar a: `C:\xampp\htdocs\`
2. Crear carpeta: `qr-manager`
3. Ruta final: `C:\xampp\htdocs\qr-manager\`

### **2.2. Copiar Archivos**
1. Descomprimir el archivo QR Manager
2. Copiar **todos los archivos** a: `C:\xampp\htdocs\qr-manager\`
3. Estructura debe quedar así:
```
C:\xampp\htdocs\qr-manager\
├── index.php
├── admin.php
├── config.php
├── users.json
├── redirects.json
├── .htaccess
├── qr/ (carpeta)
└── logs/ (carpeta)
```

### **2.3. Ejecutar Instalación Automática**

#### **Opción A: Script Automático (Recomendado)**
1. Abrir **Git Bash** o **PowerShell** en `C:\xampp\htdocs\qr-manager\`
2. Ejecutar:
```bash
bash ./install-universal.sh
```

#### **Opción B: Instalación Manual**
Si el script no funciona:

1. **Crear carpetas**:
   - `C:\xampp\htdocs\qr-manager\qr\`
   - `C:\xampp\htdocs\qr-manager\logs\`

2. **Verificar permisos** (normalmente automáticos en Windows)

---

## ⚙️ PASO 3: CONFIGURAR XAMPP PARA QR MANAGER

### **3.1. Configuración PHP**
1. Abrir: `C:\xampp\php\php.ini`
2. Buscar y modificar:

```ini
; Configuración básica
max_execution_time = 300
memory_limit = 256M
post_max_size = 10M
upload_max_filesize = 10M

; Conectividad externa
allow_url_fopen = On

; Extensiones (descomentar quitando ;)
extension=curl
extension=gd
extension=fileinfo
extension=json
```

3. **Guardar** archivo
4. **Reiniciar Apache** en XAMPP Control Panel

### **3.2. Configuración Apache**
1. Abrir: `C:\xampp\apache\conf\httpd.conf`
2. Verificar que esté habilitado:
```apache
LoadModule rewrite_module modules/mod_rewrite.so
```
3. Si está comentado (con #), descomentarlo
4. **Guardar** y **reiniciar Apache**

---

## 🔍 PASO 4: VERIFICAR INSTALACIÓN

### **4.1. Acceso Web**
1. Abrir navegador
2. Ir a: `http://localhost/qr-manager/`
3. Debe aparecer la página de login

### **4.2. Script de Verificación**
1. Ir a: `http://localhost/qr-manager/verify-installation.php`
2. Verificar que todo esté en verde ✅

### **4.3. Login Inicial**
- **URL**: `http://localhost/qr-manager/`
- **Usuario**: `admin`
- **Contraseña**: `password`

---

## 🎯 PASO 5: PRIMER USO

### **5.1. Cambiar Contraseña**
1. Hacer login con `admin`/`password`
2. Ir a **"Gestión de Usuarios"**
3. Editar usuario **admin**
4. Cambiar contraseña

### **5.2. Crear Primer QR**
1. En el panel principal
2. **URL destino**: `https://google.com`
3. **ID personalizado**: `prueba`
4. Hacer clic en **"Crear Código QR"**

### **5.3. Probar QR**
1. Ir a: `http://localhost/qr-manager/qr/prueba/`
2. Debe redirigir a Google
3. ✅ **¡Funcionando correctamente!**

---

## 🛠️ CONFIGURACIÓN AVANZADA

### **📊 Analytics en Tiempo Real**
- Los analytics funcionan automáticamente
- Ver estadísticas en pestaña **"Analytics"**
- Exportar reportes en PDF/Excel

### **🎨 Personalización Visual**
- Cambiar colores de QR
- Agregar logos (próximamente)
- Diferentes estilos de marcos

### **🔐 QRs Protegidos**
- Contraseñas de acceso
- Fecha de expiración
- Límite de usos
- Solo empleados autorizados

---

## 🚨 SOLUCIÓN DE PROBLEMAS COMUNES

### **❌ Error: "Apache no inicia"**
**Causa**: Puerto 80 ocupado por otro programa

**Solución**:
1. Cerrar Skype, IIS, otros servidores web
2. O cambiar puerto en XAMPP:
   - Clic en **"Config"** → **"Apache (httpd.conf)"**
   - Cambiar `Listen 80` por `Listen 8080`
   - Acceder con: `http://localhost:8080/qr-manager/`

### **❌ Error: "Página no se carga"**
**Verificar**:
1. Apache está corriendo (luz verde en XAMPP)
2. URL correcta: `http://localhost/qr-manager/`
3. Archivos están en `C:\xampp\htdocs\qr-manager\`

### **❌ Error: "PHP no funciona"**
**Solución**:
1. Verificar que PHP esté instalado:
   - Abrir `http://localhost/dashboard/phpinfo.php`
2. Si no funciona, reinstalar XAMPP

### **❌ Error: "Extensiones PHP faltantes"**
**Solución**:
1. Editar `C:\xampp\php\php.ini`
2. Descomentar extensiones necesarias:
```ini
extension=curl
extension=gd
extension=fileinfo
```
3. Reiniciar Apache

### **❌ Error: "No se pueden crear QRs"**
**Verificar**:
1. Permisos de escritura en carpeta
2. Conectividad a internet
3. `allow_url_fopen = On` en php.ini

---

## 📁 UBICACIONES IMPORTANTES

### **Archivos de configuración**:
```
C:\xampp\php\php.ini          → Configuración PHP
C:\xampp\apache\conf\httpd.conf → Configuración Apache
C:\xampp\htdocs\qr-manager\config.php → Configuración QR Manager
```

### **Archivos de datos**:
```
C:\xampp\htdocs\qr-manager\users.json     → Usuarios
C:\xampp\htdocs\qr-manager\redirects.json → QRs creados
C:\xampp\htdocs\qr-manager\analytics.json → Estadísticas
```

### **Logs del sistema**:
```
C:\xampp\htdocs\qr-manager\logs\access.log   → Accesos
C:\xampp\htdocs\qr-manager\logs\error.log    → Errores
C:\xampp\htdocs\qr-manager\logs\security.log → Seguridad
```

---

## 🔐 CONFIGURACIÓN DE SEGURIDAD

### **Para uso local (desarrollo)**
- Configuración por defecto es suficiente
- Solo acceso desde `localhost`

### **Para acceso desde red local**
1. Configurar firewall de Windows
2. Permitir acceso Apache puerto 80
3. Acceder desde otra PC: `http://IP-DEL-SERVIDOR/qr-manager/`

### **Para uso en producción**
- ⚠️ **NO usar XAMPP en producción**
- Migrar a servidor Apache/Linux real
- Configurar SSL/HTTPS
- Cambiar todas las contraseñas

---

## 🎉 FUNCIONALIDADES COMPLETAS DISPONIBLES

### ✅ **Gestión de QRs**
- Crear QRs con URLs personalizadas
- IDs personalizados o automáticos
- Editar destinos sin cambiar QR
- Eliminar QRs completamente

### ✅ **Analytics Profesional**
- Estadísticas en tiempo real
- Geolocalización de accesos
- Tipos de dispositivos
- Exportación PDF/Excel/CSV

### ✅ **Personalización Visual**
- Colores personalizados
- Diferentes tamaños
- Estilos de marcos y esquinas
- Vista previa en tiempo real

### ✅ **Seguridad Avanzada**
- Contraseñas de acceso
- Fechas de expiración
- Límites de uso
- Restricción por empleados

### ✅ **Gestión de Usuarios**
- Múltiples usuarios
- Roles diferenciados
- Auditoría completa
- Permisos granulares

### ✅ **Templates Predefinidos**
- Instagram, Facebook, LinkedIn
- WhatsApp, WiFi, vCard
- Google Reviews, Menús digitales
- Eventos, Contactos

---

## 📞 SOPORTE Y AYUDA

### **Verificación Automática**
- `http://localhost/qr-manager/verify-installation.php`

### **Logs de Error**
- Revisar: `C:\xampp\htdocs\qr-manager\logs\error.log`

### **Configuración**
- Ver: `C:\xampp\htdocs\qr-manager\server-config.txt`

---

## ✅ CHECKLIST FINAL

- [ ] ✅ XAMPP instalado y funcionando
- [ ] ✅ Apache corriendo (luz verde)
- [ ] ✅ PHP configurado correctamente
- [ ] ✅ QR Manager copiado a htdocs
- [ ] ✅ Script de instalación ejecutado
- [ ] ✅ Login funcionando (`admin`/`password`)
- [ ] ✅ Primer QR creado y probado
- [ ] ✅ Analytics capturando datos
- [ ] ✅ Contraseña de admin cambiada

---

## 🎯 **¡INSTALACIÓN COMPLETADA!**

Tu **QR Manager** está funcionando en:
**http://localhost/qr-manager/**

**¡Listo para crear códigos QR profesionales con analytics completo!** 🚀

---

### **URLs Útiles**:
- **Aplicación**: `http://localhost/qr-manager/`
- **Verificación**: `http://localhost/qr-manager/verify-installation.php`
- **XAMPP Dashboard**: `http://localhost/dashboard/`
- **PHP Info**: `http://localhost/dashboard/phpinfo.php`