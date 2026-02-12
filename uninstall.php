<?php

/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Check if user has opted to delete data
$delete_on_uninstall = get_option( 'dsm_delete_on_uninstall' );

if ( $delete_on_uninstall ) {
    global $wpdb;

    // 1. Drop Tables
    $table_inventory = $wpdb->prefix . 'dual_inventory';
    $table_logs = $wpdb->prefix . 'dual_inventory_logs';

    $wpdb->query( "DROP TABLE IF EXISTS $table_inventory" );
    $wpdb->query( "DROP TABLE IF EXISTS $table_logs" );

    // 2. Delete Options
    delete_option( 'dsm_db_version' );
    delete_option( 'dsm_delete_on_uninstall' ); // Clean up self
    
    // Note: 'dsm_version' is usually not stored in options table unless specifically added, 
    // but good to check if you add more options later.
}
