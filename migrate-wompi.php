<?php
/**
 * Migration: Add Wompi columns to raffle_purchases table.
 * Run once via: docker exec wp_rifas_app php /var/www/html/wp-content/plugins/raffle-system/migrate-wompi.php
 */

// Bootstrap WordPress
define( 'ABSPATH', '/var/www/html/' );
require_once ABSPATH . 'wp-load.php';

global $wpdb;
$table = $wpdb->prefix . 'raffle_purchases';

$cols = $wpdb->get_results( "SHOW COLUMNS FROM {$table}" );
$col_names = array_map( function( $c ) { return $c->Field; }, $cols );

if ( ! in_array( 'wompi_reference', $col_names, true ) ) {
    $wpdb->query( "ALTER TABLE {$table} ADD COLUMN wompi_reference varchar(100) DEFAULT NULL AFTER payment_status" );
    echo "Added wompi_reference.\n";
} else {
    echo "wompi_reference already exists.\n";
}

if ( ! in_array( 'wompi_transaction', $col_names, true ) ) {
    $wpdb->query( "ALTER TABLE {$table} ADD COLUMN wompi_transaction varchar(100) DEFAULT NULL AFTER wompi_reference" );
    echo "Added wompi_transaction.\n";
} else {
    echo "wompi_transaction already exists.\n";
}

// Add index on wompi_reference
$indexes = $wpdb->get_results( "SHOW INDEX FROM {$table} WHERE Key_name = 'wompi_reference'" );
if ( empty( $indexes ) && in_array( 'wompi_reference', $col_names, true ) || ! empty( array_diff( array( 'wompi_reference' ), $col_names ) ) === false ) {
    $existing = $wpdb->get_results( "SHOW INDEX FROM {$table} WHERE Key_name = 'wompi_reference'" );
    if ( empty( $existing ) ) {
        $wpdb->query( "ALTER TABLE {$table} ADD INDEX wompi_reference (wompi_reference)" );
        echo "Added index on wompi_reference.\n";
    }
}

echo "Migration complete.\n";
