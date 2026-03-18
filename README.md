# 🏋️ Sistema de Gestión de Gimnasio - FIT BULL CENTER

Sistema web completo para la administración integral de un gimnasio o centro de fitness. Gestiona socios, membresías, clases, empleados, equipos, reservas, pagos y más.

---

## 📋 Características principales

✅ **Gestión de socios** - Registro, edición, búsqueda y visualización de miembros  
✅ **Membresías** - Planes, asignación y control de vigencia automático  
✅ **Pagos y facturas** - Registro de ingresos con generación de comprobantes  
✅ **Clases y horarios** - Catálogo de clases con instructores asignados  
✅ **Reservas** - Sistema de reservación de clases con control de capacidad  
✅ **Equipos e inventario** - Gestión de máquinas y áreas del gimnasio  
✅ **Mantenimientos** - Registro preventivo y correctivo de equipos  
✅ **Control de accesos** - Registro de entradas y salidas de socios  
✅ **Empleados** - Gestión de personal e instructores  
✅ **Usuarios del sistema** - Control de acceso y permisos por rol  

---

## 🚀 Instalación rápida

### 1. **Requisitos**
- Apache (o servidor web compatible con PHP)
- PHP 7.4 o superior
- MySQL 5.7+ o MariaDB 10.4+

### 2. **Instalación de la base de datos**

```sql
-- En tu cliente MySQL (phpMyAdmin, MySQL Workbench, etc.)
1. Crea una base de datos: CREATE DATABASE si_if2;
2. Importa el archivo: si_if2.sql
3. Ejecuta todas las tablas y triggers automáticamente
```

**O en terminal:**
```bash
mysql -u root -p si_if2 < si_if2.sql
```

### 3. **Configurar la conexión a BD**

Edita el archivo `config/db.php`:

```php
<?php
$dbConfig = [
    'host'     => 'localhost',  // Tu servidor MySQL
    'dbname'   => 'si_if2',     // Nombre de BD
    'user'     => 'root',       // Usuario MySQL
    'password' => '',           // Contraseña MySQL
];
?>
```

### 4. **Colocar archivos en el servidor**

- Copia la carpeta `PROYECTO FINAL` a tu directorio web
  - En XAMPP: `C:\xampp\htdocs\PROYECTO-FINAL`
  - En Laragon: `C:\laragon\www\PROYECTO-FINAL`

### 5. **Acceder al sistema**

Abre tu navegador y ve a:
```
http://localhost/PROYECTO-FINAL/
```

---

## 👤 Credenciales de admin (Primera vez)

**Usuario:** `admin`  
**Contraseña:** `admin123`

> ⚠️ **IMPORTANTE:** Cambia estas credenciales en la primera sesión por seguridad.

---

## 📊 Estructura del sistema

```
PROYECTO FINAL/
├── config/
│   └── db.php                 # Configuración de base de datos
├── includes/
│   ├── init.php              # Inicialización y autoload
│   ├── auth.php              # Funciones de autenticación
│   ├── header.php            # Template HTML superior
│   └── footer.php            # Template HTML inferior
├── socios/                   # Gestión de socios
├── membresias/               # Gestión de membresías
├── pagos/                    # Registro de pagos
├── facturas/                 # Generación de facturas
├── clases/                   # Catálogo de clases
├── horarios/                 # Franjas horarias
├── reservas/                 # Reservación de clases
├── empleados/                # Gestión de personal
├── usuarios/                 # Usuarios del sistema (Admin)
├── equipos/                  # Inventario de máquinas
├── mantenimientos/           # Mantenimiento de equipos
├── accesos/                  # Entrada/salida de socios
├── index.php                 # Dashboard principal
├── login.php                 # Página de login
├── logout.php                # Cierre de sesión
└── si_if2.sql               # Schema de base de datos
```

---

## 🎯 Guía de uso por sección

### 1. **Dashboard (index.php)**
- Vista general con estadísticas en tiempo real
- Accesos recientes, pagos y membresías próximas a vencer
- Punto de entrada después de iniciar sesión

### 2. **Socios**
- **Crear:** Registra nuevo miembro (se auto-genera número de socio)
- **Ver:** Información completa + membresías + pagos + accesos
- **Editar:** Modifica datos del socio
- **Eliminar:** Desactiva el socio (soft delete)

### 3. **Membresías**
- **Crear:** Asigna plan a socio (calcula fecha fin automáticamente)
- **Editar:** Cambia estado (activa/vencida/cancelada/suspendida)
- **Ver:** Lista todas las membresías con filtros

### 4. **Pagos**
- **Crear:** Registra pago (carga dinámico de membresías del socio)
- **Ver:** Historial de ingresos con filtros
- **Anular:** Marca pago como anulado

### 5. **Facturas**
- **Crear:** Genera factura desde pago (número automático: FAC-YYYY-NNNN)
- **Ver:** Muestra comprobante con detalles
- Requiere pago existente

### 6. **Clases y Horarios**
- **Clases:** CRUD de tipos de clase (Yoga, Spinning, Zumba, etc.)
- **Horarios:** Asigna franjas horarias a clases (día/hora/sala)
- En crear horarios: puedes agregar múltiples franjas de una vez

### 7. **Reservas**
- **Crear:** Socio se reserva a una clase en fecha específica
- Validación automática de membresía vigente y capacidad
- **Cancelar:** Libera un lugar
- **Marcar asistencia:** El socio asistió a la clase

### 8. **Equipos**
- CRUD de máquinas del gimnasio
- Gestión de categorías (Cardio, Fuerza, etc.)
- Estados: operativo / mantenimiento / baja

### 9. **Mantenimientos**
- **Crear:** Registra mantenimiento (preventivo/correctivo)
- **Cerrar:** Finaliza mantenimiento
- Automático: Al crear correctivo → equipo cambia a "mantenimiento"

### 10. **Empleados**
- CRUD de personal
- Marca si es instructor (vinculado a clases)
- Datos: salario, fecha de contratación, especialidad

### 11. **Usuarios (Solo Admin)**
- Gestión de usuarios del sistema
- Asignación de roles
- Control de permisos

### 12. **Accesos**
- Registro manual de entrada/salida de socios
- Cálculo automático de duración
- Visible para Admin y Recepcionista

---

## 🔐 Roles y permisos

| Función | Admin | Recepcionista | Descripción |
|---------|-------|---------------|-------------|
| Dashboard | ✅ | ✅ | Ver estadísticas |
| Socios | ✅ | ✅ | Gestión de miembros |
| Membresías | ✅ | ✅ | Asignar/editar planes |
| Pagos | ✅ | ✅ | Registrar ingresos |
| Facturas | ✅ | ✅ | Generar comprobantes |
| Clases | ✅ | ❌ | Gestión de clases |
| Horarios | ✅ | ❌ | Asignar franjas |
| Reservas | ✅ | ✅ | Gestionar reservas |
| Empleados | ✅ | ❌ | Gestión de personal |
| Usuarios | ✅ | ❌ | Crear/editar usuarios |
| Equipos | ✅ | ❌ | Inventario |
| Mantenimientos | ✅ | ❌ | Registrar servicios |
| Accesos | ✅ | ✅ | Entrada/salida |

---

## 🛠️ Funciones especiales

### Auto-vencer membresías
**Evento diario:** `evt_vencer_membresias`
- Cada noche actualiza membresías vencidas
- Estado: activa → vencida (cuando fecha_fin < HOY)

### Triggers automáticos
1. **Equipo en mantenimiento:** Al crear mantenimiento correctivo
2. **Equipo operativo:** Al cerrar mantenimiento exitosamente

### Validaciones
- Email único (socios, empleados, usuarios)
- Número de socio único y auto-generado
- Capacidad máxima en reservas
- Horarios sin traslape por sala
- Contraseñas hasheadas con PASSWORD_DEFAULT

---

## 🔒 Seguridad implementada

✅ Autenticación por sesión  
✅ CSRF tokens en todos los formularios  
✅ Contraseñas hasheadas (password_hash)  
✅ Escape de salida HTML (función `e()`)  
✅ Control de acceso por rol  
✅ Prepared statements (PDO)  

---

## 📧 Primera conexión - Paso a paso

1. **Accede a:** `http://localhost/PROYECTO-FINAL/login.php`

2. **Ingresa:**
   - Email: `admin`
   - Contraseña: `admin123`

3. **Cambiar contraseña (recomendado):**
   - Click en "Usuarios" (menú admin)
   - Busca el usuario "admin"
   - Haz click en "Editar"
   - Ingresa nueva contraseña
   - Guarda cambios

4. **Crear primer usuario recepcionista:**
   - Menú → Usuarios → Nuevo
   - Completa datos
   - Selecciona rol "Recepcionista"
   - Guarda

---

## 📝 Notas importantes

- El sistema genera automáticamente:
  - Números de socio: `SOC-XXXXX`
  - Números de factura: `FAC-YYYY-NNNN`
  
- Las fechas de membresía se calculan automáticamente según el plan seleccionado

- Los datos sensibles (`config/db.php`) están en `.gitignore` (no se suben a repositorio)

- El dashboard muestra:
  - Total de socios activos
  - Membresías activas vigentes
  - Últimos 8 accesos
  - Últimos 6 pagos
  - Membresías próximas a vencer

---

## 🐛 Troubleshooting

**Error de conexión a BD:**
- Verifica que MySQL esté corriendo
- Revisa credenciales en `config/db.php`
- Asegúrate que la BD `si_if2` existe

**Pantalla en blanco:**
- Activa debug en PHP (php.ini)
- Revisa errores en logs del servidor

**No puedo iniciar sesión:**
- Verifica que importaste `si_if2.sql`
- Usuario "admin" debe existir en tabla `usuarios`

---

## 📞 Soporte

Para reportar bugs o sugerencias, abre un issue en el repositorio de GitHub.

---

**Versión:** 1.0  
**Última actualización:** 18 de Marzo de 2026  
**Autor:** Sistema de Gestión - FIT BULL CENTER
