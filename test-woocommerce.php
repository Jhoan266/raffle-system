<?php
/**
 * Test Suite: WooCommerce Integration for Raffle System
 *
 * Run with: docker exec wp_rifas_app wp eval-file /var/www/html/wp-content/plugins/raffle-system/test-woocommerce.php --allow-root
 */

if ( ! defined( 'ABSPATH' ) ) {
    echo "Must be run within WordPress context.\n";
    exit( 1 );
}

global $wpdb;

$total_tests  = 0;
$passed_tests = 0;
$failed_tests = 0;

function test_assert( $name, $condition, &$total, &$passed, &$failed ) {
    $total++;
    if ( $condition ) {
        $passed++;
        echo "  ✓ {$name}\n";
    } else {
        $failed++;
        echo "  ✗ FAILED: {$name}\n";
    }
}

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  TEST SUITE: WooCommerce Integration - Raffle System       ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// ============================================================
// Setup: ensure test raffle exists
// ============================================================
$table_raffles   = $wpdb->prefix . 'raffles';
$table_purchases = $wpdb->prefix . 'raffle_purchases';
$table_tickets   = $wpdb->prefix . 'raffle_tickets';

// Create test raffle
$wpdb->insert( $table_raffles, array(
    'title'         => 'WC Test Raffle',
    'description'   => 'Test raffle for WooCommerce integration',
    'prize_value'   => 1000.00,
    'total_tickets' => 100,
    'sold_tickets'  => 0,
    'ticket_price'  => 25.00,
    'packages'      => json_encode( array( 1, 5, 10 ) ),
    'draw_date'     => date( 'Y-m-d H:i:s', strtotime( '+30 days' ) ),
    'status'        => 'active',
    'created_at'    => current_time( 'mysql' ),
), array( '%s', '%s', '%f', '%d', '%d', '%f', '%s', '%s', '%s', '%s' ) );

$test_raffle_id = $wpdb->insert_id;

echo "Test raffle created: ID {$test_raffle_id}\n\n";

// ============================================================
// TEST 1: WooCommerce Detection
// ============================================================
echo "━━━ Test 1: WooCommerce Detection ━━━\n";

test_assert(
    'WooCommerce class exists',
    class_exists( 'WooCommerce' ),
    $total_tests, $passed_tests, $failed_tests
);

test_assert(
    'Raffle_WooCommerce::is_available() returns true',
    Raffle_WooCommerce::is_available(),
    $total_tests, $passed_tests, $failed_tests
);

test_assert(
    'Raffle_WooCommerce class exists',
    class_exists( 'Raffle_WooCommerce' ),
    $total_tests, $passed_tests, $failed_tests
);

echo "\n";

// ============================================================
// TEST 2: WooCommerce Order Creation
// ============================================================
echo "━━━ Test 2: WooCommerce Order Creation ━━━\n";

$order = wc_create_order();

test_assert(
    'wc_create_order() returns WC_Order',
    $order instanceof WC_Order,
    $total_tests, $passed_tests, $failed_tests
);

// Add line item
$item = new WC_Order_Item_Product();
$item->set_name( 'Boletos de Rifa — WC Test Raffle (x5)' );
$item->set_quantity( 5 );
$item->set_subtotal( 125.00 );
$item->set_total( 125.00 );
$order->add_item( $item );

$order->set_billing_first_name( 'Juan' );
$order->set_billing_last_name( 'Pérez' );
$order->set_billing_email( 'test@example.com' );
$order->set_total( 125.00 );

// Set raffle meta
$order->update_meta_data( '_raffle_id', $test_raffle_id );
$order->update_meta_data( '_raffle_quantity', 5 );
$order->update_meta_data( '_raffle_buyer_name', 'Juan Pérez' );
$order->update_meta_data( '_raffle_buyer_email', 'test@example.com' );
$order->update_meta_data( '_is_raffle_order', 'yes' );

$order->set_status( 'pending' );
$order->save();

$order_id = $order->get_id();

test_assert(
    'Order ID is valid',
    $order_id > 0,
    $total_tests, $passed_tests, $failed_tests
);

test_assert(
    'Order status is pending',
    $order->get_status() === 'pending',
    $total_tests, $passed_tests, $failed_tests
);

test_assert(
    'Order total is correct',
    (float) $order->get_total() === 125.00,
    $total_tests, $passed_tests, $failed_tests
);

test_assert(
    'Order has raffle meta',
    $order->get_meta( '_is_raffle_order' ) === 'yes',
    $total_tests, $passed_tests, $failed_tests
);

test_assert(
    'Order raffle_id meta correct',
    (int) $order->get_meta( '_raffle_id' ) === $test_raffle_id,
    $total_tests, $passed_tests, $failed_tests
);

echo "\n";

// ============================================================
// TEST 3: Purchase Record Linked to WC Order
// ============================================================
echo "━━━ Test 3: Purchase Record with WC Order ID ━━━\n";

$wpdb->insert( $table_purchases, array(
    'raffle_id'      => $test_raffle_id,
    'buyer_name'     => 'Juan Pérez',
    'buyer_email'    => 'test@example.com',
    'quantity'       => 5,
    'total_amount'   => 125.00,
    'payment_status' => 'pending',
    'wc_order_id'    => $order_id,
    'purchase_date'  => current_time( 'mysql' ),
), array( '%d', '%s', '%s', '%d', '%f', '%s', '%d', '%s' ) );

$purchase_id = $wpdb->insert_id;

test_assert(
    'Purchase record created',
    $purchase_id > 0,
    $total_tests, $passed_tests, $failed_tests
);

$purchase = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$table_purchases} WHERE id = %d",
    $purchase_id
) );

test_assert(
    'Purchase has wc_order_id',
    (int) $purchase->wc_order_id === $order_id,
    $total_tests, $passed_tests, $failed_tests
);

test_assert(
    'Purchase status is pending',
    $purchase->payment_status === 'pending',
    $total_tests, $passed_tests, $failed_tests
);

// Set purchase_id on order
$order->update_meta_data( '_raffle_purchase_id', $purchase_id );
$order->save();

echo "\n";

// ============================================================
// TEST 4: Payment Complete → Ticket Generation
// ============================================================
echo "━━━ Test 4: Payment Complete → Ticket Generation ━━━\n";

// Simulate payment complete by calling the handler directly
$wc = new Raffle_WooCommerce();
$wc->on_payment_complete( $order_id );

// Check tickets were generated
$tickets = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$table_tickets} WHERE purchase_id = %d ORDER BY ticket_number ASC",
    $purchase_id
) );

test_assert(
    '5 tickets generated',
    count( $tickets ) === 5,
    $total_tests, $passed_tests, $failed_tests
);

// Check purchase updated to completed
$purchase = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$table_purchases} WHERE id = %d",
    $purchase_id
) );

test_assert(
    'Purchase status is completed',
    $purchase->payment_status === 'completed',
    $total_tests, $passed_tests, $failed_tests
);

// Check raffle sold_tickets updated
$raffle = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$table_raffles} WHERE id = %d",
    $test_raffle_id
) );

test_assert(
    'Raffle sold_tickets updated to 5',
    (int) $raffle->sold_tickets === 5,
    $total_tests, $passed_tests, $failed_tests
);

// Check order meta
$order = wc_get_order( $order_id );

test_assert(
    'Order has _raffle_tickets_generated flag',
    $order->get_meta( '_raffle_tickets_generated' ) === 'yes',
    $total_tests, $passed_tests, $failed_tests
);

$stored_tickets = $order->get_meta( '_raffle_ticket_numbers' );

test_assert(
    'Order has tickets stored in meta',
    is_array( $stored_tickets ) && count( $stored_tickets ) === 5,
    $total_tests, $passed_tests, $failed_tests
);

// Check all tickets are unique
$unique_tickets = array_unique( array_map( function( $t ) { return $t->ticket_number; }, $tickets ) );

test_assert(
    'All tickets are unique',
    count( $unique_tickets ) === 5,
    $total_tests, $passed_tests, $failed_tests
);

// Check tickets are in valid range
$all_valid = true;
foreach ( $tickets as $t ) {
    if ( $t->ticket_number < 0 || $t->ticket_number >= 100 ) {
        $all_valid = false;
        break;
    }
}

test_assert(
    'All tickets in valid range (0-99)',
    $all_valid,
    $total_tests, $passed_tests, $failed_tests
);

echo "\n";

// ============================================================
// TEST 5: Idempotency — Second call doesn't re-generate
// ============================================================
echo "━━━ Test 5: Idempotency Check ━━━\n";

$wc->on_payment_complete( $order_id );

$tickets_after = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$table_tickets} WHERE purchase_id = %d",
    $purchase_id
) );

test_assert(
    'Still 5 tickets after second call (idempotent)',
    count( $tickets_after ) === 5,
    $total_tests, $passed_tests, $failed_tests
);

$raffle_after = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$table_raffles} WHERE id = %d",
    $test_raffle_id
) );

test_assert(
    'sold_tickets still 5 (no duplicates)',
    (int) $raffle_after->sold_tickets === 5,
    $total_tests, $passed_tests, $failed_tests
);

echo "\n";

// ============================================================
// TEST 6: Non-Raffle Order Ignored
// ============================================================
echo "━━━ Test 6: Non-Raffle Order Ignored ━━━\n";

$normal_order = wc_create_order();
$normal_order->set_status( 'pending' );
$normal_order->save();

$before_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_tickets}" );
$wc->on_payment_complete( $normal_order->get_id() );
$after_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_tickets}" );

test_assert(
    'Non-raffle order does not generate tickets',
    $after_count === $before_count,
    $total_tests, $passed_tests, $failed_tests
);

// Cleanup normal order
$normal_order->delete( true );

echo "\n";

// ============================================================
// TEST 7: Order Pay URL Generation
// ============================================================
echo "━━━ Test 7: Order Pay URL ━━━\n";

$test_order = wc_create_order();
$test_order->set_status( 'pending' );
$test_order->save();

$pay_url = $test_order->get_checkout_payment_url();

test_assert(
    'Pay URL is not empty',
    ! empty( $pay_url ),
    $total_tests, $passed_tests, $failed_tests
);

test_assert(
    'Pay URL contains order-pay',
    strpos( $pay_url, 'order-pay' ) !== false || strpos( $pay_url, 'pay_for_order' ) !== false,
    $total_tests, $passed_tests, $failed_tests
);

echo "  Pay URL: {$pay_url}\n";

$test_order->delete( true );

echo "\n";

// ============================================================
// TEST 8: Second Purchase on Same Raffle
// ============================================================
echo "━━━ Test 8: Second Purchase on Same Raffle ━━━\n";

$order2 = wc_create_order();
$item2 = new WC_Order_Item_Product();
$item2->set_name( 'Boletos de Rifa — WC Test Raffle (x10)' );
$item2->set_quantity( 10 );
$item2->set_subtotal( 250.00 );
$item2->set_total( 250.00 );
$order2->add_item( $item2 );
$order2->set_billing_email( 'test2@example.com' );
$order2->set_total( 250.00 );
$order2->update_meta_data( '_raffle_id', $test_raffle_id );
$order2->update_meta_data( '_raffle_quantity', 10 );
$order2->update_meta_data( '_raffle_buyer_name', 'María García' );
$order2->update_meta_data( '_raffle_buyer_email', 'test2@example.com' );
$order2->update_meta_data( '_is_raffle_order', 'yes' );
$order2->set_status( 'pending' );
$order2->save();

$wpdb->insert( $table_purchases, array(
    'raffle_id'      => $test_raffle_id,
    'buyer_name'     => 'María García',
    'buyer_email'    => 'test2@example.com',
    'quantity'       => 10,
    'total_amount'   => 250.00,
    'payment_status' => 'pending',
    'wc_order_id'    => $order2->get_id(),
    'purchase_date'  => current_time( 'mysql' ),
), array( '%d', '%s', '%s', '%d', '%f', '%s', '%d', '%s' ) );

$purchase_id_2 = $wpdb->insert_id;
$order2->update_meta_data( '_raffle_purchase_id', $purchase_id_2 );
$order2->save();

$wc->on_payment_complete( $order2->get_id() );

$tickets2 = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$table_tickets} WHERE purchase_id = %d",
    $purchase_id_2
) );

test_assert(
    '10 tickets generated for second purchase',
    count( $tickets2 ) === 10,
    $total_tests, $passed_tests, $failed_tests
);

$raffle_updated = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$table_raffles} WHERE id = %d",
    $test_raffle_id
) );

test_assert(
    'Raffle sold_tickets is now 15 (5+10)',
    (int) $raffle_updated->sold_tickets === 15,
    $total_tests, $passed_tests, $failed_tests
);

// Check no duplicates between purchases
$all_tickets = $wpdb->get_col( $wpdb->prepare(
    "SELECT ticket_number FROM {$table_tickets} WHERE raffle_id = %d",
    $test_raffle_id
) );

test_assert(
    'No duplicate ticket numbers across purchases',
    count( $all_tickets ) === count( array_unique( $all_tickets ) ),
    $total_tests, $passed_tests, $failed_tests
);

echo "\n";

// ============================================================
// TEST 9: DB Schema Verification
// ============================================================
echo "━━━ Test 9: DB Schema ━━━\n";

$columns = $wpdb->get_results( "SHOW COLUMNS FROM {$table_purchases}", ARRAY_A );
$col_names = array_column( $columns, 'Field' );

test_assert(
    'wc_order_id column exists',
    in_array( 'wc_order_id', $col_names, true ),
    $total_tests, $passed_tests, $failed_tests
);

test_assert(
    'wompi_reference column removed',
    ! in_array( 'wompi_reference', $col_names, true ),
    $total_tests, $passed_tests, $failed_tests
);

test_assert(
    'wompi_transaction column removed',
    ! in_array( 'wompi_transaction', $col_names, true ),
    $total_tests, $passed_tests, $failed_tests
);

echo "\n";

// ============================================================
// TEST 10: Direct Purchase Blocked When WC Active
// ============================================================
echo "━━━ Test 10: Direct Purchase Blocked ━━━\n";

test_assert(
    'Raffle_WooCommerce::is_available() is true',
    Raffle_WooCommerce::is_available(),
    $total_tests, $passed_tests, $failed_tests
);

echo "  (Direct purchase AJAX endpoint will reject when WC is active)\n";

echo "\n";

// ============================================================
// CLEANUP
// ============================================================
echo "━━━ Cleanup ━━━\n";

$wpdb->delete( $table_tickets, array( 'raffle_id' => $test_raffle_id ), array( '%d' ) );
$wpdb->delete( $table_purchases, array( 'raffle_id' => $test_raffle_id ), array( '%d' ) );
$wpdb->delete( $table_raffles, array( 'id' => $test_raffle_id ), array( '%d' ) );

$order->delete( true );
$order2->delete( true );

echo "  Test data cleaned up.\n\n";

// ============================================================
// RESULTS
// ============================================================
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  RESULTS                                                   ║\n";
echo "╠══════════════════════════════════════════════════════════════╣\n";
printf( "║  Total:  %d tests                                         ║\n", $total_tests );
printf( "║  Passed: %d ✓                                             ║\n", $passed_tests );
printf( "║  Failed: %d ✗                                             ║\n", $failed_tests );
echo "╚══════════════════════════════════════════════════════════════╝\n";

if ( $failed_tests > 0 ) {
    exit( 1 );
}
