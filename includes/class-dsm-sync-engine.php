<?php

/**
 * Handles stock synchronization and business logic.
 */
class DSM_Sync_Engine {

	/**
	 * Initialize hooks.
	 */
	public function init() {
		// REMOVED: Automatic order hooks per user request.
		// Orders will cause a discrepancy between WC Stock and Plugin Stock.
		// User will manually reconcile via Dashboard.
		
		// Hook to manual updates (REST API or Admin) to sync back to WC
		add_action( 'dsm_stock_updated', array( $this, 'sync_wc_stock' ), 10, 1 );
	}

	/**
	 * Calculate total stock from all locations and update WooCommerce product stock.
	 *
	 * @param int $product_id
	 */
	public function sync_wc_stock( $product_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'dual_inventory';

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE product_id = %d", $product_id ) );

		if ( $row ) {
			$total_stock = (int) $row->stock_local + (int) $row->stock_deposito_1 + (int) $row->stock_deposito_2;

			// Update WC Product
			$product = wc_get_product( $product_id );
			if ( $product ) {
				if ( $product->get_stock_quantity() !== $total_stock ) {
					wc_update_product_stock( $product, $total_stock );
				}
			}
		}
	}
	
	/**
	 * Transfer stock between locations.
	 */
	public function transfer_stock( $product_id, $from, $to, $qty ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'dual_inventory';
		
		$valid = array( 'stock_local', 'stock_deposito_1', 'stock_deposito_2' );
		if ( ! in_array( $from, $valid ) || ! in_array( $to, $valid ) ) {
			return new WP_Error( 'invalid_location', 'Invalid stock location' );
		}
		
		$wpdb->query( "START TRANSACTION" );
		
		// Check source stock
		$current_src = $wpdb->get_var( $wpdb->prepare( "SELECT $from FROM $table_name WHERE product_id = %d", $product_id ) );
		
		if ( $current_src < $qty ) {
			$wpdb->query( "ROLLBACK" );
			return new WP_Error( 'insufficient_stock', "Insufficient stock in $from" );
		}
		
		// Capture previous state
        $prev_state = $this->get_product_stock_state( $product_id );

		$wpdb->query( $wpdb->prepare( "UPDATE $table_name SET $from = $from - %d WHERE product_id = %d", $qty, $product_id ) );
		$wpdb->query( $wpdb->prepare( "UPDATE $table_name SET $to = $to + %d WHERE product_id = %d", $qty, $product_id ) );
        
        // Capture new state
        $new_state = $prev_state; // Start with copy
        $new_state[$from] -= $qty;
        $new_state[$to] += $qty;
        
        $this->log_transaction( $product_id, 'transfer', "Transferred $qty from $from to $to", $prev_state, $new_state );
		
		$wpdb->query( "COMMIT" );
		
		// Recalculate and push to WC
		$this->sync_wc_stock( $product_id );
		
		return true;
	}

    /**
     * Batch update discrepancies.
     * Overwrite WC stock with Plugin Stock.
     */
    public function fix_wc_discrepancy( $product_ids ) {
        if ( ! is_array( $product_ids ) ) {
            $product_ids = array( $product_ids );
        }

        foreach ( $product_ids as $pid ) {
            $this->sync_wc_stock( $pid );
        }
        return true;
    }

    /**
     * Import missing WooCommerce products into the dual inventory table.
     * 
     * @return int Number of products imported.
     */
    public function import_missing_products() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dual_inventory';
        $posts_table = $wpdb->prefix . 'posts';

        // 1. Get all published product IDs from WC that are NOT in our table
        $sql = "
            SELECT p.ID 
            FROM $posts_table p
            LEFT JOIN $table_name d ON p.ID = d.product_id
            WHERE p.post_type = 'product' 
            AND p.post_status = 'publish' 
            AND d.product_id IS NULL
        ";

        $missing_ids = $wpdb->get_col( $sql );

        if ( empty( $missing_ids ) ) {
            return 0;
        }

        // 2. Insert them with default 0 stock
        $count = 0;
        foreach ( $missing_ids as $id ) {
            $inserted = $wpdb->insert(
                $table_name,
                array(
                    'product_id' => $id,
                    'stock_local' => 0,
                    'stock_deposito_1' => 0,
                    'stock_deposito_2' => 0,
                    'audit_status' => 'pending'
                ),
                array( '%d', '%d', '%d', '%d', '%s' )
            );

            if ( $inserted ) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Manually update stock for a product in the plugin DB.
     * Does NOT sync to WC automatically, allowing for discrepancy handling.
     */
    public function update_product_stock( $product_id, $local, $dep1, $dep2 ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dual_inventory';

        // Check if exists
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT product_id FROM $table_name WHERE product_id = %d", $product_id ) );

        if ( ! $exists ) {
             // If not found, we should insert it now instead of erroring.
             // This handles the case where user sees products in the list (joined from posts table)
             // but they haven't been imported to dual_inventory yet.
             $inserted = $wpdb->insert(
                $table_name,
                array(
                    'product_id'       => $product_id,
                    'stock_local'      => $local,
                    'stock_deposito_1' => $dep1,
                    'stock_deposito_2' => $dep2,
                    'audit_status'     => 'pending'
                ),
                array( '%d', '%d', '%d', '%d', '%s' )
            );
            
            if ( $inserted === false ) {
                return new WP_Error( 'db_insert_error', 'Could not insert new stock record.' );
            }
            
            return true;
        }

        // Capture previous state
        $prev_state = $this->get_product_stock_state( $product_id );

        $updated = $wpdb->update(
            $table_name,
            array(
                'stock_local'      => $local,
                'stock_deposito_1' => $dep1,
                'stock_deposito_2' => $dep2,
                'audit_status'     => 'pending' // Mark as pending audit/sync
            ),
            array( 'product_id' => $product_id ),
            array( '%d', '%d', '%d', '%s' ),
            array( '%d' )
        );

        if ( $updated === false ) {
            return new WP_Error( 'db_error', 'Could not update database.' );
        }
        
        // Log Transaction
        $new_state = array(
            'stock_local'      => $local,
            'stock_deposito_1' => $dep1,
            'stock_deposito_2' => $dep2
        );
        
        $this->log_transaction( $product_id, 'edit', 'Manual stock update via Dashboard', $prev_state, $new_state );

        return true;
    }

    /**
     * Get current stock state for logging.
     */
    private function get_product_stock_state( $product_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dual_inventory';
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT stock_local, stock_deposito_1, stock_deposito_2 FROM $table_name WHERE product_id = %d", $product_id ), ARRAY_A );
        return $row ? $row : array( 'stock_local' => 0, 'stock_deposito_1' => 0, 'stock_deposito_2' => 0 );
    }

    /**
     * Log a stock transaction.
     */
    public function log_transaction( $product_id, $action_type, $details, $prev_state, $new_state ) {
        global $wpdb;
        $table_logs = $wpdb->prefix . 'dual_inventory_logs';
        
        $user_id = get_current_user_id();
        
        $wpdb->insert(
            $table_logs,
            array(
                'date_created'   => current_time( 'mysql' ),
                'user_id'        => $user_id,
                'product_id'     => $product_id,
                'action_type'    => $action_type,
                'details'        => $details,
                'previous_state' => json_encode( $prev_state ),
                'new_state'      => json_encode( $new_state )
            ),
            array( '%s', '%d', '%d', '%s', '%s', '%s', '%s' )
        );
    }
    
    /**
     * Revert a transaction by ID.
     */
    public function revert_transaction( $log_id ) {
        global $wpdb;
        $table_logs = $wpdb->prefix . 'dual_inventory_logs';
        
        $log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_logs WHERE id = %d", $log_id ) );
        
        if ( ! $log ) {
            return new WP_Error( 'not_found', 'Log entry not found.' );
        }
        
        $prev_state = json_decode( $log->previous_state, true );
        
        if ( ! $prev_state ) {
             return new WP_Error( 'invalid_data', 'Previous state data is corrupted.' );
        }
        
        // Restore previous values
        $result = $this->update_product_stock( 
            $log->product_id, 
            $prev_state['stock_local'], 
            $prev_state['stock_deposito_1'], 
            $prev_state['stock_deposito_2'] 
        );
        
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        
        // Log the revert action itself (update_product_stock already logs as 'edit', we might want to override or let it be)
        // Since update_product_stock logs as 'edit', we might want to update that last log entry to be 'revert' or just add a note.
        // For simplicity, we let it log as 'edit' but we could update the details.
        
        // Let's create a specific 'revert' log manually to be clear, or just let the edit flow handle it.
        // The prompt asked for "reversiones deben generar también un registro reversible".
        // Calling update_product_stock does exactly that: it creates a new log moving from Current -> Previous.
        // So a Revert IS a reversible action.
        
        // Optionally, we could update the 'details' of the log entry just created to say "Revert of Log #ID".
        // But for now, simple is better.
        
        return true;
    }
}
