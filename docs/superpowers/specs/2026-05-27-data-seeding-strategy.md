# Estrategia de Datos Iniciales (Seeders)

## Propósito

Proveer datos iniciales realistas y coherentes para que el sistema sea funcional desde el primer `migrate --seed`, permitiendo:

- Validar funcionalidades de negocio (FIFO, lotes, alertas, IA)
- Realizar demos comerciales sin configurar desde cero
- Ejecutar pruebas automatizadas con datos predecibles

## Niveles de Siembra

| Nivel | Comando | Propósito | Datos generados |
|-------|---------|-----------|-----------------|
| **Core** | `db:seed` | Mínimo para que la app funcione | Roles, usuarios, catálogos maestros |
| **Demo** | `db:seed --class=DemoSeeder` | Mostrar el sistema completo | Productos reales, clientes, proveedores, compras, ventas |
| **Stress** | `db:seed --class=StressSeeder` | Pruebas de rendimiento | Cientos de registros, múltiples sucursales simuladas |

## Datos Core

Ejecutados por `DatabaseSeeder` y obligatorios para cualquier entorno.

### Roles
| ID | Nombre | Descripción |
|----|--------|-------------|
| 1 | Administrador | Acceso completo al sistema |
| 2 | Cajero | Solo ventas y consultas básicas |
| 3 | Almacén | Gestión de inventario y compras |

### Usuarios
| Email | Contraseña | Rol |
|-------|-----------|-----|
| admin@farmacia.com | admin123 | Administrador |
| cajero@farmacia.com | cajero123 | Cajero |

### Catálogos Maestros

Todos los nombres y descripciones en **español**.

#### Categorías (10)
- Analgésicos
- Antibióticos
- Antiinflamatorios
- Antigripales
- Vitaminas y Suplementos
- Dermatológicos
- Gastrointestinales
- Cardiovasculares
- Antialérgicos
- Material de Curación

#### Laboratorios (8)
- Bayer
- Pfizer
- Roche
- Novartis
- Sanofi
- GlaxoSmithKline
- Merck
- Genéricos Nacionales

#### Tipos de Producto (5)
- Medicamento de Marca
- Medicamento Genérico
- Material Sanitario
- Producto Natural
- Suplemento Alimenticio

#### Presentaciones (7)
- Caja x 10 tabletas
- Caja x 20 tabletas
- Caja x 30 tabletas
- Frasco x 120 ml
- Blister x 10 cápsulas
- Tubo x 30 g
- Unidad

#### Condiciones de Almacenamiento (5)
- Temperatura Ambiente (15-25°C)
- Refrigeración (2-8°C)
- Congelación (-18°C)
- Lugar Seco
- Proteger de la Luz

#### Tipos de Documento (4)
- DNI
- RUC
- Pasaporte
- Cédula de Identidad

#### Tipos de Documento de Compra (3)
- Factura
- Boleta
- Nota de Crédito

## Datos Demo

Incluye todos los Core más:

- **30 productos** con nombres farmacéuticos reales
- **10 clientes** con DNI/RUC
- **5 proveedores** con datos de contacto
- **10 compras** con lotes y fechas de vencimiento reales
- **15 ventas** con consumo FIFO de lotes
- **Alertas visibles**: productos bajo stock mínimo, lotes próximos a vencer
- **Sugerencias IA** simuladas en algunos productos

## Convenciones

1. **Idioma**: todo el contenido de farmacia en español
2. **IDs**: los seeders usan `firstOrCreate` para ser idempotentes
3. **Orden**: `DatabaseSeeder` respeta dependencias (primero catálogos, luego transacciones)
4. **Entornos**: en producción solo se siembra Core; Demo y Stress solo en desarrollo
