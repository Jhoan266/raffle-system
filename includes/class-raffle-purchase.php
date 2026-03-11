<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Raffle_Purchase {

    public function __construct() {
        add_action( 'wp_ajax_raffle_purchase', array( $this, 'handle_purchase' ) );
        add_action( 'wp_ajax_nopriv_raffle_purchase', array( $this, 'handle_purchase' ) );
    }

    public function handle_purchase() {
        // Nonce verification
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'raffle_purchase_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Error de seguridad. Recarga la página e inténtalo de nuevo.' ) );
        }

        $raffle_id   = isset( $_POST['raffle_id'] ) ? absint( $_POST['raffle_id'] ) : 0;
        $quantity    = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 0;
        $buyer_name  = isset( $_POST['buyer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['buyer_name'] ) ) : '';
        $buyer_email = isset( $_POST['buyer_email'] ) ? sanitize_email( wp_unslash( $_POST['buyer_email'] ) ) : '';

        // Validate required fields
        if ( ! $raffle_id || ! $quantity || ! $buyer_name || ! $buyer_email ) {
            wp_send_json_error( array( 'message' => 'Todos los campos son obligatorios.' ) );
        }

        if ( ! is_email( $buyer_email ) ) {
            wp_send_json_error( array( 'message' => 'Correo electrónico no válido.' ) );
        }

        global $wpdb;
        $table_raffles    = $wpdb->prefix . 'raffles';
        $table_purchases  = $wpdb->prefix . 'raffle_purchases';

        // Get active raffle (lectura ligera — sin lock, solo para validaciones previas)
        $raffle = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table_raffles} WHERE id = %d AND status = 'active'",
            $raffle_id
        ) );

        if ( ! $raffle ) {
            wp_send_json_error( array( 'message' => 'Rifa no encontrada o no activa.' ) );
        }

        // Validate valid package
        $packages = json_decode( $raffle->packages, true );
        if ( ! is_array( $packages ) || ! in_array( $quantity, $packages, true ) ) {
            wp_send_json_error( array( 'message' => 'Paquete de boletos no válido.' ) );
        }

        // Pre-check availability (verificación real ocurre dentro de la transacción)
        $available = $raffle->total_tickets - $raffle->sold_tickets;
        if ( $quantity > $available ) {
            wp_send_json_error( array( 'message' => 'No hay suficientes boletos disponibles. Quedan ' . $available . '.' ) );
        }

        $total_amount = $quantity * $raffle->ticket_price;

        // If WooCommerce is available, reject direct purchase — must go through WooCommerce flow
        if ( Raffle_WooCommerce::is_available() ) {
            wp_send_json_error( array( 'message' => 'El pago debe procesarse a través de WooCommerce.' ) );
        }

        // TRANSACCIÓN ATÓMICA: purchase + tickets se crean juntos o no se crea nada
        $wpdb->query( 'START TRANSACTION' );

        // Direct purchase (Wompi disabled — for testing or free raffles)
        $inserted = $wpdb->insert( $table_purchases, array(
            'raffle_id'      => $raffle_id,
            'buyer_name'     => $buyer_name,
            'buyer_email'    => $buyer_email,
            'quantity'       => $quantity,
            'total_amount'   => $total_amount,
            'payment_status' => 'completed',
            'purchase_date'  => current_time( 'mysql' ),
        ), array( '%d', '%s', '%s', '%d', '%f', '%s', '%s' ) );

        $purchase_id = $wpdb->insert_id;

        if ( ! $inserted || ! $purchase_id ) {
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( array( 'message' => 'Error al registrar la compra. Inténtalo de nuevo.' ) );
        }

        // Generate tickets (transacción gestionada por el caller)
        $tickets = Raffle_Tickets::generate_tickets( $raffle_id, $purchase_id, $quantity, $buyer_email, false );

        if ( is_wp_error( $tickets ) ) {
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( array( 'message' => $tickets->get_error_message() ) );
        }

        $wpdb->query( 'COMMIT' );

        // Send confirmation email (fuera de transacción — no debe bloquear el commit)
        Raffle_Email::send_purchase_confirmation( $purchase_id, $raffle, $tickets );

        // Format ticket numbers with leading zeros
        $total_digits = strlen( (string) $raffle->total_tickets );
        $formatted    = array_map( function ( $num ) use ( $total_digits ) {
            return str_pad( $num, $total_digits, '0', STR_PAD_LEFT );
        }, $tickets );

        wp_send_json_success( array(
            'message'     => '¡Compra realizada con éxito!',
            'tickets'     => $formatted,
            'purchase_id' => $purchase_id,
            'total'       => number_format( $total_amount, 2 ),
        ) );
    }
}
