<?php

/**
 * Handles the plugin settings page.
 */
class DSM_Settings {

    private $plugin_name;
    private $version;
    private $option_name = 'dsm_delete_on_uninstall';

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function init() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_settings_page() {
        add_submenu_page(
            'dualstock-manager', // Parent slug
            'Settings',          // Page title
            'Settings',          // Menu title
            'manage_options',    // Capability
            'dsm-settings',      // Menu slug
            array( $this, 'render_settings_page' )
        );
    }

    public function register_settings() {
        register_setting( 'dsm_settings_group', $this->option_name );
        register_setting( 'dsm_settings_group', 'dsm_label_local' );
        register_setting( 'dsm_settings_group', 'dsm_label_dep1' );
        register_setting( 'dsm_settings_group', 'dsm_label_dep2' );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>DualStock Manager Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'dsm_settings_group' );
                do_settings_sections( 'dsm_settings_group' );
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Nombres de Almacenes</th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><span>Nombres de Almacenes</span></legend>
                                <label for="dsm_label_local">
                                    <input type="text" name="dsm_label_local" id="dsm_label_local" value="<?php echo esc_attr( get_option( 'dsm_label_local', 'Showroom' ) ); ?>" class="regular-text" placeholder="Showroom" />
                                    <span class="description">Nombre para Local/Showroom</span>
                                </label>
                                <br>
                                <label for="dsm_label_dep1">
                                    <input type="text" name="dsm_label_dep1" id="dsm_label_dep1" value="<?php echo esc_attr( get_option( 'dsm_label_dep1', 'Depósito 1' ) ); ?>" class="regular-text" placeholder="Depósito 1" />
                                    <span class="description">Nombre para Depósito 1</span>
                                </label>
                                <br>
                                <label for="dsm_label_dep2">
                                    <input type="text" name="dsm_label_dep2" id="dsm_label_dep2" value="<?php echo esc_attr( get_option( 'dsm_label_dep2', 'Depósito 2' ) ); ?>" class="regular-text" placeholder="Depósito 2" />
                                    <span class="description">Nombre para Depósito 2</span>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Desinstalación</th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>" value="1" <?php checked( 1, get_option( $this->option_name ), true ); ?> />
                                Borrar todos los datos al desinstalar (Tablas y Opciones)
                            </label>
                            <p class="description">
                                Si marcas esta opción, las tablas <code>wp_dual_inventory</code> y <code>wp_dual_inventory_logs</code> serán eliminadas permanentemente cuando borres el plugin.
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
