<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap">
    <h1 class="wp-heading-inline">Rifas</h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=raffle-new' ) ); ?>" class="page-title-action">Crear Rifa</a>
    <hr class="wp-header-end">

    <?php if ( isset( $_GET['message'] ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                $msg = sanitize_text_field( wp_unslash( $_GET['message'] ) );
                echo $msg === 'saved' ? 'Rifa guardada correctamente.' : 'Rifa eliminada.';
                ?>
            </p>
        </div>
    <?php endif; ?>

    <?php
    global $wpdb;
    $raffles = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}raffles ORDER BY created_at DESC" );
    ?>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:50px;">ID</th>
                <th>Título</th>
                <th>Premio</th>
                <th>Boletos</th>
                <th>Precio</th>
                <th>Fecha Sorteo</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $raffles ) ) : ?>
                <tr><td colspan="8">No hay rifas creadas.</td></tr>
            <?php else : ?>
                <?php foreach ( $raffles as $r ) : ?>
                    <tr>
                        <td><?php echo esc_html( $r->id ); ?></td>
                        <td><strong><?php echo esc_html( $r->title ); ?></strong></td>
                        <td>$<?php echo esc_html( number_format( $r->prize_value, 2 ) ); ?></td>
                        <td><?php echo esc_html( $r->sold_tickets ); ?> / <?php echo esc_html( $r->total_tickets ); ?></td>
                        <td>$<?php echo esc_html( number_format( $r->ticket_price, 2 ) ); ?></td>
                        <td><?php echo $r->draw_date ? esc_html( date_i18n( 'd/m/Y H:i', strtotime( $r->draw_date ) ) ) : '—'; ?></td>
                        <td>
                            <?php
                            $percent = $r->total_tickets > 0 ? round( ( $r->sold_tickets / $r->total_tickets ) * 100 ) : 0;
                            $bar_class = 'raffle-list-bar--normal';
                            if ( $percent >= 100 ) {
                                $bar_class = 'raffle-list-bar--full';
                            } elseif ( $percent >= 75 ) {
                                $bar_class = 'raffle-list-bar--high';
                            }
                            ?>
                            <div class="raffle-list-progress">
                                <div class="raffle-list-bar <?php echo esc_attr( $bar_class ); ?>" style="width:<?php echo esc_attr( $percent ); ?>%;"></div>
                            </div>
                            <span class="raffle-list-percent"><?php echo esc_html( $percent ); ?>%</span>
                            <span class="raffle-status raffle-status--<?php echo esc_attr( $r->status ); ?>">
                                <?php echo $r->status === 'active' ? 'Activa' : 'Finalizada'; ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( "admin.php?page=raffle-system&action=view&id={$r->id}" ) ); ?>">Ver</a> |
                            <a href="<?php echo esc_url( admin_url( "admin.php?page=raffle-system&action=edit&id={$r->id}" ) ); ?>">Editar</a> |
                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( "admin.php?page=raffle-system&action=delete&id={$r->id}" ), 'delete_raffle' ) ); ?>"
                               onclick="return confirm('¿Estás seguro de eliminar esta rifa y todos sus datos?');"
                               style="color:#a00;">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="raffle-shortcode-info">
        <h3>¿Cómo mostrar una rifa en tu sitio?</h3>
        <p>Usa el shortcode <code>[raffle id="X"]</code> en cualquier página o entrada, reemplazando <strong>X</strong> con el ID de la rifa.</p>
    </div>
</div>
