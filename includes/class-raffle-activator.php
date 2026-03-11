<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Raffle_Activator {

    public static function activate() {
        self::create_tables();
        update_option( 'raffle_system_version', RAFFLE_SYSTEM_VERSION );
    }

    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_raffles    = $wpdb->prefix . 'raffles';
        $table_purchases  = $wpdb->prefix . 'raffle_purchases';
        $table_tickets    = $wpdb->prefix . 'raffle_tickets';

        $sql = "CREATE TABLE {$table_raffles} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            prize_value decimal(10,2) NOT NULL DEFAULT 0,
            prize_image varchar(500) DEFAULT '',
            total_tickets int(11) NOT NULL DEFAULT 0,
            sold_tickets int(11) NOT NULL DEFAULT 0,
            ticket_price decimal(10,2) NOT NULL DEFAULT 0,
            packages text,
            draw_date datetime DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            winner_ticket_id bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset_collate};

        CREATE TABLE {$table_purchases} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            raffle_id bigint(20) UNSIGNED NOT NULL,
            buyer_name varchar(255) NOT NULL,
            buyer_email varchar(255) NOT NULL,
            quantity int(11) NOT NULL,
            total_amount decimal(10,2) NOT NULL DEFAULT 0,
            payment_status varchar(20) NOT NULL DEFAULT 'pending',
            wc_order_id bigint(20) UNSIGNED DEFAULT NULL,
            purchase_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY raffle_id (raffle_id),
            KEY wc_order_id (wc_order_id)
        ) {$charset_collate};

        CREATE TABLE {$table_tickets} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            raffle_id bigint(20) UNSIGNED NOT NULL,
            purchase_id bigint(20) UNSIGNED NOT NULL,
            ticket_number int(11) NOT NULL,
            buyer_email varchar(255) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_ticket (raffle_id, ticket_number),
            KEY raffle_id (raffle_id),
            KEY purchase_id (purchase_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }
}
