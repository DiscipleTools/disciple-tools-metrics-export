<?php
/**
 * Plugin Name: Disciple Tools - Metrics Export
 * Plugin URI: https://github.com/DiscipleTools/disciple-tools-metrics-export
 * Description: Disciple Tools - Metrics Export help facilitate security controlled exports with locations data.
 * of the Disciple Tools system.
 * Version:  1.1
 * Author URI: https://github.com/DiscipleTools
 * GitHub Plugin URI: https://github.com/DiscipleTools/disciple-tools-metrics-export
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 5.4.1
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
$dt_metrics_export_required_dt_theme_version = '0.30.0'; // @todo 0.31.0 required for dt_site_id option

/**
 * Gets the instance of the `DT_Metrics_Export` class.
 *
 * @since  0.1
 * @access public
 * @return object|bool
 */
function dt_metrics_export() {

    global $dt_metrics_export_required_dt_theme_version;
    $wp_theme = wp_get_theme();
    $version = $wp_theme->version;
    /*
     * Check if the Disciple.Tools theme is loaded and is the latest required version
     */
    $is_theme_dt = strpos( $wp_theme->get_template(), "disciple-tools-theme" ) !== false || $wp_theme->name === "Disciple Tools";
    if ( $is_theme_dt && version_compare( $version, $dt_metrics_export_required_dt_theme_version, "<" ) ) {
        add_action( 'admin_notices', 'dt_metrics_export_hook_admin_notice' );
        add_action( 'wp_ajax_dismissed_notice_handler', 'dt_hook_ajax_notice_handler' );
        return false;
    }
    if ( !$is_theme_dt ){
        return false;
    }
    /**
     * Load useful function from the theme
     */
    if ( !defined( 'DT_FUNCTIONS_READY' ) ){
        require_once get_template_directory() . '/dt-core/global-functions.php';
    }
    /*
     * Don't load the plugin on every rest request. Only those with the 'sample' namespace
     */
    $is_rest = dt_is_rest();
    if ( ! $is_rest || strpos( dt_get_url_path(), 'metrics-export' ) !== false ){
        return DT_Metrics_Export::get_instance();
    }
}
add_action( 'init', 'dt_metrics_export' );

/**
 * Singleton class for setting up the plugin.
 *
 * @since  0.1
 * @access public
 */
class DT_Metrics_Export {

    /**
     * Declares public variables
     *
     * @since  0.1
     * @access public
     * @return object
     */
    public $token;
    public $version;
    public $dir_path = '';
    public $dir_uri = '';
    public $img_uri = '';
    public $includes_path;

    /**
     * Returns the instance.
     *
     * @since  0.1
     * @access public
     * @return object
     */
    public static function get_instance() {

        static $instance = null;

        if ( is_null( $instance ) ) {
            $instance = new dt_metrics_export();
            $instance->setup();
            $instance->includes();
            $instance->setup_actions();
        }
        return $instance;
    }

    /**
     * Constructor method.
     *
     * @since  0.1
     * @access private
     * @return void
     */
    private function __construct() {
    }

    /**
     * Loads files needed by the plugin.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    private function includes() {
        if ( is_admin() ) {
            require_once( 'includes/admin-menu-and-tabs.php' );
            require_once( 'includes/format-base.php' );
            require_once( 'includes/format-utilities.php' );

            $format_files = scandir( plugin_dir_path( __FILE__ ) .'/formats/' );
            if ( ! empty( $format_files ) ) {
                foreach ( $format_files as $file ) {
                    if ( substr( $file, -4, '4' ) === '.php' ) {
                        require_once( plugin_dir_path( __FILE__ ) . '/formats/' .$file );
                    }
                }
            }
        }
    }

    /**
     * Sets up globals.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    private function setup() {

        $this->dt_site_id = get_option( 'dt_site_id' );
        if ( empty( $this->dt_site_id ) ) {
            $site_id = hash( 'SHA256', site_url() . time() );
            add_option( 'dt_site_id', $site_id );
            $this->dt_site_id = $site_id;
        }

        // Main plugin directory path and URI.
        $this->dir_path     = trailingslashit( plugin_dir_path( __FILE__ ) );
        $this->dir_uri      = trailingslashit( plugin_dir_url( __FILE__ ) );

        // Plugin directory paths.
        $this->includes_path      = trailingslashit( $this->dir_path . 'includes' );

        // Plugin directory URIs.
        $this->img_uri      = trailingslashit( $this->dir_uri . 'img' );

        // Admin and settings variables
        $this->token             = 'dt_metrics_export';
        $this->version             = '1.1';

        if ( is_admin() ) {
            // storage post type for export configurations
            add_action( 'init', [ $this, 'register_post_type' ] );
        }

    }

    public function register_post_type() {
        $args = array(
            'public'    => false
        );
        register_post_type( 'dt_metrics_export', $args );
    }

    /**
     * Sets up main plugin actions and filters.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    private function setup_actions() {

        if ( is_admin() ){
            // Check for plugin updates
            if ( ! class_exists( 'Puc_v4_Factory' ) ) {
                require( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' );
            }
            /**
             * Below is the publicly hosted .json file that carries the version information. This file can be hosted
             * anywhere as long as it is publicly accessible. You can download the version file listed below and use it as
             * a template.
             * Also, see the instructions for version updating to understand the steps involved.
             * @see https://github.com/DiscipleTools/disciple-tools-version-control/wiki/How-to-Update-the-Starter-Plugin
             */
            $hosted_json = "https://raw.githubusercontent.com/DiscipleTools/disciple-tools-version-control/master/disciple-tools-metrics-export-version-control.json";
            Puc_v4_Factory::buildUpdateChecker(
                $hosted_json,
                __FILE__,
                'disciple-tools-metrics-export'
            );
        }

        // Internationalize the text strings used.
        add_action( 'init', array( $this, 'i18n' ), 2 );
    }



    /**
     * Method that runs only when the plugin is activated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function activation() {

        // Confirm 'Administrator' has 'manage_dt' privilege. This is key in 'remote' configuration when
        // Disciple Tools theme is not installed, otherwise this will already have been installed by the Disciple Tools Theme
        $role = get_role( 'administrator' );
        if ( !empty( $role ) ) {
            $role->add_cap( 'manage_dt' ); // gives access to dt plugin options
        }

    }

    /**
     * Method that runs only when the plugin is deactivated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function deactivation() {
        delete_option( 'dismissed-dt-metrics-export' );
    }

    /**
     * Loads the translation files.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function i18n() {
        load_plugin_textdomain( 'dt_metrics_export', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ). 'languages' );
    }

    /**
     * Magic method to output a string if trying to use the object as a string.
     *
     * @since  0.1
     * @access public
     * @return string
     */
    public function __toString() {
        return 'dt_metrics_export';
    }

    /**
     * Magic method to keep the object from being cloned.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to keep the object from being unserialized.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to prevent a fatal error when calling a method that doesn't exist.
     *
     * @param string $method
     * @param array $args
     * @return null
     * @since  0.1
     * @access public
     */
    public function __call( $method = '', $args = array() ) {
        _doing_it_wrong( "dt_metrics_export::" . esc_html( $method ), 'Method does not exist.', '0.1' );
        unset( $method, $args );
        return null;
    }
}
// end main plugin class

// Register activation hook.
register_activation_hook( __FILE__, [ 'DT_Metrics_Export', 'activation' ] );
register_deactivation_hook( __FILE__, [ 'DT_Metrics_Export', 'deactivation' ] );

function dt_metrics_export_hook_admin_notice() {
    global $dt_metrics_export_required_dt_theme_version;
    $wp_theme = wp_get_theme();
    $current_version = $wp_theme->version;
    $message = __( "'Disciple Tools - Metrics Export' plugin requires 'Disciple Tools' theme to work. Please activate 'Disciple Tools' theme or make sure it is latest version.", "dt_metrics_export" );
    if ( $wp_theme->get_template() === "disciple-tools-theme" ){
        $message .= sprintf( esc_html__( 'Current Disciple Tools version: %1$s, required version: %2$s', 'dt_metrics_export' ), esc_html( $current_version ), esc_html( $dt_metrics_export_required_dt_theme_version ) );
    }
    // Check if it's been dismissed...
    if ( ! get_option( 'dismissed-dt-metrics-export', false ) ) { ?>
        <div class="notice notice-error notice-dt-metrics-export is-dismissible" data-notice="dt-metrics-export">
            <p><?php echo esc_html( $message );?></p>
        </div>
        <script>
            jQuery(function($) {
                $( document ).on( 'click', '.notice-dt-metrics-export .notice-dismiss', function () {
                    $.ajax( ajaxurl, {
                        type: 'POST',
                        data: {
                            action: 'dismissed_notice_handler',
                            type: 'dt-metrics-export',
                            security: '<?php echo esc_html( wp_create_nonce( 'wp_rest_dismiss' ) ) ?>'
                        }
                    })
                });
            });
        </script>
    <?php }
}


/**
 * AJAX handler to store the state of dismissible notices.
 */
if ( !function_exists( "dt_hook_ajax_notice_handler" )){
    function dt_hook_ajax_notice_handler(){
        check_ajax_referer( 'wp_rest_dismiss', 'security' );
        if ( isset( $_POST["type"] ) ){
            $type = sanitize_text_field( wp_unslash( $_POST["type"] ) );
            update_option( 'dismissed-' . $type, true );
        }
    }
}

if ( ! function_exists( 'dt_get_site_id' ) ) {
    function dt_get_site_id() {
        $dt_site_id = get_option( 'dt_site_id' );
        if ( empty( $dt_site_id ) ) {
            $site_id = hash( 'SHA256', site_url() . time() );
            add_option( 'dt_site_id', $site_id );
            $dt_site_id = $site_id;
        }
        return $dt_site_id;
    }
}
