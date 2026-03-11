<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Raffle_WooCommerce {

    public function __construct() {
        // AJAX: create WooCommerce order and return pay URL
        add_action( 'wp_ajax_raffle_create_order', array( $this, 'ajax_create_order' ) );
        add_action( 'wp_ajax_nopriv_raffle_create_order', array( $this, 'ajax_create_order' ) );

        // On payment complete → generate tickets
        add_action( 'woocommerce_payment_complete', array( $this, 'on_payment_complete' ) );
        add_action( 'woocommerce_order_status_completed', array( $this, 'on_payment_complete' ) );
        add_action( 'woocommerce_order_status_processing', array( $this, 'on_payment_complete' ) );

        // Custom thank-you page content for raffle orders
        add_action( 'woocommerce_thankyou', array( $this, 'thankyou_raffle_tickets' ) );

        // Show raffle info in admin order details
        add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'admin_order_meta' ) );
    }

    /**
     * Check if WooCommerce is installed and active.
     */
    public static function is_available() {
        return class_exists( 'WooCommerce' );
    }

    /**
     * AJAX: Create a WooCommerce order from the raffle purchase form.
     */
    public function ajax_create_order() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'raffle_purchase_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Error de seguridad. Recarga la página.' ) );
        }

        if ( ! self::is_available() ) {
            wp_send_json_error( array( 'message' => 'WooCommerce no está instalado.' ) );
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

        // Split buyer name into first/last
        $name_parts = explode( ' ', $buyer_name, 2 );
        $first_name = $name_parts[0];
        $last_name  = isset( $name_parts[1] ) ? $name_parts[1] : '';

        // Create WooCommerce order
        $order = wc_create_order();

        if ( is_wp_error( $order ) ) {
            wp_send_json_error( array( 'message' => 'Error al crear el pedido.' ) );
        }

        // Add line item (without backing product)
        $item = new WC_Order_Item_Product();
        $item->set_name( 'Boletos de Rifa — ' . $raffle->title . ' (x' . $quantity . ')' );
        $item->set_quantity( $quantity );
        $item->set_subtotal( $total_amount );
        $item->set_total( $total_amount );
        $order->add_item( $item );

        // Set billing details
        $order->set_billing_first_name( $first_name );
        $order->set_billing_last_name( $last_name );
        $order->set_billing_email( $buyer_email );

        // Set totals
        $order->set_total( $total_amount );

        // Store raffle meta on the order
        $order->update_meta_data( '_raffle_id', $raffle_id );
        $order->update_meta_data( '_raffle_quantity', $quantity );
        $order->update_meta_data( '_raffle_buyer_name', $buyer_name );
        $order->update_meta_data( '_raffle_buyer_email', $buyer_email );
        $order->update_meta_data( '_is_raffle_order', 'yes' );

        // Set status to pending payment
        $order->set_status( 'pending', 'Pedido de rifa creado desde formulario.' );
        $order->save();

        $order_id = $order->get_id();

        // Create pending purchase record linked to the WC order
        $wpdb->insert( $table_purchases, array(
            'raffle_id'      => $raffle_id,
            'buyer_name'     => $buyer_name,
            'buyer_email'    => $buyer_email,
            'quantity'       => $quantity,
            'total_amount'   => $total_amount,
            'payment_status' => 'pending',
            'wc_order_id'    => $order_id,
            'purchase_date'  => current_time( 'mysql' ),
        ), array( '%d', '%s', '%s', '%d', '%f', '%s', '%d', '%s' ) );

        $purchase_id = $wpdb->insert_id;

        if ( ! $purchase_id ) {
            $order->update_status( 'cancelled', 'Error al crear registro de compra.' );
            wp_send_json_error( array( 'message' => 'Error al registrar la compra.' ) );
        }

        // Link purchase ID back to the order
        $order->update_meta_data( '_raffle_purchase_id', $purchase_id );
        $order->save();

        // Generate order-pay URL (shows only payment methods, not full checkout)
        $pay_url = $order->get_checkout_payment_url();

        wp_send_json_success( array(
            'pay_url'     => $pay_url,
            'order_id'    => $order_id,
            'purchase_id' => $purchase_id,
        ) );
    }

    /**
     * Hook: On payment complete, generate raffle tickets.
     */
    public function on_payment_complete( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        // Only process raffle orders
        if ( $order->get_meta( '_is_raffle_order' ) !== 'yes' ) {
            return;
        }

        // Already processed?
        if ( $order->get_meta( '_raffle_tickets_generated' ) === 'yes' ) {
            return;
        }

        $purchase_id = (int) $order->get_meta( '_raffle_purchase_id' );
        $raffle_id   = (int) $order->get_meta( '_raffle_id' );
        $quantity    = (int) $order->get_meta( '_raffle_quantity' );
        $buyer_email = $order->get_meta( '_raffle_buyer_email' );

        if ( ! $purchase_id || ! $raffle_id || ! $quantity ) {
            return;
        }

        global $wpdb;
        $table_purchases = $wpdb->prefix . 'raffle_purchases';

        // Check purchase not already completed
        $purchase = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table_purchases} WHERE id = %d",
            $purchase_id
        ) );

        if ( ! $purchase || $purchase->payment_status === 'completed' ) {
            // Mark as generated to prevent re-processing
            $order->update_meta_data( '_raffle_tickets_generated', 'yes' );
            $order->save();
            return;
        }

        // TRANSACCIÓN ATÓMICA: tickets + status update
        $wpdb->query( 'START TRANSACTION' );

        $tickets = Raffle_Tickets::generate_tickets( $raffle_id, $purchase_id, $quantity, $buyer_email, false );

        if ( is_wp_error( $tickets ) ) {
            $wpdb->query( 'ROLLBACK' );
            $order->add_order_note( 'Error generando boletos: ' . $tickets->get_error_message() );
            return;
        }

        // Update purchase to completed
        $wpdb->update(
            $table_purchases,
            array(
                'payment_status' => 'completed',
                'wc_order_id'    => $order_id,
            ),
            array( 'id' => $purchase_id ),
            array( '%s', '%d' ),
            array( '%d' )
        );

        $wpdb->query( 'COMMIT' );

        // Mark as processed on the order
        $order->update_meta_data( '_raffle_tickets_generated', 'yes' );

        // Format tickets for order note
        $raffle = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}raffles WHERE id = %d",
            $raffle_id
        ) );

        $total_digits = $raffle ? strlen( (string) $raffle->total_tickets ) : 3;
        $formatted    = array_map( function ( $n ) use ( $total_digits ) {
            return str_pad( $n, $total_digits, '0', STR_PAD_LEFT );
        }, $tickets );

        // Store formatted tickets on order
        $order->update_meta_data( '_raffle_ticket_numbers', $formatted );
        $order->save();

        // Add order note with ticket numbers
        $order->add_order_note(
            'Boletos de rifa generados: ' . implode( ', ', $formatted )
        );

        // Send confirmation email
        Raffle_Email::send_purchase_confirmation( $purchase_id, $raffle, $tickets );
    }

    /**
     * Show raffle tickets on the WooCommerce thank-you page.
     */
    public function thankyou_raffle_tickets( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order || $order->get_meta( '_is_raffle_order' ) !== 'yes' ) {
            return;
        }

        $tickets = $order->get_meta( '_raffle_ticket_numbers' );
        $raffle_id = (int) $order->get_meta( '_raffle_id' );

        global $wpdb;
        $raffle = $wpdb->get_row( $wpdb->prepare(
            "SELECT title FROM {$wpdb->prefix}raffles WHERE id = %d",
            $raffle_id
        ) );

        if ( ! empty( $tickets ) && is_array( $tickets ) ) {
            echo '<div class="raffle-thankyou-tickets" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:30px;border-radius:16px;margin:20px 0;text-align:center;">';
            echo '<h2 style="color:#fff;margin:0 0 8px;">🎉 ¡Tus Boletos de Rifa!</h2>';
            if ( $raffle ) {
                echo '<p style="opacity:0.9;margin:0 0 16px;">' . esc_html( $raffle->title ) . '</p>';
            }
            echo '<div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;">';
            foreach ( $tickets as $ticket ) {
                echo '<span style="background:rgba(255,255,255,0.2);padding:8px 16px;border-radius:8px;font-weight:700;font-size:18px;backdrop-filter:blur(4px);">' . esc_html( $ticket ) . '</span>';
            }
            echo '</div>';
            echo '<p style="opacity:0.8;margin:16px 0 0;font-size:14px;">📧 También se envió un correo de confirmación con tus números.</p>';
            echo '</div>';
        } else {
            // Payment may still be processing
            $status = $order->get_status();
            if ( in_array( $status, array( 'pending', 'on-hold' ), true ) ) {
                echo '<div style="background:#fff3cd;color:#856404;padding:16px;border-radius:8px;margin:20px 0;">';
                echo '<p><strong>⏳ Tu pago está siendo procesado.</strong></p>';
                echo '<p>Recibirás tus boletos por correo electrónico una vez se confirme el pago.</p>';
                echo '</div>';
            }
        }
    }

    /**
     * Show raffle meta in WooCommerce admin order page.
     */
    public function admin_order_meta( $order ) {
        if ( $order->get_meta( '_is_raffle_order' ) !== 'yes' ) {
            return;
        }

        $raffle_id   = $order->get_meta( '_raffle_id' );
        $quantity    = $order->get_meta( '_raffle_quantity' );
        $purchase_id = $order->get_meta( '_raffle_purchase_id' );
        $tickets     = $order->get_meta( '_raffle_ticket_numbers' );

        echo '<div class="order_data_column" style="border-left:2px solid #667eea;padding-left:12px;margin-top:12px;">';
        echo '<h3>Datos de Rifa</h3>';
        echo '<p><strong>Rifa ID:</strong> ' . esc_html( $raffle_id ) . '</p>';
        echo '<p><strong>Cantidad:</strong> ' . esc_html( $quantity ) . ' boletos</p>';
        echo '<p><strong>Compra ID:</strong> ' . esc_html( $purchase_id ) . '</p>';

        if ( ! empty( $tickets ) && is_array( $tickets ) ) {
            echo '<p><strong>Boletos:</strong> ' . esc_html( implode( ', ', $tickets ) ) . '</p>';
        }

        echo '</div>';
    }
}
