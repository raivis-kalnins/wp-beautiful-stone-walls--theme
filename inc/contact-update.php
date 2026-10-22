<?php
/** One-time, native WordPress update for the requested demonstration contact details. */
defined( 'ABSPATH' ) || exit;

function bsw_contact_update_text( $text, $language ) {
    $shared = array(
        'info@beautifulstonewalls.ie' => 'hello@dev4.click',
        '+353862043199' => '0000',
        '086 2043199' => '0000',
        '0862043199' => '0000',
    );
    $english = array(
        'Based in Mallow, at the heart of County Cork,' => 'Based in Ireland,',
        'We operate from Mallow in the heart of County Cork.' => 'We operate in Ireland.',
        'from Mallow, County Cork,' => 'in Ireland',
        'in Mallow, County Cork' => 'in Ireland',
        'across Munster' => 'across Ireland',
        'throughout Munster' => 'throughout Ireland',
        'Stonework in Cork & Munster | Beautiful Stone Walls' => 'Stonework in Ireland | Beautiful Stone Walls',
    );
    $latvian = array(
        'Mallow pilsētā, Korkas grāfistes sirdī' => 'Latvijā',
        'Mallow pilsētā, Korkas grāfistē' => 'Latvijā',
        'Mallow, Korkas grāfistē' => 'Latvijā',
        'Mallow pilsētā' => 'Latvijā',
        'visā Munster reģionā' => 'visā Latvijā',
        'Munster reģionā' => 'Latvijā',
        'Akmens darbi Īrijā | Beautiful Stone Walls' => 'Akmens darbi Latvijā | Beautiful Stone Walls',
    );
    return strtr( (string) $text, $shared + ( 'lv' === $language ? $latvian : $english ) );
}

function bsw_upgrade_contact_details() {
    if ( ! current_user_can( 'manage_options' ) || '1.2.0' === get_option( 'bsw_contact_update_version' ) ) { return; }

    // A checkpoint stops a later dependency retry from overwriting newly edited contact settings.
    if ( ! get_option( 'bsw_contact_defaults_120_applied' ) ) {
        foreach ( array( 'phone' => '0000', 'phone_link' => '0000', 'email' => 'hello@dev4.click', 'address' => '', 'area' => '', 'country_en' => 'Ireland', 'country_lv' => 'Latvija' ) as $key => $value ) {
            set_theme_mod( 'bsw_' . $key, $value );
        }
        update_option( 'bsw_contact_defaults_120_applied', 1, false );
    }

    $reindex_ids = array();
    $errors = array();
    // These are current company summaries. Historic projects and review attributions stay intact.
    foreach ( array( 'home', 'about-us', 'contact-us' ) as $slug ) {
        $original = get_page_by_path( $slug );
        if ( ! $original ) { continue; }
        $translations = function_exists( 'pll_get_post_translations' ) ? pll_get_post_translations( $original->ID ) : array();
        if ( ! $translations ) { $translations = array( 'en' => $original->ID ); }
        foreach ( $translations as $language => $post_id ) {
            if ( ! in_array( $language, array( 'en', 'lv' ), true ) ) { continue; }
            $post = get_post( $post_id );
            if ( ! $post ) { continue; }
            $reindex_ids[] = (int) $post_id;
            $update = array( 'ID' => $post_id );
            foreach ( array( 'post_content', 'post_excerpt' ) as $field ) {
                $updated = bsw_contact_update_text( $post->$field, $language );
                if ( $updated !== $post->$field ) { $update[ $field ] = $updated; }
            }
            foreach ( array( '_yoast_wpseo_title', '_yoast_wpseo_metadesc', '_yoast_wpseo_opengraph-title', '_yoast_wpseo_opengraph-description', '_yoast_wpseo_twitter-title', '_yoast_wpseo_twitter-description' ) as $key ) {
                $current = get_post_meta( $post_id, $key, true );
                $updated = bsw_contact_update_text( $current, $language );
                if ( $updated !== $current ) {
                    update_post_meta( $post_id, $key, wp_slash( $updated ) );
                }
            }
            if ( count( $update ) > 1 ) {
                $result = wp_update_post( wp_slash( $update ), true );
                if ( is_wp_error( $result ) ) { $errors[] = $result->get_error_message(); }
            }
        }
    }

    // Update only the site's configured enquiry form and its native Polylang counterpart.
    $form_id = (int) get_option( 'bsw_contact_form_id', 70 );
    $form_ids = function_exists( 'pll_get_post_translations' ) ? array_values( pll_get_post_translations( $form_id ) ) : array();
    $form_ids[] = $form_id;
    foreach ( array_unique( array_map( 'absint', $form_ids ) ) as $id ) {
        if ( 'wpcf7_contact_form' !== get_post_type( $id ) ) { continue; }
        if ( ! class_exists( 'WPCF7_ContactForm' ) ) { $errors[] = 'Contact Form 7 must be active to update the enquiry recipient.'; continue; }
        $form = WPCF7_ContactForm::get_instance( $id );
        if ( ! $form ) { continue; }
        $properties = $form->get_properties();
        $updated = $properties;
        foreach ( array( 'mail', 'mail_2' ) as $mail_key ) {
            foreach ( array( 'recipient', 'body', 'additional_headers' ) as $field ) {
                if ( isset( $updated[ $mail_key ][ $field ] ) ) {
                    $updated[ $mail_key ][ $field ] = strtr( $updated[ $mail_key ][ $field ], array( 'info@beautifulstonewalls.ie' => 'hello@dev4.click', '086 2043199' => '0000', '+353862043199' => '0000', '0862043199' => '0000' ) );
                }
            }
        }
        if ( $updated !== $properties ) { $form->set_properties( $updated ); $form->save(); }
    }

    if ( class_exists( 'WPSEO_Options' ) ) {
        if ( 'info@beautifulstonewalls.ie' === WPSEO_Options::get( 'org-email' ) ) { WPSEO_Options::set( 'org-email', 'hello@dev4.click' ); }
        $org_phone = preg_replace( '/[^0-9]/', '', (string) WPSEO_Options::get( 'org-phone' ) );
        if ( in_array( $org_phone, array( '353862043199', '0862043199' ), true ) ) { WPSEO_Options::set( 'org-phone', '' ); }
    }
    // Rebuild only the scoped company pages, including on retries after metadata was already saved.
    // A metadata write alone can leave Yoast's cached description stale.
    if ( $reindex_ids && function_exists( 'YoastSEO' ) && class_exists( '\Yoast\WP\SEO\Integrations\Watchers\Indexable_Post_Watcher' ) ) {
        try {
            $watcher = YoastSEO()->classes->get( 'Yoast\WP\SEO\Integrations\Watchers\Indexable_Post_Watcher' );
            foreach ( array_unique( $reindex_ids ) as $id ) { $watcher->build_indexable( $id ); }
        } catch ( \Throwable $error ) {
            $errors[] = 'Yoast could not refresh its changed contact metadata.';
        }
    }
    if ( $errors ) {
        update_option( 'bsw_contact_update_error', implode( ' ', array_unique( $errors ) ), false );
        return;
    }
    delete_option( 'bsw_contact_update_error' );
    update_option( 'bsw_contact_update_version', '1.2.0', false );
}
add_action( 'admin_init', 'bsw_upgrade_contact_details', 25 );

function bsw_contact_update_notice() {
    if ( current_user_can( 'manage_options' ) && get_option( 'bsw_contact_update_error' ) ) {
        echo '<div class="notice notice-warning"><p>' . esc_html( get_option( 'bsw_contact_update_error' ) ) . '</p></div>';
    }
}
add_action( 'admin_notices', 'bsw_contact_update_notice' );
