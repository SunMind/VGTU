<?php

/**
 * Ūkininko kuriamo produkto priskyrimas jam
 */
add_action( 'save_post_product', 'auto_priskirti_farmer_farm_taxonomy', 20, 3 );
function auto_priskirti_farmer_farm_taxonomy( $post_ID, $post, $update ) {
    // Tik jei tai naujas įrašas
    if ( !$update && is_user_logged_in() ) {
        $user = wp_get_current_user();

        // Tik jei vartotojas yra ūkininkas
        if ( in_array( 'farmer', (array) $user->roles ) ) {

            // Surandam ūkininko farm'ą pagal el. paštą
            $farms = get_terms([
                'taxonomy' => 'farm',
                'hide_empty' => false,
            ]);

            $user_farm = null;
            foreach ( $farms as $farm ) {
                $farm_email = get_field( 'farmer_email', 'farm_' . $farm->term_id );
                if ( $farm_email && strtolower( $farm_email ) === strtolower( $user->user_email ) ) {
                    $user_farm = $farm;
                    break;
                }
            }

            if ( $user_farm ) {
                // Priskiriam farm taxonomy šitam produktui
                wp_set_post_terms( $post_ID, [ $user_farm->term_id ], 'farm' );
            }
        }
    }
}

add_action( 'save_post_product', 'priskirti_farmer_kaip_produkto_autoriu', 10, 3 );
function priskirti_farmer_kaip_produkto_autoriu( $post_ID, $post, $update ) {
    // Tik jei tai yra naujas įrašas (ne atnaujinamas)
    if ( !$update ) {
        // Tik jei vartotojas prisijungęs
        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();

            // Tik jei vartotojas turi 'farmer' rolę
            if ( in_array( 'farmer', (array) $user->roles ) ) {
                // Pakeičiam produkto autorių į šį vartotoją
                wp_update_post( [
                    'ID' => $post_ID,
                    'post_author' => $user->ID
                ] );
            }
        }
    }
}
