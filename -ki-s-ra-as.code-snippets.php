<?php

/**
 * Ūkių sąrašas
 */
function show_featured_farmers_shortcode() {
    $output = '<div class="farmer-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">';

    $farms = get_terms(array(
        'taxonomy' => 'farm',
        'hide_empty' => false,
    ));

    if (!empty($farms) && !is_wp_error($farms)) {
        $count = 0;
        foreach ($farms as $farm) {
            $approved = get_field('farm_approved', 'farm_' . $farm->term_id);

            if (!$approved) continue;
            if ($count >= 3) break;

            $farm_link = get_term_link($farm);
            $farm_address = get_field('farm_address', 'farm_' . $farm->term_id);
            $description = term_description($farm);
            $farm_image = get_field('farm_image', 'farm_' . $farm->term_id);
            $farm_lat = get_field('farm_lat', 'farm_' . $farm->term_id);
            $farm_lng = get_field('farm_lng', 'farm_' . $farm->term_id);

            // Čia pridėjom data-lat ir data-lng
            $output .= '<div class="farm-card" data-lat="' . esc_attr($farm_lat) . '" data-lng="' . esc_attr($farm_lng) . '" style="background: #fffaf3; padding: 20px; border-radius: 12px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); display: flex; flex-direction: column;">';

            if ($farm_image) {
                if (is_array($farm_image)) {
                    $output .= '<img src="' . esc_url($farm_image['url']) . '" alt="' . esc_attr($farm->name) . '" style="width: 100%; height: auto; border-radius: 10px; margin-bottom: 15px;">';
                } else {
                    $output .= '<img src="' . esc_url($farm_image) . '" alt="' . esc_attr($farm->name) . '" style="width: 100%; height: auto; border-radius: 10px; margin-bottom: 15px;">';
                }
            }

            $output .= '<h3 style="text-align: center; font-size: 24px; margin-bottom: 10px;">' . esc_html($farm->name) . '</h3>';
            
            // Pridėjom čia tuščią bloką atstumui
            $output .= '<div class="farm-distance" style="text-align:center; margin-bottom:10px; font-size:14px; color:#008e5d; font-weight:500;"></div>';

            if ($farm_address) {
                $output .= '<p style="text-align:center; margin-bottom: 5px;">' . esc_html($farm_address) . '</p>';
            }
            if ($description) {
                $output .= '<p style="text-align:center; font-size:14px; color:#555;">' . esc_html(wp_trim_words(strip_tags($description), 15)) . '</p>';
            }

            $output .= '</div>'; // uždarom farm-card

            $count++;
        }
    } else {
        $output .= '<p>Nėra registruotų ūkių.</p>';
    }

    $output .= '</div>';

    $output .= '<div style="text-align:center; margin-top: 30px;">';
    $output .= '<a href="' . site_url('/ukininko-rinkinys') . '" style="padding:12px 24px;background:#008e5d;color:white;text-decoration:none;border-radius:8px;font-weight:bold;">Žiūrėti visus ūkius</a>';
    $output .= '</div>';

    return $output;
}
add_shortcode('featured_farmers', 'show_featured_farmers_shortcode');


// ✅ Paliekam ūkių sąrašo shortcode
function show_farmer_list_shortcode() {
    $output = '<div class="farmer-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">';

    $farms = get_terms([
        'taxonomy' => 'farm',
        'hide_empty' => false,
    ]);

    if (!empty($farms) && !is_wp_error($farms)) {
        foreach ($farms as $farm) {
            $approved = get_field('farm_approved', 'farm_' . $farm->term_id);

            if (!$approved) {
                continue; // Tik patvirtinti ūkiai
            }

            $farm_link = get_term_link($farm);
            $farm_address = get_field('farm_address', 'farm_' . $farm->term_id);
            $description = term_description($farm);
            $lat = get_field('farm_lat', 'farm_' . $farm->term_id);
            $lng = get_field('farm_lng', 'farm_' . $farm->term_id);
            $farm_image = get_field('farm_image', 'farm_' . $farm->term_id);

            $output .= '<div class="farm-card" data-lat="' . esc_attr($lat) . '" data-lng="' . esc_attr($lng) . '" style="background: #fffaf3; padding: 20px; border-radius: 12px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); display: flex; flex-direction: column;">';
            
            if ($farm_image) {
                if (is_array($farm_image)) {
                    $output .= '<img src="' . esc_url($farm_image['url']) . '" alt="' . esc_attr($farm->name) . '" style="width: 100%; height: auto; border-radius: 10px; margin-bottom: 15px;">';
                } else {
                    $output .= '<img src="' . esc_url($farm_image) . '" alt="' . esc_attr($farm->name) . '" style="width: 100%; height: auto; border-radius: 10px; margin-bottom: 15px;">';
                }
            }

            $output .= '<div class="farm-header" style="text-align: center; margin-bottom: 10px;">';
            $output .= '<h3 style="margin: 0; font-size: 24px;">' . esc_html($farm->name) . '</h3>';
            $output .= '<div class="farm-distance" style="margin-top: 5px; font-size: 14px; color: #008e5d;"></div>';
            $output .= '</div>';

            if ($farm_address) {
                $output .= '<p style="margin: 0;">' . esc_html($farm_address) . '</p>';
            }

            if ($description) {
                $output .= '<p style="margin-top: 10px; font-size: 14px; color: #555;">' . esc_html(wp_trim_words(strip_tags($description), 20)) . '</p>';
            }

            $output .= '<a href="' . esc_url($farm_link) . '" style="margin-top: auto; padding: 10px 15px; background: #008e5d; color: white; border-radius: 8px; text-decoration: none; font-weight: bold; display: inline-block;">Peržiūrėti produktus</a>';
            $output .= '</div>';
        }
    } else {
        $output .= '<p>Nėra registruotų ūkių.</p>';
    }

    $output .= '</div>';
    return $output;
}
add_shortcode('farmer_list', 'show_farmer_list_shortcode');
