<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap">
    <h1><?php echo $raffle ? 'Editar Rifa' : 'Crear Nueva Rifa'; ?></h1>

    <form method="post" action="" class="raffle-form">
        <?php wp_nonce_field( 'raffle_save', 'raffle_nonce' ); ?>
        <input type="hidden" name="raffle_form_submit" value="1">
        <?php if ( $raffle ) : ?>
            <input type="hidden" name="raffle_id" value="<?php echo esc_attr( $raffle->id ); ?>">
        <?php endif; ?>

        <table class="form-table">
            <tr>
                <th><label for="title">Título</label></th>
                <td>
                    <input type="text" id="title" name="title" class="regular-text" required
                           value="<?php echo $raffle ? esc_attr( $raffle->title ) : ''; ?>">
                </td>
            </tr>
            <tr>
                <th><label for="description">Descripción</label></th>
                <td>
                    <textarea id="description" name="description" rows="5" class="large-text"><?php
                        echo $raffle ? esc_textarea( $raffle->description ) : '';
                    ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="prize_value">Valor del Premio ($)</label></th>
                <td>
                    <input type="number" id="prize_value" name="prize_value" step="0.01" min="0" required
                           value="<?php echo $raffle ? esc_attr( $raffle->prize_value ) : ''; ?>">
                </td>
            </tr>
            <tr>
                <th><label for="prize_image">Imagen del Premio</label></th>
                <td>
                    <input type="hidden" id="prize_image" name="prize_image"
                           value="<?php echo $raffle ? esc_attr( $raffle->prize_image ) : ''; ?>">
                    <div id="prize-image-preview">
                        <?php if ( $raffle && $raffle->prize_image ) : ?>
                            <img src="<?php echo esc_url( $raffle->prize_image ); ?>" style="max-width:200px; display:block; margin-bottom:10px;">
                        <?php endif; ?>
                    </div>
                    <button type="button" class="button" id="upload-prize-image">Seleccionar Imagen</button>
                    <button type="button" class="button" id="remove-prize-image"
                            style="<?php echo ( $raffle && $raffle->prize_image ) ? '' : 'display:none;'; ?>">Eliminar Imagen</button>
                </td>
            </tr>
            <tr>
                <th><label for="total_tickets">Total de Boletos</label></th>
                <td>
                    <input type="number" id="total_tickets" name="total_tickets" min="1" required
                           value="<?php echo $raffle ? esc_attr( $raffle->total_tickets ) : ''; ?>"
                           <?php echo ( $raffle && $raffle->sold_tickets > 0 ) ? 'readonly' : ''; ?>>
                    <?php if ( $raffle && $raffle->sold_tickets > 0 ) : ?>
                        <p class="description">No se puede modificar porque ya hay boletos vendidos.</p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="ticket_price">Precio por Boleto ($)</label></th>
                <td>
                    <input type="number" id="ticket_price" name="ticket_price" step="0.01" min="0" required
                           value="<?php echo $raffle ? esc_attr( $raffle->ticket_price ) : ''; ?>">
                </td>
            </tr>
            <tr>
                <th><label for="packages">Paquetes de Boletos</label></th>
                <td>
                    <input type="text" id="packages" name="packages" class="regular-text" required
                           placeholder="15,30,45,90"
                           value="<?php echo $raffle ? esc_attr( implode( ',', json_decode( $raffle->packages, true ) ?: array() ) ) : ''; ?>">
                    <p class="description">Cantidades separadas por comas. Ej: <code>15,30,45,90</code></p>
                </td>
            </tr>
            <tr>
                <th><label for="draw_date">Fecha del Sorteo</label></th>
                <td>
                    <input type="datetime-local" id="draw_date" name="draw_date"
                           value="<?php echo ( $raffle && $raffle->draw_date ) ? esc_attr( date( 'Y-m-d\TH:i', strtotime( $raffle->draw_date ) ) ) : ''; ?>">
                </td>
            </tr>
            <tr>
                <th><label for="status">Estado</label></th>
                <td>
                    <select id="status" name="status">
                        <option value="active"   <?php selected( $raffle ? $raffle->status : 'active', 'active' ); ?>>Activa</option>
                        <option value="finished" <?php selected( $raffle ? $raffle->status : '', 'finished' ); ?>>Finalizada</option>
                    </select>
                </td>
            </tr>
        </table>

        <?php submit_button( $raffle ? 'Actualizar Rifa' : 'Crear Rifa' ); ?>
    </form>
</div>
