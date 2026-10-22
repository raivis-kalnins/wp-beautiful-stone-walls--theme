<?php defined( 'ABSPATH' ) || exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><script>document.documentElement.classList.add('js');</script><?php wp_head(); ?></head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main-content"><?php echo esc_html( bsw_t( 'Skip to content' ) ); ?></a>
<header class="site-header">
    <div class="utility-bar"><div class="container"><span class="utility-location"><?php echo esc_html( bsw_t( 'Traditional stone masonry ·' ) ); ?> <?php echo esc_html( bsw_contact( 'area' ) ); ?></span><a class="utility-contact" href="tel:<?php echo esc_attr( bsw_contact( 'phone_link' ) ); ?>"><?php echo bsw_icon( 'phone' ); ?> <?php echo esc_html( bsw_contact( 'phone' ) ); ?></a></div></div>
    <div class="header-main container">
        <div class="site-branding"><a href="<?php echo esc_url( bsw_home_url() ); ?>" rel="home" aria-label="<?php echo esc_attr( bsw_t( 'Beautiful Stone Walls home' ) ); ?>"><?php echo bsw_logo(); ?></a></div>
        <button class="menu-toggle" type="button" aria-controls="primary-navigation" aria-expanded="false"><?php echo bsw_icon( 'menu' ); ?><span><?php echo esc_html( bsw_t( 'Menu' ) ); ?></span></button>
        <nav id="primary-navigation" class="primary-navigation" aria-label="<?php echo esc_attr( bsw_t( 'Main navigation' ) ); ?>"><?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'fallback_cb' => 'bsw_menu_fallback', 'depth' => 1 ) ); ?></nav>
        <a class="button button--primary header-cta" href="<?php echo esc_url( bsw_page_url( 'contact-us' ) ); ?>"><?php echo esc_html( bsw_t( 'Contact us' ) ); ?> <?php echo bsw_icon(); ?></a>
        <?php bsw_language_switcher(); ?>
    </div>
</header>
<main id="main-content">
