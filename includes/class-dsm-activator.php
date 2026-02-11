<?php

/**
 * Fired during plugin activation.
 */
class DSM_Activator {

	/**
	 * Create the database table for dual stock inventory.
	 */
	public static function activate() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'dual_inventory';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			product_id bigint(20) UNSIGNED NOT NULL,
			stock_local int(11) DEFAULT 0 NOT NULL,
			stock_deposito_1 int(11) DEFAULT 0 NOT NULL,
			stock_deposito_2 int(11) DEFAULT 0 NOT NULL,
			box_dimensions varchar(100) DEFAULT '' NOT NULL,
			weight_kg decimal(10,2) DEFAULT 0.00 NOT NULL,
			last_audit_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			audit_status enum('match', 'discrepancy', 'pending') DEFAULT 'pending' NOT NULL,
			PRIMARY KEY  (product_id)
		) $charset_collate;";

        $table_logs = $wpdb->prefix . 'dual_inventory_logs';
        $sql_logs = "CREATE TABLE $table_logs (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            product_id bigint(20) UNSIGNED NOT NULL,
            action_type varchar(50) NOT NULL,
            details text NOT NULL,
            previous_state longtext NOT NULL,
            new_state longtext NOT NULL,
            PRIMARY KEY  (id),
            KEY product_id (product_id),
            KEY date_created (date_created)
        ) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
        dbDelta( $sql_logs );
	}

}
