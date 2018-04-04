<?php

//This is an experimental migration, done in a hurry

namespace Waboot\migrations;

use function Waboot\functions\components\install_remote_component;

add_action('init', function(){
	if(!isset($_GET['debug_mig'])) return;

	\update_option('waboot_updates_backups_components-migrations',[
		'2.3.2_2.4.0_waboot-child' => [
			'bootstrap' => 1,
			'header_classic' => 1,
			'footer_classic' => 1
		]
	]);

	\delete_option('waboot-migrations');

	WBF()->get_service_manager()->get_notice_manager()->clear_notices();
});

add_action('init', function(){
	$migrations = \get_option('waboot-migrations', []);

	if(array_key_exists('2.3.2-2.4.0',$migrations) && isset($migrations['2.3.2-2.4.0']['status']) && $migrations['2.3.2-2.4.0']['status'] === 'done') return;

	if(!isset($migrations['2.3.2-2.4.0'])){
		$migrations['2.3.2-2.4.0'] = [
			'status' => 'incomplete'
		];
	}

	$current_migration = $migrations['2.3.2-2.4.0'];
	$complete = false;

	if(is_admin()){
		$backupped_components_states = \get_option('waboot_updates_backups_components');
		$current_theme = wp_get_theme();
		$hash = '2.3.2'.'_'.'2.4.0'.'_'.$current_theme->get_stylesheet();
		$last_backupped_components_states_update = array_key_exists($hash,$backupped_components_states) ? $backupped_components_states[$hash] : false;
		if($last_backupped_components_states_update && is_file($last_backupped_components_states_update['file'])){
			$states = file_get_contents($last_backupped_components_states_update['file']);
			$states = unserialize($states);
			$notice_msg = 'Waboot 2.4 has removed built-in components. You must reinstall some components:';
			if(is_array($states) && !empty($states)){
				$states['bootstrap'] = 1; //Force bootstrap
				$components_to_reinstall = array_filter($states,function($v){
					return $v === 1;
				});
				foreach($components_to_reinstall as $component_slug => $state){
					if($state === 1){
						$installed_component = array_key_exists('installed_component_'.$component_slug,$current_migration) && $current_migration['installed_component_'.$component_slug];
						if(!$installed_component){
							$notice_msg .= sprintf(
								__('<p> %s: <a href="%s">install</a> or <a href="%s">dismiss</a></p>'),
								$component_slug,
								add_query_arg(['waboot_perform_updates' => 'component','comp_slug' => $component_slug, 'action' => 'install'],admin_url()),
								add_query_arg(['waboot_perform_updates' => 'component','comp_slug' => $component_slug, 'action' => 'dismiss'],admin_url())
							);
						}else{
							unset($components_to_reinstall[$component_slug]);
						}
					}
				}
				if(!isset($components_to_reinstall) || empty($components_to_reinstall)){
					$complete = true;
				}else{
					WBF()->get_service_manager()->get_notice_manager()->add_notice('must_install_components',$notice_msg,'nag','_flash_');
				}
			}
		}
	}

	if($complete){
		$current_migration['status'] = 'done';
		$migrations['2.3.2-2.4.0'] = $current_migration;
		\update_option('waboot-migrations',$migrations);
	}
});

add_action('admin_init', function(){
	if(!isset($_GET['waboot_perform_updates'])) return;

	$operation = sanitize_text_field($_GET['waboot_perform_updates']);

	$migrations = \get_option('waboot-migrations', []);
	$current_migration = $migrations['2.3.2-2.4.0'];

	switch($operation){
		case 'component':
			$slug = isset($_GET['comp_slug']) ? sanitize_text_field($_GET['comp_slug']) : false;
			$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : false;
			if($slug){
				if($action === 'install'){
					mig_232_240_install_component($slug);
				}elseif($action === 'dismiss'){
					$current_migration['installed_component_'.$slug] = true;
					$migrations['2.3.2-2.4.0'] = $current_migration;
					\update_option('waboot-migrations',$migrations);
				}
			}
			break;
	}
});

function mig_232_240_install_component($component){
	$migrations = \get_option('waboot-migrations', []);
	$current_migration = $migrations['2.3.2-2.4.0'];

	//Doing the update...
	try{
		install_remote_component($component);

		//Update the option
		$current_migration['installed_component_'.$component] = true;
		$migrations['2.3.2-2.4.0'] = $current_migration;
		\update_option('waboot-migrations',$migrations);
	}catch (\Exception $e){
		WBF()->get_service_manager()->get_notice_manager()->add_notice('unable_to_install_component_'.$component,$e->getMessage(),'error','_flash_');
	}
}