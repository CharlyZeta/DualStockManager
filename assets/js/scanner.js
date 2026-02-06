jQuery(document).ready(function ($) {
    const scannerContainerId = 'dsm-reader';
    const resultContainerId = 'dsm-scan-result';

    // Only init if container exists
    if (!$('#' + scannerContainerId).length) return;

    let html5QrcodeScanner;

    $('#dsm-start-scan').on('click', function () {
        if (html5QrcodeScanner) {
            // If already running, maybe stop or toggle?
            // checking state is complex with this lib sometimes.
            return;
        }

        $('#' + scannerContainerId).show();

        // Ensure library is loaded
        if (typeof Html5QrcodeScanner === 'undefined') {
            $('#' + resultContainerId).html('<p style="color:red">Scanner library not loaded.</p>');
            return;
        }

        html5QrcodeScanner = new Html5QrcodeScanner(
            scannerContainerId,
            { fps: 10, qrbox: 250 }
        );

        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        $(this).hide();
        $('#dsm-stop-scan').show();
    });

    $('#dsm-stop-scan').on('click', function () {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear().then(() => {
                html5QrcodeScanner = null;
                $('#' + scannerContainerId).hide();
                $('#dsm-stop-scan').hide();
                $('#dsm-start-scan').show();
            }).catch(error => {
                console.error("Failed to clear html5QrcodeScanner. ", error);
            });
        }
    });

    function onScanSuccess(decodedText, decodedResult) {
        // Handle on success condition with the decoded message.
        console.log(`Scan result ${decodedText}`, decodedResult);

        // Display result
        $('#' + resultContainerId).html('<p>Searching for product: <strong>' + decodedText + '</strong>...</p>');

        // Search for product (assuming decodedText is ID or SKU)
        // We reuse the inventory search logic or call a specific endpoint
        // Using the same brute-force inventory filter for MVP as in transfer, 
        // OR better: if numeric, try ID lookup.

        $.ajax({
            url: dsm_params.root + 'inventory',
            method: 'GET',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', dsm_params.nonce);
            },
            success: function (data) {
                // Approximate match: ID or Title or Custom SKU field if we had one.
                // Assuming barcode = product_id for this MVP or some attribute.
                // Let's match against product_id first.
                const match = data.find(item => item.product_id == decodedText);

                if (match) {
                    $('#' + resultContainerId).html(`
                        <div class="dsm-card" style="background:#eaffea; border-color:#6c6;">
                            <h3>Product Found: #${match.product_id}</h3>
                            <p><strong>${match.post_title}</strong></p>
                            <ul>
                                <li>Showroom: ${match.stock_local}</li>
                                <li>Depo 1: ${match.stock_deposito_1}</li>
                                <li>Depo 2: ${match.stock_deposito_2}</li>
                            </ul>
                            <p><em>(Mockup: Actions to Edit/Transfer would go here)</em></p>
                        </div>
                    `);

                    // Optional: Stop scanning after success
                    // html5QrcodeScanner.clear(); ...
                } else {
                    $('#' + resultContainerId).html('<p style="color:red">Product not found for code: ' + decodedText + '</p>');
                }
            }
        });
    }

    function onScanFailure(error) {
        // handle scan failure, usually better to ignore and keep scanning.
        // for this demo we ignore.
        // console.warn(`Code scan error = ${error}`);
    }
});
