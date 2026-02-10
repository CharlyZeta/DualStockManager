<div class="wrap dsm-wrap">
    <h1 class="wp-heading-inline">Transferencia de Stock</h1>
    <hr class="wp-header-end">

    <div class="dsm-card" style="max-width: 600px; margin-top: 20px;">
        <form id="dsm-transfer-form">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="dsm-product-search">Producto</label></th>
                    <td>
                        <input type="text" id="dsm-product-search" class="regular-text" placeholder="Buscar por Nombre o ID...">
                        <!-- Simple dropdown or autocomplete results container -->
                        <select id="dsm-product-select" class="regular-text" style="display:none; margin-top: 5px;">
                            <option value="">Selecciona un producto...</option>
                        </select>
                        <p class="description">Escribe para buscar, luego selecciona el producto a transferir.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="dsm-from-location">Origen</label></th>
                    <td>
                        <select id="dsm-from-location" name="from_location">
                            <option value="stock_deposito_1">Depósito 1</option>
                            <option value="stock_deposito_2">Depósito 2</option>
                            <option value="stock_local">Showroom (Local)</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="dsm-to-location">Destino</label></th>
                    <td>
                        <select id="dsm-to-location" name="to_location">
                            <option value="stock_local" selected>Showroom (Local)</option>
                            <option value="stock_deposito_1">Depósito 1</option>
                            <option value="stock_deposito_2">Depósito 2</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="dsm-transfer-qty">Cantidad</label></th>
                    <td>
                        <input type="number" id="dsm-transfer-qty" name="quantity" class="small-text" min="1" value="1">
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" id="dsm-submit-transfer" class="button button-primary">Duales Transferir Stock</button>
                <span id="dsm-transfer-message" style="margin-left: 10px; font-weight: bold;"></span>
            </p>
        </form>
    </div>
</div>
