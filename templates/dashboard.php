<div class="wrap dsm-wrap" x-data="dsmDashboard()">
    <h1 class="wp-heading-inline">Gestor de Stock Dual</h1>
    <?php
    $label_local = get_option( 'dsm_label_local', 'Showroom' );
    $label_dep1  = get_option( 'dsm_label_dep1', 'Depósito 1' );
    $label_dep2  = get_option( 'dsm_label_dep2', 'Depósito 2' );
    ?>
    
    <!-- Print Header (Visible only in Print Mode) -->
    <div class="dsm-print-header">
        <h1>Inventario Dual Stock</h1>
        <div class="dsm-print-summary">
            <p><strong>Total Productos:</strong> <span x-text="stats.total"></span></p>
            <p><strong>Discrepancias:</strong> <span x-text="stats.discrepancies"></span></p>
            <p><strong>Categoría:</strong> <span x-text="selectedCategory ? (categories.find(c => c.id == selectedCategory)?.name || 'Seleccionada') : 'Todas'"></span></p>
        </div>
    </div>
    
    <div class="dsm-dashboard-widgets">
        <div class="dsm-card">
            <h2>Resumen de Inventario</h2>
            <p>Total Productos: <span x-text="stats.total"></span></p>
            <p>Discrepancias: <span x-text="stats.discrepancies" :class="stats.discrepancies > 0 ? 'dsm-badge-error' : ''"></span></p>
        </div>
        
        <div class="dsm-actions-card">
            <h3>Resumen del Día</h3>
            <div style="display: flex; gap: 15px; margin-bottom: 10px;">
                <div class="dsm-stat-box">
                    <span class="badg" style="background:#2271b1; color:white; padding:2px 6px; border-radius:4px;" x-text="dailyStats.edit">0</span> Ediciones
                </div>
                <div class="dsm-stat-box">
                    <span class="badg" style="background:#dba617; color:white; padding:2px 6px; border-radius:4px;" x-text="dailyStats.transfer">0</span> Transferencias
                </div>
            </div>
            
            <div class="dsm-tab-nav" style="border-bottom: 1px solid #ccc; margin-bottom: 15px;">
                <button class="button" :class="{ 'button-primary': activeTab === 'inventory' }" @click="switchTab('inventory')">Inventario</button>
                <button class="button" :class="{ 'button-primary': activeTab === 'history' }" @click="switchTab('history')">Historial de Cambios</button>
            </div>

            <div x-show="activeTab === 'inventory'">
                <button class="button button-secondary" @click="fixWCDiscrepancy" :disabled="loading">
                   Corregir Errores en WC
                </button>
                <button class="button button-secondary" @click="syncProducts" :disabled="loading" style="margin-left: 5px;">
                   Sincronizar Productos de WC
                </button>
                <button class="button button-secondary" @click="startScanner" style="margin-left: 5px;">
                    Iniciar Auditoría (Escáner)
                </button>

                
                <div style="margin-top: 10px;">
                    <label>
                        <input type="checkbox" x-model="showDiscrepanciesOnly"> Solo Mostrar Discrepancias
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Scanner UI -->
        <div id="dsm-reader" style="width: 100%; max-width: 600px; margin-bottom: 20px; display:none;"></div>
        <div id="dsm-scan-result"></div>
    </div>

    <hr>

    <!-- Inventory Tab -->
    <div x-show="activeTab === 'inventory'">
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
            
            <div style="flex-grow: 1;"></div> <!-- Spacer -->
            
            <button class="button" @click="printInventory" title="Imprimir Listado Actual">
                <span class="dashicons dashicons-printer" style="margin-top:3px;"></span> Imprimir
            </button>
            <button class="button" @click="exportExcel" title="Descargar como Excel">
                <span class="dashicons dashicons-media-spreadsheet" style="margin-top:3px;"></span> Exportar Excel
            </button>
        </div>

        <h2>Listado de Stock</h2>
        
        <div class="dsm-stock-table-container">
            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <th class="dsm-col-product">Producto</th>
                        <th class="dsm-col-stock-local"><?php echo esc_html( $label_local ); ?></th>
                        <th class="dsm-col-stock-dep1"><?php echo esc_html( $label_dep1 ); ?></th>
                        <th class="dsm-col-stock-dep2"><?php echo esc_html( $label_dep2 ); ?></th>
                        <th class="dsm-col-total">Control</th>
                        <th class="dsm-col-wc">Stock WC</th>
                        <th class="dsm-col-status">Estado</th>
                        <th class="dsm-col-actions">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="item in filteredItems" :key="item.product_id">
                        <tr :class="isItemDiscrepancy(item) ? 'dsm-row-warning' : ''">
                            <td class="dsm-col-product" x-text="'#' + item.product_id + ' - ' + item.post_title"></td>
                            
                            <!-- Stock Showroom -->
                            <td class="dsm-col-stock-local">
                                <input type="number" x-model.number="item.stock_local" @change="saveStock(item)" class="small-text dsm-live-edit" min="0">
                            </td>
                            
                            <!-- Stock Depo 1 -->
                            <td class="dsm-col-stock-dep1">
                                <input type="number" x-model.number="item.stock_deposito_1" @change="saveStock(item)" class="small-text dsm-live-edit" min="0">
                            </td>
                            
                            <!-- Stock Depo 2 -->
                            <td class="dsm-col-stock-dep2">
                                <input type="number" x-model.number="item.stock_deposito_2" @change="saveStock(item)" class="small-text dsm-live-edit" min="0">
                            </td>
                            
                            <!-- Calculated Total (Reactive) -->
                            <td class="dsm-col-total">
                                <strong x-text="calculateTotal(item)"></strong>
                            </td>
                            
                            <td class="dsm-col-wc" x-text="item.wc_stock"></td> 
                            
                            <td class="dsm-col-status">
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
    </div>

    <!-- History Tab -->
    <div x-show="activeTab === 'history'">
        <div class="tablenav top">
            <div class="alignleft actions">
                <button class="button" @click="fetchLogs">Actualizar Historial</button>
            </div>
            <div class="tablenav-pages">
                <span class="displaying-num" x-text="historyLogs.length + ' registros recientes'"></span>
                <span class="spinner is-active" x-show="loading"></span>
            </div>
            <br class="clear">
        </div>

        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <th style="width: 12%;">Fecha</th>
                    <th style="width: 10%;">Usuario</th>
                    <th style="width: 25%;">Producto</th>
                    <th style="width: 10%;">Acción</th>
                    <th style="width: 35%;">Detalles</th>
                    <th style="width: 8%;">Revertir</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="log in historyLogs" :key="log.id">
                    <tr>
                        <td x-text="log.date_created"></td>
                        <td x-text="log.user_name || 'Sistema'"></td>
                        <td x-text="log.product_name || ('ID: ' + log.product_id)"></td>
                        <td>
                            <span class="badg" 
                                  :style="log.action_type === 'edit' ? 'background:#2271b1; color:white; padding:2px 6px; border-radius:4px;' : 
                                         (log.action_type === 'transfer' ? 'background:#dba617; color:white; padding:2px 6px; border-radius:4px;' : 
                                         'background:#666; color:white; padding:2px 6px; border-radius:4px;')"
                                  x-text="log.action_type.toUpperCase()">
                            </span>
                        </td>
                        <td x-text="log.details"></td>
                        <td style="text-align:center;">
                            <button class="button button-small" @click="revertLog(log.id)" title="Deshacer este cambio">
                                <span class="dashicons dashicons-undo" style="margin-top:2px;"></span>
                            </button>
                        </td>
                    </tr>
                </template>
                <tr x-show="historyLogs.length === 0">
                    <td colspan="6">No hay registros de cambios recientes.</td>
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
                        <option value="stock_local"><?php echo esc_html( $label_local ); ?> (<span x-text="getStockValue(transferForm.from)"></span>)</option>
                        <option value="stock_deposito_1"><?php echo esc_html( $label_dep1 ); ?> (<span x-text="getStockValue('stock_deposito_1')"></span>)</option>
                        <option value="stock_deposito_2"><?php echo esc_html( $label_dep2 ); ?> (<span x-text="getStockValue('stock_deposito_2')"></span>)</option>
                    </select>
                </div>

                <div class="dsm-form-group">
                    <label>Hacia:</label>
                    <select x-model="transferForm.to" class="widefat">
                        <option value="stock_local" x-show="transferForm.from !== 'stock_local'"><?php echo esc_html( $label_local ); ?></option>
                        <option value="stock_deposito_1" x-show="transferForm.from !== 'stock_deposito_1'"><?php echo esc_html( $label_dep1 ); ?></option>
                        <option value="stock_deposito_2" x-show="transferForm.from !== 'stock_deposito_2'"><?php echo esc_html( $label_dep2 ); ?></option>
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
    
    <div class="dsm-print-footer">
        <p>Generado el <span x-text="new Date().toLocaleDateString() + ' ' + new Date().toLocaleTimeString()"></span> por DualStock Manager</p>
    </div>
</div>



<script>
function dsmDashboard() {
    return {
        activeTab: 'inventory',
        items: [],
        categories: [], // Initialized empty, populated in init()
        showDiscrepanciesOnly: false,
        searchQuery: '',
        selectedCategory: '',
        isSyncing: false,
        loading: false,
        errorMsg: '',
        stats: { total: 0, discrepancies: 0 },
        
        // Renamed to avoid potential collisions and clarify scope
        dailyStats: { edit: 0, transfer: 0 },
        historyLogs: [],
        
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
                this.errorMsg = "dsm_params undefined";
                alert("Error crítico: No se pudo cargar la configuración del plugin (dsm_params missing). Revisa la consola.");
                return;
            }
            // Safely load categories after dsm_params check
            this.categories = dsm_params.categories || [];

            console.log("DSM Init", dsm_params);
            this.fetchInventory();
            this.fetchLogSummary();
        },

        switchTab(tab) {
            this.activeTab = tab;
            if (tab === 'history') {
                this.fetchLogs();
            }
        },
        
        fetchLogSummary() {
            fetch(dsm_params.root + 'logs/summary', {
                headers: { 'X-WP-Nonce': dsm_params.nonce }
            })
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    this.dailyStats = data.data; // Use renamed property
                }
            })
            .catch(e => console.error(e));
        },
        
        fetchLogs() {
            this.loading = true;
            fetch(dsm_params.root + 'logs', {
                headers: { 'X-WP-Nonce': dsm_params.nonce }
            })
            .then(r => r.json())
            .then(data => {
                this.loading = false;
                if(data.success) {
                    this.historyLogs = [...data.data];
                    console.log("DSM Logs Loaded:", this.historyLogs.length);
                } else {
                    console.error("DSM Log Error:", data.message);
                }
            })
            .catch(e => {
                this.loading = false; 
                console.error(e);
            });
        },
        
        revertLog(logId) {
            if(!confirm('¿Estás seguro de revertir esta acción?')) return;
            
            fetch(dsm_params.root + 'logs/revert', {
                method: 'POST',
                headers: { 
                    'X-WP-Nonce': dsm_params.nonce,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ log_id: logId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Acción revertida exitosamente.');
                    this.fetchLogs();
                    this.fetchLogSummary();
                    this.fetchInventory(); 
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(e => alert('Error revert: ' + e));
        },
        
        fetchInventory() {
            let url = dsm_params.root + 'inventory';
            let params = new URLSearchParams();

            if (this.searchQuery) params.append('search', this.searchQuery);
            if (this.selectedCategory) params.append('category', this.selectedCategory);
            if (params.toString()) url += '?' + params.toString();

            console.log("DSM: Fetching inventory from " + url);
            this.loading = true; 
            this.errorMsg = '';

            fetch(url, { headers: { 'X-WP-Nonce': dsm_params.nonce } })
            .then(r => r.json())
            .then(data => {
                this.loading = false;
                console.log("DSM: Inventory Data", data);
                
                if (!Array.isArray(data)) {
                    this.errorMsg = "API Response invalid (not array)";
                    console.error("DSM Error: Expected array but got", data);
                    this.items = [];
                    return;
                }

                try {
                    this.items = data.map(i => ({
                        ...i,
                        stock_local: parseInt(i.stock_local) || 0,
                        stock_deposito_1: parseInt(i.stock_deposito_1) || 0,
                        stock_deposito_2: parseInt(i.stock_deposito_2) || 0,
                        wc_stock: parseInt(i.wc_stock) || 0,
                        isSaving: false 
                    }));
                    this.calculateStats();
                } catch (e) {
                    console.error("Mapping Error", e);
                    this.errorMsg = "Error mapping data: " + e.message;
                }
            })
            .catch(err => {
                this.loading = false;
                this.errorMsg = "Fetch Error: " + err.message;
                console.error("DSM Fetch Error:", err);
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
            if (!item) return 0;
            try {
                return (parseInt(item.stock_local) || 0) + 
                       (parseInt(item.stock_deposito_1) || 0) + 
                       (parseInt(item.stock_deposito_2) || 0);
            } catch (e) {
                console.error("Calc Total Error", e);
                return 0;
            }
        },

        isItemDiscrepancy(item) {
            if (!item) return false;
            try {
                const total = this.calculateTotal(item);
                return total !== item.wc_stock;
            } catch (e) {
                return false;
            }
        },

        get filteredItems() {
            if (!this.items) return [];
            let list = this.items;
            if (this.showDiscrepanciesOnly) {
                list = list.filter(i => this.isItemDiscrepancy(i));
            }
            return list;
        },

        calculateStats() {
            if (!this.items) {
                 this.stats = { total: 0, discrepancies: 0 };
                 return;
            }
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
                    this.fetchLogSummary();
                    if (this.activeTab === 'history') {
                        this.fetchLogs();
                    }
                } else {
                    console.error('DSM Save Error:', data);
                    if (data.code === 'rest_cookie_invalid_nonce') {
                        alert('La sesión ha expirado. Por favor, recarga la página.');
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
                    this.transferForm.itemRef[this.transferForm.to] += qty;
                    this.transferForm.itemRef[this.transferForm.from] -= qty; // Deduct from source
                    
                    this.closeTransferModal();
                    
                    this.fetchLogSummary();
                    if (this.activeTab === 'history') {
                        this.fetchLogs();
                    }
                    
                    // Optional: re-fetch to ensure sync
                    // this.fetchInventory(); 
                } else {
                    alert('Error en transferencia: ' + (data.message || 'Desconocido'));
                }
            })
            .catch(err => {
                this.isTransferring = false;
                console.error(err);
                alert('Error: ' + err); // More descriptive error
            });
        },
        
        printInventory() {
            window.print();
        },
        
        exportExcel() {
            // Build HTML Table for Excel
            let html = `
                <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
                <head>
                    <!--[if gte mso 9]>
                    <xml>
                        <x:ExcelWorkbook>
                            <x:ExcelWorksheets>
                                <x:ExcelWorksheet>
                                    <x:Name>Inventario Dual</x:Name>
                                    <x:WorksheetOptions>
                                        <x:DisplayGridlines/>
                                    </x:WorksheetOptions>
                                </x:ExcelWorksheet>
                            </x:ExcelWorksheets>
                        </x:ExcelWorkbook>
                    </xml>
                    <![endif]-->
                    <meta charset="utf-8">
                    <style>
                        table { border-collapse: collapse; width: 100%; }
                        th, td { border: 1px solid #000000; padding: 5px; text-align: center; }
                        th { background-color: #f2f2f2; font-weight: bold; }
                        .discrepancy { background-color: #ffcccc; color: #cc0000; font-weight: bold; }
                        .text-left { text-align: left; }
                    </style>
                </head>
                <body>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th class="text-left" style="width: 300px;">Producto</th>
                                <th>${dsm_params.labels.local}</th>
                                <th>${dsm_params.labels.dep1}</th>
                                <th>${dsm_params.labels.dep2}</th>
                                <th>Total Plugin</th>
                                <th>WC Stock</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            this.filteredItems.forEach(item => {
                let isDisc = this.isItemDiscrepancy(item);
                let rowClass = isDisc ? 'class="discrepancy"' : '';
                let status = isDisc ? 'Discrepancia' : 'OK';
                
                html += `
                    <tr>
                        <td>${item.product_id}</td>
                        <td class="text-left">${item.post_title}</td>
                        <td>${item.stock_local}</td>
                        <td>${item.stock_deposito_1}</td>
                        <td>${item.stock_deposito_2}</td>
                        <td ${rowClass}>${this.calculateTotal(item)}</td>
                        <td>${item.wc_stock}</td>
                        <td ${rowClass}>${status}</td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </body>
                </html>
            `;
            
            // Trigger Download
            let blob = new Blob([html], { type: 'application/vnd.ms-excel' });
            let link = document.createElement("a");
            if (link.download !== undefined) {
                let url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", "inventario_dual_" + new Date().toISOString().slice(0,10) + ".xls");
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        },

        runDebugDB() {
            if(!confirm('¿Ejecutar diagnóstico de base de datos?')) return;
            
            fetch(dsm_params.root + 'debug-db', {
                headers: { 'X-WP-Nonce': dsm_params.nonce }
            })
            .then(r => r.json())
            .then(data => {
                console.log('Debug DB:', data);
                let msg = "Estado de la Tabla de Logs:\n";
                msg += "Existe: " + (data.table_exists ? "SÍ" : "NO") + "\n";
                if(data.table_exists) {
                    msg += "Filas: " + data.row_count + "\n";
                    msg += "Prueba de Inserción: " + (data.insert_test_success ? "EXITOSA" : "FALLIDA") + "\n";
                    if(!data.insert_test_success) msg += "Error: " + data.insert_test_error + "\n";
                }
                alert(msg);
            })
            .catch(e => alert("Error de conexión: " + e));
        }
    }
}
</script>
```
