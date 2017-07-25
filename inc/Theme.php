<?php

namespace Waboot;

use function Waboot\functions\wbf_exists;
use WBF\components\utils\Paths;
use WBF\components\utils\Utilities;
use WBF\modules\components\ComponentsManager;
use WBF\modules\options\Framework;

class Theme{
	/**
	 * @var Theme
	 */
	private static $instance;

	/**
	 * @var Layout
	 */
	var $layout;

	/**
	 * @var array
	 */
	var $inline_styles;

	/**
	 * @var \WP_Styles
	 */
	var $custom_styles_handler;

	const GENERATOR_STEP_ALL = "ALL";
	const GENERATOR_STEP_COMPONENTS = "COMPONENTS";
	const GENERATOR_STEP_OPTIONS = "OPTIONS";
	const GENERATOR_STEP_PRE_ACTIONS = "PRE_ACTIONS";
	const GENERATOR_STEP_ACTIONS = "ACTIONS";
	const GENERATOR_ACTION_ALL = "ALL_ACTIONS";

	/**
	 * Returns the *Singleton* instance.
	 *
	 * @return Theme The *Singleton* instance.
	 */
	public static function getInstance(){
		if (null === static::$instance) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	protected function __construct(){
		$this->layout = Layout::getInstance();
		$this->custom_styles_handler = new \WP_Styles();
		add_action("waboot/head/end", [$this,"print_inline_styles"]);
	}

	/**
	 * Loads all theme hooks
	 */
	public function load_hooks(){
		$hooks_files = [
			'inc/hooks/init.php',
			'inc/hooks/hooks.php',
			'inc/hooks/layout.php',
			'inc/hooks/widget_areas.php',
			'inc/hooks/options.php',
			'inc/hooks/posts_and_pages.php',
		];
		foreach($hooks_files as $file){
			if (!$filepath = locate_template($file)) {
				trigger_error(sprintf(__('Error locating %s for inclusion', 'waboot'), $file), E_USER_ERROR);
			}
			require_once $filepath;
		}
		return $this;
	}

	/**
	 * Loads all theme dependecies
	 *
	 * @return $this
	 */
	public function load_dependencies(){
		$hooks_files = [
			'inc/Component.php',
		];
		foreach($hooks_files as $file){
			if (!$filepath = locate_template($file)) {
				trigger_error(sprintf(__('Error locating %s for inclusion', 'waboot'), $file), E_USER_ERROR);
			}
			require_once $filepath;
		}
		return $this;
	}

	/**
	 * Adds a new inline style. Inline styles will be printed during "waboot/head/end" action.
	 *
	 * @param $handle
	 * @param $path
	 */
	public function add_inline_style($handle,$src, $deps = array(), $ver = false, $media = 'all'){
		if(preg_match("/^https?/",$src)){
			$src = Utilities::url_to_path($src);
		}
		if(!file_exists($src)) return;
		$this->inline_styles[] = $handle;
		$this->custom_styles_handler->add($handle,$src, $deps, $ver);
	}

	/**
	 * Print registered inline styles
	 *
	 * @hooked "waboot/head/end"
	 */
	public function print_inline_styles(){
		$output = "";
		/*
		 * We enqueue the registered inline styles. We hope that those steps will resolve dependencies
		 */
		$this->custom_styles_handler->all_deps($this->inline_styles);
		$items = $this->custom_styles_handler->to_do;
		/*
		 * We cycle through the registered styles. We suppose that those styles are already ordered by dependency (it's the reason we used WP_Styles in the first place)
		 */
		foreach ($items as $style){
			if(isset($this->custom_styles_handler->registered[$style])){
				$current_style = $this->custom_styles_handler->registered[$style];
				//Read the file:
				$content = file_get_contents($current_style->src);
				//Append:
				if($content){
					$output .= "/********* $current_style->handle **********/\n";
					$output .= $content."\n";
				}
			}
		}
		$output = sprintf( "<style id='%s-inline-css' type='text/css'>\n%s\n</style>\n", "components", $output );
		echo $output;
	}

	/**
	 * Get theme generators
	 *
	 * @return array
	 */
	public static function get_generators(){
		$parent_dirpath = get_template_directory();
		$child_dirpath = get_stylesheet_directory();

		$generators_directories = [
			$child_dirpath."/inc/generators",
			$parent_dirpath."/inc/generators",
		];

		$generators_directories = array_unique(apply_filters("waboot/generators/directories",$generators_directories));

		$generators_files = [];

		$generators = [];

		foreach($generators_directories as $directory){
			$files = glob($directory."/*.json");
			if(is_array($files)){
				$files = array_filter($files,function($el){
					if(basename($el) == "generator-template.json") return false;
					return true;
				});
				if(!empty($files)){
					$generators_files = array_merge($generators_files,$files);
				}
			}
		}

		foreach ($generators_files as $generators_file){
			$content = file_get_contents($generators_file);
			$parsed = json_decode($content);
			if(!is_null($parsed)){
				$slug = rtrim(basename($generators_file),".json");
				$parsed->file = $generators_file;
				$parsed->slug = $slug;

				//Preview actions
				if(isset($parsed->preview)){
					if(preg_match('|'.$child_dirpath.'|',$generators_file)){
						$baseuri = get_stylesheet_directory_uri();
					}elseif(preg_match('|'.$parent_dirpath.'|',$generators_file)){
						$baseuri = get_template_directory_uri();
					}else{
						$baseuri = Paths::path_to_url(dirname($generators_file));
					}
					$parsed->preview_basepath = $baseuri;
				}

				$generators[$slug] = $parsed;
			}
		}

		return $generators;
	}

	/**
	 * Handle a generator
	 *
	 * @param $generator_slug
	 * @param string $step
	 * @param string $action
	 *
	 * @return array
	 */
	public function handle_generator($generator_slug, $step = self::GENERATOR_STEP_ALL, $action = self::GENERATOR_ACTION_ALL){
		$generators = Theme::get_generators();
		$default_return = [
			'generator' => $generator_slug,
			'step' => $step,
			'next_step' => false,
			'action' => $action,
			'next_action' => false,
			'status' => false,
			'message' => '',
			'complete' => false
		];
		if(!array_key_exists($generator_slug,$generators)){
			return wp_parse_args([
				'step' => $step,
				'status' => 'failed',
				'message' => 'No generator found'
			],$default_return);
		}
		try{
			$selected_generator = $generators[$generator_slug];

			if($step == self::GENERATOR_STEP_ALL || $step == self::GENERATOR_STEP_PRE_ACTIONS){
				//Do actions before
				if(!isset($selected_generator->pre_actions) || !is_array($selected_generator->pre_actions) || empty($selected_generator->pre_actions)){
					return wp_parse_args([
						'next_step' => self::GENERATOR_STEP_COMPONENTS,
						'status' => 'success',
					],$default_return);
				}
				$generator_instance = $this->get_generator_instance($generator_slug,$selected_generator);
				$method_name = $action;
				$methods = $this->get_generator_methods($selected_generator,$generator_instance,self::GENERATOR_STEP_PRE_ACTIONS);
				$method_key = $this->execute_generator_method($method_name,$selected_generator,$generator_instance,self::GENERATOR_STEP_PRE_ACTIONS);
				if($method_key == count($methods)-1){
					//This is the last method
					return wp_parse_args([
						'next_step' => self::GENERATOR_STEP_COMPONENTS,
						'status' => 'success'
					],$default_return);
				}else{
					//This is not the last method
					return wp_parse_args([
						'next_step' => $step,
						'next_action' => $methods[$method_key+1],
						'status' => 'success'
					],$default_return);
				}
			}

			if($step == self::GENERATOR_STEP_ALL || $step == self::GENERATOR_STEP_COMPONENTS){
				if(!wbf_exists()) throw new \Exception("WBF not detected");
				//Toggle components
				$registered_components = ComponentsManager::getAllComponents();
				foreach ($registered_components as $component_name => $component_data){ //Disable all components
					ComponentsManager::disable($component_name);
				}
				if(isset($selected_generator->components) && is_array($selected_generator->components) && !empty($selected_generator->components)){
					foreach ($selected_generator->components as $component_to_enable){
						ComponentsManager::enable($component_to_enable); //Selectively enable components
					}
				}
				if($step == self::GENERATOR_STEP_COMPONENTS){
					return wp_parse_args([
						'next_step' => self::GENERATOR_STEP_OPTIONS,
						'status' => 'success'
					],$default_return);
				}
			}

			if($step == self::GENERATOR_STEP_ALL || $step == self::GENERATOR_STEP_OPTIONS){
				if(!wbf_exists()) throw new \Exception("WBF not detected");
				//Setup options
				if(isset($selected_generator->options)){
					$options = json_decode(json_encode($selected_generator->options), true); //stdClass to array
					if(is_array($options) && !empty($options)){
						$options_to_set = [];
						foreach ($selected_generator->options as $option_name => $option_value){
							$current_option = Framework::get_option_object($option_name);
							if(is_array($current_option)){
								$options_to_set[$option_name] = $option_value;
								Framework::set_option_value($option_name,$option_value);
							}
						}
					}
				}
				if($step == self::GENERATOR_STEP_OPTIONS){
					return wp_parse_args([
						'next_step' => self::GENERATOR_STEP_ACTIONS,
						'status' => 'success'
					],$default_return);
				}
			}

			if($step == self::GENERATOR_STEP_ALL || $step == self::GENERATOR_STEP_ACTIONS){
				if(!wbf_exists()) throw new \Exception("WBF not detected");
				//Do actions
				if(!isset($selected_generator->actions) || !is_array($selected_generator->actions) || empty($selected_generator->actions)){
					return wp_parse_args([
						'next_step' => false,
						'next_action' => false,
						'status' => 'success',
						'complete' => true
					],$default_return);
				}
				$generator_instance = $this->get_generator_instance($generator_slug,$selected_generator);
				if($action == self::GENERATOR_ACTION_ALL){
					foreach ($selected_generator->actions as $method_name){
						if(method_exists($generator_instance,$method_name)){
							call_user_func([$generator_instance,$method_name]);
						}
					}
				}else{
					$method_name = $action;
					$methods = $this->get_generator_methods($selected_generator,$generator_instance,self::GENERATOR_STEP_ACTIONS);
					$method_key = $this->execute_generator_method($method_name,$selected_generator,$generator_instance,self::GENERATOR_STEP_ACTIONS);
					if($method_key == count($methods)-1){
						//This is the last method
						return wp_parse_args([
							'next_step' => false,
							'next_action' => false,
							'status' => 'success',
							'complete' => true
						],$default_return);
					}else{
						//This is not the last method
						return wp_parse_args([
							'next_step' => $step,
							'next_action' => $methods[$method_key+1],
							'status' => 'success'
						],$default_return);
					}
				}
			}

			return wp_parse_args([
				'status' => 'success',
				'complete' => true
			],$default_return);
		}catch (\Exception $e){
			return wp_parse_args([
				'status' => 'failed',
				'message' => $e->getMessage()
			],$default_return);
		}
	}

	/**
	 * @param $generator_slug
	 * @param $generator_params
	 *
	 * @return object
	 * @throws \Exception
	 */
	private function get_generator_instance($generator_slug,$generator_params){
		$generator_filename = dirname($generator_params->file)."/".$generator_slug.".php";
		if(!file_exists($generator_filename)){
			throw new \Exception("Generator file not found.");
		}
		require_once $generator_filename; //Require the generator php file
		if(!isset($generator_params->classname) || !class_exists($generator_params->classname)){
			throw new \Exception("Cannot instantiate $generator_params->classname.");
		}
		$generator_instance = new $generator_params->classname;
		return $generator_instance;
	}

	/**
	 * @param $generator_params
	 * @param $generator_instance
	 * @param $context
	 *
	 * @return array
	 * @throws \Exception
	 */
	private function get_generator_methods($generator_params,$generator_instance,$context){
		if($context === self::GENERATOR_STEP_PRE_ACTIONS){
			$methods = $generator_params->pre_actions;
		}elseif($context === self::GENERATOR_STEP_ACTIONS){
			$methods = $generator_params->actions;
		}else{
			$methods = [];
		}
		$real_methods = get_class_methods($generator_instance);
		$methods = array_filter($methods,function($var) use($real_methods){
			return in_array($var,$real_methods);
		});
		if(!is_array($methods) || empty($methods)){
			throw new \Exception("No methods found on generator instance.");
		}
		return $methods;
	}

	/**
	 * @param $method_name
	 * @param $generator_instance
	 *
	 * @return int
	 * @throws \Exception
	 */
	private function execute_generator_method($method_name,$generator_params,$generator_instance,$context){
		$methods = $this->get_generator_methods($generator_params,$generator_instance,$context);
		if(!$method_name || $method_name === '' || $method_name === 'false'){
			$method_name = $methods[0];
		}
		$method_key = array_search($method_name,$methods);
		call_user_func([$generator_instance,$method_name]);
		return $method_key;
	}

	/**
	 * Loads the hooks for displaying the generators page only
	 */
	public static function preload_generators_page(){
		locate_template('inc/hooks/stylesheets.php', true);
		locate_template('inc/hooks/scripts.php', true);
		locate_template('inc/hooks/generators.php', true);
	}

	/**
	 * Checks if the wizard (aka: the generators page) han been run once
	 *
	 * @return bool
	 */
	public static function is_wizard_done(){
		return (bool) get_option('waboot-done-wizard',false);
	}

	public static function is_wizard_skipped(){
		return (bool) get_option('waboot-skipped-wizard',false);
	}

	/**
	 * Set the wizard (aka: the the generators page) as run
	 */
	public static function set_wizard_as_done(){
		update_option('waboot-done-wizard',true);
	}

	/**
	 * Set the wizard (aka: the the generators page) as run
	 */
	public static function set_wizard_as_skipped(){
		update_option('waboot-skipped-wizard',true);
	}

	/**
	 * Reset wizard options
	 */
	public static function reset_wizard(){
		delete_option('waboot-done-wizard');
		delete_option('waboot-skipped-wizard');
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