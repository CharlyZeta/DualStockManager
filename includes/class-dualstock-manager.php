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
		require_once DSM_PLUGIN_DIR . 'includes/class-dsm-settings.php';
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

		$plugin_settings = new DSM_Settings( 'dualstock-manager', DSM_VERSION );
		$plugin_settings->init();
	}

	/**
	 * Register all of the hooks related to the public-facing functionality.
	 */
	private function define_public_hooks() {
		$this->sync_engine->init();
		$this->api->init();
        
        // Register Shortcode
        add_shortcode( 'dualstock_manager', array( $this, 'render_frontend_dashboard' ) );
	}

    /**
     * Shortcode callback to render the dashboard on frontend.
     */
    public function render_frontend_dashboard( $atts ) {
        // Security Check
        if ( ! current_user_can( 'manage_options' ) ) {
            return '<div class="error"><p>Acceso denegado. Necesitas permisos de administrador.</p></div>';
        }

        // Enqueue Assets naturally
        $this->enqueue_frontend_assets();

        // Buffer Output
        ob_start();
        include DSM_PLUGIN_DIR . 'templates/dashboard.php';
        return ob_get_clean();
    }

    /**
     * Enqueue scripts and styles for the frontend shortcode.
     */
    private function enqueue_frontend_assets() {
        // Enqueue Styles
        wp_enqueue_style( 'dsm-frontend-style', DSM_PLUGIN_URL . 'assets/css/frontend.css', array(), DSM_VERSION, 'all' );
        wp_enqueue_style( 'dashicons' ); // Ensure dashicons are loaded on frontend

        // Enqueue Scripts (Similar to Admin)
        wp_enqueue_script( 'dsm-alpine', DSM_PLUGIN_URL . 'assets/js/vendor/alpine.min.js', array(), '3.13.3', true );
        wp_enqueue_script( 'dsm-admin-script', DSM_PLUGIN_URL . 'assets/js/app.js', array( 'jquery', 'dsm-alpine' ), DSM_VERSION, true );

        // Categories for Filter
        $categories = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'fields'     => 'id=>name'
        ));
        
        $cat_list = array();
        if ( ! is_wp_error( $categories ) ) {
            foreach ( $categories as $id => $name ) {
                $cat_list[] = array( 'id' => $id, 'name' => $name );
            }
        }

        // Localize Script
		wp_localize_script( 'dsm-alpine', 'dsm_params', array(
			'root'      => esc_url_raw( rest_url( 'dsm/v1/' ) ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
            'ajax_url'  => admin_url( 'admin-ajax.php' ),
            'categories'=> $cat_list
		));
    }

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		// If we used a loader class, we would run it here.
	}

}
