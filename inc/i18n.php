<?php
/** Language-aware presentation; Polylang remains the owner of translated content. */
defined( 'ABSPATH' ) || exit;

function bsw_t( $text ) {
    return __( $text, 'beautiful-stone-walls' ); // Catalog includes the complete, finite set of theme strings.
}

function bsw_dynamic_text( $text ) {
    $translated = function_exists( 'pll__' ) ? pll__( $text ) : $text;
    return $translated !== $text ? $translated : bsw_t( $text );
}

function bsw_home_url() {
    $url = function_exists( 'pll_home_url' ) ? pll_home_url() : '';
    return $url ? $url : home_url( '/' );
}

function bsw_translated_post_id( $post_id ) {
    $translated = function_exists( 'pll_get_post' ) ? pll_get_post( $post_id ) : 0;
    return $translated ? (int) $translated : (int) $post_id;
}

function bsw_is_page( $slug ) {
    $page = get_page_by_path( $slug );
    return $page && is_page( bsw_translated_post_id( $page->ID ) );
}

// Translated page slugs use the same dedicated templates as their English originals.
function bsw_translated_page_template( $template ) {
    if ( ! is_page() || is_front_page() ) { return $template; }
    foreach ( array( 'our-work', 'contact-us' ) as $slug ) {
        if ( bsw_is_page( $slug ) ) { return get_theme_file_path( '/page-' . $slug . '.php' ); }
    }
    return $template;
}
add_filter( 'template_include', 'bsw_translated_page_template' );

function bsw_language_switcher() {
    if ( ! function_exists( 'pll_the_languages' ) ) { return; }
    $languages = pll_the_languages( array( 'raw' => 1, 'hide_if_empty' => 0, 'hide_if_no_translation' => 0 ) );
    if ( ! is_array( $languages ) || count( $languages ) < 2 ) { return; }
    usort( $languages, static function ( $a, $b ) {
        $order = array( 'en' => 0, 'lv' => 1 );
        return ( $order[ $a['slug'] ] ?? 9 ) <=> ( $order[ $b['slug'] ] ?? 9 );
    } );
    echo '<nav class="language-switcher" aria-label="' . esc_attr( bsw_t( 'Language' ) ) . '">';
    foreach ( $languages as $language ) {
        $active = ! empty( $language['current_lang'] );
        $name = $language['name'];
        echo '<a class="lang-link' . ( $active ? ' is-active' : '' ) . '" href="' . esc_url( $language['url'] ) . '" hreflang="' . esc_attr( str_replace( '_', '-', $language['locale'] ) ) . '" lang="' . esc_attr( $language['slug'] ) . '" aria-label="' . esc_attr( $name ) . '" title="' . esc_attr( $name ) . '"' . ( $active ? ' aria-current="page"' : '' ) . '>';
        if ( ! empty( $language['flag'] ) ) {
            echo '<img class="lang-flag" src="' . esc_url( $language['flag'] ) . '" width="24" height="16" alt="" decoding="async">';
        }
        echo '</a>';
    }
    echo '</nav>';
}

// Customizer values and review excerpts remain editable under Languages > Translations.
function bsw_register_dynamic_strings() {
    if ( ! function_exists( 'pll_register_string' ) || ! is_admin() ) { return; }
    $strings = array(
        'Homepage heading' => get_theme_mod( 'bsw_hero_heading', 'Handcrafted bespoke stonework.' ),
        'Homepage introduction' => get_theme_mod( 'bsw_hero_description', 'Stone houses, garden walls, fireplaces and features. Shaped with care, built to belong.' ),
        'Service area' => get_theme_mod( 'bsw_area', '' ),
        'Postal address' => get_theme_mod( 'bsw_address', '' ),
    );
    foreach ( get_option( 'bsw_home_reviews', array() ) as $index => $review ) {
        foreach ( array( 'text', 'place', 'date' ) as $field ) {
            if ( ! empty( $review[ $field ] ) ) { $strings[ 'Review ' . ( $index + 1 ) . ' ' . $field ] = $review[ $field ]; }
        }
    }
    foreach ( $strings as $name => $text ) {
        if ( '' !== trim( (string) $text ) ) { pll_register_string( $name, $text, 'Beautiful Stone Walls', strlen( $text ) > 90 ); }
    }
}
add_action( 'init', 'bsw_register_dynamic_strings', 30 );

// Keep the before/after labels translated even with JavaScript disabled.
function bsw_gallery_stage_labels( $content, $block ) {
    if ( empty( $block['blockName'] ) || 'core/image' !== $block['blockName'] || ! class_exists( 'WP_HTML_Tag_Processor' ) ) { return $content; }
    $html = new WP_HTML_Tag_Processor( $content );
    if ( $html->next_tag( 'FIGURE' ) ) {
        if ( $html->has_class( 'bsw-stage-before' ) ) { $html->set_attribute( 'data-bsw-stage-label', bsw_t( 'Before' ) ); }
        if ( $html->has_class( 'bsw-stage-after' ) ) { $html->set_attribute( 'data-bsw-stage-label', bsw_t( 'After' ) ); }
    }
    return $html->get_updated_html();
}
add_filter( 'render_block', 'bsw_gallery_stage_labels', 10, 2 );

// Contact Form 7 stores separate forms and messages for each language, using its native CPT.
function bsw_polylang_post_types( $post_types, $hide ) {
    $post_types['wpcf7_contact_form'] = 'wpcf7_contact_form';
    return $post_types;
}
add_filter( 'pll_get_post_types', 'bsw_polylang_post_types', 10, 2 );

// CF7's select-value fallback is outside its editable per-form messages.
function bsw_contact_form_fallback_translation( $translation, $text, $domain ) {
    if ( 'contact-form-7' !== $domain || 'Undefined value was submitted through this field.' !== $text ) { return $translation; }
    $locale = determine_locale();
    $form = function_exists( 'wpcf7_get_current_contact_form' ) ? wpcf7_get_current_contact_form() : null;
    // Native form locale also works for REST requests without a Polylang language context.
    if ( $form ) { $locale = $form->locale(); }
    return in_array( $locale, array( 'lv', 'lv_LV' ), true ) ? 'Šajā laukā iesniegta neatļauta vērtība.' : $translation;
}
add_filter( 'gettext_contact-form-7', 'bsw_contact_form_fallback_translation', 10, 3 );

function bsw_script_translations() {
    return array(
        'categories' => array( 'houses' => bsw_t( 'Houses & façades' ), 'garages' => bsw_t( 'Garages' ), 'gardens' => bsw_t( 'Garden features' ), 'fireplaces' => bsw_t( 'Fireplaces' ), 'pillars' => bsw_t( 'Stone pillars' ), 'restoration' => bsw_t( 'Wall restoration' ), 'materials' => bsw_t( 'Liscannor stone' ) ),
        'projectCategories' => array( 'houses' => bsw_t( 'Houses & garages' ) ),
        'filterProjectsLabel' => bsw_t( 'Filter projects by type' ),
        'filterPhotosLabel' => bsw_t( 'Filter photographs by type' ),
        'allProjects' => bsw_t( 'All projects' ),
        'allPhotos' => bsw_t( 'All photographs' ),
        'moreProjects' => bsw_t( 'View more projects' ),
        'morePhotos' => bsw_t( 'View more photographs' ),
        'statusProjects' => bsw_t( 'Projects shown: {shown} of {total}.' ),
        'statusPhotos' => bsw_t( 'Photographs shown: {shown} of {total}.' ),
        'statusProjectsIn' => bsw_t( 'Projects shown: {shown} of {total} in {category}.' ),
        'statusPhotosIn' => bsw_t( 'Photographs shown: {shown} of {total} in {category}.' ),
        'statusProject' => bsw_t( 'Projects shown: {shown} of {total}.' ),
        'statusPhoto' => bsw_t( 'Photographs shown: {shown} of {total}.' ),
        'statusProjectIn' => bsw_t( 'Projects shown: {shown} of {total} in {category}.' ),
        'statusPhotoIn' => bsw_t( 'Photographs shown: {shown} of {total} in {category}.' ),
        'photoLoadError' => bsw_t( 'This photograph could not be loaded. Please try the next image or close the gallery and try again.' ),
        'viewerLabel' => bsw_t( 'Photo viewer' ),
        'closeLabel' => bsw_t( 'Close photo viewer' ),
        'previousLabel' => bsw_t( 'Previous photo' ),
        'nextLabel' => bsw_t( 'Next photo' ),
        'fallbackCaption' => bsw_t( 'Stone masonry project photograph' ),
        'imageCount' => bsw_t( '{current} / {total}' ),
        'beforeLabel' => bsw_t( 'Before' ),
        'afterLabel' => bsw_t( 'After' ),
    );
}
