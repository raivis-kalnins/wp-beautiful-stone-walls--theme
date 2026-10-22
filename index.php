<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<header class="page-heading"><div class="container"><p class="eyebrow"><?php echo esc_html( bsw_t( 'Our stonework' ) ); ?></p><h1><?php if ( is_category() ) { single_cat_title(); } elseif ( is_search() ) { echo esc_html( sprintf( bsw_t( 'Search: %s' ), get_search_query() ) ); } else { echo esc_html( get_bloginfo( 'name' ) ); } ?></h1></div></header>
<section class="section"><div class="container"><div class="project-grid">
<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
<article class="project-card"><a href="<?php the_permalink(); ?>"><div class="project-image"><?php the_post_thumbnail( 'bsw-card' ); ?></div><div class="project-info"><h2><?php the_title(); ?></h2><span class="project-arrow"><?php echo bsw_icon(); ?></span></div></a></article>
<?php endwhile; else : ?><p><?php echo esc_html( bsw_t( 'No items found.' ) ); ?> <a href="<?php echo esc_url( bsw_page_url( 'our-work' ) ); ?>"><?php echo esc_html( bsw_t( 'Explore our work' ) ); ?></a>.</p><?php endif; ?>
</div><?php the_posts_pagination( array( 'prev_text' => bsw_t( 'Previous page' ), 'next_text' => bsw_t( 'Next page' ), 'screen_reader_text' => bsw_t( 'Page navigation' ) ) ); ?></div></section>
<?php bsw_cta(); get_footer(); ?>
