# 🛠️ SOLUCIÓN ERROR DE INSTALACIÓN - CYBERPANEL

## ❌ PROBLEMA DETECTADO

Tu error:
```
./install-cyberpanel.sh: 65: [[: not found
./install-cyberpanel.sh: 72: Syntax error: "(" unexpected
```

**Causa**: El script usa sintaxis de `bash` pero se ejecutó con `sh`

---

## ✅ SOLUCIONES (3 OPCIONES)

### 🚀 **OPCIÓN 1: USAR BASH (RECOMENDADO)**

```bash
# En lugar de: sh ./install-cyberpanel.sh
# Usa:
bash ./install-cyberpanel.sh
```

### 🔧 **OPCIÓN 2: SCRIPT CORREGIDO**

He actualizado el script para que sea compatible con `sh`. Ahora puedes usar cualquiera de estos comandos:

```bash
# Cualquiera de estos funciona:
sh ./install-cyberpanel.sh
bash ./install-cyberpanel.sh
./install-cyberpanel.sh
```

### 📋 **OPCIÓN 3: INSTALACIÓN MANUAL**

Si los scripts fallan, puedes hacer la instalación manualmente:

```bash
# 1. Configurar permisos
chmod 755 .
chmod 644 *.php *.json .htaccess
chmod 666 *.json
chmod 755 qr/

# 2. Crear directorio de logs
mkdir -p logs
touch logs/access.log logs/error.log logs/security.log
chmod 644 logs/*.log

# 3. Verificar PHP
php -v
php -m | grep -E "(json|session|curl|gd|fileinfo)"
```

---

## 🎯 **EJECUTAR INSTALACIÓN CORREGIDA**

```bash
# Método recomendado:
cd /path/to/qr-manager/
bash ./install-cyberpanel.sh
```

**Salida esperada:**
```
🚀 Iniciando instalación de QR Manager para CyberPanel + OpenLiteSpeed...
✅ Directorio verificado correctamente
📋 Configurando permisos de archivos...
📋 Versión PHP detectada: 8.0
✅ Versión PHP compatible
✅ Todas las extensiones PHP requeridas están instaladas
🎉 INSTALACIÓN COMPLETADA PARA CYBERPANEL + OPENLITESPEED
```

---

## 🔍 **VERIFICACIÓN POST-INSTALACIÓN**

### 1. **Verificar archivos creados:**
```bash
ls -la verify-installation.php openlitespeed-config.txt php-config.ini
```

### 2. **Probar acceso web:**
```
URL: https://tu-dominio.com/qr-manager/verify-installation.php
```

### 3. **Login en la aplicación:**
```
URL: https://tu-dominio.com/qr-manager/
Usuario: admin
Contraseña: password
```

---

## 🚨 **SI SIGUES TENIENDO PROBLEMAS**

### **Error: "PHP not found"**
```bash
# Verificar PHP en el sistema
which php
php --version

# Si no está disponible, verificar en CyberPanel
# que PHP esté instalado y configurado
```

### **Error: "Permission denied"**
```bash
# Asegurar permisos de ejecución
chmod +x install-cyberpanel.sh
```

### **Error: "Cannot write files"**
```bash
# Verificar permisos del directorio
ls -la
chmod 755 .
chmod 666 *.json
```

---

## ✅ **CONFIRMACIÓN DE ÉXITO**

Después de ejecutar correctamente, deberías ver:

1. ✅ **Archivos de configuración creados**:
   - `openlitespeed-config.txt`
   - `verify-installation.php` 
   - `php-config.ini`

2. ✅ **Permisos configurados correctamente**

3. ✅ **Directorio logs/ creado**

4. ✅ **Mensaje final de éxito**

---

## 🎯 **PRÓXIMOS PASOS DESPUÉS DE LA INSTALACIÓN**

1. **Configurar CyberPanel** según `openlitespeed-config.txt`
2. **Editar config.php** con tu dominio real
3. **Verificar funcionamiento** en verify-installation.php
4. **Acceder a la aplicación** con admin/password

---

## 📞 **SOPORTE ADICIONAL**

Si el problema persiste:

1. 🔍 Ejecuta: `bash -x ./install-cyberpanel.sh` (modo debug)
2. 📋 Revisa los permisos del directorio
3. 🔧 Verifica que PHP esté instalado
4. 📚 Consulta la documentación completa

**El script corregido ahora es 100% compatible con sh y bash** ✅