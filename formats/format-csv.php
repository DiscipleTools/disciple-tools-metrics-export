<?php
/**
 * Export Format: CSV export
 */

/**
 * Export Format: CSV export in the COTW Standard
 *
 * When ABSPATH is defined then WP has loaded, if ABSPATH is not defined then the file is being accessed directly.
 *
 * Direct access is used to generate the CSV from the transient store. It is directly accessed link.
 * By both supplying the format and the export from the same file, this pattern attempt to make adding additional formats
 * simple and self contained.
 */

/**
 * LOAD DATA TYPE FORMAT
 */
if (defined( 'ABSPATH' )) {
    /**
     * Class DT_Metrics_Export_CSV
     */
    class DT_Metrics_Export_CSV extends DT_Metrics_Export_Format_Base
    {

        public $token = 'csv';
        public $label = 'CSV (Totals)';

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

        public function format( $format ) {
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
                            'description' => 'all-'.microtime(),
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

//                $results = $this->query_contacts();
                $results = $this->query_churches( 'active' );

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
                'type',
                'description',
                'longitude',
                'latitude',
                'level',
                'name',
                'population',
                'count'
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
                   'Contacts' as type,
                   'Not Closed' as description,
                   lg.longitude,
                   lg.latitude,
                   lg.level_name as level,
                   CASE
                       WHEN lg.level_name = 'admin0' THEN lg.name
                       WHEN lg.level_name = 'admin1' THEN CONCAT(lg.name, ', ', lg.admin0_code)
                       WHEN lg.level_name = 'admin2' THEN CONCAT(lg.name, ', ', lgp.name, ', ', lg.admin0_code)
                       WHEN lg.level_name = 'admin3' THEN CONCAT(lg.name, ', ', lg2.name, ', ', lg.admin0_code)
                       WHEN lg.level_name = 'admin4' THEN CONCAT(lg.name, ', ', lg2.name, ', ', lg.admin0_code)
                       WHEN lg.level_name = 'admin5' THEN CONCAT(lg.name, ', ', lg2.name, ', ', lg.admin0_code)
                       ELSE CONCAT(lg.name, ', ', lg.admin0_code)
                    END as name,
                   lg.alt_population as population,
                   count_table.count
                FROM (
                         SELECT t0.admin0_grid_id as grid_id, count(t0.admin0_grid_id) as count
                         FROM (
                                  SELECT lg.admin0_grid_id,
                                         lg.admin1_grid_id,
                                         lg.admin2_grid_id,
                                         lg.admin3_grid_id,
                                         lg.admin4_grid_id,
                                         lg.admin5_grid_id
                                  FROM $wpdb->postmeta as pm
                                           JOIN $wpdb->posts as p ON p.ID = pm.post_id AND p.post_type = 'contacts'
                                           LEFT JOIN $wpdb->dt_location_grid as lg ON pm.meta_value = lg.grid_id
                                  WHERE pm.meta_key = 'location_grid'
                                    AND pm.post_id NOT IN (SELECT DISTINCT(p.post_id)
                                                           FROM $wpdb->postmeta as p
                                                           WHERE (p.meta_key = 'corresponds_to_user' AND p.meta_value != '')
                                                              OR (p.meta_key = 'overall_status' AND p.meta_value = 'closed'))
                              ) as t0
                         GROUP BY t0.admin0_grid_id
                         UNION
                         SELECT t1.admin1_grid_id as grid_id, count(t1.admin1_grid_id) as count
                         FROM (
                                  SELECT lg.admin0_grid_id,
                                         lg.admin1_grid_id,
                                         lg.admin2_grid_id,
                                         lg.admin3_grid_id,
                                         lg.admin4_grid_id,
                                         lg.admin5_grid_id
                                  FROM $wpdb->postmeta as pm
                                           JOIN $wpdb->posts as p ON p.ID = pm.post_id AND p.post_type = 'contacts'
                                           LEFT JOIN $wpdb->dt_location_grid as lg ON pm.meta_value = lg.grid_id
                                  WHERE pm.meta_key = 'location_grid'
                                    AND pm.post_id NOT IN (SELECT DISTINCT(p.post_id)
                                                           FROM $wpdb->postmeta as p
                                                           WHERE (p.meta_key = 'corresponds_to_user' AND p.meta_value != '')
                                                              OR (p.meta_key = 'overall_status' AND p.meta_value = 'closed'))
                              ) as t1
                         GROUP BY t1.admin1_grid_id
                         UNION
                         SELECT t2.admin2_grid_id as grid_id, count(t2.admin2_grid_id) as count
                         FROM (
                                  SELECT lg.admin0_grid_id,
                                         lg.admin1_grid_id,
                                         lg.admin2_grid_id,
                                         lg.admin3_grid_id,
                                         lg.admin4_grid_id,
                                         lg.admin5_grid_id
                                  FROM $wpdb->postmeta as pm
                                           JOIN $wpdb->posts as p ON p.ID = pm.post_id AND p.post_type = 'contacts'
                                           LEFT JOIN $wpdb->dt_location_grid as lg ON pm.meta_value = lg.grid_id
                                  WHERE pm.meta_key = 'location_grid'
                                    AND pm.post_id NOT IN (SELECT DISTINCT(p.post_id)
                                                           FROM $wpdb->postmeta as p
                                                           WHERE (p.meta_key = 'corresponds_to_user' AND p.meta_value != '')
                                                              OR (p.meta_key = 'overall_status' AND p.meta_value = 'closed'))
                              ) as t2
                         GROUP BY t2.admin2_grid_id
                         UNION
                         SELECT t3.admin3_grid_id as grid_id, count(t3.admin3_grid_id) as count
                         FROM (
                                  SELECT lg.admin0_grid_id,
                                         lg.admin1_grid_id,
                                         lg.admin2_grid_id,
                                         lg.admin3_grid_id,
                                         lg.admin4_grid_id,
                                         lg.admin5_grid_id
                                  FROM $wpdb->postmeta as pm
                                           JOIN $wpdb->posts as p ON p.ID = pm.post_id AND p.post_type = 'contacts'
                                           LEFT JOIN $wpdb->dt_location_grid as lg ON pm.meta_value = lg.grid_id
                                  WHERE pm.meta_key = 'location_grid'
                                    AND pm.post_id NOT IN (SELECT DISTINCT(p.post_id)
                                                           FROM $wpdb->postmeta as p
                                                           WHERE (p.meta_key = 'corresponds_to_user' AND p.meta_value != '')
                                                              OR (p.meta_key = 'overall_status' AND p.meta_value = 'closed'))
                              ) as t3
                         GROUP BY t3.admin3_grid_id
                         UNION
                         SELECT t4.admin4_grid_id as grid_id, count(t4.admin4_grid_id) as count
                         FROM (
                                  SELECT lg.admin0_grid_id,
                                         lg.admin1_grid_id,
                                         lg.admin2_grid_id,
                                         lg.admin3_grid_id,
                                         lg.admin4_grid_id,
                                         lg.admin5_grid_id
                                  FROM $wpdb->postmeta as pm
                                           JOIN $wpdb->posts as p ON p.ID = pm.post_id AND p.post_type = 'contacts'
                                           LEFT JOIN $wpdb->dt_location_grid as lg ON pm.meta_value = lg.grid_id
                                  WHERE pm.meta_key = 'location_grid'
                                    AND pm.post_id NOT IN (SELECT DISTINCT(p.post_id)
                                                           FROM $wpdb->postmeta as p
                                                           WHERE (p.meta_key = 'corresponds_to_user' AND p.meta_value != '')
                                                              OR (p.meta_key = 'overall_status' AND p.meta_value = 'closed'))
                              ) as t4
                         GROUP BY t4.admin4_grid_id
                         UNION
                         SELECT t5.admin5_grid_id as grid_id, count(t5.admin5_grid_id) as count
                         FROM (
                                  SELECT lg.admin0_grid_id,
                                         lg.admin1_grid_id,
                                         lg.admin2_grid_id,
                                         lg.admin3_grid_id,
                                         lg.admin4_grid_id,
                                         lg.admin5_grid_id
                                  FROM $wpdb->postmeta as pm
                                           JOIN $wpdb->posts as p ON p.ID = pm.post_id AND p.post_type = 'contacts'
                                           LEFT JOIN $wpdb->dt_location_grid as lg ON pm.meta_value = lg.grid_id
                                  WHERE pm.meta_key = 'location_grid'
                                    AND pm.post_id NOT IN (SELECT DISTINCT(p.post_id)
                                                           FROM $wpdb->postmeta as p
                                                           WHERE (p.meta_key = 'corresponds_to_user' AND p.meta_value != '')
                                                              OR (p.meta_key = 'overall_status' AND p.meta_value = 'closed'))
                              ) as t5
                         GROUP BY t5.admin5_grid_id
                     ) as count_table
                        JOIN $wpdb->dt_location_grid as lg ON count_table.grid_id=lg.grid_id
                        JOIN $wpdb->dt_location_grid as lgp ON lg.parent_id=lgp.grid_id
                        LEFT JOIN $wpdb->dt_location_grid as lg2 ON lg.admin1_grid_id=lg2.grid_id
            ", ARRAY_A );
            return $results;
        }

        public function query_churches( string $group_type = 'all', string $status = 'all' ) {
            global $wpdb;

            switch ( $status ) {
                case 'active':
                    $description = 'Active';
                    $where_sql = " AND pm.post_id NOT IN (SELECT DISTINCT(p.post_id) FROM $wpdb->postmeta as p WHERE p.meta_key = 'group_status' AND p.meta_value = 'inactive')";
                    break;
                case 'inactive':
                    $description = 'Inactive';
                    $where_sql = " AND pm.post_id IN (SELECT DISTINCT(p.post_id) FROM $wpdb->postmeta as p WHERE p.meta_key = 'group_status' AND p.meta_value = 'inactive')";
                    break;
                case 'all':
                default:
                    $description = 'Active and Inactive';
                    $where_sql = "";
                    break;
            }

            switch ( $group_type ) {
                case 'church':
                    $type_title = 'Churches';
                    $type_sql = " JOIN $wpdb->postmeta as pmt ON pmt.post_id = pm.post_id AND pmt.meta_key = 'group_type' AND pmt.meta_value = 'church'";
                    break;
                case 'group':
                    $type_title = 'Groups';
                    $type_sql = " JOIN $wpdb->postmeta as pmt ON pmt.post_id = pm.post_id AND pmt.meta_key = 'group_type' AND pmt.meta_value = 'group'";
                    break;
                case 'pre-group':
                    $type_title = 'Pre-Group';
                    $type_sql = " JOIN $wpdb->postmeta as pmt ON pmt.post_id = pm.post_id AND pmt.meta_key = 'group_type' AND pmt.meta_value = 'pre-group'";
                    break;
                case 'all':
                default:
                    $type_title = 'All Group Types';
                    $type_sql = "LEFT JOIN $wpdb->postmeta as pm2 ON pm2.post_id = pm.post_id AND pm2.meta_key = 'group_type'";
                    break;
            }

            // @phpcs:disable
            $results = $wpdb->get_results("
                SELECT
                    '{$type_title}' as type,
                    '{$description}' as description,
                    lg.longitude,
                    lg.latitude,
                    lg.level_name as level,
                    CASE
                        WHEN lg.level_name = 'admin0' THEN lg.name
                        WHEN lg.level_name = 'admin1' THEN CONCAT(lg.name, ', ', lg.admin0_code)
                        WHEN lg.level_name = 'admin2' THEN CONCAT(lg.name, ', ', lgp.name, ', ', lg.admin0_code)
                        WHEN lg.level_name = 'admin3' THEN CONCAT(lg.name, ', ', lg2.name, ', ', lg.admin0_code)
                        WHEN lg.level_name = 'admin4' THEN CONCAT(lg.name, ', ', lg2.name, ', ', lg.admin0_code)
                        WHEN lg.level_name = 'admin5' THEN CONCAT(lg.name, ', ', lg2.name, ', ', lg.admin0_code)
                        ELSE CONCAT(lg.name, ', ', lg.admin0_code)
                        END as name,
                    lg.alt_population as population,
                    count_table.count
                FROM (
                         SELECT t0.admin0_grid_id as grid_id, count(t0.admin0_grid_id) as count
                         FROM (
                                  SELECT lg.admin0_grid_id,
                                         lg.admin1_grid_id,
                                         lg.admin2_grid_id,
                                         lg.admin3_grid_id,
                                         lg.admin4_grid_id,
                                         lg.admin5_grid_id
                                  FROM $wpdb->postmeta as pm
                                      JOIN $wpdb->posts as p ON p.ID = pm.post_id AND p.post_type = 'groups'
                                      LEFT JOIN $wpdb->dt_location_grid as lg ON pm.meta_value = lg.grid_id
                                      {$type_sql}
                                  WHERE pm.meta_key = 'location_grid' {$where_sql}
                              ) as t0
                         GROUP BY t0.admin0_grid_id
                         UNION
                         SELECT t1.admin1_grid_id as grid_id, count(t1.admin1_grid_id) as count
                         FROM (
                                  SELECT lg.admin0_grid_id,
                                         lg.admin1_grid_id,
                                         lg.admin2_grid_id,
                                         lg.admin3_grid_id,
                                         lg.admin4_grid_id,
                                         lg.admin5_grid_id
                                  FROM $wpdb->postmeta as pm
                                      JOIN $wpdb->posts as p ON p.ID = pm.post_id AND p.post_type = 'groups'
                                      LEFT JOIN $wpdb->dt_location_grid as lg ON pm.meta_value = lg.grid_id
                                      {$type_sql}
                                  WHERE pm.meta_key = 'location_grid' {$where_sql}
                              ) as t1
                         GROUP BY t1.admin1_grid_id
                         UNION
                         SELECT t2.admin2_grid_id as grid_id, count(t2.admin2_grid_id) as count
                         FROM (
                                  SELECT lg.admin0_grid_id,
                                         lg.admin1_grid_id,
                                         lg.admin2_grid_id,
                                         lg.admin3_grid_id,
                                         lg.admin4_grid_id,
                                         lg.admin5_grid_id
                                  FROM $wpdb->postmeta as pm
                                      JOIN $wpdb->posts as p ON p.ID = pm.post_id AND p.post_type = 'groups'
                                      LEFT JOIN $wpdb->dt_location_grid as lg ON pm.meta_value = lg.grid_id
                                      {$type_sql}
                                  WHERE pm.meta_key = 'location_grid' {$where_sql}
                              ) as t2
                         GROUP BY t2.admin2_grid_id
                         UNION
                         SELECT t3.admin3_grid_id as grid_id, count(t3.admin3_grid_id) as count
                         FROM (
                                  SELECT lg.admin0_grid_id,
                                         lg.admin1_grid_id,
                                         lg.admin2_grid_id,
                                         lg.admin3_grid_id,
                                         lg.admin4_grid_id,
                                         lg.admin5_grid_id
                                  FROM $wpdb->postmeta as pm
                                      JOIN $wpdb->posts as p ON p.ID = pm.post_id AND p.post_type = 'groups'
                                      LEFT JOIN $wpdb->dt_location_grid as lg ON pm.meta_value = lg.grid_id
                                      {$type_sql}
                                  WHERE pm.meta_key = 'location_grid' {$where_sql}
                              ) as t3
                         GROUP BY t3.admin3_grid_id
                         UNION
                         SELECT t4.admin4_grid_id as grid_id, count(t4.admin4_grid_id) as count
                         FROM (
                                  SELECT lg.admin0_grid_id,
                                         lg.admin1_grid_id,
                                         lg.admin2_grid_id,
                                         lg.admin3_grid_id,
                                         lg.admin4_grid_id,
                                         lg.admin5_grid_id
                                  FROM $wpdb->postmeta as pm
                                      JOIN $wpdb->posts as p ON p.ID = pm.post_id AND p.post_type = 'groups'
                                      LEFT JOIN $wpdb->dt_location_grid as lg ON pm.meta_value = lg.grid_id
                                      {$type_sql}
                                  WHERE pm.meta_key = 'location_grid' {$where_sql}
                              ) as t4
                         GROUP BY t4.admin4_grid_id
                         UNION
                         SELECT t5.admin5_grid_id as grid_id, count(t5.admin5_grid_id) as count
                         FROM (
                                  SELECT lg.admin0_grid_id,
                                         lg.admin1_grid_id,
                                         lg.admin2_grid_id,
                                         lg.admin3_grid_id,
                                         lg.admin4_grid_id,
                                         lg.admin5_grid_id
                                  FROM $wpdb->postmeta as pm
                                      JOIN $wpdb->posts as p ON p.ID = pm.post_id AND p.post_type = 'groups'
                                      LEFT JOIN $wpdb->dt_location_grid as lg ON pm.meta_value = lg.grid_id
                                      {$type_sql}
                                  WHERE pm.meta_key = 'location_grid' {$where_sql}
                              ) as t5
                         GROUP BY t5.admin5_grid_id
                     ) as count_table
                    JOIN $wpdb->dt_location_grid as lg ON count_table.grid_id=lg.grid_id
                    JOIN $wpdb->dt_location_grid as lgp ON lg.parent_id=lgp.grid_id
                    LEFT JOIN $wpdb->dt_location_grid as lg2 ON lg.admin1_grid_id=lg2.grid_id;
            ", ARRAY_A );
            return $results;
        }
        // @phpcs:enable
    }

    DT_Metrics_Export_CSV::instance();
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
