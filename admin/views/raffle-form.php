<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap rs-wrap">
    <div class="rs-page-header">
        <h1 class="rs-page-title">
            <i class="fas fa-<?php echo $raffle ? 'edit' : 'plus-circle'; ?>"></i>
            <?php echo $raffle ? 'Editar Rifa' : 'Crear Nueva Rifa'; ?>
        </h1>
        <div class="rs-header-actions">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=raffle-system' ) ); ?>" class="rs-btn rs-btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="rs-card">
        <form method="post" action="" class="rs-form">
            <?php wp_nonce_field( 'raffle_save', 'raffle_nonce' ); ?>
            <input type="hidden" name="raffle_form_submit" value="1">
            <?php if ( $raffle ) : ?>
                <input type="hidden" name="raffle_id" value="<?php echo esc_attr( $raffle->id ); ?>">
            <?php endif; ?>

            <div class="rs-form-grid">
                <div class="rs-form-group rs-col-full">
                    <label for="title" class="rs-label">Título *</label>
                    <input type="text" id="title" name="title" class="rs-input" required
                           value="<?php echo $raffle ? esc_attr( $raffle->title ) : ''; ?>"
                           placeholder="Ej: iPhone 16 Pro Max">
                </div>

                <div class="rs-form-group rs-col-full">
                    <label for="description" class="rs-label">Descripción</label>
                    <textarea id="description" name="description" rows="4" class="rs-textarea"
                              placeholder="Descripción detallada del premio..."><?php echo $raffle ? esc_textarea( $raffle->description ) : ''; ?></textarea>
                </div>

                <div class="rs-form-group">
                    <label for="prize_value" class="rs-label">Valor del Premio ($) *</label>
                    <input type="number" id="prize_value" name="prize_value" class="rs-input" step="0.01" min="0" required
                           value="<?php echo $raffle ? esc_attr( $raffle->prize_value ) : ''; ?>"
                           placeholder="Ej: 5000000">
                </div>

                <div class="rs-form-group">
                    <label for="ticket_price" class="rs-label">Precio por Boleto ($) *</label>
                    <input type="number" id="ticket_price" name="ticket_price" class="rs-input" step="0.01" min="0" required
                           value="<?php echo $raffle ? esc_attr( $raffle->ticket_price ) : ''; ?>"
                           placeholder="Ej: 5000">
                </div>

                <div class="rs-form-group">
                    <label for="total_tickets" class="rs-label">Total de Boletos *</label>
                    <input type="number" id="total_tickets" name="total_tickets" class="rs-input" min="1" required
                           value="<?php echo $raffle ? esc_attr( $raffle->total_tickets ) : ''; ?>"
                           placeholder="Ej: 10000"
                           <?php echo ( $raffle && $raffle->sold_tickets > 0 ) ? 'readonly' : ''; ?>>
                    <?php if ( $raffle && $raffle->sold_tickets > 0 ) : ?>
                        <p class="rs-help"><i class="fas fa-lock"></i> No se puede modificar porque ya hay boletos vendidos.</p>
                    <?php endif; ?>
                </div>

                <div class="rs-form-group">
                    <label for="packages" class="rs-label">Paquetes de Boletos *</label>
                    <input type="text" id="packages" name="packages" class="rs-input" required
                           placeholder="15,30,45,90"
                           value="<?php echo $raffle ? esc_attr( implode( ',', json_decode( $raffle->packages, true ) ?: array() ) ) : ''; ?>">
                    <p class="rs-help">Cantidades separadas por comas. Ej: <strong>15, 30, 45, 90</strong></p>
                </div>

                <div class="rs-form-group">
                    <label for="draw_date" class="rs-label">Fecha del Sorteo</label>
                    <input type="datetime-local" id="draw_date" name="draw_date" class="rs-input"
                           value="<?php echo ( $raffle && $raffle->draw_date ) ? esc_attr( date( 'Y-m-d\TH:i', strtotime( $raffle->draw_date ) ) ) : ''; ?>">
                </div>

                <div class="rs-form-group">
                    <label for="status" class="rs-label">Estado</label>
                    <select id="status" name="status" class="rs-select">
                        <option value="active"   <?php selected( $raffle ? $raffle->status : 'active', 'active' ); ?>>Activa</option>
                        <option value="finished" <?php selected( $raffle ? $raffle->status : '', 'finished' ); ?>>Finalizada</option>
                    </select>
                </div>

                <div class="rs-form-group rs-col-full">
                    <label class="rs-label">Imagen del Premio</label>
                    <div class="rs-image-upload">
                        <input type="hidden" id="prize_image" name="prize_image"
                               value="<?php echo $raffle ? esc_attr( $raffle->prize_image ) : ''; ?>">
                        <div id="prize-image-preview" class="rs-image-preview">
                            <?php if ( $raffle && $raffle->prize_image ) : ?>
                                <img src="<?php echo esc_url( $raffle->prize_image ); ?>">
                            <?php else : ?>
                                <i class="fas fa-image" style="font-size:32px;opacity:.3;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="rs-upload-actions">
                            <button type="button" class="rs-btn rs-btn-secondary" id="upload-prize-image">
                                <i class="fas fa-cloud-upload-alt"></i> Seleccionar Imagen
                            </button>
                            <button type="button" class="rs-btn rs-btn-danger rs-btn-sm" id="remove-prize-image"
                                    style="<?php echo ( $raffle && $raffle->prize_image ) ? '' : 'display:none;'; ?>">
                                <i class="fas fa-times"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rs-form-actions">
                <button type="submit" class="rs-btn rs-btn-primary rs-btn-lg">
                    <i class="fas fa-save"></i> <?php echo $raffle ? 'Actualizar Rifa' : 'Crear Rifa'; ?>
                </button>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=raffle-system' ) ); ?>" class="rs-btn rs-btn-secondary rs-btn-lg">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
