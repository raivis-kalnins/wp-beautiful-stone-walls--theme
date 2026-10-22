<?php
/** Small integrations: Yoast owns metadata; load form assets only where a form is used. */
defined( 'ABSPATH' ) || exit;

function bsw_contact_assets_needed( $load ) {
    if ( is_admin() ) { return $load; }
    if ( bsw_is_page( 'contact-us' ) ) { return true; }
    $post = get_post();
    return $post && ( has_shortcode( $post->post_content, 'contact-form-7' ) || has_block( 'contact-form-7/contact-form-selector', $post ) );
}
add_filter( 'wpcf7_load_js', 'bsw_contact_assets_needed' );
add_filter( 'wpcf7_load_css', 'bsw_contact_assets_needed' );

function bsw_frontend_performance() {
    if ( is_admin() ) { return; }
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
}
add_action( 'init', 'bsw_frontend_performance' );

// Extend Yoast's existing organization node instead of emitting a second schema graph.
function bsw_schema_organization( $data ) {
    $data['@type'] = array( 'Organization', 'HomeAndConstructionBusiness' );
    $data['name'] = 'Beautiful Stone Walls';
    $phone = bsw_contact( 'phone_link' );
    // A demonstration number is useful in the layout, but is not a real business phone.
    if ( preg_match( '/[1-9]/', $phone ) ) { $data['telephone'] = $phone; } else { unset( $data['telephone'] ); }
    $data['email'] = bsw_contact( 'email' );
    $address = bsw_contact( 'address' );
    $country = bsw_contact_country();
    $country_codes = array( 'Ireland' => 'IE', 'Latvija' => 'LV', 'Latvia' => 'LV' );
    $data['address'] = array( '@type' => 'PostalAddress', 'addressCountry' => $country_codes[ $country ] ?? $country );
    if ( $address && $address !== $country ) { $data['address']['streetAddress'] = $address; }
    $data['areaServed'] = array( '@type' => 'Country', 'name' => $country );
    if ( bsw_contact( 'facebook' ) ) {
        $same_as = isset( $data['sameAs'] ) && is_array( $data['sameAs'] ) ? $data['sameAs'] : array();
        $data['sameAs'] = array_values( array_unique( array_merge( $same_as, array( bsw_contact( 'facebook' ) ) ) ) );
    }
    return $data;
}
add_filter( 'wpseo_schema_organization', 'bsw_schema_organization' );

// Native posts are stonework portfolio pages, with no invented article author or review ratings.
function bsw_schema_article_needed( $needed ) {
    return is_singular( 'post' ) ? false : $needed;
}
add_filter( 'wpseo_schema_needs_article', 'bsw_schema_article_needed' );

function bsw_hreflang_default( $links ) {
    foreach ( array( 'en-GB', 'en' ) as $key ) {
        if ( isset( $links[ $key ] ) ) { $links['x-default'] = $links[ $key ]; break; }
    }
    return $links;
}
add_filter( 'pll_rel_hreflang_attributes', 'bsw_hreflang_default' );
