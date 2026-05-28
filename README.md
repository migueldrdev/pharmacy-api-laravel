# 💊 FarmaERP — ERP Farmacéutico Inteligente para PyMEs

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-17-4169E1?logo=postgresql&logoColor=white)
![Redis](https://img.shields.io/badge/Redis-7.x-DC382D?logo=redis&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Sail-2496ED?logo=docker&logoColor=white)
![Tests](https://img.shields.io/badge/Tests-21%2F21-brightgreen)

## 🎯 ¿Qué es FarmaERP?

Un **sistema de gestión farmacéutica (ERP + Punto de Venta)** diseñado específicamente para **pequeñas y medianas farmacias locales**. A diferencia de un sistema de ventas genérico, **FarmaERP** entiende la naturaleza crítica de los medicamentos:

- 🔬 **Trazabilidad por Lotes:** cada medicamento se rastrea desde que entra hasta que se vende
- ⏰ **Algoritmo FIFO:** al vender, el sistema descuenta automáticamente los lotes más próximos a vencer
- 🤖 **Inteligencia Artificial:** predicción de demanda y sugerencias de reabastecimiento
- 📊 **Alertas inteligentes:** vencimientos próximos, stock bajo, productos sin rotación

### 🎯 Público objetivo

Dueños y administradores de **farmacias independientes** y **cadenas pequeñas** que quieren:
- Reducir mermas por caducidad 💸
- Evitar quiebres de stock ❌
- Optimizar compras con predicciones 📈
- Tener control total sin depender de un contador externo

---

## 🧱 Stack Tecnológico (Zero-Cost Mindset)

| Tecnología | Rol | Logo |
|-----------|-----|------|
| **Laravel 12** | API REST backend | ⚡ |
| **PHP 8.4** | Lenguaje | 🐘 |
| **PostgreSQL 17** | Base de datos principal | 🐘 |
| **Redis** | Colas, caché, sesiones | 🔴 |
| **Laravel Horizon** | Dashboard de jobs en background | 📊 |
| **Laravel Sanctum** | Autenticación por tokens | 🔐 |
| **MinIO** | Almacenamiento S3 local (desarrollo) | 🪣 |
| **Cloudflare R2 / Backblaze B2** | Almacenamiento S3 en producción | ☁️ |
| **Gemini 1.5 Flash (Google AI)** | Predicción de demanda con IA | 🤖 |
| **Docker + Laravel Sail** | Entorno de desarrollo | 🐳 |
| **PHPUnit** | Testing automatizado | ✅ |

---

## 🚀 Cómo levantar el proyecto

### Requisitos

- **Docker** y **Docker Compose** instalados
- Git

### 1. Clonar e instalar

```bash
git clone git@github.com:migueldrdev/pharmacy-api-laravel.git
cd pharmacy-api-laravel
cp .env.example .env
```

### 2. Instalar dependencias (opcional, Sail lo hace)

```bash
docker run --rm \
  -v $(pwd):/var/www/html \
  -w /var/www/html \
  laravelsail/php84-composer:latest \
  composer install --ignore-platform-reqs
```

### 3. Levantar contenedores

```bash
./vendor/bin/sail up -d
```

Esto arranca: **PHP 8.4**, **PostgreSQL**, **Redis**, **MinIO**.

### 4. Generar clave de la aplicación

```bash
./vendor/bin/sail artisan key:generate
```

### 5. Migrar y sembrar la base de datos

```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

Esto crea todas las tablas y siembra:
- 2 roles (Administrador, Cajero)
- 2 usuarios demo
- 10 categorías de medicamentos
- 8 laboratorios reales
- 23 productos farmacéuticos reales
- 7 clientes
- 5 proveedores
- 5 compras con lotes
- 8 ventas con trazabilidad FIFO

### 6. MinIO (Bucket de imágenes)

El bucket se debe recrear cada vez que se hace `sail down -v`:

```bash
./vendor/bin/sail exec minio sh -c "mc alias set myminio http://localhost:9000 sail password && mc mb myminio/local && mc anonymous set public myminio/local"
```

### 7. Horizon (Colas y Jobs)

```bash
./vendor/bin/sail artisan horizon
```

### 8. Accesos

| Servicio | URL | Usuario | Contraseña |
|---------|-----|---------|-----------|
| **API** | `http://localhost/api/v1/` | — | — |
| **Horizon** | `http://localhost/horizon` | — | — |
| **MinIO** | `http://localhost:8900` | `sail` | `password` |
| **PostgreSQL** | `localhost:5432` | `admin` | `password` |

### 9. Usuarios demo

| Email | Contraseña | Rol |
|-------|-----------|-----|
| `admin@farmacia.com` | `admin123` | Administrador |
| `cajero@farmacia.com` | `cajero123` | Cajero |

---

## 🧪 Tests

```bash
# Todos los tests (21 tests, 53 assertions)
./vendor/bin/sail artisan test

# Solo Feature
./vendor/bin/sail artisan test --testsuite=Feature

# Solo Unit
./vendor/bin/sail artisan test --testsuite=Unit
```

---

## 📁 Arquitectura del Proyecto

```
app/
├── Contracts/          # Interfaces (AiPredictionInterface)
├── Helpers/            # ResponseHelper
├── Http/
│   ├── Controllers/Api/  # Solo manejan HTTP, validan, responden
│   ├── Requests/         # Form Requests con validación
│   └── Resources/        # API Resources para transformar datos
├── Jobs/               # Jobs asíncronos (alertas, predicciones IA)
├── Models/             # Modelos Eloquent
├── Providers/          # Service Providers (App, Horizon)
├── Repositories/       # Acceso a datos (Eloquent)
│   ├── BaseRepository.php         # Soft deletes, user_created/updated
│   ├── Contracts/                 # Interfaces de repositorios
│   └── {Module}/                  # Repositorios por módulo
└── Services/           # Lógica de negocio y transacciones
    ├── Ai/             # Adaptadores de IA (GeminiAdapter)
    └── Prediction/     # Servicio de predicción de demanda
```

**Patrón:** Controller → Service → Repository → Model

**Principios:**
- Controladores **NO** tocan Eloquent ni lógica de negocio
- Servicios manejan transacciones (`DB::beginTransaction`)
- Repositorios son la **única** capa que consulta la BD
- Soft deletes custom (`active = 0`) en vez del Trait de Laravel

---

## 📚 Documentación

- [Modelo de Negocio](docs/superpowers/specs/2026-05-25-business-model.md)
- [Diseño Técnico y Arquitectura](docs/superpowers/specs/2026-05-25-technical-design.md)
- [Estrategia de Datos (Seeders)](docs/superpowers/specs/2026-05-27-data-seeding-strategy.md)
- [Estrategia de Testing](docs/superpowers/specs/2026-05-27-testing-strategy.md)

---

## 🔑 Comandos útiles

```bash
# Ejecutar el scheduler cada minuto (desarrollo)
./vendor/bin/sail artisan schedule:work

# Forzar generación de predicciones IA ahora
./vendor/bin/sail artisan queue:work

# Limpiar caché
./vendor/bin/sail artisan optimize:clear

# Listar rutas
./vendor/bin/sail artisan route:list --path=api

# Tinker (consola interactiva)
./vendor/bin/sail artisan tinker
```

---

## 💡 ¿Qué hace especial a este sistema?

1. **FIFO Automático:** cada venta consume primero los lotes que caducan antes
2. **IA Predictiva:** Gemini analiza el histórico de ventas y sugiere qué comprar
3. **Costo Cero Operativo:** diseñado para correr en un VPS de $5/mes
4. **Arquitectura Limpia:** fácil de extender y testear
5. **100% en español:** datos, mensajes, validaciones y documentación

---

## 📄 Licencia

MIT — Ver archivo [LICENSE](LICENSE) para más detalles.
