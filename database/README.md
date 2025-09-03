# DentexaPro - Migración a Base de Datos SQL

Este directorio contiene la migración completa de DentexaPro a una base de datos SQL estándar.

## Archivos incluidos

- `schema.sql` - Esquema completo de la base de datos con datos de ejemplo

## Estructura de la base de datos

### Tablas principales

1. **users** - Autenticación de usuarios
2. **user_profiles** - Perfiles extendidos de usuarios (dentistas)
3. **patients** - Pacientes de cada consultorio
4. **appointments** - Turnos y citas
5. **medical_records** - Historiales clínicos
6. **invoices** - Facturas
7. **invoice_items** - Items de facturas
8. **treatments** - Catálogo de tratamientos
9. **reminders** - Recordatorios automáticos
10. **subscription_history** - Historial de suscripciones

### Vistas útiles

- `v_users_complete` - Información completa de usuarios
- `v_appointments_summary` - Resumen de turnos
- `v_revenue_summary` - Resumen de ingresos por clínica

### Procedimientos almacenados

- `CreateAppointmentWithReminder()` - Crear turno con recordatorio automático
- `GetClinicStats()` - Obtener estadísticas del consultorio

## Instalación

### MySQL/MariaDB
```sql
-- Crear base de datos
CREATE DATABASE dentexapro;
USE dentexapro;

-- Ejecutar el schema
SOURCE schema.sql;
```

### PostgreSQL
```sql
-- Crear base de datos
CREATE DATABASE dentexapro;
\c dentexapro

-- Ejecutar el schema (adaptar sintaxis si es necesario)
\i schema.sql
```

## Datos de ejemplo incluidos

El schema incluye datos de ejemplo:

### Usuarios
- **admin@dentexapro.com** - Administrador del sistema
- **juan@juan.com** - Dr. Juan Pérez (Consultorio Dr. Pérez)
- **fernando@fernando.com** - Dr. Fernando García (Clínica García)

### Pacientes
- María González, Carlos Rodríguez (pacientes de Dr. Juan)
- Ana López, Luis Martínez (pacientes de Dr. Fernando)

### Turnos y tratamientos
- Turnos de ejemplo para ambos consultorios
- Catálogo de tratamientos por especialidad

## Notas importantes

1. **Contraseñas**: Los hashes de contraseña son de ejemplo. En producción usar bcrypt o similar.
2. **UUIDs**: Se usan UUIDs para todas las claves primarias.
3. **Triggers**: Actualizaciones automáticas de timestamps.
4. **Índices**: Optimizados para consultas comunes.
5. **Constraints**: Validaciones de datos a nivel de base de datos.

## Consultas útiles para administración

```sql
-- Ver todos los usuarios con info de suscripción
SELECT * FROM v_users_complete ORDER BY user_created_at DESC;

-- Ingresos por mes
SELECT 
    DATE_FORMAT(invoice_date, '%Y-%m') as month,
    COUNT(*) as total_invoices,
    SUM(total_amount) as total_revenue
FROM invoices 
WHERE status = 'paid' 
GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
ORDER BY month DESC;

-- Pruebas que vencen pronto
SELECT 
    full_name, email, clinic_name, trial_end_date,
    trial_days_remaining
FROM v_users_complete 
WHERE subscription_status = 'trial' 
  AND trial_days_remaining <= 3 
ORDER BY trial_days_remaining ASC;
```

## Próximos pasos

1. Configurar tu servidor de base de datos preferido
2. Ejecutar el schema.sql
3. Adaptar la aplicación para usar tu nueva base de datos
4. Configurar autenticación (reemplazar Supabase Auth)
5. Implementar API endpoints para CRUD operations