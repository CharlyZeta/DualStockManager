<?php

/**
 * The core plugin class.
 */
class DualStock_Manager {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 */
	protected $loader;

	/**
	 * Instance of the Sync Engine.
	 */
	protected $sync_engine;

	/**
	 * Instance of the API.
	 */
	protected $api;

	/**
	 * Define the core functionality of the plugin.
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->init_components();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 */
	private function load_dependencies() {
		require_once DSM_PLUGIN_DIR . 'includes/class-dsm-admin.php';
		require_once DSM_PLUGIN_DIR . 'includes/class-dsm-sync-engine.php';
		require_once DSM_PLUGIN_DIR . 'includes/class-dsm-api.php';
	}
	
	private function init_components() {
		$this->sync_engine = new DSM_Sync_Engine();
		$this->api         = new DSM_API();
	}

	/**
	 * Register all of the hooks related to the admin area functionality.
	 */
	private function define_admin_hooks() {
		$plugin_admin = new DSM_Admin();
		add_action( 'admin_menu', array( $plugin_admin, 'add_plugin_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality.
	 */
	private function define_public_hooks() {
		$this->sync_engine->init();
		$this->api->init();
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		// If we used a loader class, we would run it here.
	}

}
