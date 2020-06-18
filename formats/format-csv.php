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

            /**
             * PROCESS ALL LOCATIONS AT THE SAME ADMIN LEVEL OR PROCESS COUNTRY BY COUNTRY
             *
             * If processed country by country, the same query is looped over all the countries selected and returns
             * the query according to the country level. @todo confirm on large systems with many countries that this does not time out.
             */
            $results = [];
            if ('country_by_country' === $response['all_locations']) {
                /**
                 *  INDIVIDUAL ADMIN LEVELS SET FOR EACH COUNTRY
                 */
                if ( !isset( $response['selected_locations'] ) || empty( $response['selected_locations'] )) {
                    return new WP_Error( __METHOD__, 'Selected locations parameter not set.' );
                }
                foreach ($response['selected_locations'] as $grid_id => $level) {
                    if ('disabled' === $level) {
                        continue;
                    }

                    $intent_level = $level;
                    switch ($intent_level) {
                        case 'admin0':
                            $intent_level_int = 0;
                            break;
                        case 'admin1':
                            $intent_level_int = 1;
                            break;
                        case 'admin3':
                            $intent_level_int = 3;
                            break;
                        case 'admin4':
                            $intent_level_int = 4;
                            break;
                        case 'admin5':
                            $intent_level_int = 5;
                            break;
                        case 'raw':
                            $intent_level_int = 10;
                            break;
                        case 'admin2':
                        default:
                            $intent_level_int = 2;
                            break;
                    }

                    // phpcs:disable
                    $loop_results = $wpdb->get_results($wpdb->prepare( "
                            SELECT
                                SHA2( p.ID, 256) as identifier,
                                pm2.meta_value as members, /* member count */
                                DATE_FORMAT( FROM_UNIXTIME( pm3.meta_value ), '%Y-%m-%d') as start_date,
                                DATE_FORMAT( FROM_UNIXTIME( pm4.meta_value ), '%Y-%m-%d') as end_date,
                                pm5.meta_value as type, /* church or group */
                                IF (
                                    lg.level <= '{$intent_level_int}',
                                    lg.latitude,
                                CASE
                                    WHEN '{$intent_level}' = 'admin0' THEN ( SELECT lg1.latitude FROM $wpdb->dt_location_grid as lg1 WHERE lg1.grid_id = lg.admin0_grid_id )
                                    WHEN '{$intent_level}' = 'admin1' THEN ( SELECT lg1.latitude FROM $wpdb->dt_location_grid as lg1 WHERE lg1.grid_id = lg.admin1_grid_id )
                                    WHEN '{$intent_level}' = 'admin2' THEN ( SELECT lg1.latitude FROM $wpdb->dt_location_grid as lg1 WHERE lg1.grid_id = lg.admin2_grid_id )
                                    WHEN '{$intent_level}' = 'admin3' THEN ( SELECT lg1.latitude FROM $wpdb->dt_location_grid as lg1 WHERE lg1.grid_id = lg.admin3_grid_id )
                                    WHEN '{$intent_level}' = 'admin4' THEN ( SELECT lg1.latitude FROM $wpdb->dt_location_grid as lg1 WHERE lg1.grid_id = lg.admin4_grid_id )
                                    WHEN '{$intent_level}' = 'admin5' THEN ( SELECT lg1.latitude FROM $wpdb->dt_location_grid as lg1 WHERE lg1.grid_id = lg.admin5_grid_id )
                                END
                                ) as latitude,
                                IF (
                                    lg.level <= '{$intent_level_int}',
                                    lg.longitude,
                                CASE
                                    WHEN '{$intent_level}' = 'admin0' THEN ( SELECT lg2.longitude FROM $wpdb->dt_location_grid as lg2 WHERE lg2.grid_id = lg.admin0_grid_id )
                                    WHEN '{$intent_level}' = 'admin1' THEN ( SELECT lg2.longitude FROM $wpdb->dt_location_grid as lg2 WHERE lg2.grid_id = lg.admin1_grid_id )
                                    WHEN '{$intent_level}' = 'admin2' THEN ( SELECT lg2.longitude FROM $wpdb->dt_location_grid as lg2 WHERE lg2.grid_id = lg.admin2_grid_id )
                                    WHEN '{$intent_level}' = 'admin3' THEN ( SELECT lg2.longitude FROM $wpdb->dt_location_grid as lg2 WHERE lg2.grid_id = lg.admin3_grid_id )
                                    WHEN '{$intent_level}' = 'admin4' THEN ( SELECT lg2.longitude FROM $wpdb->dt_location_grid as lg2 WHERE lg2.grid_id = lg.admin4_grid_id )
                                    WHEN '{$intent_level}' = 'admin5' THEN ( SELECT lg2.longitude FROM $wpdb->dt_location_grid as lg2 WHERE lg2.grid_id = lg.admin5_grid_id )
                                END
                                ) as longitude,
                                lg.admin0_code as country_code,
                                IF (
                                    lg.level <= '{$intent_level_int}', /* condition */
                                    lg.level_name /* true */,
                                CASE
                                    WHEN '{$intent_level}' = 'admin0' THEN
                                        ( SELECT
                                            lg3.level_name
                                            FROM $wpdb->dt_location_grid as lg3
                                        WHERE lg3.grid_id = lg.admin0_grid_id )
                                    WHEN '{$intent_level}' = 'admin1' THEN
                                        ( SELECT
                                             lg3.level_name
                                        FROM $wpdb->dt_location_grid as lg3
                                        WHERE lg3.grid_id = lg.admin1_grid_id )
                                    WHEN '{$intent_level}' = 'admin2' THEN
                                        ( SELECT
                                             lg3.level_name
                                        FROM $wpdb->dt_location_grid as lg3
                                        WHERE lg3.grid_id = lg.admin2_grid_id )
                                    WHEN '{$intent_level}' = 'admin3' THEN
                                        ( SELECT
                                            lg3.level_name
                                            FROM $wpdb->dt_location_grid as lg3
                                            WHERE lg3.grid_id = lg.admin3_grid_id )
                                    WHEN '{$intent_level}' = 'admin4' THEN
                                        ( SELECT
                                            lg3.level_name
                                            FROM $wpdb->dt_location_grid as lg3
                                            WHERE lg3.grid_id = lg.admin4_grid_id )
                                    WHEN '{$intent_level}' = 'admin5' THEN
                                        ( SELECT lg3.level_name
                                            FROM $wpdb->dt_location_grid as lg3
                                            WHERE lg3.grid_id = lg.admin5_grid_id )
                                END
                                ) as level,
                               pm1.meta_value as status
                            FROM $wpdb->posts as p
                            JOIN $wpdb->postmeta as pm1 ON p.ID=pm1.post_id AND pm1.meta_key = 'group_status'
                            LEFT JOIN $wpdb->postmeta as pm2 ON p.ID=pm2.post_id AND pm2.meta_key = 'member_count'
                            LEFT JOIN $wpdb->postmeta as pm3 ON p.ID=pm3.post_id AND pm3.meta_key = 'start_date'
                            LEFT JOIN $wpdb->postmeta as pm4 ON p.ID=pm4.post_id AND pm4.meta_key = 'end_date'
                            JOIN $wpdb->postmeta as pm5 ON p.ID=pm5.post_id AND pm5.meta_key = 'group_type' AND ( pm5.meta_value = 'group' OR pm5.meta_value = 'church' )
                            LEFT JOIN $wpdb->postmeta as pm6 ON p.ID=pm6.post_id AND pm6.meta_key = 'last_modified'
                            JOIN $wpdb->postmeta as pm7 ON p.ID=pm7.post_id AND pm7.meta_key = 'location_grid'
                            LEFT JOIN $wpdb->dt_location_grid as lg ON pm7.meta_value=lg.grid_id
                            WHERE p.post_type = 'groups' AND lg.admin0_grid_id = %s;
                        ", $grid_id), ARRAY_A);
                    // phpcs:enable


                    if (empty( $loop_results )) {
                        continue;
                    }

                    $results = array_merge( $results, $loop_results );

                }
            } else {
                /**
                 *  ONE ADMIN LEVEL SET FOR ALL LOCATIONS
                 */
                $intent_level = $response['all_locations'];
                switch ($intent_level) {
                    case 'admin0':
                        $intent_level_int = 0;
                        break;
                    case 'admin1':
                        $intent_level_int = 1;
                        break;
                    case 'admin3':
                        $intent_level_int = 3;
                        break;
                    case 'admin4':
                        $intent_level_int = 4;
                        break;
                    case 'admin5':
                        $intent_level_int = 5;
                        break;
                    case 'raw':
                        $intent_level_int = 10;
                        break;
                    case 'admin2':
                    default:
                        $intent_level_int = 2;
                        break;
                }

                // phpcs:disable
                $results = $wpdb->get_results("
                SELECT
                    SHA2( p.ID, 256) as identifier,
                    pm2.meta_value as members, /* member count */
                    DATE_FORMAT( FROM_UNIXTIME( pm3.meta_value ), '%Y-%m-%d') as start_date,
                    DATE_FORMAT( FROM_UNIXTIME( pm4.meta_value ), '%Y-%m-%d') as end_date,
                    pm5.meta_value as type, /* church or group */
                    IF (
                		lg.level <= '{$intent_level_int}',
                		lg.latitude,
                    CASE
                        WHEN '{$intent_level}' = 'admin0' THEN ( SELECT lg1.latitude FROM $wpdb->dt_location_grid as lg1 WHERE lg1.grid_id = lg.admin0_grid_id )
                        WHEN '{$intent_level}' = 'admin1' THEN ( SELECT lg1.latitude FROM $wpdb->dt_location_grid as lg1 WHERE lg1.grid_id = lg.admin1_grid_id )
                        WHEN '{$intent_level}' = 'admin2' THEN ( SELECT lg1.latitude FROM $wpdb->dt_location_grid as lg1 WHERE lg1.grid_id = lg.admin2_grid_id )
                        WHEN '{$intent_level}' = 'admin3' THEN ( SELECT lg1.latitude FROM $wpdb->dt_location_grid as lg1 WHERE lg1.grid_id = lg.admin3_grid_id )
                        WHEN '{$intent_level}' = 'admin4' THEN ( SELECT lg1.latitude FROM $wpdb->dt_location_grid as lg1 WHERE lg1.grid_id = lg.admin4_grid_id )
                        WHEN '{$intent_level}' = 'admin5' THEN ( SELECT lg1.latitude FROM $wpdb->dt_location_grid as lg1 WHERE lg1.grid_id = lg.admin5_grid_id )
                    END
                    ) as latitude,
                    IF (
                		lg.level <= '{$intent_level_int}',
                		lg.longitude,
                    CASE
                        WHEN '{$intent_level}' = 'admin0' THEN ( SELECT lg2.longitude FROM $wpdb->dt_location_grid as lg2 WHERE lg2.grid_id = lg.admin0_grid_id )
                        WHEN '{$intent_level}' = 'admin1' THEN ( SELECT lg2.longitude FROM $wpdb->dt_location_grid as lg2 WHERE lg2.grid_id = lg.admin1_grid_id )
                        WHEN '{$intent_level}' = 'admin2' THEN ( SELECT lg2.longitude FROM $wpdb->dt_location_grid as lg2 WHERE lg2.grid_id = lg.admin2_grid_id )
                        WHEN '{$intent_level}' = 'admin3' THEN ( SELECT lg2.longitude FROM $wpdb->dt_location_grid as lg2 WHERE lg2.grid_id = lg.admin3_grid_id )
                        WHEN '{$intent_level}' = 'admin4' THEN ( SELECT lg2.longitude FROM $wpdb->dt_location_grid as lg2 WHERE lg2.grid_id = lg.admin4_grid_id )
                        WHEN '{$intent_level}' = 'admin5' THEN ( SELECT lg2.longitude FROM $wpdb->dt_location_grid as lg2 WHERE lg2.grid_id = lg.admin5_grid_id )
                    END
                    ) as longitude,
                    lg.admin0_code as country_code,
                    IF (
                		lg.level <= '{$intent_level_int}', /* condition */
                		lg.level_name /* true */,
                    CASE
                        WHEN '{$intent_level}' = 'admin0' THEN
                            ( SELECT
                                lg3.level_name
                                FROM $wpdb->dt_location_grid as lg3
                            WHERE lg3.grid_id = lg.admin0_grid_id )
                        WHEN '{$intent_level}' = 'admin1' THEN
                            ( SELECT
                                 lg3.level_name
                            FROM $wpdb->dt_location_grid as lg3
                            WHERE lg3.grid_id = lg.admin1_grid_id )
                        WHEN '{$intent_level}' = 'admin2' THEN
                            ( SELECT
                                 lg3.level_name
                            FROM $wpdb->dt_location_grid as lg3
                            WHERE lg3.grid_id = lg.admin2_grid_id )
                        WHEN '{$intent_level}' = 'admin3' THEN
                            ( SELECT
                                lg3.level_name
                                FROM $wpdb->dt_location_grid as lg3
                                WHERE lg3.grid_id = lg.admin3_grid_id )
                        WHEN '{$intent_level}' = 'admin4' THEN
                            ( SELECT
                                lg3.level_name
                                FROM $wpdb->dt_location_grid as lg3
                                WHERE lg3.grid_id = lg.admin4_grid_id )
                        WHEN '{$intent_level}' = 'admin5' THEN
                            ( SELECT lg3.level_name
                                FROM $wpdb->dt_location_grid as lg3
                                WHERE lg3.grid_id = lg.admin5_grid_id )
                    END
                    ) as level,
                   pm1.meta_value as status
                FROM $wpdb->posts as p
                JOIN $wpdb->postmeta as pm1 ON p.ID=pm1.post_id AND pm1.meta_key = 'group_status'
                LEFT JOIN $wpdb->postmeta as pm2 ON p.ID=pm2.post_id AND pm2.meta_key = 'member_count'
                LEFT JOIN $wpdb->postmeta as pm3 ON p.ID=pm3.post_id AND pm3.meta_key = 'start_date'
                LEFT JOIN $wpdb->postmeta as pm4 ON p.ID=pm4.post_id AND pm4.meta_key = 'end_date'
                JOIN $wpdb->postmeta as pm5 ON p.ID=pm5.post_id AND pm5.meta_key = 'group_type' AND ( pm5.meta_value = 'group' OR pm5.meta_value = 'church' )
                LEFT JOIN $wpdb->postmeta as pm6 ON p.ID=pm6.post_id AND pm6.meta_key = 'last_modified'
                JOIN $wpdb->postmeta as pm7 ON p.ID=pm7.post_id AND pm7.meta_key = 'location_grid'
                LEFT JOIN $wpdb->dt_location_grid as lg ON pm7.meta_value=lg.grid_id
                WHERE p.post_type = 'groups';
            ", ARRAY_A);
                // phpcs:enable
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
                'identifier',
                'members',
                'start_date',
                'end_date',
                'type',
                'latitude',
                'longitude',
                'country_code',
                'level',
                'status'
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
    header( 'Content-Disposition: attachment; filename=dt-groups-' . $results['timestamp'] . '.csv' );

    $output = fopen( 'php://output', 'w' );

    fputcsv( $output, $results['columns'] );

    foreach ($results['rows'] as $row) {
        fputcsv( $output, $row );
    }

    fpassthru( $output );
}
