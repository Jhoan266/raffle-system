<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Raffle_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_init', array( $this, 'handle_form_submission' ) );
    }

    public function add_menu() {
        add_menu_page(
            'Rifas',
            'Rifas',
            'manage_options',
            'raffle-system',
            array( $this, 'render_list_page' ),
            'dashicons-tickets-alt',
            30
        );

        add_submenu_page(
            'raffle-system',
            'Todas las Rifas',
            'Todas las Rifas',
            'manage_options',
            'raffle-system',
            array( $this, 'render_list_page' )
        );

        add_submenu_page(
            'raffle-system',
            'Crear Rifa',
            'Crear Rifa',
            'manage_options',
            'raffle-new',
            array( $this, 'render_form_page' )
        );

    }

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'raffle' ) === false ) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style( 'raffle-admin', RAFFLE_SYSTEM_URL . 'assets/css/admin.css', array(), RAFFLE_SYSTEM_VERSION );
        wp_enqueue_script( 'raffle-admin', RAFFLE_SYSTEM_URL . 'assets/js/admin.js', array( 'jquery' ), RAFFLE_SYSTEM_VERSION, true );
        wp_localize_script( 'raffle-admin', 'raffleAdmin', array(
            'ajax_url'   => admin_url( 'admin-ajax.php' ),
            'draw_nonce' => wp_create_nonce( 'raffle_draw_nonce' ),
        ) );
    }

    public function handle_form_submission() {
        if ( ! isset( $_POST['raffle_form_submit'] ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( ! isset( $_POST['raffle_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['raffle_nonce'] ) ), 'raffle_save' ) ) {
            wp_die( 'Error de seguridad.' );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'raffles';

        $data = array(
            'title'         => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
            'description'   => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
            'prize_value'   => floatval( $_POST['prize_value'] ?? 0 ),
            'prize_image'   => esc_url_raw( wp_unslash( $_POST['prize_image'] ?? '' ) ),
            'total_tickets' => absint( $_POST['total_tickets'] ?? 0 ),
            'ticket_price'  => floatval( $_POST['ticket_price'] ?? 0 ),
            'draw_date'     => sanitize_text_field( wp_unslash( $_POST['draw_date'] ?? '' ) ),
            'status'        => sanitize_text_field( wp_unslash( $_POST['status'] ?? 'active' ) ),
        );

        // Packages
        $packages_raw = sanitize_text_field( wp_unslash( $_POST['packages'] ?? '' ) );
        $packages     = array_values( array_filter( array_map( 'absint', explode( ',', $packages_raw ) ) ) );
        $data['packages'] = wp_json_encode( $packages );

        $formats = array( '%s', '%s', '%f', '%s', '%d', '%f', '%s', '%s', '%s' );

        $raffle_id = isset( $_POST['raffle_id'] ) ? absint( $_POST['raffle_id'] ) : 0;

        if ( $raffle_id ) {
            $wpdb->update( $table, $data, array( 'id' => $raffle_id ), $formats, array( '%d' ) );
        } else {
            $data['created_at'] = current_time( 'mysql' );
            $formats[]          = '%s';
            $wpdb->insert( $table, $data, $formats );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=raffle-system&message=saved' ) );
        exit;
    }

    public function render_list_page() {
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'view' && isset( $_GET['id'] ) ) {
            $this->render_details_page();
            return;
        }
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' && isset( $_GET['id'] ) ) {
            $this->render_form_page();
            return;
        }
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
            $this->delete_raffle();
            return;
        }

        include RAFFLE_SYSTEM_PATH . 'admin/views/raffle-list.php';
    }

    public function render_form_page() {
        $raffle = null;
        if ( isset( $_GET['id'] ) ) {
            global $wpdb;
            $raffle = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}raffles WHERE id = %d",
                absint( $_GET['id'] )
            ) );
        }
        include RAFFLE_SYSTEM_PATH . 'admin/views/raffle-form.php';
    }

    private function render_details_page() {
        global $wpdb;
        $raffle_id = absint( $_GET['id'] );
        $raffle    = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}raffles WHERE id = %d",
            $raffle_id
        ) );

        if ( ! $raffle ) {
            echo '<div class="wrap"><h1>Rifa no encontrada</h1></div>';
            return;
        }

        $purchases = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}raffle_purchases WHERE raffle_id = %d ORDER BY purchase_date DESC",
            $raffle_id
        ) );

        $winner = null;
        if ( $raffle->winner_ticket_id ) {
            $winner = $wpdb->get_row( $wpdb->prepare(
                "SELECT t.*, p.buyer_name
                 FROM {$wpdb->prefix}raffle_tickets t
                 JOIN {$wpdb->prefix}raffle_purchases p ON t.purchase_id = p.id
                 WHERE t.id = %d",
                $raffle->winner_ticket_id
            ) );
        }

        include RAFFLE_SYSTEM_PATH . 'admin/views/raffle-details.php';
    }

    private function delete_raffle() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'delete_raffle' ) ) {
            wp_die( 'Error de seguridad.' );
        }

        global $wpdb;
        $raffle_id = absint( $_GET['id'] );

        // TRANSACCIÓN ATÓMICA: eliminar todo o nada
        $wpdb->query( 'START TRANSACTION' );
        $wpdb->delete( $wpdb->prefix . 'raffle_tickets', array( 'raffle_id' => $raffle_id ), array( '%d' ) );
        $wpdb->delete( $wpdb->prefix . 'raffle_purchases', array( 'raffle_id' => $raffle_id ), array( '%d' ) );
        $wpdb->delete( $wpdb->prefix . 'raffles', array( 'id' => $raffle_id ), array( '%d' ) );
        $wpdb->query( 'COMMIT' );

        wp_safe_redirect( admin_url( 'admin.php?page=raffle-system&message=deleted' ) );
        exit;
    }
}
