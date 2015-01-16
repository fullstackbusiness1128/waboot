<?php
/**
 * Options Framework
 *
 * @package   Options Framework
 * @author    Devin Price <devin@wptheming.com>
 * @license   GPL-2.0+
 * @link      http://wptheming.com
 * @copyright 2013 WP Theming
 *
 * @wordpress-plugin
 * Plugin Name: Options Framework
 * Plugin URI:  http://wptheming.com
 * Description: A framework for building theme options.
 * Version:     1.7.1
 * Author:      Devin Price
 * Author URI:  http://wptheming.com
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: optionsframework
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Don't load if optionsframework_init is already defined
if ( ! function_exists( 'optionsframework_init' ) ) :

function optionsframework_init() {

	//  If user can't edit theme options, exit
	/*if ( ! current_user_can( 'edit_theme_options' ) )
		return;*/

	require WBF_DIRECTORY . '/vendor/options-framework/class-options-sanitization.php';
	require "waboot-options-sanitization.php";

	// Instantiate the main plugin class.
	$options_framework = new Waboot_Options_Framework;
	$options_framework->init();

	// Instantiate the options page.
	$options_framework_admin = new Waboot_Options_Framework_Admin;  //[WABOOT MOD]
	$options_framework_admin->init();

	// Instantiate the media uploader class
    $options_framework_media_uploader = new Waboot_Options_Media_Uploader; //[WABOOT MOD]
	$options_framework_media_uploader->init();

	// Instantiate the code editor class [WABOOT MOD]
    $options_framework_waboot_code_editor = new Waboot_Options_Code_Editor;
	$options_framework_waboot_code_editor->init();

	// Instantiate the gfont selector class [WABOOT MOD]
	$options_framework_waboot_gfont_selector = new Waboot_Options_Font_Selector;
	$options_framework_waboot_gfont_selector->init();
}

add_action( 'init', 'optionsframework_init', 20 );

endif;


/**
 * Helper function to return the theme option value.
 * If no value has been saved, it returns $default.
 * Needed because options are saved as serialized strings.
 *
 * Not in a class to support backwards compatibility in themes.
 */

if ( ! function_exists( 'of_get_option' ) ) :

function of_get_option( $name, $default = false ) {
	$config = get_option( 'optionsframework' );

	//[WABOOT MOD] Tries to return the default value sets into $options array if $default is false
	if(!$default){
		$options = Waboot_Options_Framework::_optionsframework_options();
		foreach($options as $opt){
			if(isset($opt['id']) && $opt['id'] == $name){
				if(isset($opt['std'])){
					$default = $opt['std'];
				}
			}
		}
	}

	if ( ! isset( $config['id'] ) ) {
		return $default;
	}

	$options = get_option( $config['id'] );

	if ( isset( $options[$name] ) ) {
		return $options[$name];
	}

	return $default;
}

endif;

/**
 * Check if current admin page is the options framework page
 * @param $hook
 *
 * @return bool
 */
function wbf_is_admin_of_page($hook){
	$menu = Waboot_Options_Framework_Admin::menu_settings();

	if ( $hook == 'waboot_page_' . $menu['old_menu_slug'] || $hook == 'toplevel_page_' . $menu['menu_slug']) {
		return true;
	}

	return false;
}

/**
 * Takes an array of options and returns the values themselves and the default value
 * @usage
 *
 * A typical array should be like this:
 *
 * array(
 *       array(
 *           "name" => __("Full width. No sidebar.","waboot"),
 *           "value" => "full-width"
 *       ),
 *       array(
 *           "name" => __("Sidebar right","waboot"),
 *           "value" => "sidebar-right"
 *       ),
 *       array(
 *           "name" => __("Sidebar left","waboot"),
 *           "value" => "sidebar-left"
 *       ),
 *       '_default' => 'sidebar-right'
 * )
 *
 * OR (more general):
 *
 * array(
 *       'opt1'
 *       'opt2,
 *       'opt2,
 *       '_default' => 'opt1'
 * )
 *
 * IF '_default' is not set or does not exists in the array, the function returns the first value (ore the 'value' field of the first key)
 *
 * @param $values
 * @return array
 */
function wbf_sanitize_of_array_values($values){
    $default = false;

    if(isset($values['_default'])){
        if(array_key_exists($values['_default'],$values)){
            $default = $values['_default'];
        }else{
            foreach($values as $v){
                if(is_array($v)){
                    if($v['value'] == $values['_default']){
                        $default = $values['_default'];
                    }
                }
            }
        }
    }
    if(!isset($values['_default']) || $default == false){
        reset($values);
        $default = key($values);
        if(is_array($values[$default])){
            $default = $values[$default]['value'];
        }
    }
    if(isset($values['_default'])) unset($values['_default']);

    return array(
        'values' => $values,
        'default' => $default
    );
}