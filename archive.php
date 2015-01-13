<?php
/**
 * The template for displaying Archive pages.
 *
 * @package Waboot
 * @since Waboot 1.0
 */

get_header(); ?>

	<section id="main-wrap" class="<?php echo apply_filters( 'waboot_mainwrap_container_class', 'content-area col-sm-8' ); ?>">

		<main id="main" class="site-main" role="main">
			<?php
			do_action( 'waboot_archive_page_title' );
			waboot_archive_sticky_posts(); // sticky post query
			if ( have_posts() ) {

				rewind_posts();

				waboot_content_nav( 'nav-above' );

				// do the main query without stickies
				$sticky = get_option( 'sticky_posts' );

				if ( is_category() && ! empty( $sticky ) ) {
					$cat_ID = get_query_var('cat');
					$args = array(
						'cat'                 => $cat_ID,
						'post_status'         => 'publish',
						'post__not_in'        => array_merge( $do_not_duplicate, get_option( 'sticky_posts' ) ),
						'ignore_sticky_posts' => 1,
						'paged'               => $paged
					);
					$wp_query = new WP_Query( $args );
				}
				elseif (is_tag() && ! empty( $sticky ) ) {
					$current_tag = get_queried_object_id();
					$args = array(
						'tag_id'              => $current_tag,
						'post_status'         => 'publish',
						'post__not_in'        => array_merge( $do_not_duplicate, get_option( 'sticky_posts' ) ),
						'ignore_sticky_posts' => 1,
						'paged'               => $paged
					);
					$wp_query = new WP_Query( $args );

				}
				else {
					new WP_Query();
				}

				// Start the Loop
				while ( $wp_query->have_posts() ) : $wp_query->the_post();
					/* Include the Post-Format-specific template for the content.
					 * If you want to override this in a child theme then include a file
					 * called content-___.php (where ___ is the Post Format name) and that will be used instead.
					 */
					get_template_part( '/templates/parts/content', get_post_format() );
				endwhile;

				// Show navigation below post content
				waboot_content_nav( 'nav-below' );

			} else {

				// No results
				get_template_part( '/templates/parts/content', 'none' );

			} //have_posts ?>
		</main><!-- #main -->

	</section><!-- #main-wrap -->
<?php
get_sidebar();
get_footer(); ?>