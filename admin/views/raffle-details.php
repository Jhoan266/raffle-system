<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$total_tickets = $raffle->total_tickets;
$revenue      = $wpdb->get_var( $wpdb->prepare(
    "SELECT SUM(total_amount) FROM {$wpdb->prefix}raffle_purchases WHERE raffle_id = %d AND payment_status = 'completed'",
    $raffle->id
) );
$remaining = $raffle->total_tickets - $raffle->sold_tickets;
$progress  = ( $raffle->total_tickets > 0 ) ? round( ( $raffle->sold_tickets / $raffle->total_tickets ) * 100 ) : 0;
$progress_class = 'blue';
if ( $progress >= 100 ) { $progress_class = 'green'; }
elseif ( $progress >= 75 ) { $progress_class = 'yellow'; }
?>

<div class="wrap rs-wrap">
    <div class="rs-page-header">
        <h1 class="rs-page-title">
            <i class="fas fa-ticket-alt"></i>
            <?php echo esc_html( $raffle->title ); ?>
            <span class="rs-badge rs-badge-<?php echo esc_attr( $raffle->status ); ?>">
                <?php echo $raffle->status === 'active' ? '● Activa' : '● Finalizada'; ?>
            </span>
        </h1>
        <div class="rs-header-actions">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=raffle-system' ) ); ?>" class="rs-btn rs-btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <a href="<?php echo esc_url( admin_url( "admin.php?page=raffle-system&action=edit&id={$raffle->id}" ) ); ?>" class="rs-btn rs-btn-primary">
                <i class="fas fa-edit"></i> Editar
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="rs-stats-grid">
        <div class="rs-stat-card border-blue">
            <div class="rs-stat-icon blue"><i class="fas fa-ticket-alt"></i></div>
            <div class="rs-stat-body">
                <div class="rs-stat-label">Boletos Vendidos</div>
                <div class="rs-stat-number"><?php echo esc_html( $raffle->sold_tickets ); ?> / <?php echo esc_html( $raffle->total_tickets ); ?></div>
            </div>
        </div>
        <div class="rs-stat-card border-orange">
            <div class="rs-stat-icon orange"><i class="fas fa-layer-group"></i></div>
            <div class="rs-stat-body">
                <div class="rs-stat-label">Restantes</div>
                <div class="rs-stat-number"><?php echo esc_html( $remaining ); ?></div>
            </div>
        </div>
        <div class="rs-stat-card border-green">
            <div class="rs-stat-icon green"><i class="fas fa-dollar-sign"></i></div>
            <div class="rs-stat-body">
                <div class="rs-stat-label">Recaudado</div>
                <div class="rs-stat-number">$<?php echo esc_html( number_format( $revenue ?: 0, 0, ',', '.' ) ); ?></div>
            </div>
        </div>
        <div class="rs-stat-card border-purple">
            <div class="rs-stat-icon purple"><i class="fas fa-calendar-alt"></i></div>
            <div class="rs-stat-body">
                <div class="rs-stat-label">Fecha Sorteo</div>
                <div class="rs-stat-number" style="font-size:16px;">
                    <?php echo $raffle->draw_date ? esc_html( date_i18n( 'd/m/Y H:i', strtotime( $raffle->draw_date ) ) ) : '—'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress -->
    <div class="rs-card">
        <h3 class="rs-card-title"><i class="fas fa-chart-bar"></i> Progreso de Ventas</h3>
        <div class="rs-progress rs-progress-lg">
            <div class="rs-progress-fill <?php echo esc_attr( $progress_class ); ?>"
                 style="width:<?php echo esc_attr( $progress ); ?>%;">
                <?php echo esc_html( $progress ); ?>%
            </div>
        </div>
    </div>

    <!-- Shortcode -->
    <div class="rs-shortcode-box">
        <i class="fas fa-code"></i>
        <strong>Shortcode:</strong>
        <code>[raffle id="<?php echo esc_attr( $raffle->id ); ?>"]</code>
    </div>

    <!-- Winner / Draw -->
    <?php if ( $winner ) : ?>
        <div class="rs-winner-banner">
            <h3><i class="fas fa-trophy"></i> ¡Ganador Seleccionado!</h3>
            <div class="rs-winner-info">
            <div class="rs-winner-ticket">#<?php echo esc_html( Raffle_Tickets::format_ticket_number( $winner->ticket_number, $raffle->total_tickets ) ); ?></div>
                <div class="rs-winner-detail">
                    <small>Nombre</small>
                    <strong><?php echo esc_html( $winner->buyer_name ); ?></strong>
                </div>
                <div class="rs-winner-detail">
                    <small>Email</small>
                    <strong><?php echo esc_html( $winner->buyer_email ); ?></strong>
                </div>
            </div>
        </div>
    <?php else : ?>
        <div class="rs-card">
            <h3 class="rs-card-title"><i class="fas fa-dice"></i> Sorteo</h3>
            <?php if ( $raffle->sold_tickets > 0 ) : ?>
                <button id="draw-winner-btn" class="rs-btn rs-btn-primary rs-btn-lg"
                        data-raffle-id="<?php echo esc_attr( $raffle->id ); ?>">
                    <i class="fas fa-random"></i> Seleccionar Ganador
                </button>
                <div id="draw-result" style="display:none;"></div>
            <?php else : ?>
                <p style="color:var(--rs-text-muted);">
                    <i class="fas fa-info-circle"></i> No se han vendido boletos aún. El sorteo estará disponible cuando haya ventas.
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Duplicate Control -->
    <div class="rs-card">
        <h3 class="rs-card-title"><i class="fas fa-search"></i> Control de Boletos Duplicados</h3>
        <p class="rs-help" style="margin-bottom:16px;">
            Si dos clientes compran al mismo tiempo, podría haber solapamiento mínimo.
            Usa estas herramientas para detectar y corregir cualquier duplicado.
        </p>

        <div style="margin-bottom:16px;">
            <label class="rs-checkbox-label">
                <input type="checkbox" id="raffle-auto-fix-toggle"
                       <?php checked( get_option( 'raffle_auto_fix_duplicates', '1' ), '1' ); ?>>
                <div>
                    <strong>Corrección automática tras cada compra</strong>
                    <span class="rs-help" style="display:block;">El sistema comprobará y corregirá duplicados después de crear cada pedido.</span>
                </div>
            </label>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button id="check-duplicates-btn" class="rs-btn rs-btn-secondary"
                    data-raffle-id="<?php echo esc_attr( $raffle->id ); ?>">
                <i class="fas fa-search"></i> Comprobar Duplicados
            </button>
            <button id="fix-duplicates-btn" class="rs-btn rs-btn-primary" style="display:none;"
                    data-raffle-id="<?php echo esc_attr( $raffle->id ); ?>">
                <i class="fas fa-wrench"></i> Corregir Duplicados
            </button>
        </div>
        <div id="duplicates-result" style="margin-top:15px;"></div>
    </div>

    <!-- Purchases Table -->
    <div class="rs-card">
        <h3 class="rs-card-title"><i class="fas fa-users"></i> Compradores (<?php echo count( $purchases ); ?>)</h3>
        <table class="rs-table">
            <thead>
                <tr>
                    <th>ID</th>
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
                    <tr>
                        <td colspan="8" class="rs-table-empty">
                            <i class="fas fa-inbox"></i>
                            No hay compras registradas.
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $purchases as $p ) :
                        $tickets = $wpdb->get_col( $wpdb->prepare(
                            "SELECT ticket_number FROM {$wpdb->prefix}raffle_tickets WHERE purchase_id = %d ORDER BY ticket_number",
                            $p->id
                        ) );
                        $formatted = array_map( function ( $n ) use ( $raffle ) {
                            return Raffle_Tickets::format_ticket_number( $n, $raffle->total_tickets );
                        }, $tickets );
                    ?>
                        <tr>
                            <td><strong>#<?php echo esc_html( $p->id ); ?></strong></td>
                            <td><?php echo esc_html( $p->buyer_name ); ?></td>
                            <td><?php echo esc_html( $p->buyer_email ); ?></td>
                            <td><?php echo esc_html( $p->quantity ); ?></td>
                            <td><strong>$<?php echo esc_html( number_format( $p->total_amount, 0, ',', '.' ) ); ?></strong></td>
                            <td>
                                <span class="rs-badge rs-badge-<?php echo esc_attr( $p->payment_status ); ?>">
                                    <?php echo esc_html( ucfirst( $p->payment_status ) ); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $p->purchase_date ) ) ); ?></td>
                            <td>
                                <div class="rs-ticket-list">
                                    <?php foreach ( $formatted as $num ) : ?>
                                        <span class="rs-ticket-num"><?php echo esc_html( $num ); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
