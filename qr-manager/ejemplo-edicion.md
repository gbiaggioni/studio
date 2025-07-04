# 🔄 Ejemplo: Cómo Editar Destinos QR

Este ejemplo muestra cómo cambiar la URL de destino de un código QR ya creado.

## 📋 Scenario de Ejemplo

Supongamos que creaste un QR para una promoción:

1. **QR creado inicialmente:**
   - ID: `promo-navidad`
   - URL original: `https://tienda.com/promocion-navidad`
   - QR URL: `https://tudominio.com/qr-manager/qr/promo-navidad`

2. **Cambio necesario:**
   - La promoción cambió de página
   - Nueva URL: `https://tienda.com/super-descuentos-navidad`

## 🎯 Pasos para Editar

### 1. Acceder al Panel
- Ve a: `https://tudominio.com/qr-manager/admin.php`
- Inicia sesión con tus credenciales

### 2. Localizar la Redirección
- En la tabla verás tu QR `promo-navidad`
- Columna "URL Destino" muestra: `https://tienda.com/promocion-navidad`

### 3. Editar el Destino
- Haz clic en el **botón amarillo** con ícono de lápiz ✏️
- Se abre el modal "Editar Destino QR"

### 4. Completar el Formulario
- **ID del QR**: `promo-navidad` (no se puede cambiar)
- **URL Actual**: `https://tienda.com/promocion-navidad` (solo lectura)
- **Nueva URL**: Ingresa `https://tienda.com/super-descuentos-navidad`

### 5. Confirmar Cambios
- Haz clic en "Actualizar Destino"
- Mensaje de éxito: "Redirección actualizada exitosamente"

## ✅ Resultado

Después de la edición:

- **El QR físico sigue igual** (no necesitas reimprimir)
- **La URL del QR no cambia**: `https://tudominio.com/qr-manager/qr/promo-navidad`
- **El destino es nuevo**: Ahora redirige a `https://tienda.com/super-descuentos-navidad`
- **Se registra el cambio**: En la columna "Última Actualización"

## 📊 Información de Auditoría

El sistema registra automáticamente:

```json
{
    "id": "promo-navidad",
    "destination_url": "https://tienda.com/super-descuentos-navidad",
    "qr_url": "https://tudominio.com/qr-manager/qr/promo-navidad",
    "created_at": "2024-12-01 10:00:00",
    "created_by": "admin",
    "updated_at": "2024-12-15 14:30:00",
    "updated_by": "admin"
}
```

## 🔧 Lo que Sucede Internamente

1. **Archivo actualizado**: `/qr/promo-navidad/index.php`
   ```php
   <?php
   header('Location: https://tienda.com/super-descuentos-navidad');
   exit;
   ?>
   ```

2. **JSON actualizado**: Se modifica `redirects.json` con nueva info

3. **Sin pérdida de datos**: Se mantiene historial de creación y se agrega info de actualización

## 💡 Casos de Uso Comunes

- **Promociones que cambian de página**
- **URLs que se mudan a otro dominio**
- **Corrección de enlaces erróneos**
- **Actualización de contenido estacional**
- **Cambios de estrategia de marketing**

## ⚠️ Notas Importantes

- ✅ El código QR **NO** cambia (puedes seguir usando las impresiones existentes)
- ✅ La URL corta **NO** cambia (enlaces guardados siguen funcionando)
- ✅ Solo cambia el **destino final**
- ✅ Se mantiene **auditoría completa** de cambios
- ❌ No se puede cambiar el **ID del QR** (sería crear uno nuevo)

---

**¡Ahora puedes actualizar tus QRs sin reimprimir! 🎉**