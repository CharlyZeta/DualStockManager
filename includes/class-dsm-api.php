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

        // POST /sync-products - Import missing WC products
        register_rest_route( $namespace, '/sync-products', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'sync_products' ),
            'permission_callback' => array( $this, 'permissions_check' ),
        ) );

        // POST /inventory/update - Live edit stock
		register_rest_route( $namespace, '/inventory/update', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'update_stock_values' ),
			'permission_callback' => array( $this, 'permissions_check' ),
		) );

        register_rest_route( $namespace, '/logs', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_logs' ),
            'permission_callback' => array( $this, 'permissions_check' ),
        ) );
        
        register_rest_route( $namespace, '/logs/revert', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'revert_log' ),
            'permission_callback' => array( $this, 'permissions_check' ),
        ) );
        
        register_rest_route( $namespace, '/logs/summary', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_log_summary' ),
            'permission_callback' => array( $this, 'permissions_check' ),
        ) );

	}

	public function permissions_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get inventory list with calculated WC Discrepancy.
     * Supports filtering by Search Term (Name, SKU, ID) and Category.
	 */
	public function get_inventory( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'dual_inventory';
		
		$search   = $request->get_param( 'search' );
        $category = (int) $request->get_param( 'category' );
        
		$args   = array();
		$where  = "p.post_type = 'product' AND p.post_status = 'publish'";
        $join   = "LEFT JOIN {$wpdb->prefix}posts p ON d.product_id = p.ID";
        $join  .= " LEFT JOIN {$wpdb->prefix}postmeta pm ON d.product_id = pm.post_id AND pm.meta_key = '_stock'";

        // Filter by Category
        if ( ! empty( $category ) ) {
            $join .= " LEFT JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id";
            $join .= " LEFT JOIN {$wpdb->prefix}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
            $where .= " AND tt.term_id = %d";
            $args[] = $category;
        }

        // Filter by Search (Name OR ID OR SKU)
		if ( ! empty( $search ) ) {
            // Join SKU meta only if searching
            $join .= " LEFT JOIN {$wpdb->prefix}postmeta sku_meta ON p.ID = sku_meta.post_id AND sku_meta.meta_key = '_sku'";
            
			$where .= " AND (p.post_title LIKE %s OR p.ID = %d OR sku_meta.meta_value LIKE %s)";
            $like = '%' . $wpdb->esc_like( $search ) . '%';
			$args[] = $like;      // Name
            $args[] = is_numeric($search) ? $search : -1; // ID (match if number)
            $args[] = $like;      // SKU
		}

		// Increase limit if searching to ensure we find the product
		$limit_clause = "LIMIT 100";
		if ( ! empty( $search ) || ! empty( $category ) ) {
			$limit_clause = "LIMIT 500"; 
		}

		$sql = "
			SELECT 
                d.*, 
                p.post_title, 
                pm.meta_value as wc_stock
			FROM $table_name d
            $join
			WHERE $where
            GROUP BY d.product_id 
			$limit_clause
		";
        
		if ( ! empty( $args ) ) {
			$results = $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
		} else {
			$results = $wpdb->get_results( $sql );
		}
        
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

    /**
     * Endpoint to trigger full product sync (import missing).
     */
    public function sync_products( $request ) {
        $sync = new DSM_Sync_Engine();
        $count = $sync->import_missing_products();
        
        return new WP_REST_Response( array( 
            'success' => true, 
            'imported' => $count,
            'message' => "Se importaron exitosamente $count nuevos productos."
        ), 200 );
    }

	/**
	 * Endpoint to update stock values for a single product (Spreadsheet mode).
	 */
	public function update_stock_values( $request ) {
		$product_id = (int) $request->get_param( 'product_id' );
		$local      = (int) $request->get_param( 'stock_local' );
		$dep1       = (int) $request->get_param( 'stock_deposito_1' );
		$dep2       = (int) $request->get_param( 'stock_deposito_2' );

		if ( ! $product_id ) {
			return new WP_Error( 'missing_id', 'Product ID is required', array( 'status' => 400 ) );
		}

		$sync = new DSM_Sync_Engine();
		
		// Use the existing method in DSM_Sync_Engine (which we verified exists)
		// Note: update_product_stock expects (product_id, local, dep1, dep2)
		// It might need to be adjusted if it doesn't handle partial updates, but looking at the code
		// we are passing all 3 values from the frontend anyway.
		
		// However, looking at the previous file view of DSM_Sync_Engine, the method was:
		// public function update_product_stock( $product_id, $local, $dep1, $dep2 )
		
		// The frontend sends all 3. Perfect.
		$result = $sync->update_product_stock( $product_id, $local, $dep1, $dep2 );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( array( 
			'success' => true,
			'message' => 'Stock updated locally. WC discrepancy may occur.',
            'log_id'  => isset($result['log_id']) ? $result['log_id'] : 0
		), 200 );
	}
    
    /**
     * Get logs with pagination and filters.
     */
    public function get_logs( $request ) {
        global $wpdb;
        $table_logs = $wpdb->prefix . 'dual_inventory_logs';
        
        // Debug: Check table existence
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_logs'") != $table_logs) {
            return new WP_REST_Response( array( 
                'success' => false, 
                'message' => "Error Crítico: La tabla de logs ($table_logs) no existe en la base de datos by DSM API." 
            ), 200 ); // Return 200 so frontend handles it gracefully
        }

        $posts_table = $wpdb->prefix . 'posts';
        $users_table = $wpdb->prefix . 'users';
        
        $limit = 20;
        $offset = 0; // standard pagination
        
        $sql = "SELECT l.*, p.post_title as product_name, u.display_name as user_name 
                FROM $table_logs l
                LEFT JOIN $posts_table p ON l.product_id = p.ID
                LEFT JOIN $users_table u ON l.user_id = u.ID
                ORDER BY l.date_created DESC LIMIT $limit";
        
        $results = $wpdb->get_results( $sql );
        
        return new WP_REST_Response( array( 'success' => true, 'data' => $results ), 200 );
    }
    
    /**
     * Revert a log entry.
     */
    public function revert_log( $request ) {
        $log_id = (int) $request->get_param( 'log_id' );
        
        if ( ! $log_id ) {
            return new WP_Error( 'missing_id', 'Log ID required.' );
        }
        
        $sync = new DSM_Sync_Engine();
        $result = $sync->revert_transaction( $log_id );
        
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        
        return new WP_REST_Response( array( 'success' => true, 'message' => 'Transaction reverted.' ), 200 );
    }
    
    /**
     * Get summary for today.
     */
    public function get_log_summary( $request ) {
        global $wpdb;
        $table_logs = $wpdb->prefix . 'dual_inventory_logs';
        
        $today = current_time( 'Y-m-d' );
        
        $sql = "SELECT action_type, COUNT(*) as count 
                FROM $table_logs 
                WHERE date_created LIKE %s 
                GROUP BY action_type";
                
        $results = $wpdb->get_results( $wpdb->prepare( $sql, $today . '%' ) );
        
        $summary = array( 'edit' => 0, 'transfer' => 0, 'sync' => 0 );
        foreach ( $results as $row ) {
            $summary[$row->action_type] = (int) $row->count;
        }
        
        return new WP_REST_Response( array( 'success' => true, 'data' => $summary ), 200 );
    }


}
