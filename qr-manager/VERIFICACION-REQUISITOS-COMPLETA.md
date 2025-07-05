# 🔍 VERIFICACIÓN COMPLETA DE REQUISITOS - QR MANAGER

## 🎯 SCRIPT DE INSTALACIÓN MEJORADO

El script `install-cyberpanel.sh` ahora incluye **verificación completa de requisitos** que detecta automáticamente el entorno y valida todas las dependencias antes de proceder.

---

## 📋 VERIFICACIONES IMPLEMENTADAS

### 🌐 **1. DETECCIÓN DE SERVIDOR WEB**

El script detecta automáticamente:

#### ✅ **OpenLiteSpeed**
- **Ubicaciones verificadas**:
  - `/usr/local/lsws/bin/litespeed`
  - `/usr/local/lsws/` (directorio)
  - Comando `litespeed` en PATH

- **Verificación CyberPanel**:
  - `/usr/local/CyberCP/cyberpanel/manage.py`
  - Comando `cyberpanel` en PATH

#### ✅ **Apache**
- **Comandos verificados**:
  - `apache2` (Ubuntu/Debian)
  - `httpd` (CentOS/RHEL)

- **Verificación mod_rewrite**:
  - `apache2ctl -M` busca `rewrite_module`
  - `httpd -M` busca `rewrite_module`

#### ✅ **Nginx**
- **Detección**: Comando `nginx` en PATH
- **Advertencia**: Requiere configuración manual

#### ❌ **Servidor no detectado**
- Muestra error crítico
- Proporciona comandos de instalación

---

### 🐘 **2. VERIFICACIÓN PHP COMPLETA**

#### **Versión PHP**
```bash
PHP_VERSION=$(php -v | head -n 1 | cut -d ' ' -f 2 | cut -d '.' -f 1,2)
```

**Compatibilidad verificada**:
- ✅ **PHP 7.4 - 9.x**: Compatible
- ⚠️ **PHP 7.0 - 7.3**: Advertencia (funciona pero desactualizado)
- ❌ **PHP < 7.0**: Error crítico

#### **Extensiones Requeridas**
Verifica que estén disponibles:
- `json` - Manejo de datos JSON
- `session` - Gestión de sesiones
- `curl` - Conectividad externa  
- `gd` - Procesamiento de imágenes
- `fileinfo` - Información de archivos

---

### 📁 **3. VERIFICACIÓN DE ESTRUCTURA DE DIRECTORIOS**

#### **Directorio Actual**
```bash
CURRENT_DIR=$(pwd)
```

**Verificaciones**:
- ✅ Contiene "qr-manager" en la ruta
- ✅ Está en directorio web típico (`public_html`, `www`, `htdocs`)
- ✅ Permisos de escritura disponibles

#### **Rutas Típicas Detectadas**
- `/home/usuario.com/public_html/qr-manager/`
- `/var/www/html/qr-manager/`
- `/htdocs/qr-manager/`

---

### 🌍 **4. VERIFICACIÓN DE CONECTIVIDAD EXTERNA**

#### **APIs Esenciales Verificadas**
```bash
test_urls="https://api.qrserver.com https://ipapi.co"
```

**Métodos de verificación**:
1. `curl -s --max-time 5 --head URL`
2. `wget -q --timeout=5 --spider URL` (fallback)

**Funciones que dependen de conectividad**:
- **QR Generation**: `api.qrserver.com`
- **Geolocalización**: `ipapi.co`

---

### 🛠️ **5. VERIFICACIÓN DE HERRAMIENTAS DEL SISTEMA**

#### **Herramientas Requeridas**
```bash
tools="curl wget chmod chown mkdir touch"
```

**Uso en la aplicación**:
- `curl`/`wget`: Conectividad externa
- `chmod`: Configuración de permisos
- `chown`: Cambio de propietarios
- `mkdir`: Creación de directorios
- `touch`: Creación de archivos log

---

## 📊 SISTEMA DE REPORTE

### **Variables de Estado**
```bash
ERRORS=0          # Errores críticos
WARNINGS=0        # Advertencias
SERVER_TYPE=""    # Tipo de servidor detectado
PHP_VERSION=""    # Versión PHP detectada
```

### **Funciones de Reporte**
```bash
show_error()      # ❌ ERROR: (incrementa ERRORS)
show_warning()    # ⚠️  ADVERTENCIA: (incrementa WARNINGS)
show_success()    # ✅ (confirmación)
show_info()       # 📋 (información)
```

---

## 🚦 LÓGICA DE DECISIÓN

### **Flujo de Verificación**

```
1. Verificar requisitos básicos
2. Detectar servidor web
3. Verificar PHP y extensiones
4. Validar estructura de directorios
5. Probar conectividad externa
6. Generar reporte final
7. Decidir si continuar
```

### **Decisión de Instalación**

#### ❌ **Errores Críticos (ERRORS > 0)**
```
❌ INSTALACIÓN NO RECOMENDADA
Se encontraron X errores críticos que deben resolverse.

¿Deseas continuar de todos modos? (no recomendado)
Escribe 'si' para continuar o 'no' para salir:
```

#### ⚠️ **Solo Advertencias (WARNINGS > 0)**
```
⚠️ INSTALACIÓN CON ADVERTENCIAS
Se encontraron X advertencias. La instalación puede continuar.

¿Deseas continuar? (recomendado: si)
Escribe 'si' para continuar o 'no' para salir:
```

#### ✅ **Sin Problemas**
```
✅ TODOS LOS REQUISITOS CUMPLIDOS
Sistema óptimo para QR Manager

🚀 CONTINUANDO CON LA INSTALACIÓN...
```

---

## 📁 CONFIGURACIÓN ESPECÍFICA POR SERVIDOR

### **OpenLiteSpeed + CyberPanel**
```
server-config.txt → Configuración para CyberPanel
- PHP Settings (Websites > Sitio > PHP)
- Security Headers (Websites > Sitio > Headers)  
- SSL/HTTPS (SSL > Sitio)
- Compression (Websites > Sitio > Caching)
```

### **Apache**
```
server-config.txt → Configuración para Apache
- Módulos requeridos (a2enmod rewrite headers expires deflate)
- Virtual Host configuration
- PHP Settings (.htaccess o php.ini)
- SSL con Certbot
```

### **Nginx u Otros**
```
server-config.txt → Configuración genérica
- PHP Settings básicos
- Reglas de rewrite necesarias
- Extensiones PHP requeridas
- Configuración manual requerida
```

---

## 🎯 EJEMPLO DE EJECUCIÓN

### **Comando**
```bash
bash ./install-cyberpanel.sh
```

### **Salida de Ejemplo**
```
🚀 Iniciando instalación de QR Manager para CyberPanel + OpenLiteSpeed...

🔍 VERIFICANDO REQUISITOS DEL SISTEMA...
========================================

📋 Detectando servidor web...
✅ OpenLiteSpeed detectado
✅ CyberPanel detectado - Configuración óptima

📋 Verificando PHP...
✅ PHP CLI disponible - Versión: 8.0
✅ Versión PHP compatible (8.0)

📋 Verificando extensiones PHP requeridas...
✅ Extensión json: Disponible
✅ Extensión session: Disponible
✅ Extensión curl: Disponible
✅ Extensión gd: Disponible
✅ Extensión fileinfo: Disponible

📋 Verificando estructura de directorios...
✅ Directorio qr-manager detectado: /home/usuario.com/public_html/qr-manager
✅ Directorio web detectado en la ruta
✅ Permisos de escritura en directorio actual

📋 Verificando conectividad externa...
✅ Conectividad OK: https://api.qrserver.com
✅ Conectividad OK: https://ipapi.co

📋 Verificando dependencias del sistema...
✅ Herramienta disponible: curl
✅ Herramienta disponible: wget
✅ Herramienta disponible: chmod
✅ Herramienta disponible: chown
✅ Herramienta disponible: mkdir
✅ Herramienta disponible: touch

📊 REPORTE FINAL DE REQUISITOS
===============================
📋 Servidor Web: OpenLiteSpeed
📋 PHP Versión: 8.0
📋 Directorio: /home/usuario.com/public_html/qr-manager
📋 Errores encontrados: 0
📋 Advertencias: 0

✅ TODOS LOS REQUISITOS CUMPLIDOS
Sistema óptimo para QR Manager

🚀 CONTINUANDO CON LA INSTALACIÓN...
```

---

## 🚨 CASOS DE ERROR COMUNES

### **Error: Servidor web no detectado**
```
❌ ERROR: No se detectó servidor web (Apache, OpenLiteSpeed, Nginx)
📋   - Instala Apache: sudo apt install apache2
📋   - O instala OpenLiteSpeed con CyberPanel
```

### **Error: mod_rewrite no disponible (Apache)**
```
❌ ERROR: mod_rewrite NO está habilitado (requerido para Apache)
📋   - Habilitar con: sudo a2enmod rewrite && sudo systemctl reload apache2
```

### **Error: PHP incompatible**
```
❌ ERROR: Versión PHP no compatible o no detectada (5.6)
```

### **Error: Sin permisos de escritura**
```
❌ ERROR: Sin permisos de escritura en directorio actual
📋   - Ejecutar: chmod 755 .
```

---

## ✅ BENEFICIOS DE LA VERIFICACIÓN

### **🔍 Detección Proactiva**
- Identifica problemas antes de la instalación
- Evita instalaciones fallidas
- Ahorra tiempo de depuración

### **📋 Configuración Específica**
- Genera configuración para el servidor detectado
- Instrucciones precisas por entorno
- Optimizaciones específicas

### **🛡️ Validación Completa**
- Verifica todos los requisitos
- Confirma compatibilidad
- Garantiza funcionamiento óptimo

### **📊 Reporte Detallado**
- Estado completo del sistema
- Errores y advertencias claras
- Recomendaciones específicas

---

## 🎯 RESUMEN

El script mejorado proporciona:

- ✅ **Detección automática** de servidor web
- ✅ **Verificación completa** de PHP y extensiones  
- ✅ **Validación** de estructura de directorios
- ✅ **Prueba** de conectividad externa
- ✅ **Configuración específica** por entorno
- ✅ **Decisión inteligente** sobre instalación
- ✅ **Reporte detallado** de estado

**¡La instalación es ahora más robusta, inteligente y específica para cada entorno!** 🚀