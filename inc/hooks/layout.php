<?php

if ( ! function_exists( 'waboot_mainwrap_container_class' ) ):
	/**
	 * Prepare the classes for mainwrap container
	 * @param $classes
	 * @return string
	 */
	function waboot_mainwrap_container_class($classes)
	{
		$body_layout = waboot_get_body_layout();
		$cols_size = _get_cols_sizes();
		$classes_array = explode(" ", $classes);

		if($body_layout){
			if ($body_layout == "full-width") {
				_remove_cols_classes($classes_array); //Remove all col- classes
				$classes_array[] = "col-sm-12";
			} else {
				_remove_cols_classes($classes_array); //Remove all col- classes
				$classes_array[] = "col-sm-".$cols_size['main'];
				//Three cols with main in the middle? Then add pull and push
				if($body_layout == "two-sidebars"){
					$classes_array[] = "col-sm-push-".$cols_size['primary'];
				}
			}
		}

		$classes = implode(" ",$classes_array);
		return $classes;
	}
	add_filter("waboot_mainwrap_container_class","waboot_mainwrap_container_class");
endif;

if ( ! function_exists( 'waboot_primary_container_class' ) ):
	/**
	 * Prepare the classes for primary container (the primary sidebar)
	 * @param $classes
	 * @return string
	 */
	function waboot_primary_container_class($classes){
		$classes_array = explode(" ",$classes);

		if(get_behavior('primary-sidebar-size')){
			_remove_cols_classes($classes_array); //Remove all col- classes
			$classes_array[] = "col-sm-"._layout_width_to_int(get_behavior('primary-sidebar-size'));
			//Three cols with main in the middle? Then add pull and push
			if(waboot_get_body_layout() == "two-sidebars"){
				$cols_size = _get_cols_sizes();
				$classes_array[] = "col-sm-pull-".$cols_size['main'];
			}
		}

		$classes = implode(" ",$classes_array);
		return $classes;
	}
	add_filter("waboot_primary_container_class","waboot_primary_container_class");
endif;

if ( ! function_exists( 'waboot_secondary_container_class' ) ):
	/**
	 * Prepare the classes for secondary container (the secondary sidebar)
	 * @param $classes
	 * @return string
	 */
	function waboot_secondary_container_class($classes){
		$classes_array = explode(" ",$classes);

		if(get_behavior('secondary-sidebar-size')){
			_remove_cols_classes($classes_array); //Remove all col- classes
			$classes_array[] = "col-sm-"._layout_width_to_int(get_behavior('secondary-sidebar-size'));
		}

		$classes = implode(" ",$classes_array);
		return $classes;
	}
	add_filter("waboot_secondary_container_class","waboot_secondary_container_class");
endif;

/**
 * Returns the sizes of each column available into current layout
 * @return array of integers
 */
function _get_cols_sizes(){
	if (waboot_body_layout_has_two_sidebars()) {
		//Primary size
		$primary_sidebar_width = get_behavior('primary-sidebar-size');
		if(!$primary_sidebar_width) $primary_sidebar_width = 0;
		//Secondary size
		$secondary_sidebar_width = get_behavior('secondary-sidebar-size');
		if(!$secondary_sidebar_width) $secondary_sidebar_width = 0;
		//Main size
		$mainwrap_size = 12 - _layout_width_to_int($primary_sidebar_width) - _layout_width_to_int($secondary_sidebar_width);

		return array("main"=>$mainwrap_size,"primary"=>_layout_width_to_int($primary_sidebar_width),"secondary"=>_layout_width_to_int($secondary_sidebar_width));
	}else{
		if(waboot_get_body_layout() != "full-width"){
			$primary_sidebar_width = get_behavior('primary-sidebar-size');
			if(!$primary_sidebar_width) $primary_sidebar_width = 0;
			$mainwrap_size = 12 - _layout_width_to_int($primary_sidebar_width);

			return array("main"=>$mainwrap_size,"primary"=>_layout_width_to_int($primary_sidebar_width));
		}
	}

	return array("main"=>12);
}

if(!function_exists("waboot_layout_body_class")) :
	function waboot_layout_body_class($classes){
		$classes[] = waboot_get_body_layout();
		return $classes;
	}
	add_filter('body_class','waboot_layout_body_class');
endif;

/**
 * Removes "col-" string values from an array
 * @param array $classes_array
 */
function _remove_cols_classes(array &$classes_array){
	foreach($classes_array as $k => $v){
		if(preg_match("/col-/",$v)){
			unset($classes_array[$k]);
		}
	}
}

/**
 * Convert size labels (1/3, 2/3, ect) into size integers (for using into col-sm-<x>)
 * @param string $width the label
 *
 * @return int
 */
function _layout_width_to_int($width){
	switch($width){
		case '0':
			return 0;
			break;
		case '1/2':
			return 6;
			break;
		case '2/3':
			return 4;
			break;
		case '1/4':
			return 3;
			break;
		case '1/6':
			return 2;
			break;
		default:
			return 4;
			break;
	}
}