# DualStock Manager

**Version:** 0.3.14
**Description:** Omnichannel stock control plugin for WooCommerce. Manages multi-location inventory (Showroom, Deposito 1, Deposito 2) and synchronizes the total with WooCommerce.

## Core Concepts

*   **Dual Inventory**: The plugin maintains its own inventory values for three locations:
    1.  **Local/Showroom** (Customizable Name)
    2.  **Depósito 1** (Customizable Name)
    3.  **Depósito 2** (Customizable Name)
*   **Total Plugin**: The sum of these three locations.
*   **Fix WC**: A one-way synchronization that updates the **WooCommerce Stock** to match the **Total Plugin** value.
*   **Logs**: Every change (edit, transfer, sync) is recorded in a separate database table (`wp_dual_inventory_logs`) for audit purposes.

## Features

### 1. Unified Dashboard
*   View all products with their stock broken down by location.
*   **Spreadsheet Mode**: Click any stock number to edit it directly. Changes auto-save.
*   **Real-time Status**: Green checkmark if Plugin Total matches WooCommerce. Red warning icon if there is a discrepancy.
*   **Filters**: Filter by Category or Search by Name/ID.

### 2. Stock Transfer
*   Move stock between locations (e.g., from Showroom to Depósito 1) without affecting the total count.
*   Click the "Transfer" button (arrows icon) on any product row.

### 3. History & Logs
*   **Audit Trail**: View a history of all changes for every product.
*   **Revert**: Undo any specific action to restore previous stock values.
*   **Visual Indicators**: Color-coded badges for Edits vs Transfers.

### 4. Scanner (Audit)
*   **Audit Mode**: Scan a product barcode to instantly retrieve its status.

### 5. Print & Export
*   **Print**: Optimizado para A4. Imprime el listado actual (con filtros aplicados) destacando discrepancias.
*   **Excel (.xls)**: Exporta el listado visible a un archivo Excel con formato básico (colores para discrepancias).

### 6. Settings & Cleanup
*   **Customize Labels**: Change the display names for your warehouses (e.g., "Main Store", "Backroom", "External").
*   **Clean Uninstall**: Option to automatically delete all plugin data (tables and settings) upon uninstallation.
*   Located under **Dual Inventory > Settings**.

## Installation

1.  Upload the `dualstock-manager` folder to `/wp-content/plugins/`.
2.  Activate via WordPress Admin.
3.  Tables `wp_dual_inventory` and `wp_dual_inventory_logs` are created automatically.

## Developer API

### REST Endpoints (`/wp-json/dsm/v1/`)

#### `GET /inventory`
Returns list of products with stock breakdown.

#### `POST /inventory/update` (Spreadsheet Save)
Updates stock for a single product.
*   **Params**: `product_id`, `stock_local`, `stock_deposito_1`, `stock_deposito_2`
*   **Returns**: Success status and new `log_id`.

#### `POST /transfer`
Move stock between locations.
*   **Params**: `product_id`, `from`, `to`, `qty`

#### `GET /logs`
Retrieve recent activity logs.

#### `POST /logs/revert`
Undo a specific transaction by ID.

## Directory Structure
```
dualstock-manager/
├── assets/             # CSS (admin-dashboard.css), JS, and Vendor libs
├── includes/           # PHP Classes (Admin, API, Sync Engine)
├── templates/          # View files (Dashboard, Transfer)
└── dualstock-manager.php # Main entry point
```

## Changelog
*   **0.2.9**: System Logs & Revert (Backend/Frontend), UI Polish, Bug Fixes.
*   **0.2.8**: Spreadsheet Editing, Frontend Shortcode.
*   **0.2.0**: Transfer UI, Scanner.
*   **0.1.0**: Initial Release.
