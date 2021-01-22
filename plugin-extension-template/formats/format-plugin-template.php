<?php
/**
 * Plugin Name: Disciple Tools - Metrics Export Extension (2414)
 * Plugin URI: https://github.com/DiscipleTools/disciple-tools-gapp
 * Description: Adds CSV List export to the Metrics Export plugin.
 * Version:  0.1.0
 * Author URI: https://github.com/DiscipleTools
 * GitHub Plugin URI: https://github.com/DiscipleTools/disciple-tools-extension
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 5.5
 *
 * Add this as a new plugin. Define the plugin title above.
 * 1. Refactor the classes.
 * 2. Change $token
 * 3. Change $label
 * 4. Modify locations levels and types to what you will support
 * 5. Match queries to formats you have defined
 * 6. Set columns
 */


/**
 * LOAD DATA TYPE FORMAT
 */
if (defined( 'ABSPATH' )) { // confirm wp is loaded

    if ( is_admin() && isset( $_GET['page'] ) && 'dt_metrics_export' === sanitize_key( wp_unslash( $_GET['page'] ) ) ) { // confirm this is the admin area and the metrics plugin

        add_action( 'dt_metrics_export_loaded', function() { // load after the metrics export is loaded
            /**
             * Class DT_Metrics_Export_CSV
             */
            class DT_Metrics_Export_Contacts_Export extends DT_Metrics_Export_Format_Base
            {

                public $token = 'contacts_list';
                public $label = 'Contacts List';

                private static $_instance = null;

                public static function instance() {
                    if (is_null( self::$_instance )) {
                        self::$_instance = new self();
                    }
                    return self::$_instance;
                }

                /**
                 * DT_Metrics_Export_CSV constructor.
                 */
                public function __construct() {
                    parent::__construct();
                    add_filter( 'dt_metrics_export_format', [ $this, 'format' ], 10, 1 );
                    add_filter( 'dt_metrics_export_register_format_class', [ $this, 'format_class' ], 10, 1 );
                } // End __construct()

                public function format( $format) {
                    /* Build base template of a format*/
                    $format[$this->token] = get_dt_metrics_export_base_format();

                    /* Add key and label for format */
                    $format[$this->token]['key'] = $this->token;
                    $format[$this->token]['label'] = $this->label;

                    // remove raw
                    $format[$this->token]['locations'] = [
                        'all' => [
                            'admin0' => 'Admin0 (Country)',
                            'admin1' => 'Admin1 (State)',
                            'admin2' => 'Admin2 (County)',
                            'admin3' => 'Admin3 (Blocks)',
                            'admin4' => 'Admin4 (Village)',
                        ],
                        'country_by_country' => [
                            'disabled' => '---disabled---',
                            'admin0' => 'Admin0 (Country)',
                            'admin1' => 'Admin1 (State)',
                            'admin2' => 'Admin2 (County)',
                            'admin3' => 'Admin3 (Blocks)',
                            'admin4' => 'Admin4 (Village)',
                        ]
                    ];

                    $format[$this->token]['types'] = [
                        'contacts' => [
                            'contacts_active' => [
                                'key' => 'contacts_all',
                                'label' => 'Active'
                            ],
                            'contacts_pre_active' => [
                                'key' => 'contacts_pre_active',
                                'label' => 'New-ish (All pre-active)'
                            ],
                            'contacts_activeish' => [
                                'key' => 'contacts_activeish',
                                'label' => 'Active-ish (All Non-Closed)'
                            ],
                            'contacts_paused' => [
                                'key' => 'contacts_paused',
                                'label' => 'Paused'
                            ],
                        ],
                        'groups' => [
                            'groups_all' => [
                                'key' => 'groups_all',
                                'label' => 'All'
                            ],
                            'groups_active' => [
                                'key' => 'groups_active',
                                'label' => 'Active'
                            ],
                            'groups_inactive' => [
                                'key' => 'groups_inactive',
                                'label' => 'Inactive'
                            ],
                        ],
                        'churches' => [
                            'churches_all' => [
                                'key' => 'churches_all',
                                'label' => 'All'
                            ],
                            'churches_active' => [
                                'key' => 'churches_active',
                                'label' => 'Active'
                            ],
                            'churches_inactive' => [
                                'key' => 'churches_inactive',
                                'label' => 'Inactive'
                            ],
                        ],
                        'users' => [
                            'users_all' => [
                                'key' => 'users_all',
                                'label' => 'All'
                            ],
                        ]
                    ];

                    unset( $format[$this->token]['destinations']['uploads'] );

                    return $format;
                }

                public function format_class( $classes) {
                    $classes[$this->token] = __CLASS__;
                    return $classes;
                }


                public function export( $response) {
                    global $wpdb;

                    if ( !isset( $response['all_locations'] ) || empty( $response['all_locations'] )) {
                        return new WP_Error( __METHOD__, 'All locations parameter not set.' );
                    }


                    $results = [];
                    if ('country_by_country' === $response['all_locations']) {
                        if ( !isset( $response['selected_locations'] ) || empty( $response['selected_locations'] )) {
                            return new WP_Error( __METHOD__, 'Selected locations parameter not set.' );
                        }
                        foreach ($response['selected_locations'] as $grid_id => $level) {
                            if ('disabled' === $level) {
                                continue;
                            }

                            /** @todo  add individual query */
                            $loop_results = [
                                [
                                    'type' => 'group',
                                    'description' => 'all-' . microtime(),
                                    'total' => '10',
                                    'longitude' => '0.0',
                                    'latitude' => '0.0',
                                    'level' => 'admin2',
                                    'level_name' => 'Nowhere, Ocean',
                                ]
                            ];

                            if (empty( $loop_results )) {
                                continue;
                            }

                            $results = array_merge( $results, $loop_results );

                        }
                    } else {
                        /** @todo add single level query */

                        $results = $this->query_contacts();

                    }


                    // kill if no results
                    if (empty( $results )) {
                        echo '<div class="notice notice-warning is-dismissible">
                                 <p>No results found for this configuration. Likely, there are no records for the countries you specified. Could not generate csv file.</p>
                             </div>';
                        return $response['configuration'] ?? 0;
                    }

                    // Setup columns
                    $columns = [
                        'ID',
                        'name',
                        'phone',
                        'email',
                        'location',
                        'description',
                        'region',
                        'security_level'
                    ];

                    // Package transient variables
                    $args = [
                        'timestamp' => current_time( 'Y-m-d_H-i-s' ),
                        'columns' => $columns,
                        'rows' => $results
                    ];

                    // build transient
                    $one_time_key = hash( 'sha256', get_current_user_id() . time() . dt_get_site_id() . rand( 0, 999 ) );
                    set_transient( $one_time_key, $args, 60 . 60 . 48 );

                    // admin notification with link
                    echo '<div class="notice notice-warning is-dismissible">
                             <p>
                                 One time download link (expires in 48 hours):<br>
                                 <a href="' . esc_url( plugin_dir_url( __FILE__ ) ) . esc_url( basename( __FILE__ ) ) . '?csv=' . esc_attr( $one_time_key ) . '"
                                 target="_blank">' . esc_url( plugin_dir_url( __FILE__ ) ) . esc_url( basename( __FILE__ ) ) . '?csv=' . esc_attr( $one_time_key ) . '
                                 </a>
                             </p>
                         </div>';

                    // return configuration selection from before export
                    return $response['configuration'] ?? 0; // return int config id, so ui reloads on same config
                }


                public function query_contacts() {
                    global $wpdb;
                    $results = $wpdb->get_results("
                        SELECT
                            p.ID,
                            p.post_title as name,
                            ( SELECT GROUP_CONCAT( pm1.meta_value) FROM  $wpdb->postmeta as pm1 WHERE p.ID=pm1.post_id AND pm1.meta_key LIKE 'contact_phone%' AND pm1.meta_key NOT LIKE '%details' ) as phone,
                            ( SELECT GROUP_CONCAT( pm2.meta_value) FROM  $wpdb->postmeta as pm2 WHERE p.ID=pm2.post_id AND pm2.meta_key LIKE 'contact_email%' AND pm2.meta_key NOT LIKE '%details' ) as email,
                            ( SELECT GROUP_CONCAT( (SELECT CONCAT( $wpdb->dt_location_grid.name, '-', lg.name ) as name FROM $wpdb->dt_location_grid JOIN $wpdb->dt_location_grid as lg ON $wpdb->dt_location_grid.admin0_grid_id=lg.grid_id WHERE $wpdb->dt_location_grid.grid_id = pm3.meta_value )) FROM  $wpdb->postmeta as pm3 WHERE pm3.post_id=p.ID AND pm3.meta_key = 'location_grid' ) as location,
                            ( SELECT GROUP_CONCAT( pm4.meta_value) FROM  $wpdb->postmeta as pm4 WHERE pm4.post_id=p.ID AND pm4.meta_key = 'description' ) as description,
                            ( SELECT GROUP_CONCAT( pm5.meta_value) FROM  $wpdb->postmeta as pm5 WHERE pm5.post_id=p.ID AND pm5.meta_key = 'region' ) as region,
                            ( SELECT GROUP_CONCAT( pm6.meta_value) FROM  $wpdb->postmeta as pm6 WHERE pm6.post_id=p.ID AND pm6.meta_key = 'security_level' ) as security_level
                            FROM $wpdb->posts as p
                            WHERE post_type = 'contacts';
                    ", ARRAY_A);
                    return $results;
                }
            }
            DT_Metrics_Export_Contacts_Export::instance();
        });
    }
}


/**
 * CREATE CSV FILE
 */
if ( !defined( 'ABSPATH' )) {

    // @codingStandardsIgnoreLine
    require($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php'); // loads the wp framework when called

    if ( !isset( $_GET['csv'] )) {
        wp_die( 'No parameter found' );
    }

    $token = sanitize_text_field( wp_unslash( $_GET['csv'] ) );
    $results = get_transient( $token );
    if (empty( $results )) {
        echo 'Link no longer available';
        return;
    }

    delete_transient( $token );

    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=dt-csv-' . $results['timestamp'] . '.csv' );

    $output = fopen( 'php://output', 'w' );

    fputcsv( $output, $results['columns'] );

    foreach ($results['rows'] as $row) {
        fputcsv( $output, $row );
    }

    fpassthru( $output );
}
