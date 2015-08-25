<?php

namespace WBF\modules\options;

use WBF\includes\compiler\Styles_Compiler;

class CustomizerManager{

	static $setting_type = "wbf_theme_option";

	public static function init(){
		global $wbf_styles_compiler;
		add_action( 'customize_register','\WBF\modules\options\CustomizerManager::register' );
		add_action( 'customize_update_wbf_theme_option', '\WBF\modules\options\CustomizerManager::update', 10, 2 );
		add_action( 'customize_save_after', '\WBF\modules\options\CustomizerManager::after_customizer_save', 10, 2 );
		add_action( 'customize_preview_wbf_theme_option', '\WBF\modules\options\CustomizerManager::preview', 10, 2 );
		//Add a new compile set to styles compiler
		if(isset($wbf_styles_compiler) && $wbf_styles_compiler){
			$wbf_styles_compiler->base_compiler->add_set("customizer_preview",[
				'input' => call_user_func(function() use($wbf_styles_compiler){
					if(file_exists($wbf_styles_compiler->base_compiler->sources_path."_theme-options-generated.less.cmp")){
						return $wbf_styles_compiler->base_compiler->sources_path."_theme-options-generated.less.cmp"; //todo: in un ottica di poter utilizzare più compilatori, questo file dovrebbe essere specificato altrove
					}else{
						return false;
					}
				}),
				'exclude_from_global_compile' => true,
				'compile_callback' => '\WBF\modules\options\CustomizerManager::styles_preview_callback'
			]);
		}
	}
	public static function register(\WP_Customize_Manager $wp_customize){
		$options = Framework::get_registered_options();
		$options_values = Framework::get_options_values();

		$wp_customize->add_panel('wbf_theme_options',[
			'title' => __("Theme Options","wbf"),
			'description' => __("WBF Managed settings","wbf")
		]);

		$current_section = "";
		foreach($options as $opt){
			if($opt['type'] == "heading"){
				$wp_customize->add_section($opt['name'],[
					'title' => $opt['name'],
					'panel' => 'wbf_theme_options'
				]);
				$current_section = $opt['name'];
			}else{

				$unsupported_types = ['info','typography','multicheck','csseditor'];
				$equivalent_types = [
					'images' => 'select'
				];

				if(in_array($opt['type'],$unsupported_types)) continue;

				$setting_id = "theme_options[{$opt['id']}]";

				$wp_customize->add_setting($setting_id,[
					'type' => self::$setting_type,
					'capability' => 'manage_options',
					'default' => call_user_func(function() use($options_values,$opt){
						if(isset($options_values[$opt['id']])){
							return $options_values[$opt['id']];
						}else{
							if(isset($opt['std'])){
								return $opt['std'];
							}
						}
						return "";
					}),
					'transport' => 'refresh',
					'sanitize_callback' => '',
					'sanitize_js_callback' => ''
				]);

				//Detect control type and choices
				$args = [];
				$custom_control = false;
				switch($opt['type']){
					case "color":
						$custom_control = "\WP_Customize_Color_Control";
						break;
					case "upload":
						$custom_control = "\WP_Customize_Upload_Control";
						break;
					case "images":
						$args['type'] = $equivalent_types[$opt['type']];
						$args['choices'] = call_user_func(function() use($opt){
							$choices = [];
							foreach($opt['options'] as $k => $v){
								$choices[$k] = $v['label'];
							}
							return $choices;
						});
						break;
					case "select":
						$args['type'] = "select";
						$args['choices'] = $opt['options'];
						break;
					default:
						$args['type'] = $opt['type'];
						break;
				}

				$args = array_merge($args,[
					'priority' => 10,
					'section' => $current_section,
					'label' => $opt['name'],
					'description' => isset($opt['desc']) ? $opt['desc'] : "",
				]);

				if(!$custom_control){
					$wp_customize->add_control($setting_id,$args);
				}else{
					$custom_control = new $custom_control($wp_customize,$setting_id,$args);
					$wp_customize->add_control($custom_control);
				}
			}
		}
	}

	public static function update($value, \WP_Customize_Setting $setting){
		$name = call_user_func(function() use($setting){
			$match = preg_match("/\[([\w_]+)\]/",$setting->id,$matches);
			if($match) return $matches[1];
			else return false;
		});
		Framework::set_option_value($name,$value);
	}

	public static function after_customizer_save(\WP_Customize_Manager $wp_customize){
		//Recompile the styles
		$values = Framework::get_options_values();
		of_recompile_styles($values,true); //compile and release
	}

	/**
	 * Handles the preview of the modified theme options into the wordpress customizer. Temporary add a filter that changes the of_get_option retrieved value
	 * @param \WP_Customize_Setting $setting
	 */
	public static function preview(\WP_Customize_Setting $setting){
		$name = call_user_func(function() use($setting){
			$match = preg_match("/\[([\w_]+)\]/",$setting->id,$matches);
			if($match) return $matches[1];
			else return false;
		});
		if($name){
			add_filter("wbf/theme_options/get/{$name}",function($value) use($setting){
				$new_value = $setting->post_value();
				if(!$new_value){
					return $value;
				}else{
					return $new_value;
				}
			});
			//self::styles_preview(); //todo: qst viene eseguito una volta per ogni opzione... è troppo. Forse si potrebbe risolvere aggiungendo una action nell'header, che a sua volta fa l'azione di styles_preview, almeno viene aggiunta una volta sola...
		}
	}

	private static function styles_preview(){
		global $wbf_styles_compiler;
		$wbf_styles_compiler->compile("customizer_preview");
	}

	public static function styles_preview_callback(){
		//todo: Qui andrebbe preso il file _theme_options_generated.less.cmp, eliminiamo le variabili less, compiliamo le variabili apponendo !important e poi lo stampiamo nell'head di WP
		return true;
	}
}