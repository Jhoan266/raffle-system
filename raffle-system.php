<?php
/**
 * Plugin Name: Raffle System
 * Plugin URI:  https://example.com
 * Description: Sistema de rifas online con boletos aleatorios por paquetes.
 * Version:     1.0.0
 * Author:      WP Rifas
 * Text Domain: raffle-system
 * License:     GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'RAFFLE_SYSTEM_VERSION', '1.0.0' );
define( 'RAFFLE_SYSTEM_PATH', plugin_dir_path( __FILE__ ) );
define( 'RAFFLE_SYSTEM_URL', plugin_dir_url( __FILE__ ) );

// Includes
require_once RAFFLE_SYSTEM_PATH . 'includes/class-raffle-activator.php';
require_once RAFFLE_SYSTEM_PATH . 'includes/class-raffle-tickets.php';
require_once RAFFLE_SYSTEM_PATH . 'includes/class-raffle-purchase.php';
require_once RAFFLE_SYSTEM_PATH . 'includes/class-raffle-draw.php';
require_once RAFFLE_SYSTEM_PATH . 'includes/class-raffle-email.php';
require_once RAFFLE_SYSTEM_PATH . 'includes/class-raffle-duplicates.php';
require_once RAFFLE_SYSTEM_PATH . 'includes/class-raffle-woocommerce.php';
require_once RAFFLE_SYSTEM_PATH . 'admin/class-raffle-admin.php';
require_once RAFFLE_SYSTEM_PATH . 'admin/class-raffle-analytics.php';
require_once RAFFLE_SYSTEM_PATH . 'public/class-raffle-public.php';

// Activation
register_activation_hook( __FILE__, array( 'Raffle_Activator', 'activate' ) );

// Init
add_action( 'plugins_loaded', function () {
    new Raffle_Admin();
    new Raffle_Analytics();
    new Raffle_Public();
    new Raffle_Purchase();
    new Raffle_Draw();
    new Raffle_Duplicates();
    new Raffle_WooCommerce();
} );
