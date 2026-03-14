<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap rs-wrap">
    <div class="rs-page-header">
        <h1 class="rs-page-title">
            <i class="fas fa-ticket-alt"></i> Rifas
        </h1>
        <div class="rs-header-actions">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=raffle-new' ) ); ?>" class="rs-btn rs-btn-primary">
                <i class="fas fa-plus"></i> Crear Rifa
            </a>
        </div>
    </div>

    <?php if ( isset( $_GET['message'] ) ) : ?>
        <div class="rs-alert rs-alert-success">
            <i class="fas fa-check-circle"></i>
            <?php
            $msg = sanitize_text_field( wp_unslash( $_GET['message'] ) );
            echo $msg === 'saved' ? 'Rifa guardada correctamente.' : 'Rifa eliminada.';
            ?>
        </div>
    <?php endif; ?>

    <?php
    global $wpdb;
    $raffles = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}raffles ORDER BY created_at DESC" );
    ?>

    <div class="rs-card">
        <h3 class="rs-card-title"><i class="fas fa-list"></i> Todas las Rifas</h3>
        <table class="rs-table">
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th>Título</th>
                    <th>Premio</th>
                    <th>Boletos</th>
                    <th>Precio</th>
                    <th>Fecha Sorteo</th>
                    <th>Progreso</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $raffles ) ) : ?>
                    <tr>
                        <td colspan="9" class="rs-table-empty">
                            <i class="fas fa-inbox"></i>
                            No hay rifas creadas. ¡Crea tu primera rifa!
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $raffles as $r ) : ?>
                        <?php
                        $percent = $r->total_tickets > 0 ? round( ( $r->sold_tickets / $r->total_tickets ) * 100 ) : 0;
                        $bar_class = 'blue';
                        if ( $percent >= 100 ) {
                            $bar_class = 'green';
                        } elseif ( $percent >= 75 ) {
                            $bar_class = 'yellow';
                        }
                        ?>
                        <tr>
                            <td><strong>#<?php echo esc_html( $r->id ); ?></strong></td>
                            <td><strong><?php echo esc_html( $r->title ); ?></strong></td>
                            <td>$<?php echo esc_html( number_format( $r->prize_value, 0, ',', '.' ) ); ?></td>
                            <td><?php echo esc_html( $r->sold_tickets ); ?> / <?php echo esc_html( $r->total_tickets ); ?></td>
                            <td>$<?php echo esc_html( number_format( $r->ticket_price, 0, ',', '.' ) ); ?></td>
                            <td><?php echo $r->draw_date ? esc_html( date_i18n( 'd/m/Y H:i', strtotime( $r->draw_date ) ) ) : '—'; ?></td>
                            <td style="min-width:120px;">
                                <div class="rs-progress">
                                    <div class="rs-progress-fill <?php echo esc_attr( $bar_class ); ?>" style="width:<?php echo esc_attr( $percent ); ?>%;"></div>
                                </div>
                                <span class="rs-progress-text"><?php echo esc_html( $percent ); ?>%</span>
                            </td>
                            <td>
                                <span class="rs-badge rs-badge-<?php echo esc_attr( $r->status ); ?>">
                                    <?php echo $r->status === 'active' ? '● Activa' : '● Finalizada'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo esc_url( admin_url( "admin.php?page=raffle-system&action=view&id={$r->id}" ) ); ?>"
                                   class="rs-btn-icon rs-btn-secondary" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?php echo esc_url( admin_url( "admin.php?page=raffle-system&action=edit&id={$r->id}" ) ); ?>"
                                   class="rs-btn-icon rs-btn-warning" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( "admin.php?page=raffle-system&action=delete&id={$r->id}" ), 'delete_raffle' ) ); ?>"
                                   class="rs-btn-icon rs-btn-danger"
                                   title="Eliminar"
                                   onclick="return confirm('¿Estás seguro de eliminar esta rifa y todos sus datos?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="rs-info-box">
        <h4><i class="fas fa-code"></i> ¿Cómo mostrar una rifa en tu sitio?</h4>
        <p>Usa el shortcode <code>[raffle id="X"]</code> en cualquier página o entrada, reemplazando <strong>X</strong> con el ID de la rifa.</p>
    </div>
</div>
