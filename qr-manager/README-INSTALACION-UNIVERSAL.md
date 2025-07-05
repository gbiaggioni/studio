# 🚀 QR MANAGER EMPRESARIAL - INSTALACIÓN UNIVERSAL

## 📋 INSTALACIÓN AUTOMÁTICA COMPATIBLE CON TODO

**QR Manager** es un sistema empresarial completo para gestión de códigos QR con analytics avanzado, compatible con **Windows (XAMPP)**, **Linux (Apache/OpenLiteSpeed)** y **macOS**.

### 🎯 **INSTALACIÓN EN 3 PASOS**

1. **Descargar** archivos a tu directorio web
2. **Ejecutar** el instalador apropiado para tu sistema
3. **Acceder** a la aplicación y crear QRs

---

## 🖥️ OPCIONES DE INSTALACIÓN

### **🪟 WINDOWS + XAMPP** (Más Fácil)
```bash
# Descargar archivos a: C:\xampp\htdocs\qr-manager\
# Ejecutar:
install-windows.bat
```
- ✅ **Detección automática** de XAMPP
- ✅ **Configuración automática** PHP/Apache
- ✅ **Verificación completa** de requisitos
- ✅ **Instalación en 1 clic**

### **🐧 LINUX** (Ubuntu/CentOS/Debian)
```bash
# Ejecutar:
bash ./install-universal.sh
```
- ✅ **Detección automática** de servidor web
- ✅ **Soporte Apache** con/sin mod_rewrite
- ✅ **Soporte OpenLiteSpeed** + CyberPanel
- ✅ **Soporte Nginx** (configuración manual)

### **🍎 macOS**
```bash
# Ejecutar:
bash ./install-universal.sh
```
- ✅ **Compatible con MAMP/XAMPP**
- ✅ **Apache nativo** de macOS
- ✅ **Homebrew** PHP/Apache

---

## 🎯 CARACTERÍSTICAS PRINCIPALES

### **📊 ANALYTICS PROFESIONAL**
- **Estadísticas en tiempo real** con gráficos interactivos
- **Geolocalización** de accesos con mapas
- **Tipos de dispositivos** (móvil, desktop, tablet)
- **Navegadores y sistemas operativos**
- **Exportación** a PDF, Excel, CSV
- **Filtros avanzados** por fechas, país, dispositivo

### **🔐 SEGURIDAD AVANZADA**
- **Contraseñas de acceso** con hints
- **Fechas de expiración** automáticas
- **Límites de uso** configurables
- **Acceso solo a empleados** registrados
- **Auditoría completa** de accesos
- **Protección contra bots** y spam

### **🎨 PERSONALIZACIÓN VISUAL**
- **Colores personalizados** para QRs
- **Diferentes tamaños** (150x150 a 1000x1000)
- **Estilos de marcos** y esquinas
- **Vista previa** en tiempo real
- **Logos corporativos** (próximamente)
- **Branding completo**

### **👥 GESTIÓN DE USUARIOS**
- **Múltiples roles**: Admin, Manager, Usuario
- **Permisos granulares** por función
- **Registro de actividades** detallado
- **Sesiones seguras** con timeout
- **Cambio de contraseñas** forzado
- **Bloqueo de cuentas** por intentos fallidos

### **📱 TEMPLATES PREDEFINIDOS**
- **Redes sociales**: Instagram, Facebook, LinkedIn, TikTok
- **Comunicación**: WhatsApp, Telegram, Email
- **Contenido**: YouTube, Podcast, Blog
- **Negocio**: Google Reviews, Menús digitales
- **Eventos**: Calendario, Zoom, Teams
- **Contacto**: vCard, Teléfono, Dirección

---

## 🔧 CONFIGURACIÓN AVANZADA

### **⚙️ OPCIONES SIN MOD_REWRITE**
Para hosting básico o Apache sin mod_rewrite:
```bash
# Durante instalación, seleccionar:
# "2) No - Sin mod_rewrite"
```
- ✅ **URLs funcionan** como `/qr/abc123/index.php`
- ✅ **Compatible** con hosting compartido básico
- ✅ **Mismo funcionamiento** que con mod_rewrite
- ✅ **No requiere** configuración Apache avanzada

### **🚀 OPTIMIZACIÓN OPENLITESPEED**
Para máximo rendimiento:
```bash
# Instalar CyberPanel + OpenLiteSpeed
# El instalador detecta automáticamente
```
- ✅ **6x más rápido** que Apache
- ✅ **50% menos memoria** utilizada
- ✅ **10,000+ conexiones** simultáneas
- ✅ **Cache integrado** nativo

### **🌐 CONFIGURACIÓN MULTI-DOMINIO**
```php
// config.php
define('BASE_URL', 'https://tu-dominio.com/qr-manager');
```
- ✅ **Múltiples dominios** soportados
- ✅ **Subdominios** dedicados
- ✅ **SSL/HTTPS** automático
- ✅ **CDN compatible**

---

## 📦 ARCHIVOS DE INSTALACIÓN

### **Instaladores Principales**
- `install-universal.sh` - **Linux/macOS universal**
- `install-windows.bat` - **Windows/XAMPP específico**

### **Archivos de Configuración**
- `config.php` - **Configuración principal**
- `.htaccess` - **Reglas Apache** (auto-generado)
- `server-config.txt` - **Instrucciones específicas** (auto-generado)

### **Verificación**
- `verify-installation.php` - **Verificación web completa**
- `verify-windows.php` - **Verificación Windows** (auto-generado)

### **Documentación**
- `INSTALACION-WINDOWS-XAMPP.md` - **Guía Windows detallada**
- `INSTALACION-CYBERPANEL-OPENLITESPEED.md` - **Guía Linux/CyberPanel**
- `server-config.txt` - **Configuración específica** del servidor

---

## 🔍 VERIFICACIÓN DE INSTALACIÓN

### **Método 1: Verificación Web**
```
http://tu-dominio.com/qr-manager/verify-installation.php
```

### **Método 2: Login Inicial**
```
URL: http://tu-dominio.com/qr-manager/
Usuario: admin
Contraseña: password
```

### **Método 3: Crear QR de Prueba**
1. Hacer login en la aplicación
2. Crear QR con URL: `https://google.com`
3. Probar que funciona correctamente

---

## 🚨 SOLUCIÓN DE PROBLEMAS

### **❌ Error: "Apache no inicia"**
**Windows/XAMPP**:
- Cerrar Skype, IIS, otros servidores
- Cambiar puerto en XAMPP: `Listen 80` → `Listen 8080`

**Linux**:
```bash
sudo systemctl stop apache2
sudo systemctl start apache2
```

### **❌ Error: "PHP no funciona"**
**Verificar instalación**:
```bash
php -v
```

**Instalar PHP** (Ubuntu):
```bash
sudo apt install php libapache2-mod-php
```

### **❌ Error: "Extensiones PHP faltantes"**
**Ubuntu/Debian**:
```bash
sudo apt install php-curl php-gd php-json php-fileinfo
```

**CentOS/RHEL**:
```bash
sudo yum install php-curl php-gd php-json
```

### **❌ Error: "No se pueden crear QRs"**
**Verificar permisos**:
```bash
chmod 755 qr-manager/
chmod 755 qr-manager/qr/
chmod 666 qr-manager/*.json
```

**Verificar conectividad**:
```bash
curl -I https://api.qrserver.com
```

---

## 🌟 CARACTERÍSTICAS EMPRESARIALES

### **📈 ANALYTICS AVANZADO**
- **Dashboard ejecutivo** con KPIs
- **Informes personalizados** por período
- **Comparativas** mes a mes
- **Alertas automáticas** por umbrales
- **Exportación programada** de reportes

### **🔒 SEGURIDAD EMPRESARIAL**
- **Auditoría completa** de acciones
- **Logs detallados** de seguridad
- **Restricciones geográficas**
- **Whitelist/Blacklist** de IPs
- **Integración LDAP** (próximamente)

### **🎯 GESTIÓN AVANZADA**
- **Bulk operations** (crear/editar múltiples QRs)
- **Importación CSV** de QRs masivos
- **API REST** para integraciones
- **Webhooks** para notificaciones
- **Backup automático** de datos

### **📱 FUNCIONES MÓVILES**
- **Interfaz responsive** 100% móvil
- **Scanner QR** integrado
- **Notificaciones push** (próximamente)
- **App móvil** dedicada (próximamente)

---

## 🎯 CASOS DE USO EMPRESARIAL

### **🏢 MARKETING DIGITAL**
- **Campañas publicitarias** con tracking
- **Materiales impresos** con QRs dinámicos
- **Redes sociales** con analytics
- **Email marketing** con engagement tracking

### **🏪 RETAIL & COMERCIO**
- **Menús digitales** en restaurantes
- **Catálogos de productos** actualizables
- **Promociones temporales** con expiración
- **Feedback de clientes** con formularios

### **🏭 INDUSTRIA & MANUFACTURA**
- **Manuales técnicos** digitales
- **Códigos de trazabilidad** de productos
- **Instrucciones de seguridad** actualizables
- **Reporting de incidencias** con QR

### **🎓 EDUCACIÓN & EVENTOS**
- **Contenido educativo** multimedia
- **Registro de eventos** automático
- **Evaluaciones** con formularios
- **Networking** con vCard automático

---

## 🚀 INSTALACIÓN RÁPIDA

### **Windows (XAMPP)**
```batch
# 1. Descargar archivos a C:\xampp\htdocs\qr-manager\
# 2. Ejecutar:
install-windows.bat
# 3. Acceder: http://localhost/qr-manager/
```

### **Linux (Apache)**
```bash
# 1. Subir archivos a /var/www/html/qr-manager/
# 2. Ejecutar:
sudo bash ./install-universal.sh
# 3. Acceder: https://tu-dominio.com/qr-manager/
```

### **Linux (OpenLiteSpeed)**
```bash
# 1. Desde CyberPanel subir archivos
# 2. Ejecutar:
bash ./install-universal.sh
# 3. Configurar en CyberPanel según server-config.txt
```

---

## 📊 ESPECIFICACIONES TÉCNICAS

### **🔧 REQUISITOS MÍNIMOS**
- **PHP**: 7.4+ (recomendado 8.0+)
- **Memoria**: 128MB (recomendado 256MB)
- **Disco**: 50MB + logs y QRs
- **Extensiones**: json, curl, gd, fileinfo, session

### **🌐 SERVIDORES COMPATIBLES**
- **Apache**: 2.4+ (con/sin mod_rewrite)
- **OpenLiteSpeed**: Todas las versiones
- **Nginx**: 1.18+ (configuración manual)
- **IIS**: 10+ (configuración manual)

### **💾 ALMACENAMIENTO**
- **Datos**: Archivos JSON (sin base de datos)
- **QRs**: Carpetas dinámicas `/qr/{id}/`
- **Logs**: Archivos de texto rotables
- **Backups**: JSON comprimidos

### **🔐 SEGURIDAD**
- **Passwords**: bcrypt hash
- **Sesiones**: PHP secure sessions
- **CSRF**: Tokens de protección
- **XSS**: Filtrado de entradas
- **SQL**: No aplica (sin base de datos)

---

## 🎉 FUNCIONALIDADES COMPLETAS

### ✅ **GESTIÓN DE QRs**
- Crear QRs dinámicos ilimitados
- URLs personalizadas o automáticas
- Editar destinos sin cambiar QR
- Eliminar QRs con confirmación
- Bulk operations para múltiples QRs

### ✅ **ANALYTICS PROFESIONAL**
- Estadísticas en tiempo real
- Geolocalización con mapas
- Tipos de dispositivos detallados
- Exportación PDF/Excel/CSV
- Comparativas y tendencias

### ✅ **PERSONALIZACIÓN VISUAL**
- Colores personalizados
- Múltiples tamaños disponibles
- Estilos de marcos y esquinas
- Vista previa en tiempo real
- Branding corporativo

### ✅ **SEGURIDAD AVANZADA**
- Contraseñas con hints
- Fechas de expiración
- Límites de uso configurables
- Acceso restringido a empleados
- Auditoría completa de accesos

### ✅ **GESTIÓN DE USUARIOS**
- Múltiples roles y permisos
- Registro de actividades
- Sesiones seguras con timeout
- Cambio de contraseñas forzado
- Bloqueo de cuentas

### ✅ **TEMPLATES PREDEFINIDOS**
- Redes sociales populares
- Comunicación empresarial
- Contenido multimedia
- Reseñas y feedback
- Eventos y contactos

---

## 📞 SOPORTE TÉCNICO

### **🔍 DIAGNÓSTICO AUTOMÁTICO**
- **Verificación web**: `/verify-installation.php`
- **Logs de sistema**: `/logs/error.log`
- **Configuración**: `/server-config.txt`

### **📚 DOCUMENTACIÓN COMPLETA**
- **Windows**: `INSTALACION-WINDOWS-XAMPP.md`
- **Linux**: `INSTALACION-CYBERPANEL-OPENLITESPEED.md`
- **Configuración**: `server-config.txt`

### **⚙️ HERRAMIENTAS DE DEBUGGING**
- **PHP info**: `/verify-installation.php`
- **Connectivity test**: Automático en instalación
- **Permissions check**: Verificación automática

---

## 🎯 **¡INSTALACIÓN COMPLETADA!**

### **📋 CHECKLIST FINAL**
- [ ] ✅ Servidor web configurado y corriendo
- [ ] ✅ PHP con extensiones requeridas
- [ ] ✅ Archivos QR Manager en directorio web
- [ ] ✅ Instalador ejecutado exitosamente
- [ ] ✅ Login funcionando (admin/password)
- [ ] ✅ Primer QR creado y probado
- [ ] ✅ Analytics capturando datos
- [ ] ✅ Contraseña de admin cambiada

### **🌟 PRÓXIMOS PASOS**
1. **Cambiar contraseña** de admin
2. **Crear usuarios** adicionales
3. **Configurar templates** corporativos
4. **Crear primeros QRs** empresariales
5. **Revisar analytics** y ajustar
6. **Integrar con marketing** existente

---

## 🚀 **¡LISTO PARA USAR!**

**QR Manager** está completamente configurado y listo para generar códigos QR empresariales con analytics profesional.

### **🎯 URLs DE ACCESO**
- **Aplicación**: `https://tu-dominio.com/qr-manager/`
- **Verificación**: `https://tu-dominio.com/qr-manager/verify-installation.php`

### **🔑 CREDENCIALES INICIALES**
- **Usuario**: `admin`
- **Contraseña**: `password`

### **💡 TIPS PROFESIONALES**
- Cambia la contraseña por defecto **inmediatamente**
- Crea usuarios específicos para cada departamento
- Usa las **categorías predefinidas** para organizar QRs
- Revisa los **analytics semanalmente** para insights
- Configura **fechas de expiración** en campañas temporales

---

**¡Disfruta de tu nueva plataforma de códigos QR empresariales!** 🎉