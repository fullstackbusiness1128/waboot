<?php

interface Waboot_Plugin_Interface {

}

class Waboot_Plugin {
	/**
	 * A reference to an instance of this class for singleton usage.
	 *
	 * @since 1.0.0
	 *
	 * @var   Waboot_Plugin
	 */
	private static $instance;
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Waboot_Galleries_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;
	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;
	/**
	 * The plugin dir
	 *
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $plugin_dir;
	/**
	 * The full path to main plugin file
	 *
	 * @since 0.10.0
	 * @access   protected
	 * @var string
	 */
	protected $plugin_path;
	/**
	 * The path relative to WP_PLUGIN_DIR
	 *
	 * @var
	 */
	protected $plugin_relative_dir;
	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;
	/**
	 * The instance of Plugin_Update_Checker
	 *
	 * @since    0.10.0
	 * @access   protected
	 * @var      object
	 */
	protected $update_instance;

	protected $debug_mode = false;

	public function __construct( $plugin_name, $dir, $version = "1.0.0" ) {
		$this->plugin_name = $plugin_name;
		$this->plugin_dir  = $dir;
		$this->plugin_path = $this->plugin_dir.$this->plugin_name.".php";

		//Set relative path
		$pinfo = pathinfo($dir);
		$this->plugin_relative_dir = "/".$pinfo['basename'];

		//Get the version
		if(function_exists("get_plugin_data")){
			$pluginHeader = get_plugin_data($this->plugin_path, false, false);
			if ( isset($pluginHeader['Version']) ) {
				$this->version = $pluginHeader['Version'];
			} else {
				$this->version = $version;
			}
		}else{
			$this->version = $version;
		}

		//Check if debug mode must be activated
		if( (defined("WABOOT_ENV") && WABOOT_ENV == "dev") || (defined("WBF_ENV") && WBF_ENV == "dev") ){
			$this->debug_mode = true;
		}

		$GLOBALS['wbf_loaded_plugins'][$this->get_plugin_name()] = &$this;

		$this->load_dependencies();
		$this->set_locale();
	}

	public function set_update_server($metadata_call){
		if(!empty($metadata_call)){
			$this->update_instance = new \WBF\includes\Plugin_Update_Checker(
				$metadata_call,
				$this->plugin_dir.$this->plugin_name.".php",
				$this->plugin_name
			);
		}
	}

	/**
	 * Load the required dependencies for the plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Waboot_Galleries_Loader. Orchestrates the hooks of the plugin.
	 * - Waboot_Galleries_i18n. Defines internationalization functionality.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	protected function load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		//require_once plugin_dir_path( dirname( __FILE__ ) ) . 'class-waboot-plugin-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		//require_once plugin_dir_path( dirname( __FILE__ ) ) . 'class-waboot-plugin-i18n.php';

		$this->loader = new Waboot_Plugin_Loader($this);
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Waboot_Plugin_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new Waboot_Plugin_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );
		$plugin_i18n->set_language_dir( $this->plugin_relative_dir."/languages/" );
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Returns an instance of this class. An implementation of the singleton design pattern.
	 *
	 * @return   Waboot_Plugin A reference to an instance of this class.
	 * @since    1.0.0
	 */
	public static function get_instance( $plugin_name, $dir, $version ) {
		if ( null == self::$instance ) {
			self::$instance = new self( $plugin_name, $dir, $version );
		}

		return self::$instance;
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	public function get_dir(){
		return $this->plugin_dir;
	}

	public function get_path(){
		return $this->plugin_path;
	}

	public function get_relative_dir(){
		return $this->plugin_relative_dir;
	}

	public function is_debug(){
		return $this->debug_mode;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Waboot_Plugin_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}