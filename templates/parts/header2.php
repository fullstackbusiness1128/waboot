<?php
/**
 * The template used to load the Header in header*.php
 *
 * @package Waboot
 * @since Waboot 1.0
 */
?>
<!-- Header 2 -->

    <div class="row header-blocks hidden-sm hidden-xs">
        <div id="logo" class="col-md-6 vcenter">
            <?php if ( of_get_option( 'waboot_logo_in_navbar' ) ) : ?>
                <a href="<?php echo home_url( '/' ); ?>"><img src="<?php echo of_get_option( 'waboot_logo_in_navbar' ); ?>"> </a>
            <?php else : ?>
                <?php
                do_action( 'waboot_site_title' );
                // do_action( 'waboot_site_description' );
                ?>
            <?php endif; ?>
        </div><!--
        --><div id="header-right" class="col-md-6 vcenter">
            <?php if ( of_get_option('waboot_social_position') === 'header-left' && of_get_option("social_position_none") != 1 ) { get_template_part('templates/parts/social-widget'); } ?>
            <?php dynamic_sidebar( 'header-left' ); ?>
            <?php if ( of_get_option('waboot_social_position') === 'header-right' && of_get_option("social_position_none") != 1 ) { get_template_part('templates/parts/social-widget'); } ?>
            <?php dynamic_sidebar( 'header-right' ); ?>
        </div>
    </div>

<!-- End Header 2 -->
