<?php
/**
 * The main template file.
 *
 * @package Waboot
 */

get_header();
?>
<?php if ( waboot_get_sidebar_layout() == "full-width" ) : ?>
    <div id="primary" class="<?php echo apply_filters( 'waboot_primary_container_class', 'content-area col-sm-12' ); ?>">
<?php else : ?>
    <div id="primary" class="<?php echo apply_filters( 'waboot_primary_container_class', 'content-area col-sm-8' ); ?>">
<?php endif; ?>
        <main id="main" class="site-main" role="main">
            <?php if (get_behavior('title-position') == "bottom") : ?>
                <?php waboot_index_title('<h1 class=\'entry-header\'>', '</h1>'); ?>
            <?php endif; ?>
            <?php if ( have_posts() ) : ?>
                <?php waboot_content_nav( 'nav-above' ); // display content nav above posts ?>
                <?php
                while ( have_posts() ) {
                    the_post();
                    /* Include the Post-Format-specific template for the content.
                     * If you want to override this in a child theme then include a file
                     * called content-___.php (where ___ is the Post Format name) and that will be used instead.
                     */
                    switch(of_get_option("waboot_blogpage_layout")){
                        case 'timeline':
                            get_template_part( '/templates/parts/content', "blog-timeline" );
                            break;
                        case 'masonry':
                            get_template_part( '/templates/parts/content', "blog-masonry" );
                            break;
                        case 'blog':
                        default:
                            get_template_part( '/templates/parts/content', get_post_format() );
                            break;
                    }
                }
                ?>
                <?php waboot_content_nav( 'nav-below' ); // display content nav below posts? ?>
            <?php else: ?>
                <?php get_template_part('/templates/parts/content', 'none'); // No results ?>
            <?php endif; //have_posts ?>
        </main><!-- #main -->
    </div><!-- #primary -->
<?php
get_sidebar();
get_footer();