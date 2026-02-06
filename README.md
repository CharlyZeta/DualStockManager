# DualStock Manager

**DualStock Manager** es una solución integral para el control de stock omnicanal en WordPress/WooCommerce. Permite la gestión de inventario distribuido entre múltiples ubicaciones (Showroom, Depósito 1, Depósito 2) y ofrece herramientas para la sincronización y transferencia de stock.

## Descripción

El plugin intercepta los pedidos de WooCommerce y gestiona el stock desde una tabla de inventario personalizada, permitiendo mantener un registro preciso de la disponibilidad de productos en diferentes ubicaciones físicas. Además, proporciona una API REST para integraciones externas y gestión remota.

## Componentes Principales

El plugin está estructurado en varios componentes clave, cada uno responsable de una parte específica de la funcionalidad:

### 1. `DualStock_Manager` (Core)
*   **Archivo**: `includes/class-dualstock-manager.php`
*   **Función**: Es la clase principal que orquesta la carga de dependencias, la definición de la internacionalización y el registro de los hooks (ganchos) tanto para el área de administración como para el frontend.

### 2. `DSM_Admin` (Administración)
*   **Archivo**: `includes/class-dsm-admin.php`
*   **Función**: Gestiona la interfaz de usuario en el panel de control de WordPress.
*   **Características**:
    *   Registra el menú "Dual Inventory".
    *   Carga los scripts y estilos necesarios para el dashboard (`assets/js/app.js`, `assets/css/style.css`).
    *   Provee la vista para el dashboard principal y la página de "Transfer Stock".

### 3. `DSM_API` (API REST)
*   **Archivo**: `includes/class-dsm-api.php`
*   **Función**: Expone endpoints REST para consultar y manipular el inventario externamente.
*   **Endpoints**:
    *   `GET /dsm/v1/inventory`: Obtiene la lista de inventario con detalles de stock por ubicación.
    *   `POST /dsm/v1/transfer`: Ejecuta una transferencia de stock entre ubicaciones. Requiere los parámetros `product_id`, `from`, `to`, y `qty`. Verifica permisos de administrador (`manage_options`).

### 4. `DSM_Sync_Engine` (Motor de Sincronización)
*   **Archivo**: `includes/class-dsm-sync-engine.php`
*   **Función**: Contiene la lógica de negocio para el manejo del stock.
*   **Lógica**:
    *   **Reducción de Stock**: Al realizarse un pedido (`woocommerce_reduce_order_stock`), reduce el stock de la ubicación local (`stock_local`).
    *   **Restauración de Stock**: Al cancelarse un pedido, restaura el stock a la ubicación local.
    *   **Sincronización WC**: Mantiene sincronizado el stock total de WooCommerce sumando las cantidades de todas las ubicaciones (`stock_local` + `stock_deposito_1` + `stock_deposito_2`).
    *   **Transferencias**: Gestiona el movimiento de unidades entre ubicaciones de manera transaccional (ACID) para evitar inconsistencias.

## Instalación

1.  Subir la carpeta `dualstock-manager` al directorio `/wp-content/plugins/`.
2.  Activar el plugin desde el menú 'Plugins' en WordPress.
3.  La instalación creará automáticamente las tablas de base de datos necesarias (manejado por `DSM_Activator`).

## Changelog

Consulte el archivo `changelog.txt` para ver el historial de cambios y versiones.
