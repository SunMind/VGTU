<?php

/**
 * Po apmokėjimo. Maršrutas pirkėjui. Laiškai pirkėjui / tiekėjui.
 */
add_action('woocommerce_thankyou', function($order_id) {
    if (!$order_id) return;
    $order = wc_get_order($order_id);
    if (!$order) return;

    $delivery_method = get_post_meta($order_id, 'pickup_or_delivery', true);

    // Pirkėjo informacija
    $first_name = $order->get_billing_first_name();
    $last_name = $order->get_billing_last_name();
    $phone = $order->get_billing_phone();
    $email = $order->get_billing_email();

    // Užsakytų ūkių ir produktų surinkimas
    $farmer_orders = [];
    $farms = [];

    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        $farm_terms = wp_get_post_terms($product_id, 'farm');

        if (!empty($farm_terms) && !is_wp_error($farm_terms)) {
            $farm = $farm_terms[0];
            $farm_id = $farm->term_id;

            if (!isset($farmer_orders[$farm_id])) {
                $farmer_orders[$farm_id] = [
                    'farm' => $farm,
                    'items' => []
                ];
            }

            $farmer_orders[$farm_id]['items'][] = $item;

            if (!isset($farms[$farm_id])) {
                $farm_lat = get_field('farm_lat', 'farm_' . $farm_id);
                $farm_lng = get_field('farm_lng', 'farm_' . $farm_id);
                $farm_address = get_field('farm_address', 'farm_' . $farm_id);

                if ($farm_lat && $farm_lng) {
                    $farms[$farm_id] = [
                        'name' => $farm->name,
                        'address' => $farm_address,
                        'lat' => $farm_lat,
                        'lng' => $farm_lng
                    ];
                }
            }
        }
    }

    // === 1. Siunčiam laiškus ūkininkams apie jų produktus ===
    foreach ($farmer_orders as $farm_id => $farm_data) {
        $farm = $farm_data['farm'];
        $items = $farm_data['items'];
        $farmer_email = get_field('farmer_email', 'farm_' . $farm_id);

        if (!$farmer_email) {
            $farmer_email = get_option('admin_email'); // Jei nėra ūkininko el. pašto
        }

        $farmer_message = "Sveiki,\n\n";
        $farmer_message .= "Gavote naują užsakymą!\n\n";
        $farmer_message .= "Pirkėjo vardas: {$first_name} {$last_name}\n";
        $farmer_message .= "Telefonas: {$phone}\n";
        $farmer_message .= "El. paštas: {$email}\n";

        if ($delivery_method === 'pickup') {
            $farmer_message .= "Prekių gavimas: Pirkėjas atsiims ūkyje.\n\n";
        } else {
            $address_parts = [];
            if ($order->get_billing_address_1()) $address_parts[] = $order->get_billing_address_1();
            if ($order->get_billing_city()) $address_parts[] = $order->get_billing_city();
            if ($order->get_billing_state()) $address_parts[] = $order->get_billing_state();
            if ($order->get_billing_postcode()) $address_parts[] = $order->get_billing_postcode();
            $address_text = implode(', ', array_filter($address_parts));

            $farmer_message .= "Pristatymo adresas: {$address_text}\n\n";
        }

        $farmer_message .= "Užsakytos prekės:\n";
        foreach ($items as $item) {
            $farmer_message .= "- " . $item->get_name() . ' (Kiekis: ' . $item->get_quantity() . ")\n";
        }

        $farmer_message .= "\nParuoškite užsakymą iki ketvirtadienio vakaro.\n";
        $farmer_message .= "Pristatymas vykdomas penktadienį.\n\n";

        wp_mail($farmer_email, 'Naujas užsakymas jūsų ūkiui - KaimoProduktai.lt', $farmer_message);

        error_log("\n\n===== Išsiųstas laiškas ūkininkui: {$farmer_email} =====\n" . $farmer_message . "\n");
    }

    // === 2. Siunčiam pirkėjui jo užsakymo informaciją su maršrutu ===
    $buyer_message = "Sveiki, {$first_name}!\n\n";
    $buyer_message .= "Ačiū, kad pateikėte užsakymą KaimoProduktai.lt platformoje.\n\n";
    $buyer_message .= "Jūsų užsakymo detalės:\n";

    foreach ($farmer_orders as $farm_data) {
        $farm = $farm_data['farm'];
        $items = $farm_data['items'];

        $buyer_message .= "\nIš ūkio: " . $farm->name . "\n";
        foreach ($items as $item) {
            $buyer_message .= "- " . $item->get_name() . ' (Kiekis: ' . $item->get_quantity() . ")\n";
        }
    }

    $route_text = '';

    if ($delivery_method === 'pickup' && !empty($farms)) {
        $user_lat = get_post_meta($order_id, '_user_lat', true);
        $user_lng = get_post_meta($order_id, '_user_lng', true);

        if ($user_lat && $user_lng) {
            $route = [];
            $current_lat = $user_lat;
            $current_lng = $user_lng;

            while (!empty($farms)) {
                $closest_farm_id = null;
                $closest_distance = INF;

                foreach ($farms as $farm_id => $farm) {
                    $distance = haversine_distance($current_lat, $current_lng, $farm['lat'], $farm['lng']);
                    if ($distance < $closest_distance) {
                        $closest_distance = $distance;
                        $closest_farm_id = $farm_id;
                    }
                }

                if ($closest_farm_id !== null) {
                    $route[] = $farms[$closest_farm_id];
                    $current_lat = $farms[$closest_farm_id]['lat'];
                    $current_lng = $farms[$closest_farm_id]['lng'];
                    unset($farms[$closest_farm_id]);
                } else {
                    break;
                }
            }

            $buyer_message .= "\nJūsų atsiėmimo maršrutas:\n";
            $step = 1;
            foreach ($route as $farm) {
                $line = "{$step}. {$farm['name']} – {$farm['address']}";
                $buyer_message .= $line . "\n";
                $route_text .= $line . "\n";
                $step++;
            }

            $buyer_message .= "\nPrimename: atsiėmimą atlikite penktadienį nuo 10:00 iki 18:00.\n";
        }
    } else {
        $address_parts = [];
        if ($order->get_billing_address_1()) $address_parts[] = $order->get_billing_address_1();
        if ($order->get_billing_city()) $address_parts[] = $order->get_billing_city();
        if ($order->get_billing_state()) $address_parts[] = $order->get_billing_state();
        if ($order->get_billing_postcode()) $address_parts[] = $order->get_billing_postcode();
        $address_text = implode(', ', array_filter($address_parts));

        $buyer_message .= "\nPrekės bus pristatytos adresu:\n{$address_text}\n";
    }

    $buyer_message .= "\nAčiū, kad palaikote Lietuvos ūkininkus!\n\nKaimoProduktai.lt komanda 🌱";

    wp_mail($email, 'Jūsų užsakymo patvirtinimas - KaimoProduktai.lt', $buyer_message);

    // Rodyti maršrutą „Thank you“ puslapyje
    if (!empty($route_text)) {
        add_action('woocommerce_thankyou_' . $order->get_payment_method(), function() use ($route_text) {
            echo '<section style="margin-top:40px;">';
            echo '<h2>Jūsų atsiėmimo maršrutas:</h2>';
            echo '<pre style="background:#f8f4ec;padding:20px;border-radius:8px;">' . esc_html($route_text) . '</pre>';
            echo '</section>';
        });
    }

}, 20);

// Atstumo formulė
function haversine_distance($lat1, $lng1, $lat2, $lng2) {
    $earth_radius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);

    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng/2) * sin($dLng/2);

    $c = 2 * atan2(sqrt($a), sqrt(1-$a));

    return $earth_radius * $c;
}
