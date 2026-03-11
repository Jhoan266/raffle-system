<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Raffle_Tickets {

    /**
     * Generate unique random ticket numbers for a purchase.
     *
     * Uses random_int() for cryptographically secure randomness.
     * UNIQUE constraint in DB prevents duplicates even under concurrency.
     */
    public static function generate_tickets( $raffle_id, $purchase_id, $quantity, $buyer_email, $manage_transaction = true ) {
        global $wpdb;

        $table_tickets = $wpdb->prefix . 'raffle_tickets';
        $table_raffles = $wpdb->prefix . 'raffles';

        // Si $manage_transaction es false, el llamador ya abrió la transacción
        if ( $manage_transaction ) {
            $wpdb->query( 'START TRANSACTION' );
        }

        // SELECT ... FOR UPDATE bloquea la fila de la rifa
        // Ninguna otra compra concurrente puede leer sold_tickets hasta que esta transacción termine
        $raffle = $wpdb->get_row( $wpdb->prepare(
            "SELECT total_tickets, sold_tickets FROM {$table_raffles} WHERE id = %d FOR UPDATE",
            $raffle_id
        ) );

        if ( ! $raffle ) {
            if ( $manage_transaction ) { $wpdb->query( 'ROLLBACK' ); }
            return new WP_Error( 'invalid_raffle', 'Rifa no encontrada.' );
        }

        // Check availability (protegido por el lock)
        $available = $raffle->total_tickets - $raffle->sold_tickets;
        if ( $quantity > $available ) {
            if ( $manage_transaction ) { $wpdb->query( 'ROLLBACK' ); }
            return new WP_Error( 'not_enough_tickets', 'No hay suficientes boletos disponibles.' );
        }

        // Get already assigned numbers (lock garantiza consistencia)
        $taken = $wpdb->get_col( $wpdb->prepare(
            "SELECT ticket_number FROM {$table_tickets} WHERE raffle_id = %d",
            $raffle_id
        ) );
        $taken_set = array_flip( array_map( 'intval', $taken ) );
        $actual_available = $raffle->total_tickets - count( $taken_set );

        if ( $actual_available < $quantity ) {
            if ( $manage_transaction ) { $wpdb->query( 'ROLLBACK' ); }
            return new WP_Error( 'not_enough_tickets', 'No hay suficientes boletos disponibles.' );
        }

        // Seleccionar números aleatorios no tomados sin crear array de rango completo.
        // Usa random_int() sobre [1, total_tickets] y verifica contra $taken_set (O(1) lookup).
        // Para paquetes pequeños sobre rifas grandes, esto es mucho más eficiente que range()+array_diff().
        $selected  = array();
        $total     = $raffle->total_tickets;

        if ( $actual_available <= $quantity * 3 ) {
            // Pool casi agotado: construir array de disponibles (pequeño) es más eficiente
            $pool = array();
            for ( $n = 1; $n <= $total; $n++ ) {
                if ( ! isset( $taken_set[ $n ] ) ) {
                    $pool[] = $n;
                }
            }
            for ( $i = 0; $i < $quantity; $i++ ) {
                $index      = random_int( 0, count( $pool ) - 1 );
                $selected[] = $pool[ $index ];
                array_splice( $pool, $index, 1 );
            }
        } else {
            // Pool amplio: generar aleatorios y verificar contra set (O(1) por lookup)
            $selected_set = array();
            while ( count( $selected ) < $quantity ) {
                $num = random_int( 1, $total );
                if ( ! isset( $taken_set[ $num ] ) && ! isset( $selected_set[ $num ] ) ) {
                    $selected[]          = $num;
                    $selected_set[ $num ] = true;
                }
            }
        }

        sort( $selected );

        // Insert tickets — verificar cada inserción
        foreach ( $selected as $number ) {
            $result = $wpdb->insert( $table_tickets, array(
                'raffle_id'     => $raffle_id,
                'purchase_id'   => $purchase_id,
                'ticket_number' => $number,
                'buyer_email'   => $buyer_email,
            ), array( '%d', '%d', '%d', '%s' ) );

            if ( false === $result ) {
                if ( $manage_transaction ) { $wpdb->query( 'ROLLBACK' ); }
                return new WP_Error( 'insert_failed', 'Error al asignar boletos. Inténtalo de nuevo.' );
            }
        }

        // Update sold count
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$table_raffles} SET sold_tickets = sold_tickets + %d WHERE id = %d",
            $quantity,
            $raffle_id
        ) );

        // COMMIT — libera el lock y confirma todo
        if ( $manage_transaction ) {
            $wpdb->query( 'COMMIT' );
        }

        return $selected;
    }

    /**
     * Get tickets for a specific purchase.
     */
    public static function get_tickets_by_purchase( $purchase_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'raffle_tickets';

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE purchase_id = %d ORDER BY ticket_number ASC",
            $purchase_id
        ) );
    }
}
