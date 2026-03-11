<?php if ( ! defined( 'ABSPATH' ) ) exit;
// Determine best-value package (largest available)
$available_packages = array_filter( $packages, function( $q ) use ( $remaining ) { return $q <= $remaining; } );
$best_value_qty     = ! empty( $available_packages ) ? max( $available_packages ) : 0;
?>

<div class="raffle-container" data-raffle-id="<?php echo esc_attr( $raffle->id ); ?>">

    <?php if ( $raffle->status === 'finished' ) : ?>
        <div class="raffle-finished-banner">
            <span class="raffle-banner-icon">🏁</span>
            <span>Esta rifa ha finalizado</span>
        </div>
    <?php endif; ?>

    <!-- Hero -->
    <div class="raffle-hero">
        <?php if ( $raffle->prize_image ) : ?>
            <div class="raffle-hero-image">
                <img src="<?php echo esc_url( $raffle->prize_image ); ?>"
                     alt="<?php echo esc_attr( $raffle->title ); ?>">
                <div class="raffle-hero-overlay"></div>
            </div>
        <?php endif; ?>
        <div class="raffle-hero-body">
            <h2 class="raffle-title"><?php echo esc_html( $raffle->title ); ?></h2>
            <div class="raffle-prize-badge">
                <span class="raffle-prize-badge-label">Premio</span>
                <span class="raffle-prize-badge-value">$<?php echo esc_html( number_format( $raffle->prize_value, 2 ) ); ?></span>
            </div>
            <?php if ( $raffle->description ) : ?>
                <div class="raffle-description"><?php echo wp_kses_post( nl2br( $raffle->description ) ); ?></div>
            <?php endif; ?>
            <?php if ( $raffle->draw_date ) : ?>
                <div class="raffle-meta">
                    <span class="raffle-meta-item">📅 Sorteo: <?php echo esc_html( date_i18n( 'd \d\e F, Y — H:i', strtotime( $raffle->draw_date ) ) ); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Countdown -->
    <?php if ( $raffle->draw_date && $raffle->status === 'active' ) : ?>
        <div class="raffle-countdown-section">
            <div class="raffle-countdown-label-top">Tiempo restante</div>
            <div class="raffle-countdown" id="raffle-countdown"
                 data-draw-date="<?php echo esc_attr( gmdate( 'Y-m-d\TH:i:s\Z', strtotime( $raffle->draw_date ) ) ); ?>">
                <div class="raffle-countdown-item">
                    <span class="raffle-countdown-number" id="cd-days">00</span>
                    <span class="raffle-countdown-label">Días</span>
                </div>
                <div class="raffle-countdown-separator">:</div>
                <div class="raffle-countdown-item">
                    <span class="raffle-countdown-number" id="cd-hours">00</span>
                    <span class="raffle-countdown-label">Horas</span>
                </div>
                <div class="raffle-countdown-separator">:</div>
                <div class="raffle-countdown-item">
                    <span class="raffle-countdown-number" id="cd-minutes">00</span>
                    <span class="raffle-countdown-label">Min</span>
                </div>
                <div class="raffle-countdown-separator">:</div>
                <div class="raffle-countdown-item">
                    <span class="raffle-countdown-number" id="cd-seconds">00</span>
                    <span class="raffle-countdown-label">Seg</span>
                </div>
            </div>
            <div class="raffle-countdown-expired" id="raffle-countdown-expired" style="display:none;">
                🎉 ¡Es hora del sorteo!
            </div>
        </div>
    <?php endif; ?>

    <!-- Progress -->
    <div class="raffle-progress-section">
        <div class="raffle-progress-heading">
            <span class="raffle-progress-title">Boletos vendidos</span>
            <span class="raffle-progress-big-percent"><?php echo esc_html( $progress ); ?>%</span>
        </div>
        <div class="raffle-progress-bar-container">
            <div class="raffle-progress-bar-fill" style="width:<?php echo esc_attr( $progress ); ?>%"></div>
        </div>
        <div class="raffle-progress-details">
            <div class="raffle-progress-detail">
                <span class="raffle-progress-detail-number raffle-progress-detail--sold"><?php echo esc_html( $raffle->sold_tickets ); ?></span>
                <span class="raffle-progress-detail-label">vendidos</span>
            </div>
            <div class="raffle-progress-detail">
                <span class="raffle-progress-detail-number"><?php echo esc_html( $raffle->total_tickets ); ?></span>
                <span class="raffle-progress-detail-label">total</span>
            </div>
            <div class="raffle-progress-detail">
                <span class="raffle-progress-detail-number raffle-progress-detail--remaining"><?php echo esc_html( $remaining ); ?></span>
                <span class="raffle-progress-detail-label">disponibles</span>
            </div>
            <div class="raffle-progress-detail">
                <span class="raffle-progress-detail-number">$<?php echo esc_html( number_format( $raffle->ticket_price, 2 ) ); ?></span>
                <span class="raffle-progress-detail-label">c/u</span>
            </div>
        </div>
    </div>

    <?php if ( $raffle->status === 'active' && $remaining > 0 ) : ?>

        <!-- Packages -->
        <div class="raffle-packages">
            <div class="raffle-packages-header">
                <h3>Elige tu paquete</h3>
                <p class="raffle-packages-sub">Selecciona la cantidad de boletos que deseas</p>
            </div>
            <div class="raffle-packages-grid">
                <?php foreach ( $packages as $qty ) :
                    if ( $qty > $remaining ) continue;
                    $package_price  = $qty * $raffle->ticket_price;
                    $is_best        = ( (int) $qty === (int) $best_value_qty && count( $available_packages ) > 1 );
                    $per_ticket     = $raffle->ticket_price;
                ?>
                    <div class="raffle-package-card<?php echo $is_best ? ' raffle-package-card--best' : ''; ?>" data-quantity="<?php echo esc_attr( $qty ); ?>">
                        <?php if ( $is_best ) : ?>
                            <div class="raffle-package-ribbon">Mejor opción</div>
                        <?php endif; ?>
                        <div class="raffle-package-qty"><?php echo esc_html( $qty ); ?></div>
                        <div class="raffle-package-label">boletos</div>
                        <div class="raffle-package-price">$<?php echo esc_html( number_format( $package_price, 2 ) ); ?></div>
                        <div class="raffle-package-per">$<?php echo esc_html( number_format( $per_ticket, 2 ) ); ?> c/u</div>
                        <button class="raffle-buy-btn" data-quantity="<?php echo esc_attr( $qty ); ?>">
                            Comprar ahora
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Trust -->
        <div class="raffle-trust">
            <div class="raffle-trust-item">🔒 Compra segura</div>
            <div class="raffle-trust-item">📧 Confirmación inmediata</div>
            <div class="raffle-trust-item">🎰 Números aleatorios</div>
        </div>

        <!-- Purchase Modal -->
        <div class="raffle-modal" id="raffle-modal" style="display:none;">
            <div class="raffle-modal-content">
                <span class="raffle-modal-close">&times;</span>
                <div class="raffle-modal-header">
                    <h3>Completar Compra</h3>
                    <p class="raffle-modal-summary"></p>
                </div>
                <form id="raffle-purchase-form">
                    <input type="hidden" name="raffle_id" value="<?php echo esc_attr( $raffle->id ); ?>">
                    <input type="hidden" name="quantity" id="raffle-quantity" value="">
                    <div class="raffle-form-group">
                        <label for="buyer_name">Nombre completo</label>
                        <input type="text" id="buyer_name" name="buyer_name" required
                               placeholder="Ej: Juan Pérez">
                    </div>
                    <div class="raffle-form-group">
                        <label for="buyer_email">Correo electrónico</label>
                        <input type="email" id="buyer_email" name="buyer_email" required
                               placeholder="tu@email.com">
                    </div>
                    <button type="submit" class="raffle-submit-btn">
                        <?php if ( Raffle_WooCommerce::is_available() ) : ?>
                            <span class="raffle-submit-btn-icon">💳</span> Proceder al Pago
                        <?php else : ?>
                            <span class="raffle-submit-btn-icon">🎟️</span> Confirmar Compra
                        <?php endif; ?>
                    </button>
                </form>
                <div class="raffle-loading" style="display:none;">
                    <div class="raffle-spinner"></div>
                    <span>Procesando tu compra...</span>
                </div>
                <div class="raffle-modal-secure">🔒 Tus datos están protegidos</div>
            </div>
        </div>

        <!-- Confirmation -->
        <div class="raffle-confirmation" id="raffle-confirmation" style="display:none;">
            <div class="raffle-confirmation-content">
                <span class="raffle-modal-close">&times;</span>
                <div class="raffle-confirmation-icon">🎉</div>
                <h3>¡Compra Exitosa!</h3>
                <p>Tus números de boleto:</p>
                <div class="raffle-ticket-numbers" id="raffle-ticket-numbers"></div>
                <p class="raffle-confirmation-email">
                    📧 Se ha enviado un correo de confirmación con tus números.
                </p>
            </div>
        </div>

    <?php elseif ( $raffle->status === 'active' && $remaining <= 0 ) : ?>
        <div class="raffle-soldout-banner">
            <span class="raffle-banner-icon">🎟️</span>
            <span>¡Todos los boletos han sido vendidos!</span>
        </div>
    <?php endif; ?>

</div>
