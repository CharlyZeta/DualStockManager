<div class="wrap dsm-wrap" x-data="dsmDashboard()">
    <h1 class="wp-heading-inline">DualStock Manager</h1>
    
    <div class="dsm-dashboard-widgets">
        <div class="dsm-card">
            <h2>Inventory Overview</h2>
            <p>Total Products: <span x-text="stats.total"></span></p>
            <p>Discrepancies: <span x-text="stats.discrepancies" :class="stats.discrepancies > 0 ? 'dsm-badge-error' : ''"></span></p>
        </div>
        
        <div class="dsm-card">
            <h2>Actions</h2>
            <button class="button button-primary" @click="fixAllDiscrepancies" x-show="stats.discrepancies > 0">Fix All WC Errors</button>
            <button class="button button-secondary" @click="fetchInventory">Refresh Data</button>
            <label>
                <input type="checkbox" x-model="showOnlyDiscrepancies"> Show Only Discrepancies
            </label>
        </div>
    </div>

    <hr>

    <h2>Stock List</h2>
    <div class="dsm-stock-table-container">
        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Showroom</th>
                    <th>Deposito 1</th>
                    <th>Deposito 2</th>
                    <th>Total Plugin</th>
                    <th>WC Stock</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="item in filteredItems" :key="item.product_id">
                    <tr :class="item.is_discrepancy ? 'dsm-row-warning' : ''">
                        <td x-text="'#' + item.product_id + ' - ' + item.post_title"></td>
                        <td x-text="item.stock_local"></td>
                        <td x-text="item.stock_deposito_1"></td>
                        <td x-text="item.stock_deposito_2"></td>
                        <td><strong x-text="item.plugin_total"></strong></td>
                        <td x-text="item.wc_stock"></td> 
                        <td>
                            <span x-show="item.is_discrepancy" class="dashicons dashicons-warning" style="color:red"></span>
                            <span x-show="!item.is_discrepancy" class="dashicons dashicons-yes" style="color:green"></span>
                        </td>
                        <td>
                            <button x-show="item.is_discrepancy" 
                                    class="button button-small" 
                                    @click="fixSingle(item)">
                                Fix WC
                            </button>
                        </td>
                    </tr>
                </template>
                <tr x-show="filteredItems.length === 0">
                    <td colspan="8">No products found.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
function dsmDashboard() {
    return {
        items: [],
        showOnlyDiscrepancies: false,
        stats: { total: 0, discrepancies: 0 },
        
        init() {
            this.fetchInventory();
        },

        fetchInventory() {
            fetch(dsm_params.root + 'inventory', {
                headers: { 'X-WP-Nonce': dsm_params.nonce }
            })
            .then(r => r.json())
            .then(data => {
                this.items = data;
                this.calculateStats();
            });
        },

        get filteredItems() {
            if (this.showOnlyDiscrepancies) {
                return this.items.filter(i => i.is_discrepancy);
            }
            return this.items;
        },

        calculateStats() {
            this.stats.total = this.items.length;
            this.stats.discrepancies = this.items.filter(i => i.is_discrepancy).length;
        },
        
        fixSingle(item) {
            this.fixList([item.product_id]);
        },
        
        fixAllDiscrepancies() {
            const ids = this.items.filter(i => i.is_discrepancy).map(i => i.product_id);
            if (ids.length > 0 && confirm('Are you sure you want to overwrite WC stock for ' + ids.length + ' items?')) {
                this.fixList(ids);
            }
        },
        
        fixList(ids) {
             const formData = new FormData();
             // API params usually JSON for REST...
             
             fetch(dsm_params.root + 'fix-wc', {
                method: 'POST',
                headers: { 
                    'X-WP-Nonce': dsm_params.nonce,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ product_ids: ids })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Optimistic update or refresh
                    this.fetchInventory();
                } else {
                    alert('Error updating stock');
                }
            });
        }
    }
}
</script>
