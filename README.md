# DualStock Manager

**Version:** 0.2.9
**Description:** Omnichannel stock control plugin for WooCommerce. Manages multi-location inventory (Showroom, Deposito 1, Deposito 2) and synchronizes the total with WooCommerce.

## Core Concepts

1.  **Single Source of Truth**: The plugin's inventory table (`wp_dual_inventory`) is the master record for physical stock.
2.  **Locations**:
    *   **Showroom (Local)**: Stock available for immediate sale/pickup.
    *   **Deposito 1**: Warehouse 1.
    *   **Deposito 2**: Warehouse 2.
3.  **Discrepancy Model**: The plugin tracks the difference between its total and WooCommerce's stock. It allows for manual "Push" synchronization to fix these discrepancies without automatic interference during order placement (user preference).

## Features

### 1. Dashboard & Spreadsheet Editing
*   **Live Editing**: Direct "Spreadsheet-style" editing of stock numbers in the dashboard. Changes are saved automatically via AJAX.
*   **Real-time Calculations**: Total stock and discrepancies are recalculated instantly as you type.
*   **Visual Feedback**:
    *   **Green/Red Indicators**: Instantly see if Plugin Total matches WooCommerce.
    *   **Saving Spinners**: Visual confirmation when data is being written to the DB.
*   **Alpine.js**: Reactive, lag-free interface.

### 2. Stock Transfer
*   **Inline Transfers**: "Transfer" button on every row to move stock between locations (e.g., Deposito 1 -> Showroom).
*   **Smart Validation**: Prevents negative stock; ensures source and destination are different.
*   **Logging**: Every transfer is recorded in the history log.

### 3. History & Logs (Audit Trail)
*   **Complete History**: A dedicated "Historial de Cambios" tab tracks EVERY change.
    *   **Who**: User who made the change.
    *   **What**: Product and strict before/after values.
    *   **When**: Exact timestamp.
*   **Revert Capability**: Undo any action (Edit or Transfer) with a single click.
*   **Daily Stats**: Quick summary of today's edits and transfers.

### 4. Scanner (Audit)
*   **Audit Mode**: Scan a product barcode to instantly retrieve its status.

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
