<?php

/**
 * Handles REST API endpoints.
 */
class DSM_API {

	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		$namespace = 'dsm/v1';

		// GET /inventory - List all
		register_rest_route( $namespace, '/inventory', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_inventory' ),
			'permission_callback' => array( $this, 'permissions_check' ),
		) );

		// POST /transfer - Transfer stock
		register_rest_route( $namespace, '/transfer', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'transfer_stock' ),
			'permission_callback' => array( $this, 'permissions_check' ),
		) );

        // POST /fix-wc - Force sync WC stock to match dual inventory
        register_rest_route( $namespace, '/fix-wc', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'fix_wc_stock' ),
			'permission_callback' => array( $this, 'permissions_check' ),
		) );
	}

	public function permissions_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get inventory list with calculated WC Discrepancy.
	 */
	public function get_inventory( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'dual_inventory';
		
		// Join with posts to get title, and postmeta to get current WC stock (_stock)
		// We explicitly want to see if `_stock` differs from sum(locations).
        // Note: _stock is in postmeta.
		
		$results = $wpdb->get_results( "
			SELECT 
                d.*, 
                p.post_title, 
                pm.meta_value as wc_stock
			FROM $table_name d
			LEFT JOIN {$wpdb->prefix}posts p ON d.product_id = p.ID
            LEFT JOIN {$wpdb->prefix}postmeta pm ON d.product_id = pm.post_id AND pm.meta_key = '_stock'
			WHERE p.post_type = 'product' AND p.post_status = 'publish'
			LIMIT 100
		" );
        
        // Process results to flag discrepancies
        foreach ( $results as $row ) {
            $plugin_total = (int)$row->stock_local + (int)$row->stock_deposito_1 + (int)$row->stock_deposito_2;
            $wc_val = (int)$row->wc_stock;
            $row->plugin_total = $plugin_total;
            $row->is_discrepancy = ($plugin_total !== $wc_val);
        }

		return new WP_REST_Response( $results, 200 );
	}

	/**
	 * Perform stock transfer.
	 */
	public function transfer_stock( $request ) {
		$product_id = (int) $request->get_param( 'product_id' );
		$from       = $request->get_param( 'from' );
		$to         = $request->get_param( 'to' );
		$qty        = (int) $request->get_param( 'qty' );

		if ( ! $product_id || ! $from || ! $to || ! $qty ) {
			return new WP_Error( 'missing_params', 'Missing parameters', array( 'status' => 400 ) );
		}

		$sync = new DSM_Sync_Engine();
		$result = $sync->transfer_stock( $product_id, $from, $to, $qty );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

    public function fix_wc_stock( $request ) {
        $product_ids = $request->get_param( 'product_ids' );
        if ( empty( $product_ids ) ) {
             return new WP_Error( 'missing_params', 'Missing product_ids', array( 'status' => 400 ) );
        }
        
        $sync = new DSM_Sync_Engine();
        $sync->fix_wc_discrepancy( $product_ids );
        
        return new WP_REST_Response( array( 'success' => true ), 200 );
    }
}
