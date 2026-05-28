# Estrategia de Testing

## Filosofía

"Testear lo que duele si falla." En un sistema de farmacia, los errores en inventario, FIFO o precios causan pérdidas reales de dinero y medicamentos vencidos.

## Tipos de Tests

| Tipo | Directorio | Objetivo | Velocidad | Toca BD |
|------|-----------|----------|-----------|---------|
| **Feature** | `tests/Feature` | Flujo completo de API | Media | Sí |
| **Unit** | `tests/Unit` | Lógica aislada (FIFO, cálculos) | Alta | No |

## Stack

- **Framework**: Pest PHP (más legible, menos boilerplate que PHPUnit)
- **BD**: `RefreshDatabase` trait para tests aislados
- **HTTP**: `$this->getJson()`, `$this->postJson()` nativos de Laravel
- **Mocking**: `Http::fake()` para simular APIs externas (Gemini, IA)

## Prioridad de Tests

### Feature Tests (Alta prioridad)

| # | Test | ¿Qué valida? | Impacto si falla |
|---|------|-------------|------------------|
| 1 | Autenticación | Login exitoso, credenciales inválidas, token | Bloquea toda la API |
| 2 | CRUD Productos | Crear, leer, actualizar, eliminar producto | Datos maestros corruptos |
| 3 | Compra crea lote | Al registrar compra se genera/actualiza `batches` | Inventario descontrolado |
| 4 | Venta FIFO | Venta descuenta lote más próximo a vencer | Mermas por caducidad |
| 5 | Venta sin stock | Error si no hay stock suficiente en lotes | Sobreventa |
| 6 | Alerta stock bajo | Endpoint retorna productos bajo `min_stock` | Quiebres de stock |
| 7 | Alerta vencimiento | Endpoint retorna lotes próximos a vencer | Medicamentos vencidos |
| 8 | Permisos por rol | Cajero no accede a predicciones IA | Seguridad |
| 9 | Combo endpoints | `/api/v1/categories-combo` retorna formato correcto | Frontend roto |

### Unit Tests (Media prioridad)

| # | Test | ¿Qué valida? |
|---|------|-------------|
| 1 | Algoritmo FIFO | Selección correcta de lotes por fecha de vencimiento |
| 2 | Cálculo de subtotales | Precio × cantidad en detalles |
| 3 | Agrupación de ventas | `DemandPredictionService` agrupa correctamente por producto |
| 4 | Armado de prompt IA | El prompt contiene los campos requeridos |
| 5 | Builder de alertas | Jobs construyen lista correcta de alertas |

## Cómo ejecutar

```bash
# Todos los tests
./vendor/bin/sail artisan test

# Solo Feature
./vendor/bin/sail artisan test --testsuite=Feature

# Solo Unit
./vendor/bin/sail artisan test --testsuite=Unit

# Un test específico
./vendor/bin/sail artisan test --filter=VentaFifoTest
```

## Curva de Aprendizaje

| Concepto | Dificultad |
|----------|-----------|
| Test HTTP (->getJson, ->postJson) | Baja |
| Assertions básicas (assertOk, assertJsonPath) | Baja |
| RefreshDatabase trait | Baja |
| Factories para datos de prueba | Media |
| Http::fake() para mock de APIs | Media |
| TDD (escribir test antes que código) | Alta (no obligatorio) |

## Reglas

1. Cada test debe ser independiente (no depende de orden de ejecución)
2. Usar `RefreshDatabase` para que cada test empiece con BD limpia
3. Usar factories, no seeders, en tests
4. Los tests se ejecutan contra BD de testing (`.env.testing`)
5. Nunca testear en producción
