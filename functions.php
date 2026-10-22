<?php
/** Theme setup and presentation helpers. Content remains native WordPress content. */
defined( 'ABSPATH' ) || exit;
require_once get_theme_file_path( '/inc/i18n.php' );
require_once get_theme_file_path( '/inc/seo-performance.php' );
require_once get_theme_file_path( '/inc/contact-update.php' );
require_once get_theme_file_path( '/inc/site-privacy.php' );

function bsw_setup() {
    load_theme_textdomain( 'beautiful-stone-walls', get_template_directory() . '/languages' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'align-wide' );
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
    add_theme_support( 'custom-logo', array( 'height' => 160, 'width' => 700, 'flex-height' => true, 'flex-width' => true ) );
    add_theme_support( 'editor-styles' );
    add_editor_style( 'assets/editor.css' );
    register_nav_menus( array( 'primary' => __( 'Main navigation', 'beautiful-stone-walls' ), 'footer' => __( 'Footer links', 'beautiful-stone-walls' ) ) );
    add_image_size( 'bsw-card', 640, 480, true );
    add_image_size( 'bsw-large', 1280, 0, false );
    add_image_size( 'bsw-display', 850, 0, false );
}
add_action( 'after_setup_theme', 'bsw_setup' );

// Preserve uploaded originals while serving small modern formats for new image sizes.
function bsw_image_formats( $formats ) {
    if ( wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
        $formats['image/jpeg'] = 'image/webp';
        $formats['image/png'] = 'image/webp';
    }
    return $formats;
}
add_filter( 'image_editor_output_format', 'bsw_image_formats' );

function bsw_assets() {
    $version = wp_get_theme()->get( 'Version' );
    wp_enqueue_style( 'bsw-site', get_theme_file_uri( '/assets/site.css' ), array(), $version );
    wp_enqueue_script( 'bsw-site', get_theme_file_uri( '/assets/site.js' ), array(), $version, array( 'strategy' => 'defer', 'in_footer' => true ) );
    wp_add_inline_script( 'bsw-site', 'window.bswI18n = ' . wp_json_encode( bsw_script_translations(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';', 'before' );
}
add_action( 'wp_enqueue_scripts', 'bsw_assets' );

// Supply the original brand icon without changing the site's Media Library or settings.
function bsw_site_icon_url( $url, $size, $blog_id ) {
    if ( $blog_id && (int) $blog_id !== get_current_blog_id() ) {
        return $url;
    }

    $wordpress_defaults = array(
        includes_url( 'images/w-logo-gray-white-bg.png' ),
        includes_url( 'images/w-logo-gray-white-bg.svg' ),
    );
    if ( $url && ! in_array( $url, $wordpress_defaults, true ) ) {
        return $url;
    }

    $icon = (int) $size <= 16 ? '/assets/favicon-16.png' : '/assets/favicon.png';
    return get_theme_file_uri( $icon );
}
add_filter( 'get_site_icon_url', 'bsw_site_icon_url', 10, 3 );

function bsw_icon( $name = 'arrow' ) {
    $paths = array(
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'phone' => '<path d="m7 3 3 5-2 2a15 15 0 0 0 6 6l2-2 5 3-1 4C10 22 2 14 3 4Z"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'close' => '<path d="m6 6 12 12M18 6 6 18"/>',
        'chevron' => '<path d="m9 5 7 7-7 7"/>',
    );
    if ( ! isset( $paths[ $name ] ) ) { $name = 'arrow'; }
    return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

function bsw_contact_country( $language = '' ) {
    if ( ! $language ) {
        $language = function_exists( 'pll_current_language' ) ? pll_current_language( 'slug' ) : '';
        if ( ! $language ) { $language = substr( determine_locale(), 0, 2 ); }
    }
    $latvian = 'lv' === $language;
    return get_theme_mod( $latvian ? 'bsw_country_lv' : 'bsw_country_en', $latvian ? 'Latvija' : 'Ireland' );
}

function bsw_contact( $key ) {
    $defaults = array(
        'phone' => '0000',
        'phone_link' => '0000',
        'email' => 'hello@dev4.click',
        'address' => '',
        'area' => '',
        'facebook' => 'https://www.facebook.com/beautifulstonewalls.beautifulstonewalls',
    );
    $value = isset( $defaults[ $key ] ) ? get_theme_mod( 'bsw_' . $key, $defaults[ $key ] ) : '';
    if ( in_array( $key, array( 'address', 'area' ), true ) ) {
        return '' === trim( (string) $value ) ? bsw_contact_country() : bsw_dynamic_text( $value );
    }
    return $value;
}

function bsw_media_id( $path ) {
    static $map = null;
    if ( null === $map ) { $map = get_option( 'bsw_media_map', array() ); }
    if ( ! isset( $map[ $path ] ) ) {
        $webp_path = preg_replace( '/\.(?:jpe?g|png)$/i', '.webp', $path );
        if ( isset( $map[ $webp_path ] ) ) {
            $map[ $path ] = $map[ $webp_path ];
        } else {
            global $wpdb;
            $map[ $path ] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value IN (%s, %s) ORDER BY post_id ASC LIMIT 1", $path, $webp_path ) );
        }
    }
    return isset( $map[ $path ] ) ? bsw_translated_post_id( absint( $map[ $path ] ) ) : 0;
}

function bsw_image( $path, $size = 'large', $attributes = array() ) {
    $id = bsw_media_id( $path );
    if ( $id ) {
        return wp_get_attachment_image( $id, $size, false, $attributes );
    }
    $uploads = wp_get_upload_dir();
    $attributes = wp_parse_args( $attributes, array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) );
    $html = '<img src="' . esc_url( trailingslashit( $uploads['baseurl'] ) . $path ) . '"';
    foreach ( array( 'alt', 'loading', 'decoding', 'fetchpriority', 'width', 'height', 'sizes', 'srcset', 'class' ) as $attribute ) {
        if ( isset( $attributes[ $attribute ] ) ) { $html .= ' ' . $attribute . '="' . esc_attr( $attributes[ $attribute ] ) . '"'; }
    }
    return $html . '>';
}

function bsw_page_url( $slug ) {
    $page = get_page_by_path( $slug );
    return $page ? get_permalink( bsw_translated_post_id( $page->ID ) ) : home_url( '/' . trim( $slug, '/' ) . '/' );
}

function bsw_logo( $loading = 'eager' ) {
    $id = (int) get_theme_mod( 'custom_logo' );
    if ( $id ) {
        return wp_get_attachment_image( $id, 'full', false, array( 'alt' => get_bloginfo( 'name' ), 'loading' => $loading ) );
    }
    return '<img src="' . esc_url( get_theme_file_uri( '/assets/logo.png' ) ) . '" width="700" height="160" alt="' . esc_attr( bsw_t( 'Beautiful Stone Walls — Stone Masonry Craftsmanship' ) ) . '" loading="' . esc_attr( $loading ) . '" decoding="async">';
}

function bsw_menu_fallback() {
    $links = array( '' => 'Home', 'about-us' => 'About us', 'our-work' => 'Our work', 'recommended-stone' => 'Recommended stone', 'testimonials' => 'Testimonials', 'before-and-after' => 'Before & after', 'contact-us' => 'Contact' );
    echo '<ul class="menu">';
    foreach ( $links as $slug => $label ) {
        echo '<li><a href="' . esc_url( $slug ? bsw_page_url( $slug ) : bsw_home_url() ) . '">' . esc_html( bsw_t( $label ) ) . '</a></li>';
    }
    echo '</ul>';
}

function bsw_category_key( $post_id ) {
    $categories = get_the_category( $post_id );
    $map = array( 'housesgarages' => 'houses', 'fireplaces' => 'fireplaces', 'stone-garden-walls-and-features' => 'gardens', 'stone-restoration' => 'restoration', 'stone-pillars' => 'pillars' );
    foreach ( $categories as $category ) {
        if ( function_exists( 'pll_get_term' ) && function_exists( 'pll_default_language' ) ) {
            $original_id = pll_get_term( $category->term_id, pll_default_language() );
            if ( $original_id ) { $original = get_term( $original_id, 'category' ); if ( $original && ! is_wp_error( $original ) ) { $category = $original; } }
        }
        if ( isset( $map[ $category->slug ] ) ) { return $map[ $category->slug ]; }
    }
    return 'stonework';
}

function bsw_project_grid( $limit = -1, $ids = array() ) {
    $args = array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => $limit, 'orderby' => 'menu_order title', 'order' => 'ASC', 'ignore_sticky_posts' => true, 'no_found_rows' => true );
    if ( function_exists( 'pll_current_language' ) && pll_current_language() ) { $args['lang'] = pll_current_language(); }
    if ( $ids ) { $args['post__in'] = array_map( 'bsw_translated_post_id', $ids ); $args['orderby'] = 'post__in'; }
    $projects = new WP_Query( $args );
    echo '<div class="project-grid">';
    while ( $projects->have_posts() ) {
        $projects->the_post();
        $categories = get_the_category();
        $category = $categories ? $categories[0]->name : bsw_t( 'Stonework' );
        ?>
        <article class="project-card" data-category="<?php echo esc_attr( bsw_category_key( get_the_ID() ) ); ?>">
            <a href="<?php the_permalink(); ?>">
                <div class="project-image"><?php the_post_thumbnail( 'bsw-card', array( 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 599px) 92vw, (max-width: 959px) 46vw, 390px' ) ); ?></div>
                <div class="project-info"><span class="project-category"><?php echo esc_html( $category ); ?></span><h3><?php the_title(); ?></h3><span class="project-arrow"><?php echo bsw_icon(); ?></span></div>
            </a>
        </article>
        <?php
    }
    echo '</div>';
    wp_reset_postdata();
}

function bsw_heading( $eyebrow = 'Beautiful Stone Walls' ) {
    ?>
    <header class="page-heading"><div class="container">
        <nav class="breadcrumbs" aria-label="<?php echo esc_attr( bsw_t( 'Breadcrumb' ) ); ?>"><a href="<?php echo esc_url( bsw_home_url() ); ?>"><?php echo esc_html( bsw_t( 'Home' ) ); ?></a><span aria-hidden="true">/</span><span aria-current="page"><?php the_title(); ?></span></nav>
        <p class="eyebrow"><?php echo esc_html( bsw_t( $eyebrow ) ); ?></p>
        <h1><?php the_title(); ?></h1>
        <?php if ( has_excerpt() ) : ?><p class="page-intro"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
    </div></header>
    <?php
}

function bsw_cta() {
    ?>
    <section class="cta-band" aria-labelledby="project-cta"><div class="container">
        <div class="cta-copy"><p class="eyebrow"><?php echo esc_html( bsw_t( "Let's talk stonework" ) ); ?></p><h2 id="project-cta"><?php echo esc_html( bsw_t( 'Have a project in mind?' ) ); ?></h2><p><?php echo esc_html( bsw_t( 'From a garden wall to a complete stone façade, tell us what you have in mind.' ) ); ?></p></div>
        <div class="cta-actions"><a class="button button--light" href="<?php echo esc_url( bsw_page_url( 'contact-us' ) ); ?>"><?php echo esc_html( bsw_t( 'Get a quote' ) ); ?> <?php echo bsw_icon(); ?></a><a class="cta-phone" href="tel:<?php echo esc_attr( bsw_contact( 'phone_link' ) ); ?>"><?php echo esc_html( bsw_contact( 'phone' ) ); ?></a></div>
    </div></section>
    <?php
}

function bsw_customize( $customizer ) {
    $customizer->add_section( 'bsw_details', array( 'title' => __( 'Stone Walls · Contact & homepage', 'beautiful-stone-walls' ), 'priority' => 30 ) );
    $fields = array( 'phone' => 'Telephone display', 'phone_link' => 'Telephone international number', 'email' => 'Email address', 'address' => 'Postal address', 'area' => 'Service area', 'facebook' => 'Facebook URL' );
    foreach ( $fields as $key => $label ) {
        $sanitizer = 'email' === $key ? 'sanitize_email' : ( 'facebook' === $key ? 'esc_url_raw' : 'sanitize_textarea_field' );
        $country_fallback = in_array( $key, array( 'address', 'area' ), true );
        $customizer->add_setting( 'bsw_' . $key, array( 'default' => $country_fallback ? '' : bsw_contact( $key ), 'sanitize_callback' => $sanitizer ) );
        $customizer->add_control( 'bsw_' . $key, array( 'label' => $label, 'section' => 'bsw_details', 'type' => 'address' === $key ? 'textarea' : 'text', 'description' => $country_fallback ? __( 'Leave blank to show the country for the current language.', 'beautiful-stone-walls' ) : '' ) );
    }
    foreach ( array( 'en' => 'Country — English', 'lv' => 'Country — Latvian' ) as $language => $label ) {
        $customizer->add_setting( 'bsw_country_' . $language, array( 'default' => bsw_contact_country( $language ), 'sanitize_callback' => 'sanitize_text_field' ) );
        $customizer->add_control( 'bsw_country_' . $language, array( 'label' => bsw_t( $label ), 'section' => 'bsw_details', 'type' => 'text' ) );
    }
    $customizer->add_setting( 'bsw_hero_heading', array( 'default' => 'Handcrafted bespoke stonework.', 'sanitize_callback' => 'sanitize_text_field' ) );
    $customizer->add_control( 'bsw_hero_heading', array( 'label' => 'Homepage heading', 'section' => 'bsw_details' ) );
    $customizer->add_setting( 'bsw_hero_description', array( 'default' => 'Stone houses, garden walls, fireplaces and features. Shaped with care, built to belong.', 'sanitize_callback' => 'sanitize_textarea_field' ) );
    $customizer->add_control( 'bsw_hero_description', array( 'label' => 'Homepage introduction', 'section' => 'bsw_details', 'type' => 'textarea' ) );
}
add_action( 'customize_register', 'bsw_customize' );

// Keep legacy portfolio category URLs useful after moving projects into native posts.
function bsw_legacy_category_redirect() {
    if ( ! is_404() ) { return; }
    $request = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
    if ( preg_match( '~^/portfolio_category/([^/]+)/?$~', (string) $request, $match ) ) {
        $term = get_term_by( 'slug', sanitize_title( $match[1] ), 'category' );
        if ( $term ) { wp_safe_redirect( get_category_link( $term->term_id ), 301 ); exit; }
    }
}
add_action( 'template_redirect', 'bsw_legacy_category_redirect' );

function bsw_body_classes( $classes ) { $classes[] = 'bsw-site'; return $classes; }
add_filter( 'body_class', 'bsw_body_classes' );
