<?php defined( 'ABSPATH' ) || exit; ?>
</main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand"><a href="<?php echo esc_url( bsw_home_url() ); ?>"><?php echo bsw_logo( 'lazy' ); ?></a><p><?php echo esc_html( bsw_t( 'Traditional stone masonry, thoughtful detailing and a personal approach to every project.' ) ); ?></p><p class="footer-area"><?php echo esc_html( bsw_contact( 'area' ) ); ?></p></div>
        <div class="footer-links"><h2><?php echo esc_html( bsw_t( 'Explore' ) ); ?></h2><?php wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false, 'fallback_cb' => 'bsw_menu_fallback', 'depth' => 1 ) ); ?></div>
        <div class="footer-contact"><h2><?php echo esc_html( bsw_t( 'Get in touch' ) ); ?></h2><a class="footer-phone" href="tel:<?php echo esc_attr( bsw_contact( 'phone_link' ) ); ?>"><?php echo esc_html( bsw_contact( 'phone' ) ); ?></a><a href="mailto:<?php echo esc_attr( bsw_contact( 'email' ) ); ?>"><?php echo esc_html( bsw_contact( 'email' ) ); ?></a><address><?php echo nl2br( esc_html( bsw_contact( 'address' ) ) ); ?></address><?php if ( bsw_contact( 'facebook' ) ) : ?><a href="<?php echo esc_url( bsw_contact( 'facebook' ) ); ?>" rel="noopener noreferrer" target="_blank"><?php echo esc_html( bsw_t( 'Find us on Facebook' ) ); ?> <span class="screen-reader-text"><?php echo esc_html( bsw_t( '(opens in a new tab)' ) ); ?></span><?php echo bsw_icon(); ?></a><?php endif; ?></div>
    </div>
    <div class="container footer-bottom"><p>© <?php echo esc_html( wp_date( 'Y' ) ); ?> Beautiful Stone Walls.</p><p><?php echo esc_html( bsw_t( 'Stone masonry craftsmanship' ) . ' · ' . bsw_contact_country() ); ?></p></div>
</footer>
<dialog id="bsw-lightbox" class="lightbox" aria-label="<?php echo esc_attr( bsw_t( 'Photo viewer' ) ); ?>">
    <button class="lightbox-close" type="button" aria-label="<?php echo esc_attr( bsw_t( 'Close photo viewer' ) ); ?>"><?php echo bsw_icon( 'close' ); ?></button>
    <button class="lightbox-prev" type="button" aria-label="<?php echo esc_attr( bsw_t( 'Previous photo' ) ); ?>"><?php echo bsw_icon( 'chevron' ); ?></button>
    <figure><img class="lightbox-image" alt=""><figcaption class="lightbox-caption"></figcaption></figure>
    <button class="lightbox-next" type="button" aria-label="<?php echo esc_attr( bsw_t( 'Next photo' ) ); ?>"><?php echo bsw_icon( 'chevron' ); ?></button>
    <p class="lightbox-count" aria-live="polite"></p>
</dialog>
<?php wp_footer(); ?>
</body></html>
