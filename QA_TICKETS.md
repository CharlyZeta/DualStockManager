# QA Report & Tickets - Version 0.2.0

Este documento detalla los problemas encontrados y observaciones tras el análisis de la versión 0.2.0.

## 🔴 Critical / Bloqueantes

### 1. Dependencia Faltante: html5-qrcode
*   **Versión**: 0.2.0
*   **Componente**: Escáner (Frontend)
*   **Descripción**: El archivo `assets/js/vendor/html5-qrcode.min.js` es actualmente un archivo "placeholder" (marcador de posición) y no contiene la librería real.
*   **Impacto**: El escáner NO funcionará. La consola mostrará advertencias y la UI mostrará error si se intenta activar.
*   **Solución**: Reemplazar el archivo con la versión minificada real de html5-qrcode (v2.3.8).

## 🟡 Major / Importantes

### 2. Limitación de Búsqueda de Productos
*   **Versión**: 0.2.0
*   **Componente**: UI de Transferencias
*   **Descripción**: La búsqueda de productos (`admin-transfer.js`) carga **toda** la lista de inventario (limitada a 100 productos por la API) y filtra en la interfaz (cliente).
*   **Impacto**: Si la tienda tiene más de 100 productos, los productos más antiguos/nuevos fuera de ese límite de 100 no aparecerán en la búsqueda y no podrán ser transferidos.
*   **Solución**: Implementar paginación o parámetro de búsqueda en el endpoint `GET /dsm/v1/inventory`.

### 3. Lógica de Sincronización Automática Desactivada
*   **Versión**: 0.2.0
*   **Componente**: Backend (SyncEngine)
*   **Descripción**: La función `reduce_custom_stock` no se está ejecutando o está vacía, lo que significa que los pedidos de WooCommerce **no descuentan automáticamente** stock de la tabla personalizada.
*   **Impacto**: Descuadre garantizado entre el stock real y el stock en el sistema DualStock hasta que se haga una sincronización manual o auditoría.
*   **Nota**: Esto fue documentado como "solicitud del usuario", pero se marca aquí como riesgo operativo.

## 🟢 Minor / Mejoras UX

### 4. Feedback Visual en Transferencia
*   **Versión**: 0.2.0
*   **Componente**: UI de Transferencias
*   **Descripción**: Al realizar una transferencia exitosa, el formulario no se resetea visualmente por completo (solo la cantidad), lo que podría llevar a transferencias duplicadas accidentales si el usuario presiona "Transfer" nuevamente creyendo que debe confirmar.
*   **Solución**: Limpiar la selección de producto o deshabilitar el botón temporalmente con un contador.

### 5. Validación de Stock Negativo
*   **Versión**: 0.2.0
*   **Componente**: API (`transfer_stock`)
*   **Descripción**: El sistema permite transferencias siempre que el origen tenga stock. No hay alertas si el stock destino es negativo antes de la transferencia (aunque esto es matemáticamente correcto, operativamente puede ser raro).
*   **Solución**: Agregar advertencias visuales si un depósito tiene stock negativo.
