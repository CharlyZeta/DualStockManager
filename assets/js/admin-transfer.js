jQuery(document).ready(function ($) {
    const productSearchInput = $('#dsm-product-select'); // We might use Select2 later, for now simple search
    // Actually, let's implement a basic search behavior on the text input that populates the select
    const searchInput = $('#dsm-product-search');
    const productSelect = $('#dsm-product-select');
    const transferForm = $('#dsm-transfer-form');
    const msgSpan = $('#dsm-transfer-message');

    let searchTimer;

    // simplistic product search
    searchInput.on('input', function () {
        clearTimeout(searchTimer);
        const term = $(this).val();

        if (term.length < 3) return;

        searchTimer = setTimeout(() => {
            if (dsm_params.root) {
                let url = dsm_params.root + 'inventory';
                if (term) {
                    url += '?search=' + encodeURIComponent(term);
                }

                $.ajax({
                    url: url,
                    method: 'GET',
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', dsm_params.nonce);
                    },
                    success: function (data) {
                        productSelect.empty().show();

                        if (data.length === 0) {
                            productSelect.append('<option value="">No se encontraron productos</option>');
                        } else {
                            data.forEach(item => {
                                productSelect.append(`<option value="${item.product_id}">#${item.product_id} - ${item.post_title} (L:${item.stock_local} | D1:${item.stock_deposito_1} | D2:${item.stock_deposito_2})</option>`);
                            });
                        }
                    }
                });
            }
        }, 500);
    });

    transferForm.on('submit', function (e) {
        e.preventDefault();

        const productId = productSelect.val();
        const fromLoc = $('#dsm-from-location').val();
        const toLoc = $('#dsm-to-location').val();
        const qty = $('#dsm-transfer-qty').val();

        if (!productId) {
            alert('Por favor selecciona un producto.');
            return;
        }

        if (fromLoc === toLoc) {
            alert('El origen y el destino deben ser diferentes.');
            return;
        }

        msgSpan.text('Procesando...').css('color', 'black');
        $('#dsm-submit-transfer').prop('disabled', true);

        $.ajax({
            url: dsm_params.root + 'transfer',
            method: 'POST',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', dsm_params.nonce);
            },
            data: {
                product_id: productId,
                from: fromLoc,
                to: toLoc,
                qty: qty
            },
            success: function (response) {
                msgSpan.text('¡Transferencia Exitosa!').css('color', 'green');
                // Ticket #4: Reset form
                $('#dsm-transfer-qty').val(1);
                productSelect.val(null).trigger('change'); // Reset selection
                productSelect.empty(); // Optional: clear the dropdown until next search
                // Trigger refresh of search to update stock display in dropdown if they search again?
                // For now just leave it.
            },
            error: function (xhr) {
                const err = xhr.responseJSON ? xhr.responseJSON.message : 'Error desconocido';
                msgSpan.text('Error: ' + err).css('color', 'red');
            },
            complete: function () {
                $('#dsm-submit-transfer').prop('disabled', false);
            }
        });
    });
});
