<?php

namespace Waboot;

use WBF\components\mvc\HTMLView;
use WBF\components\mvc\View;
use WBF\components\utils\Utilities;

class Layout{
	/**
	 * @var Layout
	 */
	private static $instance;
	/**
	 * @var array
	 */
	private $zones = [];
	
	const LAYOUT_FULL_WIDTH = "full-width";
	const LAYOUT_PRIMARY_RIGHT = "sidebar-right";
	const LAYOUT_PRIMARY_LEFT = "sidebar-left";
	const LAYOUT_TWO_SIDEBARS = "two-sidebars";
	const LAYOUT_TWO_SIDEBARS_LEFT = "two-sidebars-left";
	const LAYOUT_TWO_SIDEBARS_RIGHT = "two-sidebars-right";
	
	/**
	 * Returns the *Singleton* instance.
	 *
	 * @return Layout The *Singleton* instance.
	 */
	public static function getInstance(){
		if (null === static::$instance) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Creates a new template zone
	 *
	 * @param string $slug
	 * @param string|array|View|bool $template
	 * @param array $args
	 *
	 * @return $this
	 * @throws \Exception
	 */
	public function create_zone($slug,$template = false,$args = []){
		//Check for valid slug
		if(preg_match("/ /",$slug)){
			throw new \Exception("You cannot have whitespaces in a zone slug");
		}
		
		if(isset($template) && $template !== false){
			//Checks for valid template
			if(!is_string($template) && !is_array($template) && !$template instanceof View){
				throw new \Exception("You cannot create a zone thats is neither a string, an array or a View instance");
			}
			//Check template existence
			if(is_string($template) || is_array($template)){
				if(is_array($template)){
					$template = implode("-",$template);
				}
				$tpl_file = \locate_template($template);
				if(!$tpl_file){
					throw new \Exception("The {$template} for the zone {$slug} does not exists");
				}
			}
		}
			
		if(!is_array($args)){
			throw new \Exception('$args must be an array');
		}
		
		//Save the zone
		$args = wp_parse_args($args,[
			'always_load' => false,
			'can_render_callback' => null,
		]);

		$this->zones[$slug] = [
			'slug' => $slug,
			'template' => $template,
			'actions_hook' => 'waboot/zones/'.$slug,
			'actions' => [],
			'options' => $args
		];
		return $this;
	}

	/**
	 * Set an attribute value (the options array) to a zone
	 *
	 * @param $zone_slug
	 * @param $zone_attr
	 * @param $attr_value
	 *
	 * @throws \Exception
	 */
	public function set_zone_attr($zone_slug,$zone_attr,$attr_value){
		$this->check_zone($zone_slug);
		$this->zones[$zone_slug]['options'][$zone_attr] = $attr_value;
	}

	/**
	 * Renders a template zone
	 *
	 * @param $slug
	 *
	 * @throws \Exception
	 */
	public function render_zone($slug){
		$this->check_zone($slug);

		$zone = $this->zones[$slug];

		if($zone['options']['always_load'] || !empty($zone['actions'])){ //Render the zone only if need to...
			$can_render = true;
			if(isset($zone['options']['can_render_callback']) && is_callable($zone['options']['can_render_callback'])){
				$can_render = false;
				$can_render = call_user_func($zone['options']['can_render_callback']);
			}
			if($can_render){
				if(is_string($zone['template'])){
					get_template_part($zone['template']);
				}elseif(is_array($zone['template'])){
					list($template,$part) = $zone['template'];
					get_template_part($template,$part);
				}elseif($zone['template'] instanceof View){
					$zone['template']->clean()->display([
						"name" => $zone['slug']
					]);
				}else{
					//Here we do not have a template
					$this->do_zone_action($slug);
				}
			}
		}
	}

	/**
	 * Checks if a zone can be rendered (is on "always_load" or has actions hooked.
	 *
	 * @param $slug
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function can_render_zone($slug){
		$this->check_zone($slug);
		$zone = $this->zones[$slug];
		return $zone['options']['always_load'] || !empty($zone['actions']);
	}

	/**
	 * Renders wordpress theme hierarchy templates through our template system.
	 * 
	 * This function is a draft. For now we prefer to user WP native get_template_parts for bettere compatibility.
	 * 
	 * @param $template
	 */
	public function render_wp_template_content($template){
		switch($template){
			case "archive.php":
				$template_file = "templates/wordpress/archive-content";
				\Waboot\functions\render_archives($template_file);
				break;
			case "page.php":
				break;
			case "single.php":
				break;
		}
	}

	/**
	 * Get the variables needed for render a specific Wordpress template.
	 * 
	 * We use this function to avoid conditionals and stuff in our templates.
	 * 
	 * @param $template
	 * 
	 * @return array
	 */
	public function get_wp_template_vars($template){
		$vars = [];
		switch($template){
			case "archive.php":
				$vars = \Waboot\functions\get_archives_vars();
				break;
			case "page.php":
				break;
			case "single.php":
				break;
		}
		return $vars;
	}

	/**
	 * Adds an action to the zone
	 * 
	 * @param $slug
	 * @param $function_to_call
	 * @param $priority
	 * @param $accepted_args
	 *
	 * @throws \Exception
	 */
	public function add_zone_action($slug,$function_to_call,$priority = 10,$accepted_args = 1){
		$this->check_zone($slug);

		$zone = $this->zones[$slug];

		$this->zones[$slug]['actions'][] = [
			"callable" =>  $function_to_call,
			"priority" => $priority
		];
		
		add_action($zone['actions_hook'],$function_to_call,$priority,$accepted_args);
	}

	/**
	 * Performs zone actions
	 * 
	 * @param $slug
	 *
	 * @throws \Exception
	 */
	public function do_zone_action($slug){
		$this->check_zone($slug);

		$zone = $this->zones[$slug];
		
		do_action($zone['actions_hook']);
	}

	/**
	 * Get the available zones
	 *
	 * @return array
	 */
	public function getZones(){
		return $this->zones;
	}

	/**
	 * Checks whether a zone exists or not
	 * 
	 * @param $slug
	 *
	 * @return bool
	 * @throws \Exception
	 */
	private function check_zone($slug){
		if(!is_string($slug)){
			throw new \Exception("Zone slug must be a string");
		}elseif(empty($slug)){
			throw new \Exception("Empty slug provided");
		}
		if(!isset($this->zones[$slug])){
			throw new \Exception("Zone {$slug} not found");
		}
		return true;
	}
	
	/*
	 * Utilities
	 */
	
	/**
	 * Removes "col-" string values from an array
	 * @param array $classes_array
	 */
	static function remove_cols_classes(array &$classes_array){
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
	static function layout_width_to_int($width){
		switch($width){
			case '0':
				return 0;
				break;
			case '1/2':
				return 6;
				break;
			case '1/3':
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

	/**
	 * Private clone method to prevent cloning of the instance of the *Singleton* instance.
	 */
	private function __clone(){}

	/**
	 * Private unserialize method to prevent unserializing of the *Singleton* instance.
	 */
	private function __wakeup(){}
}