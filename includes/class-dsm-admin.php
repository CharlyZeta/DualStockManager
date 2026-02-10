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
			'Inventario Dual', 
			'Inventario Dual', 
			'manage_options', 
			'dualstock-manager', 
			array( $this, 'display_plugin_dashboard' ), 
			'dashicons-store', 
			6 
		);

		add_submenu_page(
			'dualstock-manager',
			'Panel Principal',
			'Panel Principal',
			'manage_options',
			'dualstock-manager',
			array( $this, 'display_plugin_dashboard' )
		);

		// Transfer submenu
		add_submenu_page(
			'dualstock-manager',
			'Transferir Stock',
			'Transferir Stock',
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
		require_once DSM_PLUGIN_DIR . 'templates/transfer.php';
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
        
        // Scanner Scripts (Dashboard only or global? Global for now as Audit might be needed elsewhere)
        if ( isset( $_GET['page'] ) && 'dualstock-manager' === $_GET['page'] ) {
            wp_enqueue_script( 'html5-qrcode', DSM_PLUGIN_URL . 'assets/js/vendor/html5-qrcode.min.js', array(), '2.3.8', true );
            wp_enqueue_script( 'dsm-scanner', DSM_PLUGIN_URL . 'assets/js/scanner.js', array( 'jquery', 'html5-qrcode' ), DSM_VERSION, true );
        }
        
        // Enqueue Transfer Script if on the transfer page
		if ( isset( $_GET['page'] ) && 'dsm-transfer' === $_GET['page'] ) {
			wp_enqueue_script( 'dsm-transfer-script', DSM_PLUGIN_URL . 'assets/js/admin-transfer.js', array( 'jquery' ), DSM_VERSION, true );
			wp_localize_script( 'dsm-transfer-script', 'dsm_params', array(
				'root'      => esc_url_raw( rest_url( 'dsm/v1/' ) ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
			));
		}

        // Get categories for Dashboard filter
        $categories = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'fields'     => 'id=>name' // Simplified map: ID => Name
        ));
        
        // Format for JS: [{id: 1, name: 'Foo'}, ...]
        $cat_list = array();
        if ( ! is_wp_error( $categories ) ) {
            foreach ( $categories as $id => $name ) {
                $cat_list[] = array( 'id' => $id, 'name' => $name );
            }
        }

		wp_localize_script( 'dsm-alpine', 'dsm_params', array(
			'root'      => esc_url_raw( rest_url( 'dsm/v1/' ) ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
            'ajax_url'  => admin_url( 'admin-ajax.php' ),
            'categories'=> $cat_list
		));
	}
}
