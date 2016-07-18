<?php
/*
 * The main blog template. It is hooked at the "main" zone in "zones_std_hooks.php"
 */
?>
<?php if(have_posts()): ?>
	<?php \Waboot\template_tags\post_navigation( 'nav-above' ); // display content nav above posts if needed ?>
	<?php while(have_posts()) :  the_post(); ?>
		<div class="<?php \Waboot\template_tags\posts_wrapper_class(); ?>">
		<?php \Waboot\functions\get_template_part( '/templates/wordpress/parts/content', get_post_format() ); ?>
		</div>
	<?php endwhile; ?>
	<?php \Waboot\template_tags\post_navigation( 'nav-below' ); // display content nav below posts if needed ?>
<?php else: ?>
	<?php get_template_part('/templates/parts/content', 'none'); // No results ?>
<?php endif; //have_posts ?>