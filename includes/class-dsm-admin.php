<?php

/**
 * The admin-specific functionality of the plugin.
 */
class DSM_Admin {

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 */
	public function add_plugin_admin_menu() {
		add_menu_page(
			'Dual Inventory', 
			'Dual Inventory', 
			'manage_options', 
			'dualstock-manager', 
			array( $this, 'display_plugin_dashboard' ), 
			'dashicons-store', 
			6 
		);

		add_submenu_page(
			'dualstock-manager',
			'Dashboard',
			'Dashboard',
			'manage_options',
			'dualstock-manager',
			array( $this, 'display_plugin_dashboard' )
		);

		// Transfer submenu
		add_submenu_page(
			'dualstock-manager',
			'Transfer Stock',
			'Transfer Stock',
			'manage_options',
			'dsm-transfer',
			array( $this, 'display_transfer_page' )
		);
	}

	/**
	 * Render the partials for the admin area.
	 */
	public function display_plugin_dashboard() {
		require_once DSM_PLUGIN_DIR . 'templates/dashboard.php';
	}

	public function display_transfer_page() {
		// Just reuse dashboard for now or a simple placeholder
		echo '<h1>Transfer Stock</h1><div id="dsm-transfer-app">Coming Soon (Use API)</div>';
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'dsm-admin-style', DSM_PLUGIN_URL . 'assets/css/style.css', array(), DSM_VERSION, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 */
	public function enqueue_scripts() {
        // Enqueue local Alpine.js
        wp_enqueue_script( 'dsm-alpine', DSM_PLUGIN_URL . 'assets/js/vendor/alpine.min.js', array(), '3.13.3', true );

		wp_enqueue_script( 'dsm-admin-script', DSM_PLUGIN_URL . 'assets/js/app.js', array( 'jquery', 'dsm-alpine' ), DSM_VERSION, true );
		
		wp_localize_script( 'dsm-admin-script', 'dsm_params', array(
			'root'      => esc_url_raw( rest_url( 'dsm/v1/' ) ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
            'ajax_url'  => admin_url( 'admin-ajax.php' )
		));
	}
}
