# Modelo de Negocio: ERP Farmacéutico Inteligente (PyMEs)

## 1. Visión General
El proyecto es un sistema de planificación de recursos empresariales (ERP) y Punto de Venta (POS) diseñado específicamente para pequeñas y medianas farmacias locales. A diferencia de un sistema de ventas genérico, este software entiende la naturaleza crítica de los medicamentos: fechas de caducidad, lotes, condiciones de almacenamiento y regulaciones.

## 2. Propuesta de Valor (El Problema que Resuelve)
Las farmacias pequeñas pierden miles de dólares al año por dos razones principales:
1. **Mermas por caducidad:** Medicamentos que se vencen en los estantes porque no hubo un control de "Lo primero que entra, es lo primero que sale" (FIFO) ni alertas tempranas.
2. **Quiebres de stock o Sobre-stock:** Quedarse sin productos estrella (perdiendo ventas) o comprar en exceso productos de baja rotación (dinero estancado).

El sistema resuelve esto integrando trazabilidad estricta (Lotes) e Inteligencia Artificial para sugerir qué, cuándo y cuánto comprar.

## 3. Módulos Core
- **Inventario y Trazabilidad:** Control estricto por Lotes (`batches`). Cada medicamento ingresa con un lote y fecha de vencimiento. 
- **Ventas (POS):** Sistema rápido de venta. El sistema descuenta automáticamente del lote más próximo a vencer (Algoritmo FIFO).
- **Compras y Proveedores:** Registro de ingresos, actualizando costos y creando nuevos lotes en el inventario.
- **Inteligencia y Alertas (AI & Analytics):**
  - Alertas automáticas de vencimiento (30, 60, 90 días).
  - Alertas de stock mínimo.
  - Predicciones de demanda impulsadas por IA basadas en estacionalidad y rotación histórica.

## 4. Monetización y Costos Operativos (Filosofía "Zero-Cost")
El sistema está diseñado para ser altamente rentable y de bajo costo de mantenimiento para poder ofrecerse como un SaaS accesible:
- **Hosting:** Diseñado para funcionar en VPS económicos ($5-$10/mes).
- **Almacenamiento:** Uso de capas gratuitas robustas (ej. Cloudflare R2 para imágenes).
- **IA:** Consultas optimizadas (procesamiento en background o lotes) para gastar fracciones de centavo en APIs como OpenAI/Claude, o modelos locales si el VPS lo permite.
