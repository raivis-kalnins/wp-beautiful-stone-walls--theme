<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<?php while ( have_posts() ) : the_post(); bsw_heading(); ?>
<section class="section"><div class="container"><article id="post-<?php the_ID(); ?>" <?php post_class( 'entry-content' ); ?>><?php the_content(); wp_link_pages(); ?></article></div></section>
<?php endwhile; bsw_cta(); get_footer(); ?>
