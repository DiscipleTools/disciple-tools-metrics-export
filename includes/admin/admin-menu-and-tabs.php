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
                <a href="<?php echo esc_attr( $link ) . 'webhooks' ?>" class="nav-tab <?php echo esc_html( ( $tab == 'webhooks' || !isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>"><?php esc_attr_e( 'Webhooks', 'dt_metrics_export' ) ?></a>
                <a href="<?php echo esc_attr( $link ) . 'second' ?>" class="nav-tab <?php echo esc_html( ( $tab == 'second' || !isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>"><?php esc_attr_e( 'Tutorial', 'dt_metrics_export' ) ?></a>
            </h2>

            <?php
            switch ($tab) {
                case "general":
                    $object = new DT_Metrics_Export_Tab_Location_Export();
                    $object->content();
                    break;
                case "webhooks":
                    $object = new DT_Metrics_Export_Tab_Webhooks();
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
        $countries = Disciple_Tools_Mapping_Queries::get_countries( );

        $configuration_id = $this->process_post();

        $configuration = $this->get_configurations( $configuration_id );

        $types = $this->get_types();

        $formats = $this->get_formats();

        ?>
        <style>
            .column-wrapper {
                width: 100%;
            }
            .fifth{
                width: 24%;
                padding-right: 5px;
                float: left;
            }
            @media screen and (max-width : 1000px) {
                .fifth {
                    width: 100%;
                    float: left;
                }
            }
            .float-right {
                float:right;
            }
        </style>
        <div class="wrap">
            <form method="POST">
            <?php wp_nonce_field( 'metrics-location-export'.get_current_user_id(), 'metrics-location-export' ) ?>
            <div class="column-wrapper">
                <div class="fifth">
                    <!-- Box -->
                    <table class="widefat striped">
                        <thead>
                        <tr>
                            <th><strong>Step 1:</strong><br>Select Configuration <span class="float-right">&#10060;</span></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                Configuration<br>
                                <select name="configuration" class="regular-text">
                                    <option value="new">New</option>
                                    <?php foreach( $configuration as $config ) : ?>
                                        <option value="<?php echo esc_attr( $config['id']) ?>"><?php echo esc_html( $config['label']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Certain saved configurations can be enabled to run in response to a scheduled (cron) job or as a webhook.
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <br>
                    <!-- End Box -->

                </div>
                <div class="fifth">
                    <!-- Box -->
                    <table class="widefat striped">
                        <thead>
                        <tr>
                            <th colspan="2"><strong>Step 2:</strong><br>Select Locations & Levels <span class="float-right">&#9989;</span></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                All Locations
                            </td>
                            <td>
                                <select name="all_locations">
                                    <option value="admin2">Admin2 (County)</option>
                                    <option disabled>---disabled---</option>
                                    <option value="admin0">Admin0 (Country)</option>
                                    <option value="admin1">Admin1 (State)</option>
                                    <option value="admin2">Admin2 (County)</option>
                                    <option value="admin3">Admin3</option>
                                    <option value="admin4">Admin4</option>
                                    <option value="admin5">Admin5</option>
                                    <option value="country_by_country">Country by Country</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <hr>
                            </td>
                        </tr>
                        <!-- List of countries -->
                        <?php if ( ! empty( $countries ) ) : foreach( $countries as $country ) : ?>
                            <tr>
                                <td>
                                    <?php echo $country['name'] ?>
                                </td>
                                <td>
                                    <select name="selected_locations[][<?php echo $country['grid_id'] ?>]">
                                        <option value="disabled">---disabled---</option>
                                        <option value="admin0">Admin0 (Country)</option>
                                        <option value="admin1">Admin1 (State)</option>
                                        <option value="admin2">Admin2 (County)</option>
                                        <option value="admin3">Admin3</option>
                                        <option value="admin4">Admin4</option>
                                        <option value="admin5">Admin5</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                    <br>
                    <!-- End Box -->

                </div>
                <div class="fifth">
                    <!-- Box -->
                    <table class="widefat striped">
                        <thead>
                        <tr>
                            <th colspan="2"><strong>Step 3:</strong><br>Select Data Types <span class="float-right">&#9989;</span></th>
                        </tr>
                        </thead>
                        <?php
                        if ( ! empty( $types ) ) :
                            $list = [];
                            foreach( $types as $value ) :
                                if ( ! isset( $list[$value['type']] ) ) {
                                    $list[$value['type']] = [];
                                }
                                $list[$value['type']][] = $value;
                            endforeach;
                            foreach ( $list as $key => $items ) :
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo ucfirst( $key ) ?></strong>
                                    </td>
                                    <td class="float-right"></td>
                                </tr>
                                <?php
                                foreach( $items as $item ) :
                                    ?>
                                    <tr>
                                        <td>
                                            -- <?php echo $item['label'] ?>
                                        </td>
                                        <td class="float-right">
                                            <input type="checkbox" name="type[<?php echo $item['key'] ?>]" value="true" />
                                        </td>
                                    </tr>
                                    <?php
                                endforeach;
                            endforeach;
                        endif; ?>
                        </tbody>
                    </table>
                    <br>
                    <!-- End Box -->

                </div>
                <div class="fifth">
                    <!-- Box -->
                    <table class="widefat striped">
                        <thead>
                        <tr>
                            <th><strong>Step 4:</strong><br>Export <span class="float-right">&#10060;</span></th>
                        </tr>
                        </thead>
                        <tbody>

                        <tr>
                            <td>
                                Format<br>
                                <select name="format" class="regular-text">
                                    <?php foreach( $formats as $key => $item ) : ?>
                                        <option value="<?php echo esc_attr( $key ) ?>"><?php echo esc_html( $item ) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Destination<br>
                                <select name="destination" class="regular-text">
                                    <option value="download">Download</option>
                                    <option value="webhook">Webhook</option>
                                    <option value="uploads">Uploads Folder (unrestricted public access)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Configuration Name<br>
                                <input type="text" name="label" class="regular-text" placeholder="Name for Configuration" /><br>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <hr>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <button type="submit" name="action" value="save" class="button regular-text">Save Configuration and Export</button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <button type="submit" name="action" value="update" class="button regular-text">Update Configuration and Export</button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <button type="submit" name="action" value="export"  class="button regular-text">Export without Saving</button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <button type="submit" name="action" value="delete" class="button regular-text">Delete Configuration</button>
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

    public function process_post() : int {
        dt_write_log( __METHOD__ );

        if ( isset( $_POST['metrics-location-export'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['metrics-location-export'] ) ), 'metrics-location-export'.get_current_user_id() ) ) {
            // create
            if ( isset( $_POST['action']  ) && 'save' === sanitize_text_field( wp_unslash( $_POST['action']  ) ) ) {
                $response = $this->filter_post( $_POST );
                return $this->save_new( $response );
            }

            // update
            if ( isset( $_POST['action']  ) && 'update' === sanitize_text_field( wp_unslash( $_POST['action']  ) ) ) {
                $response = $this->filter_post( $_POST );
                return $this->update( $response );
            }

            // delete
            if ( isset( $_POST['action']  ) && 'delete' === sanitize_text_field( wp_unslash( $_POST['action']  ) ) ) {
                $response = $this->filter_post( $_POST );
                return $this->delete( $response );
            }

            // export
            if ( isset( $_POST['action']  ) && 'export' === sanitize_text_field( wp_unslash( $_POST['action']  ) ) ) {
                $response = $this->filter_post( $_POST );
                return $this->export( $response );
            }
        }
        return 0;
    }

    public function get_configurations( $default_id = 0 ) : array {
        $defaults = [
            'id' => 0, // post id
            'label' => '',
            'all_locations' => '',
            'selected_locations' => [],
            'format' => '',
            'destination' => '',
            'last_export' => time(),
        ];
        $configurations = [];

        if ( 0 === $default_id ) {
            return $configurations;
        }
        return [];
    }

    public function get_types() {
        $types = [
            [
                'type' => 'contacts',
                'key' => 'contacts_all',
                'label' => 'All'
            ],
            [
                'type' => 'contacts',
                'key' => 'contacts_active',
                'label' => 'Active'
            ],
            [
                'type' => 'contacts',
                'key' => 'contacts_paused',
                'label' => 'Paused'
            ],
            [
                'type' => 'contacts',
                'key' => 'contacts_seekers',
                'label' => 'Seekers'
            ],
            [
                'type' => 'contacts',
                'key' => 'contacts_believers',
                'label' => 'Believers'
            ],
            [
                'type' => 'groups',
                'key' => 'groups_all',
                'label' => 'All'
            ],
            [
                'type' => 'groups',
                'key' => 'groups_active',
                'label' => 'Active'
            ],
            [
                'type' => 'groups',
                'key' => 'groups_inactive',
                'label' => 'Inactive'
            ],
            [
                'type' => 'groups',
                'key' => 'groups_groups',
                'label' => 'Groups & Pre-Groups'
            ],
            [
                'type' => 'groups',
                'key' => 'groups_churches',
                'label' => 'Churches'
            ],
            [
                'type' => 'groups',
                'key' => 'groups_unformed',
                'label' => 'Unformed Believers by Area'
            ],
            [
                'type' => 'users',
                'key' => 'users_all',
                'label' => 'Users'
            ],
            [
                'type' => 'users',
                'key' => 'users_active',
                'label' => 'Active'
            ],
            [
                'type' => 'users',
                'key' => 'users_inactive',
                'label' => 'Inactive'
            ],
            [
                'type' => 'users',
                'key' => 'users_by_roles',
                'label' => 'Users by Roles'
            ]
        ];

        return apply_filters( 'dt_metrics_export_types', $types );
    }

    public function get_formats() {
        $formats = [
            'csv' => 'CSV',
            'json' => 'JSON',
            'geojson' => 'GEOJSON',
            'kml' => 'KML',
        ];

        return apply_filters( 'dt_metrics_export_types', $formats );
    }

    public function filter_post( $response ) : array {

        return $response;
    }

    public function save_new( $response ) : int {
        dt_write_log('action: save');
        global $wpdb;
        $new_config_id = 0;

        $args = [
            'post_type' => 'dt_metrics_export',
            'post_title' => $response['label'], // label
            'post_status' => 'publish',
            'ping_status' => 'closed',
            'comment_status' => 'closed',
            'meta_input' => [
                'label' => $response['label'],
                'id' => $response['label'],
                'format' => $response['label'],
                'destination' => $response['label'],
                'all_locations' => $response['label'],
                'selected_locations' => $response['label'],
                'types' => $response['label'],
                'last_export' => $response['label'],
            ]

        ];

        wp_insert_post( $args );



        return $wpdb->insert_id; // @todo new created id
    }

    public function update( $response ) : int {
        dt_write_log('action: update');
        return 0; // return updated id
    }

    public function delete( $response ) : int {
        dt_write_log('action: delete');
        return 0;
    }

    public function export( $response ) : int {
        dt_write_log('action: export');
        return 0;
    }
}

class DT_Metrics_Export_Tab_Webhooks {
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

