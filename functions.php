<?php

/*****************************
 * FRAMEWORK INITIALIZATION
 *****************************/

locate_template('/wbf/wbf.php', true);

/**
 * Set update server
 */
$GLOBALS['WBFThemeUpdateChecker'] = new \WBF\includes\Theme_Update_Checker(
	'waboot', //Theme slug. Usually the same as the name of its directory.
	'http://update.waboot.org/?action=get_metadata&slug=waboot' //Metadata URL.
);

/*****************************
 * WABOOT INITIALIZATION
 *****************************/

locate_template('/inc/template-tags.php', true);

if ( ! function_exists( 'waboot_setup' ) ):
    function waboot_setup() {
        //Make theme available for translation.
        load_theme_textdomain( 'waboot', get_template_directory() . '/languages' );

        // Custom hooks.
        locate_template( '/inc/hooks.php', true );

        // Register the navigation menus.
        locate_template( '/inc/menus.php', true );

        // Register sidebars
        locate_template( '/inc/widgets.php', true );

        // Header image
        //locate_template( '/inc/custom-header.php', true );

        // Customizer
        locate_template( '/inc/customizer.php', true );

        // Load the CSS
        locate_template( '/inc/stylesheets.php', true );

        // Load scripts
        locate_template( '/inc/scripts.php', true );

        // Switch default core markup for search form, comment form, and comments to output valid HTML5.
        add_theme_support( 'html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption') );

        // Add default posts and comments RSS feed links to head
        add_theme_support( 'automatic-feed-links' );

        // Add support for custom backgrounds
        add_theme_support( 'custom-background', array('default-color' => 'ffffff') );

        // Add support for post-thumbnails
        add_theme_support( 'post-thumbnails' );

        // Add support for post formats. To be styled in later release.
        add_theme_support( 'post-formats', array( 'aside', 'gallery', 'link', 'image', 'quote', 'status', 'video', 'audio', 'chat' ) );

        // Load Jetpack related support if needed.
        if ( class_exists( 'Jetpack' ) )
            locate_template( '/inc/jetpack.php', true );

	    init_style_compiler();
    }
endif;
add_action('after_setup_theme', 'waboot_setup', 11);

/**
 * Set the pagebuilder
 */
if(!function_exists("theme_get_pagebuilder")):
	function theme_get_pagebuilder(){
		return "bootstrap";
	}
endif;

/**
 * INIT STYLES COMPILER
 */
if ( ! function_exists( 'init_style_compiler' ) ) :
function init_style_compiler(){
	$theme = waboot_get_compiled_stylesheet_name();
	$GLOBALS['waboot_styles_compiler'] = new \WBF\includes\compiler\Styles_Compiler(array(
		"theme_frontend" => array(
			"input" => get_stylesheet_directory()."/sources/less/{$theme}.less",
			"output" => get_stylesheet_directory()."/assets/css/{$theme}.css",
			"map" => get_stylesheet_directory()."/assets/css/{$theme}.css.map",
			"map_url" => get_stylesheet_directory_uri()."/assets/css/{$theme}.css.map",
			"cache" => get_stylesheet_directory()."/assets/cache",
			"import_url" => get_stylesheet_directory_uri()
		)
	));

	//Run a compilation if the styles file is not present
	$sets = $GLOBALS['waboot_styles_compiler']->get_compile_sets();
	if(!is_file($sets['theme_frontend']['output'])){
		$GLOBALS['waboot_styles_compiler']->compile();
	}
}
endif;