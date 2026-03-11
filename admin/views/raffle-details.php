<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$total_digits = strlen( (string) $raffle->total_tickets );
$revenue      = $wpdb->get_var( $wpdb->prepare(
    "SELECT SUM(total_amount) FROM {$wpdb->prefix}raffle_purchases WHERE raffle_id = %d AND payment_status = 'completed'",
    $raffle->id
) );
$remaining = $raffle->total_tickets - $raffle->sold_tickets;
$progress  = ( $raffle->total_tickets > 0 ) ? round( ( $raffle->sold_tickets / $raffle->total_tickets ) * 100 ) : 0;
?>

<div class="wrap">
    <h1>
        <?php echo esc_html( $raffle->title ); ?>
        <span class="raffle-status raffle-status--<?php echo esc_attr( $raffle->status ); ?>">
            <?php echo $raffle->status === 'active' ? 'Activa' : 'Finalizada'; ?>
        </span>
    </h1>

    <a href="<?php echo esc_url( admin_url( 'admin.php?page=raffle-system' ) ); ?>" class="page-title-action">← Volver</a>
    <a href="<?php echo esc_url( admin_url( "admin.php?page=raffle-system&action=edit&id={$raffle->id}" ) ); ?>" class="page-title-action">Editar</a>

    <!-- Estadísticas -->
    <div class="raffle-stats-grid">
        <div class="raffle-stat-card">
            <h3>Boletos Vendidos</h3>
            <span class="raffle-stat-number"><?php echo esc_html( $raffle->sold_tickets ); ?> / <?php echo esc_html( $raffle->total_tickets ); ?></span>
            <div class="raffle-progress-bar">
                <div class="raffle-progress-fill" style="width:<?php echo esc_attr( $progress ); ?>%"></div>
            </div>
        </div>
        <div class="raffle-stat-card">
            <h3>Boletos Restantes</h3>
            <span class="raffle-stat-number"><?php echo esc_html( $remaining ); ?></span>
        </div>
        <div class="raffle-stat-card">
            <h3>Dinero Recaudado</h3>
            <span class="raffle-stat-number">$<?php echo esc_html( number_format( $revenue ?: 0, 2 ) ); ?></span>
        </div>
        <div class="raffle-stat-card">
            <h3>Fecha del Sorteo</h3>
            <span class="raffle-stat-number" style="font-size:18px;">
                <?php echo $raffle->draw_date ? esc_html( date_i18n( 'd/m/Y H:i', strtotime( $raffle->draw_date ) ) ) : '—'; ?>
            </span>
        </div>
    </div>

    <!-- Shortcode -->
    <div class="raffle-shortcode-box">
        <strong>Shortcode:</strong> <code>[raffle id="<?php echo esc_attr( $raffle->id ); ?>"]</code>
    </div>

    <!-- Sorteo -->
    <div class="raffle-draw-section">
        <h2>Sorteo</h2>
        <?php if ( $winner ) : ?>
            <div class="raffle-winner-card">
                <h3>🏆 Ganador</h3>
                <p><strong>Nombre:</strong> <?php echo esc_html( $winner->buyer_name ); ?></p>
                <p><strong>Email:</strong> <?php echo esc_html( $winner->buyer_email ); ?></p>
                <p><strong>Boleto:</strong> #<?php echo esc_html( str_pad( $winner->ticket_number, $total_digits, '0', STR_PAD_LEFT ) ); ?></p>
            </div>
        <?php elseif ( $raffle->sold_tickets > 0 ) : ?>
            <button id="draw-winner-btn" class="button button-primary button-hero"
                    data-raffle-id="<?php echo esc_attr( $raffle->id ); ?>">
                🎲 Seleccionar Ganador
            </button>
            <div id="draw-result" style="display:none;"></div>
        <?php else : ?>
            <p>No se han vendido boletos aún. El sorteo estará disponible cuando haya ventas.</p>
        <?php endif; ?>
    </div>

    <!-- Detección de Duplicados -->
    <div class="raffle-draw-section">
        <h2>🔍 Control de Boletos Duplicados</h2>
        <p class="description">
            Si dos clientes compran al mismo tiempo, podría haber solapamiento mínimo.
            Usa estas herramientas para detectar y corregir cualquier duplicado.
        </p>

        <div style="margin-top:15px;">
            <label style="display:flex;align-items:center;gap:8px;margin-bottom:15px;">
                <input type="checkbox" id="raffle-auto-fix-toggle"
                       <?php checked( get_option( 'raffle_auto_fix_duplicates', '1' ), '1' ); ?>>
                <strong>Corrección automática tras cada compra</strong>
                <span class="description"> — El sistema comprobará y corregirá duplicados después de crear cada pedido.</span>
            </label>
        </div>

        <button id="check-duplicates-btn" class="button"
                data-raffle-id="<?php echo esc_attr( $raffle->id ); ?>">
            🔎 Comprobar Duplicados
        </button>
        <button id="fix-duplicates-btn" class="button button-primary" style="display:none;"
                data-raffle-id="<?php echo esc_attr( $raffle->id ); ?>">
            🔧 Corregir Duplicados
        </button>
        <div id="duplicates-result" style="margin-top:15px;"></div>
    </div>

    <!-- Compradores -->
    <h2>Compradores (<?php echo count( $purchases ); ?>)</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:50px;">ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Boletos</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Fecha</th>
                <th>Números</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $purchases ) ) : ?>
                <tr><td colspan="8">No hay compras registradas.</td></tr>
            <?php else : ?>
                <?php foreach ( $purchases as $p ) :
                    $tickets = $wpdb->get_col( $wpdb->prepare(
                        "SELECT ticket_number FROM {$wpdb->prefix}raffle_tickets WHERE purchase_id = %d ORDER BY ticket_number",
                        $p->id
                    ) );
                    $formatted = array_map( function ( $n ) use ( $total_digits ) {
                        return str_pad( $n, $total_digits, '0', STR_PAD_LEFT );
                    }, $tickets );
                ?>
                    <tr>
                        <td><?php echo esc_html( $p->id ); ?></td>
                        <td><?php echo esc_html( $p->buyer_name ); ?></td>
                        <td><?php echo esc_html( $p->buyer_email ); ?></td>
                        <td><?php echo esc_html( $p->quantity ); ?></td>
                        <td>$<?php echo esc_html( number_format( $p->total_amount, 2 ) ); ?></td>
                        <td><?php echo esc_html( ucfirst( $p->payment_status ) ); ?></td>
                        <td><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $p->purchase_date ) ) ); ?></td>
                        <td><small><?php echo esc_html( implode( ', ', $formatted ) ); ?></small></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
