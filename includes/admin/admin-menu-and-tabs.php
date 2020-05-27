<?php
/**
 * DT_Metrics_Export_Menu class for the admin page
 *
 * @class       DT_Metrics_Export_Menu
 * @version     0.1.0
 * @since       0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}

/**
 * Initialize menu class
 */
DT_Metrics_Export_Menu::instance();

/**
 * Class DT_Metrics_Export_Menu
 */
class DT_Metrics_Export_Menu {

    public $token = 'dt_metrics_export';

    private static $_instance = null;

    /**
     * DT_Metrics_Export_Menu Instance
     *
     * Ensures only one instance of DT_Metrics_Export_Menu is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return DT_Metrics_Export_Menu instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()


    /**
     * Constructor function.
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {

        add_action( "admin_menu", array( $this, "register_menu" ) );

    } // End __construct()


    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        add_menu_page( __( 'Extensions (DT)', 'disciple_tools' ), __( 'Extensions (DT)', 'disciple_tools' ), 'manage_dt', 'dt_extensions', [ $this, 'extensions_menu' ], 'dashicons-admin-generic', 59 );
        add_submenu_page( 'dt_extensions', __( 'Metrics Export', 'dt_metrics_export' ), __( 'Metrics Export', 'dt_metrics_export' ), 'manage_dt', $this->token, [ $this, 'content' ] );
    }

    /**
     * Menu stub. Replaced when Disciple Tools Theme fully loads.
     */
    public function extensions_menu() {}

    /**
     * Builds page contents
     * @since 0.1
     */
    public function content() {

        if ( !current_user_can( 'manage_dt' ) ) { // manage dt is a permission that is specific to Disciple Tools and allows admins, strategists and dispatchers into the wp-admin
            wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        if ( isset( $_GET["tab"] ) ) {
            $tab = sanitize_key( wp_unslash( $_GET["tab"] ) );
        } else {
            $tab = 'general';
        }

        $link = 'admin.php?page='.$this->token.'&tab=';

        ?>
        <div class="wrap">
            <h2><?php esc_attr_e( 'Metrics Export', 'dt_metrics_export' ) ?></h2>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr( $link ) . 'general' ?>"
                   class="nav-tab <?php echo esc_html( ( $tab == 'general' || !isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>"><?php esc_attr_e( 'Location Export', 'dt_metrics_export' ) ?></a>
                <a href="<?php echo esc_attr( $link ) . 'second' ?>" class="nav-tab <?php echo esc_html( ( $tab == 'second' || !isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>"><?php esc_attr_e( 'Tutorial', 'dt_metrics_export' ) ?></a>
            </h2>

            <?php
            switch ($tab) {
                case "general":
                    $object = new DT_Metrics_Export_Tab_Location_Export();
                    $object->content();
                    break;
                case "second":
                    $object = new DT_Metrics_Export_Tab_Tutorial();
                    $object->content();
                    break;
                default:
                    break;
            }
            ?>

        </div><!-- End wrap -->

        <?php
    }

}

/**
 * Class DT_Metrics_Export_Tab_Location_Export
 */
class DT_Metrics_Export_Tab_Location_Export {
    public function content() {
        $this->process_post();
        ?>
        <style>
            .column-wrapper {
                width: 100%;
            }
            .quarter {
                width: 25%;
                float: left;
            }
            @media screen and (max-width : 1000px) {
                .quarter {
                    width: 100%;
                    float: left;
                }
            }
            .float-right {
                float:right;
            }
        </style>
        <div class="wrap">
            <form method="post">
            <?php wp_nonce_field( 'metrics-location-export'.get_current_user_id(), 'metrics-location-export' ) ?>
            <div class="column-wrapper">
                <div class="quarter">
                    <!-- Box -->
                    <table class="widefat striped">
                        <thead>
                        <tr>
                            <th colspan="2">Step 1:<br>Location / Levels <span class="float-right">&#9989;</span></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                United States
                            </td>
                            <td>
                                <select>
                                    <option>---disabled---</option>
                                    <option value="">Admin0 (Country)</option>
                                    <option value="">Admin1 (State)</option>
                                    <option value="">Admin2 (County)</option>
                                    <option value="">Admin3</option>
                                    <option value="">Admin4</option>
                                    <option value="">Admin5</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Germany
                            </td>
                            <td>
                                <select>
                                    <option>---disabled---</option>
                                    <option value="">Admin0 (Country)</option>
                                    <option value="">Admin1 (State)</option>
                                    <option value="">Admin2 (County)</option>
                                    <option value="">Admin3</option>
                                    <option value="">Admin4</option>
                                    <option value="">Admin5</option>
                                </select>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <br>
                    <!-- End Box -->

                </div>
                <div class="quarter">
                    <!-- Box -->
                    <table class="widefat striped">
                        <thead>
                        <tr>
                            <th colspan="2">Step 2:<br>Data Types <span class="float-right">&#9989;</span></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                Contacts
                            </td>
                            <td class="float-right">
                                <input type="checkbox" name="" value="true" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                -- Seekers
                            </td>
                            <td class="float-right">
                                <input type="checkbox" name="" value="true" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                -- Baptized
                            </td>
                            <td class="float-right">
                                <input type="checkbox" name="" value="true" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Groups
                            </td>
                            <td class="float-right">
                                <input type="checkbox" name="" value="true" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                -- Pre-Group
                            </td>
                            <td class="float-right">
                                <input type="checkbox" name="" value="true" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                -- Groups
                            </td>
                            <td class="float-right">
                                <input type="checkbox" name="" value="true" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                -- Churches
                            </td>
                            <td class="float-right">
                                <input type="checkbox" name="" value="true" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                -- Non-grouped by Location
                            </td>
                            <td class="float-right">
                                <input type="checkbox" name="" value="true" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Users
                            </td>
                            <td class="float-right">
                                <input type="checkbox" name="" value="true" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Trainings
                            </td>
                            <td class="float-right">
                                <input type="checkbox" name="" value="true" />
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <br>
                    <!-- End Box -->

                </div>
                <div class="quarter">
                    <!-- Box -->
                    <table class="widefat striped">
                        <thead>
                        <tr>
                            <th>Step 3:<br>Format of Export <span class="float-right">&#10060;</span></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                Content
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <br>
                    <!-- End Box -->

                </div>
                <div class="quarter">
                    <!-- Box -->
                    <table class="widefat striped">
                        <thead>
                        <tr>
                            <th>Step 4:<br>Destination of Export <span class="float-right">&#9989;</span></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>

                            </td>
                        </tr>
                        <tr>
                            <td>
                                <button type="submit" class="button">Export</button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <br>
                    <!-- End Box -->

                </div>
            </div>
            </form>
        </div><!-- End wrap -->
        <?php
    }

    public function process_post() {
        dt_write_log(__METHOD__);
    }
}

/**
 * Class DT_Metrics_Export_Tab_Tutorial
 */
class DT_Metrics_Export_Tab_Tutorial {
    public function content() {
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Right Column -->

                        <?php $this->right_column() ?>

                        <!-- End Right Column -->
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function main_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Header</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        Content
                    </td>
                </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    public function right_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Information</th>
                </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    Content
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }
}

