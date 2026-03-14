<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap rs-wrap">
    <div class="rs-page-header">
        <h1 class="rs-page-title">
            <i class="fas fa-credit-card"></i> Configuración de Wompi
        </h1>
    </div>

    <?php if ( isset( $_GET['message'] ) && $_GET['message'] === 'saved' ) : ?>
        <div class="rs-alert rs-alert-success">
            <i class="fas fa-check-circle"></i> Configuración guardada correctamente.
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field( 'wompi_settings_save', 'wompi_nonce' ); ?>
        <input type="hidden" name="wompi_settings_submit" value="1">

        <div class="rs-card">
            <h3 class="rs-card-title"><i class="fas fa-toggle-on"></i> General</h3>
            <div class="rs-form-grid">
                <div class="rs-form-group">
                    <label class="rs-checkbox-label">
                        <input type="checkbox" name="wompi_enabled" value="1"
                            <?php checked( get_option( 'wompi_enabled', '0' ), '1' ); ?>>
                        <div>
                            <strong>Activar pasarela Wompi</strong>
                            <span class="rs-help" style="display:block;">Habilita los pagos con Wompi en tu sitio.</span>
                        </div>
                    </label>
                </div>
                <div class="rs-form-group">
                    <label class="rs-checkbox-label">
                        <input type="checkbox" name="wompi_sandbox" value="1"
                            <?php checked( get_option( 'wompi_sandbox', '1' ), '1' ); ?>>
                        <div>
                            <strong>Modo Sandbox</strong>
                            <span class="rs-help" style="display:block;">Desactiva esta opción cuando vayas a producción.</span>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <div class="rs-card">
            <h3 class="rs-card-title"><i class="fas fa-key"></i> Credenciales API</h3>
            <div class="rs-form-grid">
                <div class="rs-form-group">
                    <label for="wompi_public_key" class="rs-label">Llave Pública</label>
                    <input type="text" id="wompi_public_key" name="wompi_public_key" class="rs-input"
                           value="<?php echo esc_attr( get_option( 'wompi_public_key', '' ) ); ?>"
                           placeholder="pub_test_... o pub_prod_...">
                    <p class="rs-help">Encuéntrala en Wompi → Desarrolladores.</p>
                </div>
                <div class="rs-form-group">
                    <label for="wompi_private_key" class="rs-label">Llave Privada</label>
                    <input type="password" id="wompi_private_key" name="wompi_private_key" class="rs-input"
                           value="<?php echo esc_attr( get_option( 'wompi_private_key', '' ) ); ?>"
                           placeholder="prv_test_... o prv_prod_...">
                    <p class="rs-help">Solo se usa en consultas servidor a servidor.</p>
                </div>
                <div class="rs-form-group">
                    <label for="wompi_integrity_secret" class="rs-label">Secreto de Integridad</label>
                    <input type="password" id="wompi_integrity_secret" name="wompi_integrity_secret" class="rs-input"
                           value="<?php echo esc_attr( get_option( 'wompi_integrity_secret', '' ) ); ?>"
                           placeholder="test_integrity_... o prod_integrity_...">
                    <p class="rs-help">Para firmar los pagos y evitar manipulación.</p>
                </div>
                <div class="rs-form-group">
                    <label for="wompi_events_secret" class="rs-label">Secreto de Eventos</label>
                    <input type="password" id="wompi_events_secret" name="wompi_events_secret" class="rs-input"
                           value="<?php echo esc_attr( get_option( 'wompi_events_secret', '' ) ); ?>"
                           placeholder="test_events_... o prod_events_...">
                    <p class="rs-help">Para verificar la firma de los webhooks.</p>
                </div>
            </div>
        </div>

        <div class="rs-card">
            <h3 class="rs-card-title"><i class="fas fa-link"></i> Webhook</h3>
            <div class="rs-form-group">
                <label class="rs-label">URL del Webhook</label>
                <div class="rs-webhook-url"><?php echo esc_html( rest_url( 'raffle-system/v1/wompi-webhook' ) ); ?></div>
                <p class="rs-help">Configura esta URL en tu panel de Wompi → Desarrolladores → Eventos.</p>
            </div>
        </div>

        <div class="rs-form-actions">
            <button type="submit" class="rs-btn rs-btn-primary rs-btn-lg">
                <i class="fas fa-save"></i> Guardar Configuración
            </button>
        </div>
    </form>
</div>
