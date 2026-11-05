# LogsGuard

Panel de auditoría de seguridad y monitoreo de actividad para aplicaciones web,
construido en PHP y PDO. El proyecto está pensado como un micro-proyecto
demostrable, con una arquitectura modular y una base de datos relacional.

## Estructura

```text
LogsGuard/
├── config/
│   └── .env.example             # Variables de entorno de referencia
├── database/
│   └── schema.sql               # Tabla, índices y datos de demostración
├── public/                      # Punto de entrada HTTP y recursos públicos
├── src/
│   ├── Config/                  # Configuración y conexión PDO
│   ├── Controllers/             # Coordinación de peticiones
│   ├── Models/                  # Entidades del dominio
│   ├── Repositories/            # Consultas y persistencia
│   └── Views/                   # Vistas HTML con Bootstrap 5
├── tests/                       # Pruebas automatizadas
├── .gitignore
└── README.md
```

## Requisitos

- PHP 8.1 o superior.
- MySQL 8+ o PostgreSQL 14+.
- Extensión PDO y el driver correspondiente (`pdo_mysql` o `pdo_pgsql`).
- Composer (cuando se incorporen las dependencias de desarrollo).

## Instalación local

1. Clona el repositorio:

   ```bash
   git clone https://github.com/DavidSaavedraDev/LogsGuard.git
   cd LogsGuard
   ```

2. Crea la base de datos:

   ```sql
   CREATE DATABASE logsguard;
   ```

3. Ejecuta [`database/schema.sql`](database/schema.sql) usando el motor elegido.
   El script usa SQL común a MySQL y PostgreSQL, y crea la tabla `audit_logs`,
   sus índices y tres registros de demostración. El backend generará los nuevos
   identificadores antes de insertarlos.

4. Copia [`config/.env.example`](config/.env.example) como `.env` en la raíz y
   ajusta las credenciales. Nunca publiques `.env`.

5. Inicia el servidor PHP local cuando exista el punto de entrada:

   ```bash
   php -S localhost:8000 -t public
   ```

6. Abre <http://localhost:8000>.

## Modelo de datos

La tabla `audit_logs` almacena:

| Campo | Descripción |
|---|---|
| `id` | Identificador único del evento |
| `username` | Usuario o servicio que originó la acción |
| `action` | Descripción de la acción auditada |
| `ip_address` | Dirección IPv4 o IPv6 de origen |
| `occurred_at` | Fecha y hora del evento |
| `risk_level` | `low`, `medium`, `high` o `critical` |
| `event_type` | Categoría, por ejemplo `authentication` |
| `details` | Información adicional no estructurada |

## Seguridad

- Las consultas se realizarán con PDO y sentencias preparadas.
- Las credenciales se cargarán desde variables de entorno.
- El nivel de riesgo se limita mediante una restricción `CHECK`.
- Los filtros del dashboard deberán validarse contra listas permitidas.
- No se deben registrar contraseñas, tokens ni otros secretos en `details`.

## Próximos módulos

1. Bootstrap de la aplicación y fábrica de conexión PDO.
2. Repositorio de logs con filtros parametrizados.
3. Dashboard con estadísticas y paginación.
4. Pruebas automatizadas y configuración de calidad.

## Licencia

Este proyecto se publica con fines demostrativos. Añade una licencia explícita
antes de reutilizarlo en producción.
