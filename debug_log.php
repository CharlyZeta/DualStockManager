<?php
// Load WordPress environment
require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/wp-load.php';
require_once __DIR__ . '/includes/class-dsm-sync-engine.php';

global $wpdb;
$wpdb->show_errors();

echo "Testing Log Transaction...\n";

$engine = new DSM_Sync_Engine();

// Force user context if needed (CLI context has no user)
if ( ! get_current_user_id() ) {
    $first_user = $wpdb->get_var("SELECT ID FROM {$wpdb->users} ORDER BY ID ASC LIMIT 1");
    if ($first_user) {
        wp_set_current_user($first_user);
        echo "Set current user to ID: $first_user\n";
    } else {
        echo "WARNING: No user found. user_id will satisfy logic but might be issue if table requires valid user foreign key (unlikely for MyISAM/default WP).\n";
    }
}

$product_id = 1; // Dummy product ID
$action_type = 'test_debug';
$details = 'Debug Script Test';
$prev_state = array('stock_local' => 10);
$new_state = array('stock_local' => 20);

// Call log directly
$engine->log_transaction($product_id, $action_type, $details, $prev_state, $new_state);

// Check if it was inserted
$last_id = $wpdb->insert_id;
echo "Last Insert ID: " . $last_id . "\n";
echo "Last DB Error: " . $wpdb->last_error . "\n";

// Check row count again
$table = $wpdb->prefix . 'dual_inventory_logs';
$count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
echo "Total Rows in $table: $count\n";

// Get last row
$last_row = $wpdb->get_row("SELECT * FROM $table ORDER BY id DESC LIMIT 1");
print_r($last_row);
