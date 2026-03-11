<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
    <h1>Configuración de Wompi</h1>

    <?php if ( isset( $_GET['message'] ) && $_GET['message'] === 'saved' ) : ?>
        <div class="notice notice-success is-dismissible">
            <p>Configuración guardada correctamente.</p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field( 'wompi_settings_save', 'wompi_nonce' ); ?>
        <input type="hidden" name="wompi_settings_submit" value="1">

        <table class="form-table">
            <tr>
                <th scope="row">Habilitar Wompi</th>
                <td>
                    <label>
                        <input type="checkbox" name="wompi_enabled" value="1"
                            <?php checked( get_option( 'wompi_enabled', '0' ), '1' ); ?>>
                        Activar pasarela de pago Wompi
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">Modo Sandbox</th>
                <td>
                    <label>
                        <input type="checkbox" name="wompi_sandbox" value="1"
                            <?php checked( get_option( 'wompi_sandbox', '1' ), '1' ); ?>>
                        Usar ambiente de pruebas (sandbox)
                    </label>
                    <p class="description">Desactiva esta opción cuando vayas a producción.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wompi_public_key">Llave Pública</label></th>
                <td>
                    <input type="text" id="wompi_public_key" name="wompi_public_key"
                           value="<?php echo esc_attr( get_option( 'wompi_public_key', '' ) ); ?>"
                           class="regular-text" placeholder="pub_test_... o pub_prod_...">
                    <p class="description">Se encuentra en tu panel de Wompi → Desarrolladores.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wompi_private_key">Llave Privada</label></th>
                <td>
                    <input type="password" id="wompi_private_key" name="wompi_private_key"
                           value="<?php echo esc_attr( get_option( 'wompi_private_key', '' ) ); ?>"
                           class="regular-text" placeholder="prv_test_... o prv_prod_...">
                    <p class="description">Solo se usa en consultas servidor a servidor.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wompi_integrity_secret">Secreto de Integridad</label></th>
                <td>
                    <input type="password" id="wompi_integrity_secret" name="wompi_integrity_secret"
                           value="<?php echo esc_attr( get_option( 'wompi_integrity_secret', '' ) ); ?>"
                           class="regular-text" placeholder="test_integrity_... o prod_integrity_...">
                    <p class="description">Para firmar los pagos y evitar manipulación.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wompi_events_secret">Secreto de Eventos</label></th>
                <td>
                    <input type="password" id="wompi_events_secret" name="wompi_events_secret"
                           value="<?php echo esc_attr( get_option( 'wompi_events_secret', '' ) ); ?>"
                           class="regular-text" placeholder="test_events_... o prod_events_...">
                    <p class="description">Para verificar la firma de los webhooks.</p>
                </td>
            </tr>
        </table>

        <h2>Información del Webhook</h2>
        <table class="form-table">
            <tr>
                <th scope="row">URL del Webhook</th>
                <td>
                    <code><?php echo esc_html( rest_url( 'raffle-system/v1/wompi-webhook' ) ); ?></code>
                    <p class="description">Configura esta URL en tu panel de Wompi → Desarrolladores → Eventos.</p>
                </td>
            </tr>
        </table>

        <?php submit_button( 'Guardar Configuración' ); ?>
    </form>
</div>
