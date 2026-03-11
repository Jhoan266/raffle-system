<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Raffle_Wompi {

    /** Sandbox / Production URLs */
    const SANDBOX_API    = 'https://sandbox.wompi.co/v1';
    const PRODUCTION_API = 'https://production.wompi.co/v1';
    const WIDGET_JS      = 'https://checkout.wompi.co/widget.js';

    public function __construct() {
        // AJAX: initiate purchase (create pending + return Wompi params)
        add_action( 'wp_ajax_raffle_initiate_purchase', array( $this, 'ajax_initiate_purchase' ) );
        add_action( 'wp_ajax_nopriv_raffle_initiate_purchase', array( $this, 'ajax_initiate_purchase' ) );

        // AJAX: confirm purchase after Wompi widget callback
        add_action( 'wp_ajax_raffle_confirm_purchase', array( $this, 'ajax_confirm_purchase' ) );
        add_action( 'wp_ajax_nopriv_raffle_confirm_purchase', array( $this, 'ajax_confirm_purchase' ) );

        // Webhook endpoint (Wompi events)
        add_action( 'rest_api_init', array( $this, 'register_webhook' ) );
    }

    /**
     * Get Wompi settings.
     */
    public static function get_settings() {
        return array(
            'enabled'          => get_option( 'wompi_enabled', '0' ),
            'sandbox'          => get_option( 'wompi_sandbox', '1' ),
            'public_key'       => get_option( 'wompi_public_key', '' ),
            'private_key'      => get_option( 'wompi_private_key', '' ),
            'integrity_secret' => get_option( 'wompi_integrity_secret', '' ),
            'events_secret'    => get_option( 'wompi_events_secret', '' ),
        );
    }

    /**
     * Check if Wompi is enabled and configured.
     */
    public static function is_active() {
        $s = self::get_settings();
        return $s['enabled'] === '1' && ! empty( $s['public_key'] ) && ! empty( $s['integrity_secret'] );
    }

    /**
     * Get API base URL.
     */
    public static function api_url() {
        return get_option( 'wompi_sandbox', '1' ) === '1' ? self::SANDBOX_API : self::PRODUCTION_API;
    }

    /**
     * Generate integrity signature.
     * Concatenation: reference + amountInCents + currency + integritySecret
     */
    public static function generate_signature( $reference, $amount_cents, $currency = 'COP' ) {
        $secret = get_option( 'wompi_integrity_secret', '' );
        $concat = $reference . $amount_cents . $currency . $secret;
        return hash( 'sha256', $concat );
    }

    /**
     * Generate unique payment reference.
     */
    public static function generate_reference( $purchase_id ) {
        return 'RIFA-' . $purchase_id . '-' . bin2hex( random_bytes( 4 ) );
    }

    /**
     * AJAX: Create pending purchase and return Wompi widget params.
     */
    public function ajax_initiate_purchase() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'raffle_purchase_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Error de seguridad. Recarga la página.' ) );
        }

        if ( ! self::is_active() ) {
            wp_send_json_error( array( 'message' => 'Pasarela de pago no configurada.' ) );
        }

        $raffle_id   = isset( $_POST['raffle_id'] ) ? absint( $_POST['raffle_id'] ) : 0;
        $quantity    = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 0;
        $buyer_name  = isset( $_POST['buyer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['buyer_name'] ) ) : '';
        $buyer_email = isset( $_POST['buyer_email'] ) ? sanitize_email( wp_unslash( $_POST['buyer_email'] ) ) : '';

        if ( ! $raffle_id || ! $quantity || ! $buyer_name || ! $buyer_email ) {
            wp_send_json_error( array( 'message' => 'Todos los campos son obligatorios.' ) );
        }

        if ( ! is_email( $buyer_email ) ) {
            wp_send_json_error( array( 'message' => 'Correo electrónico no válido.' ) );
        }

        global $wpdb;
        $table_raffles   = $wpdb->prefix . 'raffles';
        $table_purchases = $wpdb->prefix . 'raffle_purchases';

        $raffle = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table_raffles} WHERE id = %d AND status = 'active'",
            $raffle_id
        ) );

        if ( ! $raffle ) {
            wp_send_json_error( array( 'message' => 'Rifa no encontrada o no activa.' ) );
        }

        $packages = json_decode( $raffle->packages, true );
        if ( ! is_array( $packages ) || ! in_array( $quantity, $packages, true ) ) {
            wp_send_json_error( array( 'message' => 'Paquete de boletos no válido.' ) );
        }

        $available = $raffle->total_tickets - $raffle->sold_tickets;
        if ( $quantity > $available ) {
            wp_send_json_error( array( 'message' => 'No hay suficientes boletos disponibles. Quedan ' . $available . '.' ) );
        }

        $total_amount = $quantity * $raffle->ticket_price;

        // Create purchase as PENDING
        $wpdb->insert( $table_purchases, array(
            'raffle_id'      => $raffle_id,
            'buyer_name'     => $buyer_name,
            'buyer_email'    => $buyer_email,
            'quantity'       => $quantity,
            'total_amount'   => $total_amount,
            'payment_status' => 'pending',
            'purchase_date'  => current_time( 'mysql' ),
        ), array( '%d', '%s', '%s', '%d', '%f', '%s', '%s' ) );

        $purchase_id = $wpdb->insert_id;
        if ( ! $purchase_id ) {
            wp_send_json_error( array( 'message' => 'Error al crear la compra.' ) );
        }

        // Generate Wompi params
        $reference    = self::generate_reference( $purchase_id );
        $amount_cents = (int) round( $total_amount * 100 );
        $currency     = 'COP';
        $signature    = self::generate_signature( $reference, $amount_cents, $currency );

        // Save reference in purchase for later verification
        $wpdb->update(
            $table_purchases,
            array( 'wompi_reference' => $reference ),
            array( 'id' => $purchase_id ),
            array( '%s' ),
            array( '%d' )
        );

        $settings = self::get_settings();

        wp_send_json_success( array(
            'purchase_id'    => $purchase_id,
            'wompi_params'   => array(
                'publicKey'      => $settings['public_key'],
                'currency'       => $currency,
                'amountInCents'  => $amount_cents,
                'reference'      => $reference,
                'signature'      => array( 'integrity' => $signature ),
                'redirectUrl'    => add_query_arg( array(
                    'wompi_return'  => 1,
                    'purchase_id'   => $purchase_id,
                ), get_permalink() ),
                'customerData'   => array(
                    'email'    => $buyer_email,
                    'fullName' => $buyer_name,
                ),
            ),
        ) );
    }

    /**
     * AJAX: Confirm purchase after Wompi widget callback.
     */
    public function ajax_confirm_purchase() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'raffle_purchase_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Error de seguridad.' ) );
        }

        $purchase_id    = isset( $_POST['purchase_id'] ) ? absint( $_POST['purchase_id'] ) : 0;
        $transaction_id = isset( $_POST['transaction_id'] ) ? sanitize_text_field( wp_unslash( $_POST['transaction_id'] ) ) : '';

        if ( ! $purchase_id || ! $transaction_id ) {
            wp_send_json_error( array( 'message' => 'Datos de transacción incompletos.' ) );
        }

        global $wpdb;
        $table_purchases = $wpdb->prefix . 'raffle_purchases';

        $purchase = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table_purchases} WHERE id = %d",
            $purchase_id
        ) );

        if ( ! $purchase ) {
            wp_send_json_error( array( 'message' => 'Compra no encontrada.' ) );
        }

        // Already completed
        if ( $purchase->payment_status === 'completed' ) {
            $tickets = $wpdb->get_col( $wpdb->prepare(
                "SELECT ticket_number FROM {$wpdb->prefix}raffle_tickets WHERE purchase_id = %d ORDER BY ticket_number",
                $purchase_id
            ) );
            $raffle = $wpdb->get_row( $wpdb->prepare(
                "SELECT total_tickets FROM {$wpdb->prefix}raffles WHERE id = %d",
                $purchase->raffle_id
            ) );
            $total_digits = $raffle ? strlen( (string) $raffle->total_tickets ) : 3;
            $formatted = array_map( function( $n ) use ( $total_digits ) {
                return str_pad( $n, $total_digits, '0', STR_PAD_LEFT );
            }, $tickets );

            wp_send_json_success( array(
                'message' => '¡Compra ya confirmada!',
                'tickets' => $formatted,
                'already_confirmed' => true,
            ) );
        }

        // Verify transaction with Wompi API
        $api_url  = self::api_url() . '/transactions/' . $transaction_id;
        $response = wp_remote_get( $api_url, array( 'timeout' => 15 ) );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => 'Error al verificar la transacción.' ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['data'] ) ) {
            wp_send_json_error( array( 'message' => 'Transacción no encontrada en Wompi.' ) );
        }

        $tx = $body['data'];

        // Verify reference matches
        if ( $tx['reference'] !== $purchase->wompi_reference ) {
            wp_send_json_error( array( 'message' => 'La referencia de pago no coincide.' ) );
        }

        // Verify amount
        $expected_cents = (int) round( $purchase->total_amount * 100 );
        if ( (int) $tx['amount_in_cents'] !== $expected_cents ) {
            wp_send_json_error( array( 'message' => 'El monto de pago no coincide.' ) );
        }

        // Check transaction status
        if ( $tx['status'] !== 'APPROVED' ) {
            $status_map = array(
                'DECLINED' => 'Pago rechazado.',
                'VOIDED'   => 'Pago anulado.',
                'ERROR'    => 'Error en el pago.',
                'PENDING'  => 'Pago pendiente. Espera unos minutos.',
            );
            $msg = isset( $status_map[ $tx['status'] ] ) ? $status_map[ $tx['status'] ] : 'Estado de pago: ' . $tx['status'];

            // Update purchase status
            $wpdb->update(
                $table_purchases,
                array(
                    'payment_status'    => strtolower( $tx['status'] ),
                    'wompi_transaction' => $transaction_id,
                ),
                array( 'id' => $purchase_id ),
                array( '%s', '%s' ),
                array( '%d' )
            );

            wp_send_json_error( array( 'message' => $msg ) );
        }

        // APPROVED — process the purchase
        $result = self::process_approved_purchase( $purchase_id, $transaction_id );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( $result );
    }

    /**
     * Process an approved purchase: generate tickets, send email, update status.
     */
    public static function process_approved_purchase( $purchase_id, $transaction_id ) {
        global $wpdb;
        $table_purchases = $wpdb->prefix . 'raffle_purchases';

        $purchase = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table_purchases} WHERE id = %d",
            $purchase_id
        ) );

        if ( ! $purchase ) {
            return new WP_Error( 'not_found', 'Compra no encontrada.' );
        }

        // Already processed
        if ( $purchase->payment_status === 'completed' ) {
            $tickets = $wpdb->get_col( $wpdb->prepare(
                "SELECT ticket_number FROM {$wpdb->prefix}raffle_tickets WHERE purchase_id = %d ORDER BY ticket_number",
                $purchase_id
            ) );
            $raffle = $wpdb->get_row( $wpdb->prepare(
                "SELECT total_tickets FROM {$wpdb->prefix}raffles WHERE id = %d",
                $purchase->raffle_id
            ) );
            $total_digits = $raffle ? strlen( (string) $raffle->total_tickets ) : 3;
            $formatted = array_map( function( $n ) use ( $total_digits ) {
                return str_pad( $n, $total_digits, '0', STR_PAD_LEFT );
            }, $tickets );
            return array( 'message' => '¡Compra ya confirmada!', 'tickets' => $formatted );
        }

        // TRANSACCIÓN ATÓMICA: tickets + status update en una sola operación
        $wpdb->query( 'START TRANSACTION' );

        // Generate tickets (transacción gestionada por el caller)
        $tickets = Raffle_Tickets::generate_tickets(
            $purchase->raffle_id,
            $purchase_id,
            $purchase->quantity,
            $purchase->buyer_email,
            false
        );

        if ( is_wp_error( $tickets ) ) {
            $wpdb->query( 'ROLLBACK' );
            return $tickets;
        }

        // Update purchase to completed (dentro de la misma transacción)
        $wpdb->update(
            $table_purchases,
            array(
                'payment_status'    => 'completed',
                'wompi_transaction' => $transaction_id,
            ),
            array( 'id' => $purchase_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        $wpdb->query( 'COMMIT' );

        // Send email (fuera de transacción — no debe bloquear el commit)
        $raffle = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}raffles WHERE id = %d",
            $purchase->raffle_id
        ) );
        Raffle_Email::send_purchase_confirmation( $purchase_id, $raffle, $tickets );

        // Format tickets
        $total_digits = $raffle ? strlen( (string) $raffle->total_tickets ) : 3;
        $formatted = array_map( function( $n ) use ( $total_digits ) {
            return str_pad( $n, $total_digits, '0', STR_PAD_LEFT );
        }, $tickets );

        return array(
            'message' => '¡Compra realizada con éxito!',
            'tickets' => $formatted,
        );
    }

    /**
     * Register REST webhook endpoint for Wompi events.
     */
    public function register_webhook() {
        register_rest_route( 'raffle-system/v1', '/wompi-webhook', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_webhook' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * Handle incoming Wompi webhook event.
     */
    public function handle_webhook( $request ) {
        $body = $request->get_json_params();

        if ( empty( $body['event'] ) || $body['event'] !== 'transaction.updated' ) {
            return new WP_REST_Response( array( 'status' => 'ignored' ), 200 );
        }

        if ( empty( $body['data']['transaction'] ) ) {
            return new WP_REST_Response( array( 'status' => 'no_data' ), 200 );
        }

        $tx = $body['data']['transaction'];

        // Verify event signature using checksum
        $events_secret = get_option( 'wompi_events_secret', '' );
        if ( ! empty( $events_secret ) && ! empty( $body['signature']['checksum'] ) ) {
            // Wompi checksum = SHA256 of concatenated event properties + events_secret
            $properties = isset( $body['signature']['properties'] ) ? $body['signature']['properties'] : array();
            $values = array();
            foreach ( $properties as $prop ) {
                $keys = explode( '.', $prop );
                $val  = $body;
                foreach ( $keys as $k ) {
                    $val = isset( $val[ $k ] ) ? $val[ $k ] : '';
                }
                $values[] = $val;
            }
            $values[]        = $body['timestamp'];
            $values[]        = $events_secret;
            $concat          = implode( '', $values );
            $expected_hash   = hash( 'sha256', $concat );

            if ( ! hash_equals( $expected_hash, $body['signature']['checksum'] ) ) {
                return new WP_REST_Response( array( 'status' => 'invalid_signature' ), 401 );
            }
        }

        if ( $tx['status'] !== 'APPROVED' ) {
            // Update purchase status if declined
            global $wpdb;
            $purchase = $wpdb->get_row( $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}raffle_purchases WHERE wompi_reference = %s",
                $tx['reference']
            ) );
            if ( $purchase ) {
                $wpdb->update(
                    $wpdb->prefix . 'raffle_purchases',
                    array(
                        'payment_status'    => strtolower( $tx['status'] ),
                        'wompi_transaction' => $tx['id'],
                    ),
                    array( 'id' => $purchase->id ),
                    array( '%s', '%s' ),
                    array( '%d' )
                );
            }
            return new WP_REST_Response( array( 'status' => 'not_approved' ), 200 );
        }

        // Find purchase by reference
        global $wpdb;
        $purchase = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}raffle_purchases WHERE wompi_reference = %s",
            $tx['reference']
        ) );

        if ( ! $purchase ) {
            return new WP_REST_Response( array( 'status' => 'purchase_not_found' ), 200 );
        }

        // Process if not yet completed
        if ( $purchase->payment_status !== 'completed' ) {
            self::process_approved_purchase( $purchase->id, $tx['id'] );
        }

        return new WP_REST_Response( array( 'status' => 'ok' ), 200 );
    }
}
