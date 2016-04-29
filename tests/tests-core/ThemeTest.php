<?php

namespace tests;

use WP_Mock\Functions;

class ThemeTest extends \PHPUnit_Framework_TestCase{
	public function setUp() {
		\WP_Mock::setUp();

		//Loads WBF
		\WP_Mock::wpFunction( 'get_bloginfo', array(
			'args' => ['url'],
			'return' => "http://waboot.dev"
		) );

		\WP_Mock::wpFunction('wp_parse_args', [
			'args' => [
				[],
				[
					'do_global_theme_customizations' => true,
					'check_for_updates' => true
				]
			],
			'return' => [
				'do_global_theme_customizations' => true,
				'check_for_updates' => true
			]
		]);

		\WP_Mock::wpFunction('get_option',[
			'args' => ["wbf_installed"],
			'return' => true
		]);

		\WP_Mock::wpFunction('get_option',[
			'args' => ["wbf_path"],
			'return' => WBTEST_WP_CONTENT_PATH."/plugins/wbf"
		]);

		\WP_Mock::wpFunction('get_option',[
			'args' => ["wbf_url"],
			'return' => "http://waboot.dev/plugins/wbf"
		]);

		\WP_Mock::wpFunction('get_template_directory',[
			'args' => [],
			'return' => WBTEST_WP_CONTENT_PATH."/themes/waboot"
		]);

		\WP_Mock::wpFunction('get_stylesheet_directory',[
			'args' => [],
			'return' => WBTEST_WP_CONTENT_PATH."/themes/waboot"
		]);

		\WP_Mock::wpFunction('wp_get_theme',[
			'args' => [],
			'return' => new \WP_Theme()
		]);

		\WP_Mock::wpFunction('plugin_basename',[
			'args' => [WBTEST_WP_CONTENT_PATH."/plugins/wbf/wbf.php"],
			'return' => "wbf"
		]);

		require_once WBTEST_WP_CONTENT_PATH."/plugins/wbf/wbf.php";

		//Loads Layout
		require_once WBTEST_WP_CONTENT_PATH."/themes/waboot/inc/Layout.php";
		require_once WBTEST_WP_CONTENT_PATH."/themes/waboot/inc/Theme.php";
		require_once WBTEST_WP_CONTENT_PATH."/themes/waboot/inc/Component.php";

		\WP_Mock::wpFunction('locate_template',[
			'args' => ["inc/template-functions.php"],
			'return' => WBTEST_WP_CONTENT_PATH."/themes/waboot/"."inc/template-functions.php"
		]);
		\WP_Mock::wpFunction('locate_template',[
			'args' => ["inc/template-tags.php"],
			'return' => WBTEST_WP_CONTENT_PATH."/themes/waboot/"."inc/template-tags.php"
		]);
		\WP_Mock::wpFunction('locate_template',[
			'args' => ["inc/Layout.php"],
			'return' => WBTEST_WP_CONTENT_PATH."/themes/waboot/"."inc/Layout.php"
		]);
		\WP_Mock::wpFunction('locate_template',[
			'args' => ["inc/Theme.php"],
			'return' => WBTEST_WP_CONTENT_PATH."/themes/waboot/"."inc/Theme.php"
		]);
		\WP_Mock::wpFunction('locate_template',[
			'args' => ["inc/Component.php"],
			'return' => WBTEST_WP_CONTENT_PATH."/themes/waboot/"."inc/Component.php"
		]);
		\WP_Mock::wpFunction('locate_template',[
			'args' => ["inc/hooks/init.php"],
			'return' => WBTEST_WP_CONTENT_PATH."/themes/waboot/"."inc/hooks/init.php"
		]);
		\WP_Mock::wpFunction('locate_template',[
			'args' => ["inc/hooks/hooks.php"],
			'return' => WBTEST_WP_CONTENT_PATH."/themes/waboot/"."inc/hooks/hooks.php"
		]);
		\WP_Mock::wpFunction('locate_template',[
			'args' => ["inc/hooks/stylesheets.php"],
			'return' => WBTEST_WP_CONTENT_PATH."/themes/waboot/"."inc/hooks/stylesheets.php"
		]);
		\WP_Mock::wpFunction('locate_template',[
			'args' => ["inc/hooks/scripts.php"],
			'return' => WBTEST_WP_CONTENT_PATH."/themes/waboot/"."inc/hooks/scripts.php"
		]);
		\WP_Mock::wpFunction('locate_template',[
			'args' => ["inc/hooks/zones_std_hooks.php"],
			'return' => WBTEST_WP_CONTENT_PATH."/themes/waboot/"."inc/hooks/zones_std_hooks.php"
		]);

		\WP_Mock::wpFunction('wp_parse_args', [
			'args' => [
				["always_load"=>true],
				["always_load"=>false]
			],
			'return' => ["always_load"=>true]
		]);
		\WP_Mock::wpFunction('wp_parse_args', [
			'args' => [
				[],
				["always_load"=>false]
			],
			'return' => ["always_load"=>false]
		]);

		//Loads theme functions
		require_once WBTEST_WP_CONTENT_PATH."/themes/waboot/functions.php";
	}

	/**
	 * Checks if the theme is loaded correctly
	 */
	public function testWBInit(){
		$wb = Waboot();

		//Is Waboot correctly loaded?
		$this->assertInstanceOf("\\Waboot\\Theme",$wb);
		$this->assertInstanceOf("\\Waboot\\Layout",$wb->layout);

		$zones = $wb->layout->getZones();
		$this->assertEquals(true,is_array($zones));

		//Checking Zones
		$this->assertArrayHasKey("header",$zones);
		$this->assertArrayHasKey("aside-primary",$zones);
		$this->assertArrayHasKey("main",$zones);
		$this->assertArrayHasKey("aside-secondary",$zones);
		$this->assertArrayHasKey("footer",$zones);
	}

	public function tearDown() {
		\WP_Mock::tearDown();
	}
}