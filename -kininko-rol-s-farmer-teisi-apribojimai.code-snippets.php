<?php

/**
 * Ūkininko rolės "farmer" teisių apribojimai
 */
add_action( 'init', 'prideti_farmer_teises' );
function prideti_farmer_teises() {
    $role = get_role( 'farmer' );

    if ( $role ) {
        $role->add_cap( 'read' );
        $role->add_cap( 'edit_posts' );
        $role->add_cap( 'edit_products' );
        $role->add_cap( 'publish_products' );
        $role->add_cap( 'edit_published_products' );
    }
}

// 1. Paslepiam visą admin meniu, paliekant tik "Produktai"
add_action( 'admin_menu', 'apriboti_farmer_admin_meniu', 999 );
function apriboti_farmer_admin_meniu() {
    if ( current_user_can('farmer') ) {

        global $menu;
        global $submenu;

        $allowed = [
            'edit.php?post_type=product', // Produktų meniu
        ];

        foreach ( $menu as $key => $value ) {
            if ( isset( $value[2] ) && !in_array( $value[2], $allowed ) ) {
                unset( $menu[ $key ] );
            }
        }

        // Panaikinam net profilio redagavimą
        remove_menu_page('profile.php');
    }
}

// 2. Paslepiam viršutinę WordPress admin juostą (admin bar)
add_action('after_setup_theme', function() {
    if ( current_user_can('farmer') ) {
        show_admin_bar( false );
    }
});

// 3. Peradresuojam ūkininką prisijungus į produktų sąrašą
add_action( 'admin_init', 'peradresuoti_farmer_i_produktus' );
function peradresuoti_farmer_i_produktus() {
    if ( current_user_can('farmer') && is_admin() && !defined('DOING_AJAX') ) {
        $current_screen = get_current_screen();
        if ( $current_screen && $current_screen->base === 'dashboard' ) {
            wp_redirect( admin_url( 'edit.php?post_type=product' ) );
            exit;
        }
    }
}

// 4. Paslepiam WooCommerce pranešimus (notifikacijas) ūkininkams
add_filter( 'woocommerce_screen_ids', function( $screen_ids ) {
    if ( current_user_can('farmer') ) {
        // Išvalom WooCommerce notifikacijas
        return [];
    }
    return $screen_ids;
});

add_action( 'add_meta_boxes', 'paslepti_farm_taxonomy_farmeriams', 999 );
function paslepti_farm_taxonomy_farmeriams() {
    if ( current_user_can( 'farmer' ) ) {
        remove_meta_box( 'tagsdiv-farm', 'product', 'side' );
    }
}
add_action( 'init', 'papildyti_farmer_teises_darbui_su_produktais' );
function papildyti_farmer_teises_darbui_su_produktais() {
    $role = get_role( 'farmer' );
    
    if ( $role ) {
        // Leidžiame įkelti paveikslėlius
        $role->add_cap( 'upload_files' );
        
        // Leidžiame priskirti produktų kategorijas
        $role->add_cap( 'assign_product_terms' );
        
        // Galimybė redaguoti paskelbtus produktus
        $role->add_cap( 'edit_published_products' );
    }
}
