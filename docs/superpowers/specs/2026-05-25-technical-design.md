# Especificación Técnica y de Arquitectura (ERP Farmacéutico)

## 1. Stack Tecnológico de Bajo Costo ("Zero-Cost" Mindset)
- **Backend Framework:** Laravel 12 (PHP 8.4) operando como API REST pura.
- **Base de Datos:** PostgreSQL 17. Confiable, ACID compliance, y open-source.
- **Almacenamiento de Archivos (Imágenes/Recetas):**
  - *Local/Desarrollo:* **MinIO** (incluido nativamente en Laravel Sail, emula S3 localmente al 100% gratis).
  - *Producción:* **Cloudflare R2** (Ofrece 10GB gratis y $0 costos de salida de datos) o **Backblaze B2**. Se utilizará el driver `s3` de Laravel, lo que permite cambiar de proveedor solo modificando variables de entorno `.env`.
- **Colas y Tareas en Background (Jobs):**
  - *Driver:* **Redis**.
  - *Gestor:* **Laravel Horizon** (Dashboard gratuito de Laravel para monitorizar trabajos).
- **Entorno de Desarrollo:** Laravel Sail (Docker).

## 2. Arquitectura de Software (Clean Architecture)
El proyecto se rige por una separación estricta de responsabilidades:
- **Controllers (`App\Http\Controllers\Api`):** Únicamente manejan la petición HTTP, validan mediante `FormRequests` y retornan JSON estandarizado usando `ResponseHelper`. **PROHIBIDO** usar Eloquent aquí.
- **Services (`App\Services`):** Contienen toda la lógica de negocio, cálculos (como el algoritmo FIFO), integraciones con APIs de IA y transacciones de base de datos (`DB::beginTransaction()`).
- **Repositories (`App\Repositories`):** La única capa autorizada para consultar o modificar la base de datos usando Eloquent. Manejan el soft delete customizado (`active = 0`).

## 3. Plan de Refactorización e Implementación
### Fase 1: Consolidación
1. Refactorizar los controladores actuales para extraer la lógica a los Services y Repositories.
2. Estandarizar el soft-delete (`active = 0`, `user_updated`) en todos los repositorios.
3. Configurar **MinIO** en Docker para desarrollo de archivos locales simulando S3.

### Fase 2: Motor de Lotes y FIFO
1. **Compras:** Modificar `PurchaseService` para que al registrar una compra, se creen/actualicen los registros en la tabla `batches`.
2. **Ventas:** Modificar `SaleService`. Al vender un producto, el sistema debe consultar los lotes disponibles ordenados por `expiration_date` (ascendente) y descontar el stock en cascada hasta satisfacer la cantidad vendida.

### Fase 3: Background Jobs & Colas (Redis + Horizon)
1. Instalar y configurar Redis y Laravel Horizon.
2. Crear un comando (Schedule) que se ejecute a medianoche para:
   - Identificar lotes próximos a vencer.
   - Identificar productos por debajo del `min_stock`.
3. Despachar estas alertas como eventos que la PyME pueda visualizar en su dashboard.

### Fase 4: Inteligencia Artificial
1. Crear `DemandPredictionService`.
2. Implementar un Job encolado que recolecte los datos de ventas semanales, los formatee anonimizados y los envíe asíncronamente a un proveedor de IA de bajo costo.
3. Guardar la respuesta (Sugerencias de reabastecimiento) en una tabla de base de datos para lectura rápida.
