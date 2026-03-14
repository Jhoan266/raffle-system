<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Raffle_Email {

    /**
     * Send purchase confirmation email with ticket numbers.
     */
    public static function send_purchase_confirmation( $purchase_id, $raffle, $tickets ) {
        global $wpdb;
        $table_purchases = $wpdb->prefix . 'raffle_purchases';

        $purchase = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table_purchases} WHERE id = %d",
            $purchase_id
        ) );

        if ( ! $purchase ) {
            return;
        }

        $formatted    = array_map( function ( $num ) use ( $raffle ) {
            return Raffle_Tickets::format_ticket_number( $num, $raffle->total_tickets );
        }, $tickets );

        $to          = $purchase->buyer_email;
        $subject     = 'Confirmación de compra — ' . $raffle->title;
        $ticket_list = implode( ' &nbsp;·&nbsp; ', $formatted );
        $draw_date   = $raffle->draw_date
            ? date_i18n( 'd/m/Y H:i', strtotime( $raffle->draw_date ) )
            : 'Por definir';

        $buyer_name  = esc_html( $purchase->buyer_name );
        $raffle_title = esc_html( $raffle->title );
        $qty         = (int) $purchase->quantity;
        $total       = esc_html( number_format( $purchase->total_amount, 2 ) );

        $message = "
        <html>
        <body style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#f4f4f4;'>
            <div style='background:#4CAF50;color:#fff;padding:25px;text-align:center;border-radius:8px 8px 0 0;'>
                <h1 style='margin:0;font-size:24px;'>¡Compra Confirmada!</h1>
            </div>
            <div style='padding:25px;background:#fff;'>
                <p>Hola <strong>{$buyer_name}</strong>,</p>
                <p>Tu compra para la rifa <strong>{$raffle_title}</strong> ha sido procesada correctamente.</p>

                <div style='background:#f8f9fa;padding:15px;border-radius:8px;margin:20px 0;'>
                    <h3 style='color:#333;margin-top:0;'>Detalles de la compra</h3>
                    <p><strong>Rifa:</strong> {$raffle_title}</p>
                    <p><strong>Boletos:</strong> {$qty}</p>
                    <p><strong>Total pagado:</strong> \${$total}</p>
                    <p><strong>Fecha del sorteo:</strong> {$draw_date}</p>
                </div>

                <div style='background:#e8f5e9;padding:15px;border-radius:8px;margin:20px 0;border:2px solid #4CAF50;'>
                    <h3 style='color:#2e7d32;margin-top:0;'>Tus números de boleto</h3>
                    <p style='font-size:18px;font-weight:bold;color:#1b5e20;line-height:1.8;'>{$ticket_list}</p>
                </div>

                <p style='color:#888;font-size:13px;text-align:center;'>¡Buena suerte! 🍀</p>
            </div>
        </body>
        </html>";

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        wp_mail( $to, $subject, $message, $headers );
    }
}
