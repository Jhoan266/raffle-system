<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Raffle_Public {

    public function __construct() {
        add_shortcode( 'raffle', array( $this, 'render_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets() {
        global $post;
        if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'raffle' ) ) {
            wp_enqueue_style( 'raffle-public', RAFFLE_SYSTEM_URL . 'assets/css/public.css', array(), RAFFLE_SYSTEM_VERSION );

            wp_enqueue_script( 'raffle-public', RAFFLE_SYSTEM_URL . 'assets/js/public.js', array( 'jquery' ), RAFFLE_SYSTEM_VERSION, true );

            $localize_data = array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'raffle_purchase_nonce' ),
            );

            if ( Raffle_WooCommerce::is_available() ) {
                $localize_data['wc_enabled'] = '1';
            }

            wp_localize_script( 'raffle-public', 'rafflePublic', $localize_data );
            wp_localize_script( 'raffle-public', 'raffleCountdown', array(
                'labels' => array(
                    'days'    => 'Días',
                    'hours'   => 'Horas',
                    'minutes' => 'Min',
                    'seconds' => 'Seg',
                    'expired' => '¡Es hora del sorteo!',
                ),
            ) );
        }
    }

    public function render_shortcode( $atts ) {
        $atts      = shortcode_atts( array( 'id' => 0 ), $atts, 'raffle' );
        $raffle_id = absint( $atts['id'] );

        if ( ! $raffle_id ) {
            return '<p>ID de rifa no especificado.</p>';
        }

        global $wpdb;
        $raffle = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}raffles WHERE id = %d",
            $raffle_id
        ) );

        if ( ! $raffle ) {
            return '<p>Rifa no encontrada.</p>';
        }

        $packages  = json_decode( $raffle->packages, true ) ?: array();
        $progress  = ( $raffle->total_tickets > 0 ) ? round( ( $raffle->sold_tickets / $raffle->total_tickets ) * 100 ) : 0;
        $remaining = $raffle->total_tickets - $raffle->sold_tickets;

        ob_start();
        include RAFFLE_SYSTEM_PATH . 'public/views/raffle-display.php';
        return ob_get_clean();
    }
}
