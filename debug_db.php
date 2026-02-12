<?php
global $wpdb;
$table = $wpdb->prefix . 'dual_inventory_logs';

echo "Checking table: $table\n";

// Check if table exists
$exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
if ($exists == $table) {
    echo "Table EXISTS.\n";
    
    // Check row count
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    echo "Total Rows: $count\n";
    
    // Show last 5 rows
    if ($count > 0) {
        $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 5");
        echo "Last 5 entries:\n";
        print_r($rows);
    } else {
        echo "Table is EMPTY.\n";
    }
} else {
    echo "Table DOES NOT EXIST.\n";
    echo "Last DB Error: " . $wpdb->last_error . "\n";
}
