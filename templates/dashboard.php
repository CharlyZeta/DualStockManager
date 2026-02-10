<div class="wrap dsm-wrap" x-data="dsmDashboard()">
    <h1 class="wp-heading-inline">Gestor de Stock Dual</h1>
    
    <div class="dsm-dashboard-widgets">
        <div class="dsm-card">
            <h2>Resumen de Inventario</h2>
            <p>Total Productos: <span x-text="stats.total"></span></p>
            <p>Discrepancias: <span x-text="stats.discrepancies" :class="stats.discrepancies > 0 ? 'dsm-badge-error' : ''"></span></p>
        </div>
        
        <div class="dsm-card">
            <h2>Acciones</h2>
            <button class="button button-primary" @click="fixAllDiscrepancies" x-show="stats.discrepancies > 0">Corregir Errores en WC</button>
            <button class="button button-secondary" @click="fetchInventory">Actualizar Datos</button>
            <button class="button button-secondary" @click="syncProducts" :disabled="isSyncing" x-text="isSyncing ? 'Sincronizando...' : 'Sincronizar Productos de WC'"></button>
            <a href="<?php echo admin_url('admin.php?page=dsm-transfer'); ?>" class="button button-secondary">Transferir Stock</a>
            <button id="dsm-start-scan" class="button button-secondary">Iniciar Auditoría (Escanear)</button>
            <button id="dsm-stop-scan" class="button button-secondary" style="display:none;">Detener Escaneo</button>
            
            <div style="margin-top: 10px;">
                <label>
                    <input type="checkbox" x-model="showOnlyDiscrepancies"> Solo Mostrar Discrepancias
                </label>
            </div>
        </div>
        
        <!-- Scanner UI -->
        <div id="dsm-reader" style="width: 100%; max-width: 600px; margin-bottom: 20px; display:none;"></div>
        <div id="dsm-scan-result"></div>
    </div>

    <hr>

    <div class="dsm-toolbar" style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <!-- Category Filter -->
        <select x-model="selectedCategory" @change="fetchInventory" class="dsm-select">
            <option value="">Todas las Categorías</option>
            <template x-for="cat in categories" :key="cat.id">
                <option :value="cat.id" x-text="cat.name"></option>
            </template>
        </select>

        <!-- Search Input -->
        <input type="text" x-model="searchQuery" @keydown.enter="fetchInventory" placeholder="Buscar por Nombre, SKU o ID..." class="regular-text" style="height: 30px; min-width: 250px;">
        
        <button class="button" @click="fetchInventory">Buscar</button>
        <button class="button" @click="clearSearch" x-show="searchQuery.length > 0 || selectedCategory !== ''">Limpiar</button>
    </div>

    <h2>Listado de Stock</h2>
    <div class="dsm-stock-table-container">
        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <th style="width: 25%;">Producto</th>
                    <th>Showroom</th>
                    <th>Depósito 1</th>
                    <th>Depósito 2</th>
                    <th>Control</th>
                    <th>Stock WC</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="item in filteredItems" :key="item.product_id">
                    <tr :class="isItemDiscrepancy(item) ? 'dsm-row-warning' : ''">
                        <td x-text="'#' + item.product_id + ' - ' + item.post_title"></td>
                        
                        <!-- Stock Showroom -->
                        <td>
                            <input type="number" x-model.number="item.stock_local" @change="saveStock(item)" class="small-text dsm-live-edit" min="0">
                        </td>
                        
                        <!-- Stock Depo 1 -->
                        <td>
                            <input type="number" x-model.number="item.stock_deposito_1" @change="saveStock(item)" class="small-text dsm-live-edit" min="0">
                        </td>
                        
                        <!-- Stock Depo 2 -->
                        <td>
                            <input type="number" x-model.number="item.stock_deposito_2" @change="saveStock(item)" class="small-text dsm-live-edit" min="0">
                        </td>
                        
                        <!-- Calculated Total (Reactive) -->
                        <td>
                            <strong x-text="calculateTotal(item)"></strong>
                        </td>
                        
                        <td x-text="item.wc_stock"></td> 
                        
                        <td>
                            <span x-show="isItemDiscrepancy(item)" class="dashicons dashicons-warning" style="color:red" title="Discrepancia: Total Plugin no coincide con WC"></span>
                            <span x-show="!isItemDiscrepancy(item)" class="dashicons dashicons-yes" style="color:green" title="Correcto"></span>
                            
                            <!-- Saving Indicator -->
                            <span x-show="item.isSaving" class="spinner is-active" style="float:none; margin:0;"></span>
                        </td>
                        
                        <td>
                            <!-- Fix Discrepancy Action -->
                            <div style="display: flex; gap: 5px;">
                                <div x-show="isItemDiscrepancy(item)">
                                    <button class="button button-small" @click="fixSingle(item)" title="Corregir Stock en WC">Corregir WC</button>
                                </div>
                                <button class="button button-small" @click="openTransferModal(item)" title="Transferir Stock">
                                    <span class="dashicons dashicons-randomize" style="margin-top:2px;"></span>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
                <tr x-show="items.length === 0">
                    <td colspan="8">
                        No se encontraron productos. <span x-show="!searchQuery && !selectedCategory">Prueba hacer clic en "Sincronizar Productos de WC" para importar tu catálogo.</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Transfer Modal -->
    <div class="dsm-modal-overlay" 
         x-show="showTransferModal" 
         x-transition.opacity
         style="display:none;">
        
        <div class="dsm-modal-content" 
             @click.away="closeTransferModal"
             x-show="showTransferModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-90"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-90">
            
            <div class="dsm-modal-header">
                <h2>Transferir Stock</h2>
                <button type="button" @click="closeTransferModal" class="dsm-close-btn">&times;</button>
            </div>
            
            <div class="dsm-modal-body">
                <p>Producto: <strong x-text="transferForm.productName"></strong></p>
                
                <div class="dsm-form-group">
                    <label>Desde:</label>
                    <select x-model="transferForm.from" class="widefat">
                        <option value="stock_local">Showroom (<span x-text="getStockValue(transferForm.from)"></span>)</option>
                        <option value="stock_deposito_1">Depósito 1 (<span x-text="getStockValue('stock_deposito_1')"></span>)</option>
                        <option value="stock_deposito_2">Depósito 2 (<span x-text="getStockValue('stock_deposito_2')"></span>)</option>
                    </select>
                </div>

                <div class="dsm-form-group">
                    <label>Hacia:</label>
                    <select x-model="transferForm.to" class="widefat">
                        <option value="stock_local" x-show="transferForm.from !== 'stock_local'">Showroom</option>
                        <option value="stock_deposito_1" x-show="transferForm.from !== 'stock_deposito_1'">Depósito 1</option>
                        <option value="stock_deposito_2" x-show="transferForm.from !== 'stock_deposito_2'">Depósito 2</option>
                    </select>
                </div>

                <div class="dsm-form-group">
                    <label>Cantidad:</label>
                    <input type="number" x-model.number="transferForm.qty" class="widefat" min="1" :max="getMaxTransfer()" placeholder="Cantidad" style="font-size: 1.2em; padding: 5px;">
                    <p class="description" style="color:red;" x-show="transferForm.qty > getMaxTransfer()">Stock insuficiente en origen.</p>
                    <p class="description" style="color:red;" x-show="transferForm.from === transferForm.to">Origen y destino deben ser diferentes.</p>
                </div>
            </div>

            <div class="dsm-modal-footer">
                <button class="button button-large" @click="closeTransferModal">Cancelar</button>
                <button class="button button-primary button-large" @click="submitTransfer" :disabled="!isValidTransfer() || isTransferring">
                    <span x-show="!isTransferring">Transferir Stock</span>
                    <span x-show="isTransferring">Procesando...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Modal Styles */
    .dsm-modal-overlay {
        position: fixed;
        top: 0; 
        left: 0; 
        width: 100%; 
        height: 100%; 
        background: rgba(0, 0, 0, 0.6); 
        backdrop-filter: blur(2px);
        z-index: 10000; 
        display: flex; 
        align-items: center; 
        justify-content: center;
    }

    .dsm-modal-content {
        background: #fff;
        width: 450px;
        max-width: 90%;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: dsm-modal-pop 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .dsm-modal-header {
        padding: 15px 20px;
        background: #f0f0f1;
        border-bottom: 1px solid #c3c4c7;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .dsm-modal-header h2 {
        margin: 0;
        font-size: 1.2rem;
        color: #1d2327;
    }
    
    .dsm-close-btn {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #646970;
        line-height: 1;
    }
    
    .dsm-close-btn:hover {
        color: #d63638;
    }

    .dsm-modal-body {
        padding: 20px;
    }
    
    .dsm-form-group {
        margin-bottom: 20px;
    }
    
    .dsm-form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 5px;
        color: #2c3338;
    }
    
    .dsm-modal-footer {
        padding: 15px 20px;
        background: #f6f7f7;
        border-top: 1px solid #c3c4c7;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    @keyframes dsm-modal-pop {
        0% { transform: scale(0.9); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }

    /* Spreadsheet-like input styling */
    .dsm-stock-table-container input[type=number].dsm-live-edit {
        background: transparent;
        border: 1px solid transparent;
        width: 100%;
        max-width: 80px;
        padding: 5px;
        transition: all 0.2s;
    }
    .dsm-stock-table-container input[type=number].dsm-live-edit:hover,
    .dsm-stock-table-container input[type=number].dsm-live-edit:focus {
        background: #fff;
        border-color: #8c8f94;
        box-shadow: 0 0 0 1px #8c8f94;
    }
    
    /* Vertical Alignment Fix */
    .dsm-stock-table-container table td, 
    .dsm-stock-table-container table th {
        vertical-align: middle !important;
    }
</style>

<script>
function dsmDashboard() {
    return {
        items: [],
        categories: (typeof dsm_params !== 'undefined' && dsm_params.categories) ? dsm_params.categories : [],
        showOnlyDiscrepancies: false,
        searchQuery: '',
        selectedCategory: '',
        isSyncing: false,
        stats: { total: 0, discrepancies: 0 },
        
        // Transfer Modal State
        showTransferModal: false,
        isTransferring: false,
        transferForm: {
            productId: null,
            productName: '',
            from: 'stock_local',
            to: 'stock_deposito_1',
            qty: 1,
            // Helper to access current stock of selected item
            itemRef: null 
        },
        
        init() {
            if (typeof dsm_params === 'undefined') {
                console.error("DSM Error: dsm_params is not defined!");
                alert("Error crítico: No se pudo cargar la configuración del plugin (dsm_params missing). Revisa la consola.");
                return;
            }
            console.log("DSM Init", dsm_params);
            this.fetchInventory();
        },

        fetchInventory() {
            let url = dsm_params.root + 'inventory';
            let params = new URLSearchParams();

            if (this.searchQuery) {
                params.append('search', this.searchQuery);
            }
            if (this.selectedCategory) {
                params.append('category', this.selectedCategory);
            }

            if (params.toString()) {
                url += '?' + params.toString();
            }

            fetch(url, {
                headers: { 'X-WP-Nonce': dsm_params.nonce }
            })
            .then(r => r.json())
            .then(data => {
                this.items = data.map(i => ({
                    ...i,
                    stock_local: parseInt(i.stock_local) || 0,
                    stock_deposito_1: parseInt(i.stock_deposito_1) || 0,
                    stock_deposito_2: parseInt(i.stock_deposito_2) || 0,
                    wc_stock: parseInt(i.wc_stock) || 0,
                    isSaving: false 
                }));
                this.calculateStats();
            });
        },
        
        clearSearch() {
            this.searchQuery = '';
            this.selectedCategory = '';
            this.fetchInventory();
        },

        syncProducts() {
            this.isSyncing = true;
            fetch(dsm_params.root + 'sync-products', {
                method: 'POST',
                headers: { 'X-WP-Nonce': dsm_params.nonce }
            })
            .then(r => r.json())
            .then(data => {
                this.isSyncing = false;
                if (data.success) {
                    alert(data.message);
                    this.fetchInventory();
                } else {
                    alert('Falló la sincronización.');
                }
            })
            .catch(err => {
                this.isSyncing = false;
                alert('Error conectando al servidor.');
            });
        },

        calculateTotal(item) {
            return (parseInt(item.stock_local) || 0) + 
                   (parseInt(item.stock_deposito_1) || 0) + 
                   (parseInt(item.stock_deposito_2) || 0);
        },

        isItemDiscrepancy(item) {
            const total = this.calculateTotal(item);
            return total !== item.wc_stock;
        },

        get filteredItems() {
            let list = this.items;
            if (this.showOnlyDiscrepancies) {
                list = list.filter(i => this.isItemDiscrepancy(i));
            }
            return list;
        },

        calculateStats() {
            this.stats.total = this.items.length;
            this.stats.discrepancies = this.items.filter(i => this.isItemDiscrepancy(i)).length;
        },
        
        saveStock(item) {
            item.isSaving = true;
            this.calculateStats(); 
            
            fetch(dsm_params.root + 'inventory/update', {
                method: 'POST',
                headers: { 
                    'X-WP-Nonce': dsm_params.nonce,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: item.product_id,
                    stock_local: item.stock_local,
                    stock_deposito_1: item.stock_deposito_1,
                    stock_deposito_2: item.stock_deposito_2
                })
            })
            .then(r => r.json())
            .then(data => {
                item.isSaving = false;
                if (data.success) {
                    // Success
                } else {
                    console.error('DSM Save Error:', data);
                    const msg = data.message || 'Error al guardar stock. Compruebe su conexión.';
                    if (data.code === 'rest_cookie_invalid_nonce') {
                        alert('La sesión ha expirado. Por favor, recarga la página.');
                    } else {
                        alert(msg);
                    }
                }
            })
            .catch(err => {
                 item.isSaving = false;
                 console.error(err);
                 alert('Error de conexión al guardar.');
            });
        },
        
        fixSingle(item) {
            this.fixList([item.product_id]);
        },
        
        fixAllDiscrepancies() {
            const ids = this.items.filter(i => this.isItemDiscrepancy(i)).map(i => i.product_id);
            if (ids.length > 0 && confirm('¿Estás seguro de que quieres sobrescribir el stock de WC para ' + ids.length + ' productos?')) {
                this.fixList(ids);
            }
        },
        
        fixList(ids) {
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
                    this.fetchInventory();
                } else {
                    alert('Error actualizando stock');
                }
            });
        },

        // --- Transfer Modal Functions ---
        
        openTransferModal(item) {
            this.transferForm.productId = item.product_id;
            this.transferForm.productName = item.post_title;
            this.transferForm.itemRef = item; // Keep reference to update UI immediately if needed
            this.transferForm.qty = 1;
            this.transferForm.from = 'stock_local';
            this.transferForm.to = 'stock_deposito_1';
            this.showTransferModal = true;
        },
        
        closeTransferModal() {
            this.showTransferModal = false;
        },
        
        getStockValue(location) {
            if (!this.transferForm.itemRef) return 0;
            return this.transferForm.itemRef[location] || 0;
        },
        
        getMaxTransfer() {
             return this.getStockValue(this.transferForm.from);
        },
        
        isValidTransfer() {
            if (this.transferForm.from === this.transferForm.to) return false;
            if (this.transferForm.qty <= 0) return false;
            if (this.transferForm.qty > this.getMaxTransfer()) return false;
            return true;
        },
        
        submitTransfer() {
            if (!this.isValidTransfer()) return;
            
            this.isTransferring = true;
            
            fetch(dsm_params.root + 'transfer', {
                method: 'POST',
                headers: { 
                    'X-WP-Nonce': dsm_params.nonce,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: this.transferForm.productId,
                    from: this.transferForm.from,
                    to: this.transferForm.to,
                    qty: this.transferForm.qty
                })
            })
            .then(r => r.json())
            .then(data => {
                this.isTransferring = false;
                if (data.success) {
                    // Update local model to reflect changes immediately
                    const qty = parseInt(this.transferForm.qty);
                    this.transferForm.itemRef[this.transferForm.from] -= qty;
                    this.transferForm.itemRef[this.transferForm.to] += qty;
                    
                    this.closeTransferModal();
                    // Optional: re-fetch to ensure sync
                    // this.fetchInventory(); 
                } else {
                    alert('Error en transferencia: ' + (data.message || 'Desconocido'));
                }
            })
            .catch(err => {
                this.isTransferring = false;
                console.error(err);
                alert('Error de conexión.');
            });
        }
    }
}
</script>
