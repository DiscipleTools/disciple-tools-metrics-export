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
        add_menu_page( 'Extensions (DT)', 'Extensions (DT)', 'manage_dt', 'dt_extensions', [ $this, 'extensions_menu' ], 'dashicons-admin-generic', 59 );
        add_submenu_page( 'dt_extensions', 'Metrics Export', 'Metrics Export', 'manage_dt', $this->token, [ $this, 'content' ] );
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
            wp_die( esc_html( 'You do not have sufficient permissions to access this page.' ) );
        }

        if ( isset( $_GET["tab"] ) ) {
            $tab = sanitize_key( wp_unslash( $_GET["tab"] ) );
        } else {
            $tab = 'active';
        }

        $link = 'admin.php?page='.$this->token.'&tab=';

        ?>
        <div class="wrap">
            <h2><?php esc_attr_e( 'Metrics Export', 'dt_metrics_export' ) ?></h2>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr( $link ) . 'active' ?>" class="nav-tab <?php echo esc_html( ( $tab == 'active' || !isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>"><?php echo esc_html( 'Current Links') ?></a>
                <a href="<?php echo esc_attr( $link ) . 'location_export' ?>" class="nav-tab <?php echo esc_html( ( $tab == 'location_export' ) ? 'nav-tab-active' : '' ); ?>"><?php echo esc_html( 'Create Links' ) ?></a>
                <!-- <a href="<?php echo esc_attr( $link ) . 'cron' ?>" class="nav-tab <?php echo esc_html( ( $tab == 'cron' ) ? 'nav-tab-active' : '' ); ?>"><?php echo esc_html( 'Cron' ) ?></a>
                <a href="<?php echo esc_attr( $link ) . 'webhooks' ?>" class="nav-tab <?php echo esc_html( ( $tab == 'webhooks' ) ? 'nav-tab-active' : '' ); ?>"><?php echo esc_html( 'Webhooks' ) ?></a>
                <a href="<?php echo esc_attr( $link ) . 'cloud' ?>" class="nav-tab <?php echo esc_html( ( $tab == 'cloud' ) ? 'nav-tab-active' : '' ); ?>"><?php echo esc_html( 'Cloud Storage' ) ?></a>
                <a href="<?php echo esc_attr( $link ) . 'tutorial' ?>" class="nav-tab <?php echo esc_html( ( $tab == 'tutorial' ) ? 'nav-tab-active' : '' ); ?>"><?php echo esc_html( 'Tutorial' ) ?></a> -->
            </h2>

            <?php
            switch ($tab) {
                case "active":
                    $object = new DT_Metrics_Export_Tab_Active();
                    $object->content();
                    break;
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

class DT_Metrics_Export_Tab_Active {
    public function content() {
        $this->process_post();
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-1">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->one_time_exports() ?>
                        <?php $this->expiring_seven_day_exports() ?>
                        <?php $this->permanent_linked_exports() ?>

                        <script>
                            const copyToClipboard = str => {
                                const el = document.createElement('textarea');
                                el.value = str;
                                el.setAttribute('readonly', '');
                                el.style.position = 'absolute';
                                el.style.left = '-9999px';
                                document.body.appendChild(el);
                                const selected =
                                    document.getSelection().rangeCount > 0
                                        ? document.getSelection().getRangeAt(0)
                                        : false;
                                el.select();
                                document.execCommand('copy');
                                document.body.removeChild(el);
                                if (selected) {
                                    document.getSelection().removeAllRanges();
                                    document.getSelection().addRange(selected);
                                }
                                alert('Copied')
                            };
                        </script>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->

        <?php
    }

    public function process_post() {
        if ( empty( $_POST ) ) {
            return;
        }
        global $wpdb;
        dt_write_log($_POST);

        if ( isset( $_POST['one_time_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['one_time_nonce'] ) ), 'one_time'.get_current_user_id() )
            && ! empty( $_POST['delete_one_time'] )
        ) {
            $token = sanitize_text_field( wp_unslash( $_POST['delete_one_time'] ) );
            $wpdb->query(  $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key = %s", 'download_'.$token) );

        }
        if ( isset( $_POST['expiring_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['expiring_nonce'] ) ), 'expiring'.get_current_user_id() )
            && ! empty( $_POST['delete_expiring'] )
        ){
            $token = sanitize_text_field( wp_unslash( $_POST['delete_expiring'] ) );
            delete_transient( 'metrics_exports_'. $token );
        }
        if ( isset( $_POST['permanent_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['permanent_nonce'] ) ), 'permanent'.get_current_user_id() )
                && ! empty( $_POST['delete_permanent'] )
            ){
            $token = sanitize_text_field( wp_unslash( $_POST['delete_permanent'] ) );
            $wpdb->query(  $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key = %s", 'permanent_'.$token) );
        }

    }

    public function one_time_exports() {
        global $wpdb;

        $links = $wpdb->get_results(  "
            SELECT *
            FROM $wpdb->posts as p
            JOIN $wpdb->postmeta as pm ON p.ID=pm.post_id AND meta_key LIKE 'download_%'
            WHERE post_type = 'dt_metrics_export'
        ",  ARRAY_A );

        $configurations = get_dt_metrics_export_formats();
        ?>
        <!-- Box -->
        <form method="post">
            <?php wp_nonce_field('one_time'.get_current_user_id(), 'one_time_nonce') ?>
            <table class="widefat unstriped">
                <thead>
                <tr>
                    <th>One-Time Links</th>
                </tr>
                </thead>
                <tbody>
                <?php
                if ( ! empty( $links ) ) :
                    foreach( $links as $row ):
                        $value = maybe_unserialize( $row['meta_value']);
                        if ( ! isset( $configurations[$value['export']['format']] ) ) {
                            continue;
                        }
                        ?>
                        <tr>
                            <td>
                                <table class="widefat striped">
                                    <tbody>
                                    <tr>
                                        <td>
                                            <strong><?php echo $configurations[$value['export']['format']]['label'] ?></strong>
                                        </td>
                                        <td>
                                            <button type="submit" class="button" name="delete_one_time" value="<?php echo $value['key'] ?>" style="float:right;">Delete</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <?php echo $value['export']['label'] ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2"> Contents:
                                            <?php
                                            foreach( $value['export']['type'] as $index => $type_key ) {
                                                echo $configurations[$value['export']['format']]['types'][$index][$type_key]['label'];
                                            }
                                            ?>
                                            on <?php echo $value['timestamp'] ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <a href="javascript:void(0)" class="copy-link" onclick="copyToClipboard('<?php echo $value['link'] ?>')">Copy Link</a> |
                                            <a href="javascript:void(0)" onclick="jQuery('#link-<?php echo $value['key'] ?>').toggle()">Show link</a> |
                                            <a href="<?php echo $value['link'] ?>" target="_blank" class="download-reload">Download and delete this one-time link</a>
                                        </td>
                                    </tr>
                                    <tr id="link-<?php echo $value['key'] ?>" style="display:none;">
                                        <td colspan="2">
                                            <input type="text" value="<?php echo $value['link'] ?>" style="width:100%;" id="input-<?php echo $value['key'] ?>" />
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                                <script>
                                    jQuery(document).ready(function(){
                                        jQuery('.download-reload').on('click', function(){
                                            window.location.reload();
                                        })
                                    })
                                </script>
                            </td>
                        </tr>
                    <?php
                    endforeach;
                else :
                    ?>
                    <tr>
                        <td>
                            No links found
                        </td>
                    </tr>
                <?php
                endif;
                ?>
                </tbody>
            </table>
        </form>
        <br>
        <!-- End Box -->
        <?php
    }

    public function expiring_seven_day_exports() {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM $wpdb->options WHERE option_name LIKE '_transient_metrics_export%'", ARRAY_A );
        $configurations = get_dt_metrics_export_formats();
        ?>
        <form method="post">
            <?php wp_nonce_field('expiring'.get_current_user_id(), 'expiring_nonce') ?>
            <!-- Box -->
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>Expiring Links</th>
                </tr>
                </thead>
                <tbody>
                <?php
                if ( ! empty( $results ) ) :
                    foreach ( $results as $result ) :
                        $value = maybe_unserialize( $result['option_value'] );
                        ?>
                        <tr>
                            <td>
                                <table class="widefat striped">
                                    <tbody>
                                    <tr>
                                        <td>
                                            <strong><?php echo $configurations[$value['export']['format']]['label'] ?></strong>
                                            <?php echo $configurations[$value['export']['format']]['destinations'][$value['export']['destination']]['label']; ?>
                                        </td>
                                        <td>
                                            <button type="submit" class="button" name="delete_expiring" value="<?php echo $value['key'] ?>" style="float:right;">Delete</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <?php echo $value['export']['label'] ?>
                                            <?php
                                            $expires = (int) get_option( '_transient_timeout_metrics_exports_'.$value['key'] );
                                            if ( ! empty( $expires ) ){
                                                echo '| Expires: ' . date( 'Y-m-d H:i', $expires);
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2"> Contents:
                                            <?php
                                            foreach( $value['export']['type'] as $index => $type_key ) {
                                                echo $configurations[$value['export']['format']]['types'][$index][$type_key]['label'];
                                            }

                                            ?>
                                            on <?php echo $value['timestamp'] ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <a href="javascript:void(0)" class="copy-link" onclick="copyToClipboard('<?php echo $value['link'] ?>')">Copy Link</a> |
                                            <a href="javascript:void(0)" onclick="jQuery('#link-<?php echo $value['key'] ?>').toggle()">Show link</a> |
                                            <a href="<?php echo $value['link'] ?>">Download</a>
                                        </td>
                                    </tr>
                                    <tr id="link-<?php echo $value['key'] ?>" style="display:none;">
                                        <td colspan="2">
                                            <input type="text" value="<?php echo $value['link'] ?>" style="width:100%;" id="input-<?php echo $value['key'] ?>" />
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    <?php
                    endforeach;
                else :
                    ?>
                    <tr>
                        <td>
                            No expiring links
                        </td>
                    </tr>
                    <?php
                endif;
                ?>
                </tbody>
            </table>
        </form>
        <br>
        <!-- End Box -->
        <?php
    }

    public function permanent_linked_exports() {
        global $wpdb;

        $links = $wpdb->get_results(  "
            SELECT *
            FROM $wpdb->posts as p
            JOIN $wpdb->postmeta as pm ON p.ID=pm.post_id AND meta_key LIKE 'permanent_%'
            WHERE post_type = 'dt_metrics_export'
        ",  ARRAY_A );

        $configurations = get_dt_metrics_export_formats();
        ?>
        <!-- Box -->
        <form method="post">
            <?php wp_nonce_field('permanent'.get_current_user_id(), 'permanent_nonce') ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>Permanent Links</th>
                </tr>
                </thead>
                <tbody>
                <?php
                if ( ! empty( $links ) ) :
                    foreach( $links as $row ):
                        $value = maybe_unserialize( $row['meta_value']);
                        if ( ! isset( $configurations[$value['export']['format']] ) ) {
                            continue;
                        }
                        ?>
                        <tr>
                            <td>
                                <table class="widefat striped">
                                    <tbody>
                                    <tr>
                                        <td>
                                            <strong><?php echo $configurations[$value['export']['format']]['label'] ?></strong>
                                        </td>
                                        <td>
                                            <button type="submit" class="button" name="delete_permanent" value="<?php echo $value['key'] ?>" style="float:right;">Delete</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <?php echo $value['export']['label'] ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2"> Contents:
                                            <?php
                                            foreach( $value['export']['type'] as $index => $type_key ) {
                                                echo $configurations[$value['export']['format']]['types'][$index][$type_key]['label'];
                                            }
                                            ?>
                                            on <?php echo $value['timestamp'] ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <a href="javascript:void(0)" class="copy-link" onclick="copyToClipboard('<?php echo $value['link'] ?>')">Copy Link</a> |
                                            <a href="javascript:void(0)" onclick="jQuery('#link-<?php echo $value['key'] ?>').toggle()">Show link</a> |
                                            <a href="<?php echo $value['link'] ?>">Download</a>
                                        </td>
                                    </tr>
                                    <tr id="link-<?php echo $value['key'] ?>" style="display:none;">
                                        <td colspan="2">
                                            <input type="text" value="<?php echo $value['link'] ?>" style="width:100%;" id="input-<?php echo $value['key'] ?>" />
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    <?php
                    endforeach;
                  else :
                     ?>
                    <tr>
                        <td>
                            No expiring links
                        </td>
                    </tr>
                    <?php
                    endif;
                    ?>
                </tbody>
            </table>
        </form>
        <br>
        <!-- End Box -->
        <?php
    }
}

/**
 * Class DT_Metrics_Export_Tab_Location_Export
 */
class DT_Metrics_Export_Tab_Location_Export {
    public function content() {
        $last_config = $this->process_post();
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
            .default-hide {
                display:none;
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
                            <th><strong>Step 1:</strong><br>Create or Manage Configuration </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                Select Configuration<br>
                                <select name="configuration" id="input-configuration" class="regular-text" required>
                                    <!-- load configurations -->
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Configuration Title<br>
                                <input type="text" name="label" id="input-configuration-name" class="regular-text" placeholder="Title" /><br>
                            </td>
                        </tr>

                        <tr id="button-duplicate" class="old default-hide">
                            <td>
                                <button type="submit" name="action" value="save" class="button regular-text" >Duplicate Configuration</button>
                            </td>
                        </tr>
                        <tr id="button-update" class="old default-hide">
                            <td>
                                <button type="submit" name="action" value="update" class="button regular-text">Update Configuration</button>
                            </td>
                        </tr>
                        <tr id="button-delete" class="old default-hide">
                            <td>
                                <button type="submit" name="action" value="delete" class="button regular-text">Delete Configuration</button>
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
                            <th colspan="2"><strong>Step 2:</strong><br>Select Data Types </th>
                        </tr>
                        </thead>
                        <tr>
                            <td colspan="2">
                                Format<br>
                                <select name="format" class="regular-text" id="input-format" required>
                                    <!-- load formats-->
                                </select>
                            </td>
                        </tr>
                    </table>
                    <!-- Box -->
                    <table class="widefat striped" >
                        <tbody id="types-list"><!-- List of types --></tbody>
                    </table>
                    <br>
                    <!-- End Box -->

                </div>

                <div class="quarter">
                    <!-- Box -->
                    <table class="widefat striped">
                        <thead>
                        <tr>
                            <th colspan="2"><strong>Step 3:</strong><br>Select Locations & Levels </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                All Locations<br>
                                <select name="all_locations" id="input-all-locations" class="regular-text" required>
                                    <!-- All locations -->
                                </select>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <table class="widefat striped" id="countries-wrapper">
                        <tbody id="country-list-table"><!-- List of countries --></tbody>
                    </table>
                    <br>
                    <!-- End Box -->
                </div>
                <div class="quarter">
                    <!-- Box -->
                    <table class="widefat striped">
                        <thead>
                        <tr>
                            <th><strong>Step 4:</strong><br>Export </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                Export Destination<br>
                                <select name="destination" class="regular-text" id="input-destination" required>
                                    <!-- load destination -->
                                </select>
                            </td>
                        </tr>
                        <tr id="button-export" class="old default-hide">
                            <td>
                                <button type="submit" name="action" value="export"  class="button regular-text">Export</button>
                            </td>
                        </tr>
                        <tr id="button-save-new" class="new default-hide">
                            <td>
                                <button type="submit" name="action" value="save" class="button regular-text" >Save New Configuration</button>
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
            window.export_configurations = [<?php echo json_encode( get_dt_metrics_export_configurations() ) ?>][0]
            window.export_formats = [<?php echo json_encode( get_dt_metrics_export_formats() ) ?>][0]
            window.countries = [<?php echo json_encode( Disciple_Tools_Mapping_Queries::get_countries() ) ?>][0]
            window.last_config = <?php echo esc_attr( $last_config_id ?? 0 ) ?>

            console.log( `window.export_configurations` )
            console.log( window.export_configurations )
            console.log( `window.export_formats` )
            console.log( window.export_formats )
            console.log( `window.countries` )
            console.log( window.countries )
            console.log( `window.last_config` )
            console.log( window.last_config )

            jQuery(document).ready(function() {
                load_all_configurations()
                let config = jQuery('#input-configuration')
                config.on('change', function() {
                    load_selected_configuration( config.val() )
                    set_buttons()
                })
                if ( window.last_config > 0 ) {
                    load_selected_configuration( window.last_config )
                }
                set_buttons()

                let input_format = jQuery('#input-format')
                input_format.on('change', function() {
                    load_format( input_format.val() )
                })

                let input_all_locations = jQuery('#input-all-locations' )
                input_all_locations.on('change', function() {
                    if ( 'country_by_country' === jQuery(this).val() ) {
                        jQuery('#countries-wrapper').show()
                    } else {
                        jQuery('#countries-wrapper').hide()
                    }
                })
            })

            function set_buttons() {
                let is_old = jQuery('.old')
                let is_new = jQuery('.new')

                if ( jQuery('#input-configuration').val() === 'new' ) {
                    is_old.hide()
                    is_new.show()
                }
                else {
                    is_old.show()
                    is_new.hide()
                }
            }
            function load_all_configurations() {
                let input_configuration = jQuery('#input-configuration')
                let input_configuration_label = jQuery('#input-configuration-name')
                let rand_time = <?php echo esc_attr( time() ) ?>

                // load list
                input_configuration.empty().append(`<option value="new">New</option><option disabled>----</option>`)
                jQuery.each(  window.export_configurations, function(i,v) {
                    input_configuration.append(`
                    <option value="${v.id}">${v.label}</option>
                    `)
                })

                // load title field
                input_configuration_label.val(`Configuration ${rand_time}`)

                // load formats
                load_all_formats()
            }
            function load_all_formats() {
                let input_format = jQuery('#input-format')

                // load formats
                input_format.empty().append(`<option></option>`)
                jQuery.each(  window.export_formats, function(i,v) {
                    input_format.append(`
                    <option value="${v.key}">${v.label}</option>
                    `)
                })

                // clear other settings
                jQuery('#types-list').empty()
                jQuery('#input-all-locations').empty()
                jQuery('#country-list-table').empty()
                jQuery('#input-destination').empty()

                // set buttons
                set_buttons()
            }
            function load_types( format_key ) {
                let list = jQuery('#types-list')

                list.empty()
                jQuery.each( window.export_formats[format_key].types, function(i,v) {
                    list.append(`<tr><td style="text-transform:capitalize;" ><strong>${i}</strong></td><td></td></tr>`)
                    jQuery.each(v, function (ii, vv) {
                        list.append(`<tr><td>-- ${vv.label}</td><td class="float-right"><input type="radio" id="${vv.key}" name="type[${i}]" value="${vv.key}" /></td></tr>`)
                    })
                })

                let selected_configuration = jQuery('#input-configuration').val()
                if ( 'new' !== selected_configuration && typeof window.export_configurations[selected_configuration] !== 'undefined' ) {
                    jQuery.each( window.export_configurations[selected_configuration].type, function(ind, val) {
                        jQuery('#'+val).prop('checked', 'checked')
                    } )
                }

               list.append('<tr><th></th><th class="float-right"><a href="javascript:void(0)" onclick="jQuery(\'#types-list input[type=radio]\').prop(\'checked\', false)">clear</a></th></tr>')
            }
            function load_all_locations( format_key ) {
                if ( typeof format_key === 'undefined') {
                    return
                }
                let list = jQuery('#input-all-locations')

                list.empty()
                jQuery.each(window.export_formats[format_key].locations.all, function(i,v){
                    list.append(`<option value="${i}">${v}</option>`)
                })

                if ( typeof window.export_formats[format_key].locations.country_by_country !== 'undefined' ){
                    list.append(`<option disabled>-----</option>`)
                    list.append(`<option value="country_by_country">Country by Country</option>`)
                }

                list.val('admin2')

            }
            function load_countries( format_key ) {
                jQuery('#countries-wrapper').hide()
                let countries_list = jQuery('#country-list-table')
                countries_list.empty()

                let options_list = ''
                jQuery.each(window.export_formats[format_key].locations.country_by_country, function(i,v){
                    options_list += '<option value="'+i+'">'+v+'</option>'
                })

                jQuery.each( window.countries, function(i,v) {
                    countries_list.append(`
                    <tr>
                        <td>
                            ${v.name}
                        </td>
                        <td>
                            <select class="selected-locations" name="selected_locations[${v.grid_id}]" id="${v.grid_id}">
                                ${options_list}
                            </select>
                        </td>
                    </tr>
                    `)
                })
            }
            function load_destinations( format_key ) {
                let input_destination = jQuery('#input-destination')
                input_destination.empty()

                jQuery.each(  window.export_formats[format_key].destinations, function(i,v) {
                    input_destination.append(`
                    <option value="${v.value}">${v.label}</option>
                    `)
                })
            }
            function load_format( format_key ) {
                let input_format = jQuery('#input-format')
                input_format.val(format_key)
                load_types( format_key )
                load_all_locations(format_key)
                load_countries( format_key )
                load_destinations( format_key )
            }
            function load_selected_configuration( configuration_id ) {
                let input_configuration = jQuery('#input-configuration')
                let input_configuration_label = jQuery('#input-configuration-name')

                console.log(configuration_id)

                if ( typeof configuration_id === 'undefined' || 'new' === configuration_id ) {
                    load_all_configurations()
                    return
                }

                // set format elements
                let format_key = window.export_configurations[configuration_id].format
                load_format( format_key )

                // configure elements to the configuration
                configure_types( configuration_id )
                configure_all_locations( configuration_id )
                configure_destinations( configuration_id )



                // set name of configuration
                input_configuration.val( configuration_id )
                input_configuration_label.val(window.export_configurations[configuration_id].label)

                set_buttons()
            }
            function configure_types( configuration_id ) {
                let inputs = jQuery('#types-list input:checkbox')

                inputs.prop('checked', false)
                jQuery.each( window.export_configurations[configuration_id].type, function(iii, vvv ) {
                    jQuery('#'+vvv).prop('checked', 'checked')
                })
            }
            function configure_all_locations( configuration_id ) {
                let input_all_locations = jQuery('#input-all-locations')
                input_all_locations.val(window.export_configurations[configuration_id].all_locations)

                configure_countries( configuration_id )

                if ( 'country_by_country' === window.export_configurations[configuration_id].all_locations ) {
                    jQuery('#countries-wrapper').show()
                } else {
                    jQuery('#countries-wrapper').hide()
                }
            }
            function configure_countries( configuration_id ) {
                let selected_locations = window.export_configurations[configuration_id].selected_locations

                jQuery.each( selected_locations, function(i,v){
                    jQuery('#'+i).val(v)
                })
            }
            function configure_destinations( configuration_id ) {
                let input_destination = jQuery('#input-destination')
                input_destination.val(window.export_configurations[configuration_id].destination)
            }

        </script>
        <?php
    }

    public function process_post() {

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
            wp_delete_post( $response['configuration'] );
        }
        return 0;
    }

    public function export( $response ) {
        dt_write_log( 'action: export' );

        $formats = apply_filters( 'dt_metrics_export_register_format_class', [] );

        if ( isset( $response['format'] ) && ! empty( $response['format'] ) && isset( $formats[$response['format']] ) && class_exists( $formats[$response['format']] ) ) {
            $result = $formats[$response['format']]::instance()->export( $response );
            dt_activity_insert(
                [
                    'action' => 'export',
                    'object_type' => 'metrics', // If this could be contacts/groups, that would be best
                    'object_subtype' => 'metrics',
                    'object_note' => "disciple-tools-metrics-export",
                ]
            );
            if ( is_wp_error( $result ) ) {
                dt_write_log( $result );
                return 0;
            }
            return $result;
        }
        return 0;
    }
}

/**
 * Class DT_Metrics_Export_Tab_Webhooks
 */
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

