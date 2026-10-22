<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<?php while ( have_posts() ) : the_post(); bsw_heading( 'Our stonework' ); ?>
<section class="section"><div class="container project-main"><article id="post-<?php the_ID(); ?>" <?php post_class( 'entry-content' ); ?>><?php the_content(); ?></article><nav class="project-pagination" aria-label="<?php echo esc_attr( bsw_t( 'Project navigation' ) ); ?>"><?php previous_post_link( '%link', esc_html( bsw_t( '← Previous project' ) ) ); ?><a class="button button--secondary" href="<?php echo esc_url( bsw_page_url( 'our-work' ) ); ?>"><?php echo esc_html( bsw_t( 'All projects' ) ); ?></a><?php next_post_link( '%link', esc_html( bsw_t( 'Next project →' ) ) ); ?></nav></div></section>
<?php endwhile; bsw_cta(); get_footer(); ?>
