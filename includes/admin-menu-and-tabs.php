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
            $tab = 'location_export';
        }

        $link = 'admin.php?page='.$this->token.'&tab=';

        ?>
        <div class="wrap">
            <h2><?php esc_attr_e( 'Metrics Export', 'dt_metrics_export' ) ?></h2>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr( $link ) . 'location_export' ?>" class="nav-tab <?php echo esc_html( ( $tab == 'location_export' || !isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>"><?php esc_attr_e( 'Location Export', 'dt_metrics_export' ) ?></a>
                <a href="<?php echo esc_attr( $link ) . 'cron' ?>" class="nav-tab <?php echo esc_html( ( $tab == 'cron' ) ? 'nav-tab-active' : '' ); ?>"><?php esc_attr_e( 'Cron', 'dt_metrics_export' ) ?></a>
                <a href="<?php echo esc_attr( $link ) . 'webhooks' ?>" class="nav-tab <?php echo esc_html( ( $tab == 'webhooks' ) ? 'nav-tab-active' : '' ); ?>"><?php esc_attr_e( 'Webhooks', 'dt_metrics_export' ) ?></a>
                <a href="<?php echo esc_attr( $link ) . 'cloud' ?>" class="nav-tab <?php echo esc_html( ( $tab == 'cloud' ) ? 'nav-tab-active' : '' ); ?>"><?php esc_attr_e( 'Cloud Storage', 'dt_metrics_export' ) ?></a>
                <a href="<?php echo esc_attr( $link ) . 'tutorial' ?>" class="nav-tab <?php echo esc_html( ( $tab == 'tutorial' ) ? 'nav-tab-active' : '' ); ?>"><?php esc_attr_e( 'Tutorial', 'dt_metrics_export' ) ?></a>
            </h2>

            <?php
            switch ($tab) {
                case "location_export":
                    $object = new DT_Metrics_Export_Tab_Location_Export();
                    $object->content();
                    break;
                case "webhooks":
                    $object = new DT_Metrics_Export_Tab_Webhooks();
                    $object->content();
                    break;
                case "cron":
                    $object = new DT_Metrics_Export_Tab_Cron();
                    $object->content();
                    break;
                case "cloud":
                    $object = new DT_Metrics_Export_Tab_Cloud();
                    $object->content();
                    break;
                case "tutorial":
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
        $countries = Disciple_Tools_Mapping_Queries::get_countries();

        $last_config = $this->process_post();

        $configuration = $this->get_configurations();

        $formats = get_dt_metrics_export_formats();

        ?>
        <style>
            .column-wrapper {
                width: 100%;
            }
            .quarter{
                width: 24%;
                padding-right: 5px;
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
            <form method="POST">
            <?php wp_nonce_field( 'metrics-location-export'.get_current_user_id(), 'metrics-location-export' ) ?>
            <div class="column-wrapper">
                <div class="quarter">
                    <!-- Box -->
                    <table class="widefat striped">
                        <thead>
                        <tr>
                            <th><strong>Step 1:</strong><br>Select Configuration </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                Configuration<br>
                                <select name="configuration" id="configuration" class="regular-text">
                                    <option value="new">New</option>
                                    <?php foreach ( $configuration as $config ) : ?>
                                        <option value="<?php echo esc_attr( $config['id'] ) ?>"><?php echo esc_html( $config['label'] ) ?></option>
                                    <?php endforeach; ?>
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
                            <th colspan="2"><strong>Step 2:</strong><br>Select Locations & Levels </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                All Locations<br>

                                <select name="all_locations" id="all-locations" class="regular-text">
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
                        </tbody>
                    </table>

                    <table class="widefat striped">
                        <!-- List of countries -->
                        <?php if ( ! empty( $countries ) ) : foreach ( $countries as $country ) : ?>
                            <tr class="country-list" style="display:none;">
                                <td>
                                    <?php echo esc_html( $country['name'] ) ?>
                                </td>
                                <td>
                                    <select class="selected-locations" name="selected_locations[<?php echo esc_attr( $country['grid_id'] ) ?>]" id="<?php echo esc_attr( $country['grid_id'] ) ?>">
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
                        <?php endforeach;
endif; ?>
                    </table>
                    <br>
                <!-- End Box -->
                </div>
                <div class="quarter">
                    <!-- Box -->
                    <table class="widefat striped">
                        <thead>
                        <tr>
                            <th colspan="2"><strong>Step 3:</strong><br>Select Data Types </th>
                        </tr>
                        </thead>
                        <tr>
                            <td colspan="2">
                                Format<br>
                                <select name="format" class="regular-text" id="format-input">
                                    <?php foreach ( $formats as $item ) : ?>
                                        <option value="<?php echo esc_attr( $item['key'] ) ?>"><?php echo esc_html( $item['label'] ) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <!-- Box -->
                    <table class="widefat striped" id="selectable_types"></table>
                    <br>
                    <!-- End Box -->

                </div>
                <div class="quarter">
                    <!-- Box -->
                    <table class="widefat striped">
                        <thead>
                        <tr>
                            <th><strong>Step 4:</strong><br>Save or Export </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                Configuration Name<br>
                                <input type="text" name="label" id="configuration-name" class="regular-text" placeholder="Title" /><br>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <button type="submit" name="action" value="save" class="button regular-text">Save New</button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <button type="submit" name="action" value="update" class="button regular-text">Update</button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <button type="submit" name="action" value="delete" class="button regular-text">Delete</button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <hr>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Destination<br>
                                <select name="destination" class="regular-text">
                                    <option value="download">Download</option>
                                    <option value="uploads">Uploads Folder (unrestricted public access)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <button type="submit" name="action" value="export"  class="button regular-text">Export</button>
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
        $this->content_scripts( $last_config );
    }

    public function content_scripts( $last_config_id = 0 ) {
        ?>
        <script>
            window.export_formats = [<?php echo json_encode( get_dt_metrics_export_formats() ) ?>][0]
            console.log(window.export_formats)
            window.export_configurations = [<?php echo json_encode( $this->get_configurations() ) ?>][0]
            console.log(window.export_configurations)

            jQuery(document).ready(function(){

                // show and hide country list
                let locations = jQuery('#all-locations')
                if ( 'country_by_country' === locations.val()  ) {
                    jQuery('.country-list').show()
                }
                locations.on('change', function() {
                    if ( 'country_by_country' === jQuery(this).val()  ) {
                        jQuery('.country-list').show()
                    } else {
                        jQuery('.country-list').hide()
                    }
                })

                // add selectable types
                let format_input = jQuery('#format_input')
                load_selectable_types( format_input.val() )
                format_input.on('change', function() {
                    load_selectable_types( format_input.val() )
                })

                /* change configuration selector */
                let config = jQuery('#configuration')
                config.on('change', function() {
                    load_configuration( config.val() )
                })
                load_configuration( config.val() )

                if ( <?php echo esc_attr( $last_config_id ) ?> > 0 ) {
                    load_configuration(<?php echo esc_attr( $last_config_id ) ?>)
                }

            })

            function reset_locations() {
                let selected_locations = jQuery('.selected-locations')
                selected_locations.each(function(){
                    jQuery(this).val('disabled')
                })
            }

            function load_configuration( id ) {
                console.log( id )

                let configuration_input = jQuery('#configuration')
                let all_locations = jQuery('#all-locations')
                let format = jQuery('#format-input')
                let configuration_title = jQuery('#configuration-name')
                let selected_locations = jQuery('.selected-locations')
                let country_list = jQuery('.country-list')

                reset_locations()

                if ( id === 'undefined' || id === 'new' ) {
                    configuration_input.val('new')
                    all_locations.val('admin2')
                    configuration_title.val('')
                    country_list.hide()
                    return
                }

                configuration_input.val(window.export_configurations[id].id)
                all_locations.val(window.export_configurations[id].all_locations)
                configuration_title.val(window.export_configurations[id].label)
                format.val(window.export_configurations[id].format)
                jQuery.each( window.export_configurations[id].selected_locations, function(i,v){
                    jQuery('#'+i).val(v)
                })
                if ( 'country_by_country' === window.export_configurations[id].all_locations  ) {
                    country_list.show()
                } else {
                    country_list.hide()
                }

            }

            function load_selectable_types( id ) {

                if ( id === 'undefined' || typeof window.export_formats[id] === 'undefined') {
                    return;
                }

                let types = window.export_formats[id].selectable_types
                let html = ''
                let container = jQuery('#selectable_types')

                let list = []
                jQuery.each( types, function(i,v){
                    list.push(v.type)
                })
                let unique_list = getUnique(list)
                jQuery.each( unique_list, function(i,v){
                    html += '<tr><td style="text-transform:capitalize;" ><strong>'+v+'</strong></td><td></td></tr>'
                    jQuery.each( types, function(ii,vv){
                        if ( v === vv.type ) {
                            html += '<tr><td>-- '+vv.label+'</td><td class="float-right"><input type="checkbox" name="type['+v.key+']" value="true" /></td></tr>'
                        }
                    })
                })

                container.html(html)

            }

            function getUnique(array){
                var uniqueArray = [];
                for(i=0; i < array.length; i++){
                    if(uniqueArray.indexOf(array[i]) === -1) {
                        uniqueArray.push(array[i]);
                    }
                }
                return uniqueArray;
            }



        </script>
        <?php
    }

    public function process_post() {
        dt_write_log( __METHOD__ );

        if ( isset( $_POST['metrics-location-export'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['metrics-location-export'] ) ), 'metrics-location-export'.get_current_user_id() ) ) {
            // create
            if ( isset( $_POST['action'] ) && 'save' === sanitize_text_field( wp_unslash( $_POST['action'] ) ) ) {
                $response = $this->filter_post( $_POST );
                return $this->create( $response );
            }

            // update
            if ( isset( $_POST['action'] ) && 'update' === sanitize_text_field( wp_unslash( $_POST['action'] ) ) ) {
                $response = $this->filter_post( $_POST );
                return $this->update( $response );
            }

            // delete
            if ( isset( $_POST['action'] ) && 'delete' === sanitize_text_field( wp_unslash( $_POST['action'] ) ) ) {
                $response = $this->filter_post( $_POST );
                return $this->delete( $response );
            }

            // export
            if ( isset( $_POST['action'] ) && 'export' === sanitize_text_field( wp_unslash( $_POST['action'] ) ) ) {
                $response = $this->filter_post( $_POST );
                return $this->export( $response );
            }
        }
        return 0;
    }

    public function get_configurations() : array {
        $configurations = [];
        $config_posts = get_posts( [ 'post_type' => 'dt_metrics_export' ] );
        foreach ( $config_posts as $key => $post ) {
            $configurations[$post->ID] = dt_get_simple_postmeta( $post->ID );
            $configurations[$post->ID]['id'] = $post->ID;
        }
        return $configurations;
    }

    public function filter_post( $response ) : array {

        // @todo add sanitization of post elements.

        unset( $response['metrics-location-export'] );
        unset( $response['_wp_http_referer'] );

        return $response;
    }

    public function create( $response ) {
        dt_write_log( 'action: save' );

        unset( $response['action'] );
        unset( $response['configuration'] );

        $args = [
            'post_type' => 'dt_metrics_export',
            'post_title' => $response['label'], // label
            'post_content' => $response['label'], // label
            'post_status' => 'publish',
            'ping_status' => 'closed',
            'comment_status' => 'closed',
            'meta_input' => $response
        ];

        $id = wp_insert_post( $args, true );
        if ( is_wp_error( $id ) ) {
            dt_write_log( 'error' );
            dt_write_log( $id );
        }
        return $id;
    }

    public function update( $response ) {
        dt_write_log( 'action: update' );

        if ( 'new' === $response['configuration'] ?? null ) {
            return $this->create( $response );
        }

        unset( $response['action'] );

        $args = [
            'ID' => $response['configuration'],
            'post_type' => 'dt_metrics_export',
            'post_title' => $response['label'], // label
            'post_content' => $response['label'], // label
            'post_status' => 'publish',
            'ping_status' => 'closed',
            'comment_status' => 'closed',
            'meta_input' => $response
        ];

        $id = wp_update_post( $args, true );
        if ( is_wp_error( $id ) ) {
            dt_write_log( 'error' );
            dt_write_log( $id );
        }
        return $id;

    }

    public function delete( $response ) {
        dt_write_log( 'action: delete' );

        if ( isset( $response['configuration'] ) && ! empty( $response['configuration'] ) ) {
            return wp_delete_post( $response['configuration'] );
        }
        return 0;
    }

    public function export( $response ) {
        dt_write_log( 'action: export' );
        $formats = apply_filters( 'dt_metrics_export_register_format_class', [] );

        if ( isset( $response['format'] ) && ! empty( $response['format'] ) && isset( $formats[$response['format']] ) && class_exists( $formats[$response['format']] ) ) {
            $formats[$response['format']]::instance()->export( $response );
        }
        return 0;
    }
}
if ( ! function_exists( 'dt_get_simple_postmeta' ) ) {
    function dt_get_simple_postmeta( $post_id ) {
        return array_map( function ( $a ) { return maybe_unserialize( $a[0] );
        }, get_post_meta( $post_id ) );
    }
}

function get_dt_metrics_export_types() : array {
    $types = [];

    // Contacts
    $types['contacts_all'] = [
        'type' => 'contacts',
        'key' => 'contacts_all',
        'label' => 'All'
    ];
    $types['contacts_active'] = [
        'type' => 'contacts',
        'key' => 'contacts_active',
        'label' => 'Active'
    ];
    $types['contacts_paused'] = [
        'type' => 'contacts',
        'key' => 'contacts_paused',
        'label' => 'Paused'
    ];
    $types['contacts_seekers'] = [
        'type' => 'contacts',
        'key' => 'contacts_seekers',
        'label' => 'Seekers'
    ];
    $types['contacts_believers'] = [
        'type' => 'contacts',
        'key' => 'contacts_believers',
        'label' => 'Believers'
    ];

    // Groups
    $types['groups_all'] = [
        'type' => 'groups',
        'key' => 'groups_all',
        'label' => 'All'
    ];
    $types['groups_active'] = [
        'type' => 'groups',
        'key' => 'groups_active',
        'label' => 'Active'
    ];
    $types['groups_inactive'] = [
        'type' => 'groups',
        'key' => 'groups_inactive',
        'label' => 'Inactive'
    ];
    $types['groups_pre_groups'] = [
        'type' => 'groups',
        'key' => 'groups_pre_groups',
        'label' => 'Pre-Groups'
    ];
    $types['groups_groups'] = [
        'type' => 'groups',
        'key' => 'groups_groups',
        'label' => 'Groups'
    ];
    $types['groups_churches'] = [
        'type' => 'groups',
        'key' => 'groups_churches',
        'label' => 'Churches'
    ];
    $types['groups_unformed'] = [
        'type' => 'groups',
        'key' => 'groups_unformed',
        'label' => 'Unformed Believers by Area'
    ];

    // Users
    $types['users_all'] = [
        'type' => 'users',
        'key' => 'users_all',
        'label' => 'Users'
    ];
    $types['users_active'] = [
        'type' => 'users',
        'key' => 'users_active',
        'label' => 'Active'
    ];
    $types['users_inactive'] = [
        'type' => 'users',
        'key' => 'users_inactive',
        'label' => 'Inactive'
    ];
    $types['users_by_roles'] = [
        'type' => 'users',
        'key' => 'users_by_roles',
        'label' => 'Users by Roles'
    ];

    return apply_filters( 'dt_metrics_export_types', $types );
}

function get_dt_metrics_export_formats() : array {
    return apply_filters( 'dt_metrics_export_format', [] );
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
class DT_Metrics_Export_Tab_Cron {
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
class DT_Metrics_Export_Tab_Cloud{
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

