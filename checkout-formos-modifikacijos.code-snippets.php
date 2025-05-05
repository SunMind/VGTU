<?php

/**
 * Checkout formos modifikacijos
 */
// 1. TELEFONAS privalomas
add_filter('woocommerce_billing_fields', function($fields) {
    if (isset($fields['billing_phone'])) {
        $fields['billing_phone']['required'] = true;
    }
    return $fields;
});

// 2. Pristatymo pasirinkimas viršuje checkout
add_action('woocommerce_before_checkout_billing_form', function() {
    ?>
    <div id="pickup_or_delivery_choice" style="margin-bottom:30px;">
        <label for="pickup_or_delivery" style="font-weight:bold;display:block;margin-bottom:5px;">Kaip norėtumėte gauti prekes? <span style="color:red">*</span></label>
        <select name="pickup_or_delivery" id="pickup_or_delivery" required style="width:100%;padding:12px 15px;font-size:16px;line-height:1.4;height:auto;border:1px solid #ccc;border-radius:8px;">
            <option value="">-- Pasirinkite --</option>
            <option value="delivery">Pristatyti nurodytu adresu</option>
            <option value="pickup">Atsiimsiu pats ūkyje</option>
        </select>
    </div>
    <?php
});

// 3. Validuojam pasirinkimą
add_action('woocommerce_checkout_process', function() {
    if (empty($_POST['pickup_or_delivery'])) {
        wc_add_notice(__('Prašome pasirinkti prekių gavimo būdą.'), 'error');
    }
});

// 4. Išsaugom pasirinktą reikšmę užsakyme
add_action('woocommerce_checkout_update_order_meta', function($order_id) {
    if (isset($_POST['pickup_or_delivery'])) {
        update_post_meta($order_id, 'pickup_or_delivery', sanitize_text_field($_POST['pickup_or_delivery']));
    }
});

// 5. Rodyti pasirinkimą admin panelėje
add_action('woocommerce_admin_order_data_after_billing_address', function($order){
    $delivery_method = get_post_meta($order->get_id(), 'pickup_or_delivery', true);
    if ($delivery_method) {
        echo '<p><strong>Pristatymo pasirinkimas:</strong> ' . esc_html($delivery_method === 'pickup' ? 'Atsiims pats ūkyje' : 'Reikalingas pristatymas') . '</p>';
    }
});

// 6. Automatiškai pildyti adresą pagal išsaugotą adresą iš localStorage
add_action('wp_footer', function() {
    if (is_checkout()) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const address = localStorage.getItem('userAddress');

            if (address) {
                const parts = address.split(',');

                if (document.getElementById('billing_address_1') && parts[0]) {
                    document.getElementById('billing_address_1').value = parts[0].trim();
                }
                if (document.getElementById('billing_city') && parts[1]) {
                    document.getElementById('billing_city').value = parts[1].trim();
                }
                if (parts[2]) {
                    const postcodeMatch = parts[2].trim().match(/\d{5}/);
                    if (postcodeMatch && document.getElementById('billing_postcode')) {
                        document.getElementById('billing_postcode').value = postcodeMatch[0];
                    }
                    if (document.getElementById('billing_state')) {
                        const region = parts[2].replace(/\d{5}/, '').trim();
                        document.getElementById('billing_state').value = region;
                    }
                }
                if (document.getElementById('billing_country')) {
                    document.getElementById('billing_country').value = 'LT';
                }
            }

            // Adresų laukų valdymas pagal pasirinkimą
            const methodSelect = document.getElementById('pickup_or_delivery');
            const addressFields = document.querySelectorAll('#billing_address_1_field, #billing_city_field, #billing_postcode_field, #billing_country_field, #billing_state_field');

            function toggleAddressFields() {
                if (methodSelect && methodSelect.value === 'pickup') {
                    addressFields.forEach(field => field.style.display = 'none');
                } else {
                    addressFields.forEach(field => field.style.display = '');
                }
            }

            if (methodSelect) {
                methodSelect.addEventListener('change', toggleAddressFields);
                toggleAddressFields();
            }
        });
        </script>
        <?php
    }
});

// 7. Pirkėjo koordinačių įrašymas į užsakymą (user_lat ir user_lng)
add_action('woocommerce_checkout_before_customer_details', function() {
    echo '<input type="hidden" name="user_lat" id="user_lat">';
    echo '<input type="hidden" name="user_lng" id="user_lng">';
});

add_action('wp_footer', function() {
    if (is_checkout()) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const lat = localStorage.getItem('userLat');
            const lng = localStorage.getItem('userLng');

            if (lat && lng) {
                const latInput = document.getElementById('user_lat');
                const lngInput = document.getElementById('user_lng');

                if (latInput) latInput.value = lat;
                if (lngInput) lngInput.value = lng;
            }
        });
        </script>
        <?php
    }
});

add_action('woocommerce_checkout_update_order_meta', function($order_id) {
    if (isset($_POST['user_lat']) && isset($_POST['user_lng'])) {
        update_post_meta($order_id, '_user_lat', sanitize_text_field($_POST['user_lat']));
        update_post_meta($order_id, '_user_lng', sanitize_text_field($_POST['user_lng']));
    }
});
