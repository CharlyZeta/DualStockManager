<div class="wrap dsm-wrap">
    <h1 class="wp-heading-inline">Transfer Stock</h1>
    <hr class="wp-header-end">

    <div class="dsm-card" style="max-width: 600px; margin-top: 20px;">
        <form id="dsm-transfer-form">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="dsm-product-search">Product</label></th>
                    <td>
                        <input type="text" id="dsm-product-search" class="regular-text" placeholder="Search by name or ID...">
                        <!-- Simple dropdown or autocomplete results container -->
                        <select id="dsm-product-select" class="regular-text" style="display:none; margin-top: 5px;">
                            <option value="">Select a product...</option>
                        </select>
                        <p class="description">Type to search, then select the product to transfer.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="dsm-from-location">From Location</label></th>
                    <td>
                        <select id="dsm-from-location" name="from_location">
                            <option value="stock_deposito_1">Deposito 1</option>
                            <option value="stock_deposito_2">Deposito 2</option>
                            <option value="stock_local">Showroom (Local)</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="dsm-to-location">To Location</label></th>
                    <td>
                        <select id="dsm-to-location" name="to_location">
                            <option value="stock_local" selected>Showroom (Local)</option>
                            <option value="stock_deposito_1">Deposito 1</option>
                            <option value="stock_deposito_2">Deposito 2</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="dsm-transfer-qty">Quantity</label></th>
                    <td>
                        <input type="number" id="dsm-transfer-qty" name="quantity" class="small-text" min="1" value="1">
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" id="dsm-submit-transfer" class="button button-primary">Transfer Stock</button>
                <span id="dsm-transfer-message" style="margin-left: 10px; font-weight: bold;"></span>
            </p>
        </form>
    </div>
</div>
