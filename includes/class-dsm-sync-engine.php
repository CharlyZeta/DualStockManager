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
		
		$wpdb->query( $wpdb->prepare( "UPDATE $table_name SET $from = $from - %d WHERE product_id = %d", $qty, $product_id ) );
		$wpdb->query( $wpdb->prepare( "UPDATE $table_name SET $to = $to + %d WHERE product_id = %d", $qty, $product_id ) );
		
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
}
