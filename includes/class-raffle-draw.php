<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Raffle_Draw {

    public function __construct() {
        add_action( 'wp_ajax_raffle_draw', array( $this, 'handle_draw' ) );
    }

    public function handle_draw() {
        // Only admins can draw
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'No tienes permisos para realizar el sorteo.' ) );
        }

        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'raffle_draw_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Error de seguridad.' ) );
        }

        $raffle_id = isset( $_POST['raffle_id'] ) ? absint( $_POST['raffle_id'] ) : 0;

        if ( ! $raffle_id ) {
            wp_send_json_error( array( 'message' => 'Rifa no válida.' ) );
        }

        global $wpdb;
        $table_raffles  = $wpdb->prefix . 'raffles';
        $table_tickets  = $wpdb->prefix . 'raffle_tickets';

        $raffle = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table_raffles} WHERE id = %d",
            $raffle_id
        ) );

        if ( ! $raffle ) {
            wp_send_json_error( array( 'message' => 'Rifa no encontrada.' ) );
        }

        if ( (int) $raffle->sold_tickets === 0 ) {
            wp_send_json_error( array( 'message' => 'No se han vendido boletos aún.' ) );
        }

        // TRANSACCIÓN: lock + sorteo atómico para prevenir sorteos concurrentes
        $wpdb->query( 'START TRANSACTION' );

        // Lock raffle row — previene sorteos simultáneos
        $raffle = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table_raffles} WHERE id = %d FOR UPDATE",
            $raffle_id
        ) );

        if ( $raffle->winner_ticket_id ) {
            $wpdb->query( 'COMMIT' );
            wp_send_json_error( array( 'message' => 'Esta rifa ya tiene un ganador seleccionado.' ) );
        }

        // Get all sold tickets
        $tickets = $wpdb->get_results( $wpdb->prepare(
            "SELECT t.*, p.buyer_name
             FROM {$table_tickets} t
             JOIN {$wpdb->prefix}raffle_purchases p ON t.purchase_id = p.id
             WHERE t.raffle_id = %d",
            $raffle_id
        ) );

        if ( empty( $tickets ) ) {
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( array( 'message' => 'No hay boletos vendidos.' ) );
        }

        // Select random winner using secure random_int()
        $winner_index  = random_int( 0, count( $tickets ) - 1 );
        $winner_ticket = $tickets[ $winner_index ];

        // Save winner and finalize raffle (dentro de transacción)
        $wpdb->update(
            $table_raffles,
            array(
                'winner_ticket_id' => $winner_ticket->id,
                'status'           => 'finished',
            ),
            array( 'id' => $raffle_id ),
            array( '%d', '%s' ),
            array( '%d' )
        );

        $wpdb->query( 'COMMIT' );

        $total_digits = strlen( (string) $raffle->total_tickets );

        wp_send_json_success( array(
            'message'       => '¡Ganador seleccionado!',
            'ticket_number' => str_pad( $winner_ticket->ticket_number, $total_digits, '0', STR_PAD_LEFT ),
            'buyer_name'    => $winner_ticket->buyer_name,
            'buyer_email'   => $winner_ticket->buyer_email,
        ) );
    }
}
