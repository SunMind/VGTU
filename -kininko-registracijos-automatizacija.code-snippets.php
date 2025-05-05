<?php

/**
 * Ūkininko registracijos automatizacija
 */
// ✅ Paliekam WPForms registracijos automatiką
add_action('wpforms_process_complete', function($fields, $entry, $form_data) {
    if ($form_data['id'] != 217) {
        return;
    }

    $user_email = '';
    $user_pass = '';
    $user_name = '';
    $farm_name = '';
    $farm_address = '';
    $farm_description = '';
    $farm_certificate = '';

    foreach ($fields as $field) {
        switch ($field['id']) {
            case 2: $user_email = sanitize_email($field['value']); break;
            case 3: $user_pass = sanitize_text_field($field['value']); break;
            case 1: $user_name = sanitize_text_field($field['value']); break;
            case 4: $farm_name = sanitize_text_field($field['value']); break;
            case 5: $farm_address = sanitize_text_field($field['value']); break;
            case 6: $farm_description = sanitize_textarea_field($field['value']); break;
            case 7: $farm_certificate = sanitize_text_field($field['value']); break;
        }
    }

    if ($user_email && !email_exists($user_email)) {
        $user_id = wp_insert_user([
            'user_login' => sanitize_user(current(explode('@', $user_email))),
            'user_email' => $user_email,
            'user_pass' => $user_pass,
            'role' => 'farmer',
            'first_name' => $user_name,
        ]);

        if (!is_wp_error($user_id)) {
            if (!empty($farm_name)) {
                $existing_term = term_exists($farm_name, 'farm');

                if (!$existing_term) {
                    $result = wp_insert_term($farm_name, 'farm', [
                        'slug' => sanitize_title($farm_name),
                    ]);

                    if (!is_wp_error($result)) {
                        $term_id = $result['term_id'];

                        if (!empty($farm_address)) {
                            update_field('farm_address', $farm_address, 'farm_' . $term_id);
                        }
                        if (!empty($farm_description)) {
                            wp_update_term($term_id, 'farm', ['description' => $farm_description]);
                        }
                        if (!empty($farm_certificate)) {
                            update_field('farm_certificate', $farm_certificate, 'farm_' . $term_id);
                        }

                        // Adreso korekcija
                        $farm_address = str_replace(['k.', 'rajonas'], ['kaimas ', ''], $farm_address);
                        $farm_address = str_replace('  ', ' ', $farm_address);
                        if (strpos($farm_address, 'Lietuva') === false) {
                            $farm_address .= ', Lietuva';
                        }

                        // Koordinatės
                        $coords = get_lat_lng_from_address($farm_address);
                        if (!empty($coords)) {
                            update_field('farm_lat', $coords['lat'], 'farm_' . $term_id);
                            update_field('farm_lng', $coords['lng'], 'farm_' . $term_id);
                        }

                        // Notifikacija adminui
                        $admin_email = get_option('admin_email');
                        $message = "Sveiki,\n\nSukurtas naujas ūkis:\n\n";
                        $message .= "Pavadinimas: $farm_name\n";
                        $message .= "Adresas: $farm_address\n";
                        $message .= "Aprašymas: $farm_description\n";
                        $message .= "Sertifikato numeris: $farm_certificate\n\n";

                        if (!empty($coords)) {
                            $message .= "✅ Koordinatės rastos: Lat: {$coords['lat']}, Lng: {$coords['lng']}\n";
                        } else {
                            $message .= "⚠️ Koordinatės nerastos.\n";
                        }

                        $message .= "\nPrisijunkite prie administravimo panelės patvirtinti šį ūkį.";
                        wp_mail($admin_email, 'Naujas ūkis – KaimoProduktai.lt', $message);
                    }
                }
            }
        }
    }
}, 10, 3);

// ✅ Funkcija koordinačių gavimui
function get_lat_lng_from_address($address) {
    $api_key = 'AIzaSyD3YkNGPwswa8eGfRQYeS_6ixIS5I6gm2o';
    $address = urlencode($address);
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$api_key}";

    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!empty($data['results'][0]['geometry']['location'])) {
        return [
            'lat' => $data['results'][0]['geometry']['location']['lat'],
            'lng' => $data['results'][0]['geometry']['location']['lng'],
        ];
    }
    return false;
}

add_filter('wpforms_field_properties', function( $properties, $field, $form_data ) {
    // Pakeisk form_id ir field_id pagal savo formą ir lauką
    if ( $form_data['id'] == 217 && $field['id'] == 5 ) {
        $properties['inputs']['primary']['attr']['id'] = 'userAddress';
    }
    return $properties;
}, 10, 3);

add_action('wp_footer', function() {
    // if (!is_page('ukininko-registracija')) return; // arba naudok page ID
    ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD3YkNGPwswa8eGfRQYeS_6ixIS5I6gm2o&libraries=places&callback=initAutocomplete" async defer></script>
    <script>
		document.addEventListener('DOMContentLoaded', function () {
    const originalInput = document.getElementById('wpforms-217-field_5');
    if (originalInput) {
        originalInput.setAttribute('id', 'userAddress');
        initAutocomplete(); // dabar paleidžiam, kai jau yra tas ID
    }
});
    </script>
    <?php
});
