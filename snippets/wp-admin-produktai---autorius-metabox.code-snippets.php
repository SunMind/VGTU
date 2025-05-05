<?php

/**
 * WP Admin: Produktai -> Autorius metabox
 */
// 1. Pridedam "Autorius" laukelį produktams
add_action( 'add_meta_boxes', 'prideti_autoriaus_lauka_produktams', 10 );
function prideti_autoriaus_lauka_produktams() {
    add_meta_box(
        'authordiv',
        'Autorius',
        'post_author_meta_box',
        'product',
        'normal',
        'default'
    );
}

// 2. Paslepiam "Autorius" laukelį ūkininkams
add_action( 'add_meta_boxes', 'paslepti_autoriaus_lauka_farmeriams', 20 );
function paslepti_autoriaus_lauka_farmeriams() {
    $user = wp_get_current_user();
    if ( in_array( 'farmer', (array) $user->roles ) && !current_user_can('administrator') ) {
        remove_meta_box( 'authordiv', 'product', 'normal' );
    }
}
