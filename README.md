# DualStock Manager

**Version:** 0.2.1
**Description:** Omnichannel stock control plugin for WooCommerce. Manages multi-location inventory (Showroom, Deposito 1, Deposito 2) and synchronizes the total with WooCommerce.

## Core Concepts

1.  **Single Source of Truth**: The plugin's inventory table (`wp_dual_inventory`) is the master record for physical stock.
2.  **Locations**:
    *   **Showroom (Local)**: Stock available for immediate sale/pickup.
    *   **Deposito 1**: Warehouse 1.
    *   **Deposito 2**: Warehouse 2.
3.  **Discrepancy Model**: The plugin does *not* automatically deduct from specific locations when a WooCommerce order is placed. Instead, it lets WooCommerce reduce its own stock, creating a "Discrepancy" (Mismatch) vs the Plugin Total. The store manager then audits the shelf and uses the Plugin Dashboard to "Fix WC" (push actual physical count to WC) or manually adjusts the physical location count if the item was shipped.

## Features

### 1. Dashboard
*   **Overview**: View stock across all 3 locations + Total + WC Stock.
*   **Discrepancy Alert**: Rows are highlighted in red/orange if `Total Plugin Stock != WC Stock`.
*   **Fix Button**: A "Fix WC" button updates WooCommerce stock to match the Plugin's total immediately.
*   **Alpine.js**: Built with local Alpine.js for a fast, reactive interface.

### 2. Stock Transfer
*   **Transfer UI**: Dedicated page to move items between Deposito 1, Deposito 2, and Showroom.
*   **Validation**: Prevents transfers if source location has insufficient stock.
*   **Product Search**: Integrated search bar to find products by name or ID.
*   Located under **Dual Inventory > Transfer Stock**.

### 3. Scanner (Audit)
*   Integrates `html5-qrcode` for barcode scanning.
*   **Audit Mode**: Scan a product barcode to instantly retrieve its status across all locations.
*   **Real-time Lookup**: Uses the API to fetch product details immediately upon scan.

## Installation

1.  Upload the `dualstock-manager` folder to `/wp-content/plugins/`.
2.  Activate via WordPress Admin.
3.  Upon activation, the `wp_dual_inventory` table is created.

## Developer API

### REST Endpoints (`/wp-json/dsm/v1/`)

#### `GET /inventory`
Returns list of products with stock breakdown.
*   **Params**: `?search=query` (Optional: Filter by product name)
*   **Response**: `[{ product_id, stock_local, stock_deposito_1, stock_deposito_2, plugin_total, wc_stock, is_discrepancy }, ...]`

#### `POST /transfer`
Move stock between locations.
*   **Params**:
    *   `product_id` (int)
    *   `from` (string: `stock_local` | `stock_deposito_1` | `stock_deposito_2`)
    *   `to` (string: same as above)
    *   `qty` (int)

#### `POST /fix-wc`
Force WooCommerce stock to match Plugin total.
*   **Params**:
    *   `product_ids` (array of ints)

## Directory Structure
```
dualstock-manager/
├── assets/             # CSS, JS, and Vendor libs (Alpine, HTML5-QRCode)
├── includes/           # PHP Classes (Admin, API, Sync Engine)
├── templates/          # View files (Dashboard, Transfer)
└── dualstock-manager.php # Main entry point
```

## Changelog
*   **0.2.1**: Improved Search API, Fix html5-qrcode integration.
*   **0.2.0**: Added UI for transfers and Scanner.
*   **0.1.1**: Added Stock Transfer UI, Scanner integration, and Transfer API. Removed automatic order hooks.
*   **0.1.0**: Initial Release. Sync Engine, Custom DB Table, Dashboard.
