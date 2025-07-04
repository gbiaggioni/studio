# 👥 Ejemplo: Administración de Usuarios

Este ejemplo muestra cómo gestionar usuarios en el sistema QR Manager.

## 📋 Scenario de Ejemplo

Una empresa quiere configurar diferentes niveles de acceso para su equipo:

- **Administrador general**: Control total del sistema
- **Manager de marketing**: Puede crear/editar QRs y algunos usuarios  
- **Asistente**: Solo puede ver y crear QRs básicos

## 🎯 Pasos para Configurar Usuarios

### 1. Acceder a Gestión de Usuarios

1. Inicia sesión como administrador
2. Ve al panel de administración
3. Haz clic en la pestaña **"Gestión de Usuarios"**
4. Verás la tabla con usuarios existentes

### 2. Crear Usuario Manager

**Caso**: Agregar manager de marketing

1. En el formulario "Crear Nuevo Usuario":
   - **Nombre de Usuario**: `marketing_manager`
   - **Contraseña**: `Marketing2024!`
   - **Rol**: `Manager`

2. Haz clic en **"Crear Usuario"**

3. **Resultado**: Usuario creado y visible en la tabla

### 3. Crear Usuario Básico

**Caso**: Agregar asistente

1. Completa el formulario:
   - **Nombre de Usuario**: `asistente_qr`
   - **Contraseña**: `Asistente123`
   - **Rol**: `Usuario`

2. Crear usuario

### 4. Editar Usuario Existente

**Caso**: Cambiar rol de un usuario

1. En la tabla, localiza el usuario `asistente_qr`
2. Haz clic en el **botón de lápiz** (editar)
3. En el modal:
   - **Usuario**: `asistente_qr` (mantener)
   - **Rol**: Cambiar a `Manager`
   - **Contraseña**: Dejar vacío (mantener actual)
4. Haz clic en **"Actualizar Usuario"**

### 5. Eliminar Usuario

**Caso**: Remover usuario que ya no trabaja

1. Localiza el usuario en la tabla
2. Haz clic en el **botón de papelera** (eliminar)
3. En el modal de confirmación:
   - Lee la advertencia
   - Confirma con **"Eliminar Usuario"**

## ✅ Resultado Final

Después de configurar usuarios, tu sistema tendrá:

```json
[
    {
        "id": 1,
        "username": "admin",
        "role": "admin",
        "created_by": "system"
    },
    {
        "id": 2,
        "username": "marketing_manager", 
        "role": "manager",
        "created_by": "admin"
    },
    {
        "id": 3,
        "username": "asistente_qr",
        "role": "manager", 
        "created_by": "admin",
        "updated_by": "admin"
    }
]
```

## 📊 Información de la Tabla

La tabla de usuarios muestra:

| Columna | Descripción |
|---------|-------------|
| **ID** | Identificador único del usuario |
| **Usuario** | Nombre de usuario + badge "Tú" si es tu usuario |
| **Rol** | Badge con color: Admin (rojo), Manager (amarillo), Usuario (azul) |
| **Creado** | Fecha/hora de creación + quién lo creó |
| **Última Actualización** | Fecha/hora de última edición + quién editó |
| **Estado** | Activo (todos los usuarios están activos) |
| **Acciones** | Botones Editar/Eliminar |

## 🔒 Restricciones de Seguridad

### No puedes eliminar:
- ❌ **Tu propio usuario** (evita bloqueo accidental)
- ❌ **El último administrador** (sistema quedaría sin admin)

### No puedes editar:
- ⚠️ **Último admin a otro rol** (debe haber al menos 1 admin)

### Validaciones automáticas:
- ✅ **Nombres únicos** (no duplicados)
- ✅ **Contraseña mínima** (6 caracteres)
- ✅ **Solo caracteres válidos** (letras, números, guiones bajos)

## 💡 Casos de Uso Comunes

### 🏢 **Empresa pequeña**
- 1 Admin (dueño)
- 2-3 Managers (empleados clave)
- 5+ Usuarios (staff general)

### 🏪 **Tienda retail**
- 1 Admin (gerente)
- 2 Managers (supervisores)
- 10+ Usuarios (vendedores)

### 🎓 **Institución educativa**
- 2 Admins (IT + director)
- 5 Managers (coordinadores)
- 50+ Usuarios (profesores)

## ⚠️ Buenas Prácticas

### ✅ **Recomendado:**
- Usar nombres descriptivos (`marketing_juan`, `ventas_maria`)
- Asignar roles según responsabilidades reales
- Cambiar contraseñas periódicamente
- Mantener al menos 2 administradores

### ❌ **Evitar:**
- Usuarios genéricos (`usuario1`, `test`)
- Contraseñas débiles (`123456`, `password`)
- Dar permisos de admin innecesarios
- Eliminar usuarios sin previo aviso al equipo

## 🔄 Flujo de Trabajo Típico

```
1. Admin crea usuarios nuevos
   ↓
2. Usuarios reciben credenciales
   ↓  
3. Usuarios inician sesión y trabajan
   ↓
4. Admin monitorea actividad en tabla
   ↓
5. Admin edita roles según necesidades
   ↓
6. Admin elimina usuarios inactivos
```

---

**¡Ahora tienes control total sobre quién accede a tu sistema QR! 🎉**