<?php

/**
 * Thankyou puslapio modifikacijos
 */
// 1. Panaikinam WooCommerce numatytą adresų lentelę
remove_action('woocommerce_order_details_after_order_table', 'woocommerce_order_again_button');
remove_action('woocommerce_thankyou', 'woocommerce_order_details_table', 10);

// 2. Parodom gražią, pritaikytą informaciją
add_action('woocommerce_thankyou', function($order_id) {
    if (!$order_id) return;
    $order = wc_get_order($order_id);
    if (!$order) return;

    // Pirkėjo duomenys
    $first_name = $order->get_billing_first_name();
    $last_name = $order->get_billing_last_name();
    $phone = $order->get_billing_phone();
    $email = $order->get_billing_email();

    // Pasirinktas pristatymo metodas
    $delivery_method = get_post_meta($order_id, 'pickup_or_delivery', true);

    // Pirkėjo adresas
    $address_parts = [];
    if ($order->get_billing_address_1()) $address_parts[] = $order->get_billing_address_1();
    if ($order->get_billing_city()) $address_parts[] = $order->get_billing_city();
    if ($order->get_billing_state()) $address_parts[] = $order->get_billing_state();
    if ($order->get_billing_postcode()) $address_parts[] = $order->get_billing_postcode();

    $address_text = implode(', ', array_filter($address_parts));

    echo '<div style="margin: 30px 0; padding: 20px; background: #f8f8f8; border-radius: 10px;">';
    echo '<h2 style="margin-top: 0;">Jūsų pateikti duomenys</h2>';

    // Vardas + pavardė
    echo '<p><strong>Vardas Pavardė:</strong> ' . esc_html($first_name . ' ' . $last_name) . '</p>';

    // Priklausomai nuo pasirinkimo rodom adresą arba "atsiimsite pats"
    if ($delivery_method === 'pickup') {
        echo '<p><strong>Prekių gavimas:</strong> Atsiimsite pats ūkyje.</p>';
    } else {
        echo '<p><strong>Pristatymo adresas:</strong> ' . esc_html($address_text) . '</p>';
    }

    // Telefonas
    echo '<p><strong>Telefonas:</strong> ' . esc_html($phone) . '</p>';

    // El. paštas
    echo '<p><strong>El. paštas:</strong> ' . esc_html($email) . '</p>';

    echo '</div>';
}, 5);
