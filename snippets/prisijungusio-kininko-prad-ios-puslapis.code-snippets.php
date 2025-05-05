<?php

/**
 * Prisijungusio ūkininko pradžios puslapis
 */
add_filter( 'woocommerce_account_menu_items', 'prideti_farmer_skiltis', 20 );
function prideti_farmer_skiltis( $menu_links ) {
    if ( current_user_can('farmer') ) {

        // Pašalinam nenaudojamus punktus
        unset( $menu_links['dashboard'] ); // Skydelis
        unset( $menu_links['downloads'] ); // Atsisiuntimai
        unset( $menu_links['edit-address'] ); // Adresai
        unset( $menu_links['payment-methods'] ); // Mokėjimo metodai
        unset( $menu_links['user-info'] );

		//Papildomi meniu punktai
        $menu_links = array(
            'farm-info' => 'Ūkio informacija',
            'products' => 'Produktai',
        ) + $menu_links; // Pridedam į pradžią
    }
    return $menu_links;
}

add_action( 'init', 'registruoti_farmer_endpointus' );
function registruoti_farmer_endpointus() {
    add_rewrite_endpoint( 'farm-info', EP_ROOT | EP_PAGES );
    add_rewrite_endpoint( 'products', EP_ROOT | EP_PAGES );
}

// Turinio generavimas:
add_action( 'woocommerce_account_farm-info_endpoint', 'rodyti_ukio_info' );
function rodyti_ukio_info() {
    if ( !is_user_logged_in() ) {
        echo '<p>Turite prisijungti, kad matytumėte savo ūkį.</p>';
        return;
    }

    $user = wp_get_current_user();
    $user_email = $user->user_email;

    // Surandam ūkių term'us
    $farms = get_terms([
        'taxonomy' => 'farm',
        'hide_empty' => false,
    ]);

    $user_farm = null;
    foreach ( $farms as $farm ) {
        $farm_email = get_field( 'farmer_email', 'farm_' . $farm->term_id );
        if ( $farm_email && strtolower( $farm_email ) === strtolower( $user_email ) ) {
            $user_farm = $farm;
            break;
        }
    }

    if ( !$user_farm ) {
        echo '<p>Ūkio informacija nerasta. Prašome susisiekti su administracija.</p>';
        return;
    }

    // Surandam info iš ACF laukų
    $farm_address = get_field( 'farm_address', 'farm_' . $user_farm->term_id );
    $farm_certificate = get_field( 'farm_certificate', 'farm_' . $user_farm->term_id );
    $farm_intro = get_field( 'farm_intro', 'farm_' . $user_farm->term_id );
    $farm_products = get_field( 'farm_products', 'farm_' . $user_farm->term_id );
    $farm_image = get_field( 'farm_image', 'farm_' . $user_farm->term_id );

    echo '<h2>Ūkio informacija</h2>';

    // --- Graži ūkininko kortelė ---
    echo '<div style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px; padding: 20px; background: #f9f9f9; border-radius: 12px;">';
    
    if ( $farm_image ) {
    $image_url = '';

    if ( is_array( $farm_image ) && isset( $farm_image['url'] ) ) {
        // jei masyvas
        $image_url = $farm_image['url'];
    } elseif ( is_numeric( $farm_image ) ) {
        // jei ID
        $image_url = wp_get_attachment_url( $farm_image );
    } elseif ( is_string( $farm_image ) ) {
        // jei jau URL
        $image_url = $farm_image;
    }

    if ( $image_url ) {
        echo '<div style="flex-shrink: 0;">';
        echo '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $user_farm->name ) . '" style="width: 120px; height: 120px; object-fit: cover; border-radius: 12px;">';
        echo '</div>';
    }
}


    echo '<div>';
    echo '<h3 style="margin:0;">' . esc_html( $user_farm->name ) . '</h3>';
    if ( $farm_intro ) {
        echo '<p style="margin:5px 0 0 0; color: #555;"><strong>Aprašymas:</strong> ' . esc_html( $farm_intro ) . '</p>';
    }
    if ( $farm_address ) {
        echo '<p style="margin:5px 0 0 0; font-size:14px; color: #777;"><strong>📍Adresas:</strong> ' . esc_html( $farm_address ) . '</p>';
    }
	if ( $farm_certificate ) {
    echo '<p style="margin:5px 0 0 0; font-size:14px; color: #777;"><strong>Pažymėjimo nr.:</strong> ' . esc_html( $farm_certificate ) . '</p>';
}

    echo '</div>';

    echo '</div>';
}



add_action( 'woocommerce_account_products_endpoint', 'rodyti_farmer_produktus' );
function rodyti_farmer_produktus() {
    if ( !is_user_logged_in() ) {
        echo '<p>Turite prisijungti, kad matytumėte savo produktus.</p>';
        return;
    }

    $user = wp_get_current_user();
    $user_email = $user->user_email;

    // Surandam ūkių term'us
    $farms = get_terms([
        'taxonomy' => 'farm',
        'hide_empty' => false,
    ]);

    $user_farm = null;
    foreach ( $farms as $farm ) {
        $farm_email = get_field( 'farmer_email', 'farm_' . $farm->term_id );
        if ( $farm_email && strtolower( $farm_email ) === strtolower( $user_email ) ) {
            $user_farm = $farm;
            break;
        }
    }

    if ( !$user_farm ) {
        echo '<p>Neradome jūsų ūkininko paskyros pagal el. paštą: ' . esc_html( $user_email ) . '</p>';
        return;
    }

    // Renkam produktus, kurie priskirti šitam ūkiui
    $products = get_posts([
        'post_type' => 'product',
        'posts_per_page' => -1,
        'tax_query' => [
            [
                'taxonomy' => 'farm',
                'field'    => 'term_id',
                'terms'    => $user_farm->term_id,
            ],
        ],
    ]);

    echo '<h2>Jūsų produktai</h2>';

    if ( $products ) {
        echo '<ul style="list-style: none; padding-left: 0;">';
        foreach ( $products as $product ) {
            $thumbnail = get_the_post_thumbnail_url( $product->ID, 'thumbnail' );
            $edit_link = admin_url( 'post.php?post=' . $product->ID . '&action=edit' );

            echo '<li style="margin-bottom: 15px; padding: 10px; background: #f9f9f9; border-radius: 8px; display: flex; align-items: center; gap: 15px;">';

            // Rodom produkto mažą nuotrauką
            if ( $thumbnail ) {
                echo '<img src="' . esc_url( $thumbnail ) . '" alt="' . esc_attr( $product->post_title ) . '" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">';
            } else {
                echo '<div style="width: 60px; height: 60px; background: #e0e0e0; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #888;">📷</div>';
            }

            // Produkto pavadinimas + redagavimo nuoroda
            echo '<div>';
            echo '<strong>' . esc_html( $product->post_title ) . '</strong><br>';
            echo '<a href="' . esc_url( $edit_link ) . '" style="font-size: 14px; color: #007cba;">✏️ Redaguoti produktą</a>';
            echo '</div>';

            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>Jūs dar neturite pridėtų produktų.</p>';
    }

    $new_product_link = admin_url( 'post-new.php?post_type=product' );
    echo '<a href="' . esc_url( $new_product_link ) . '" style="display: inline-block; margin-top: 20px; padding: 10px 20px; background: #008e5d; color: white; border-radius: 8px; text-decoration: none;">➕ Pridėti naują produktą</a>';
}

//By default rodyti "Ūkio informacija"
add_action('template_redirect', function () {
    if (!is_user_logged_in()) return;

    if (is_account_page() && !isset($_GET['action']) && !isset($_GET['key']) && !isset($_GET['lost-password'])) {
        $user = wp_get_current_user();

        if (in_array('farmer', (array) $user->roles)) {
            global $wp;
            if ($wp->request === 'my-account' || $wp->request === 'mano-paskyra') { // jei naudosi lokalizuotą URL
                wp_safe_redirect(site_url('/my-account/farm-info/'));
                exit;
            }
        }
    }
});
