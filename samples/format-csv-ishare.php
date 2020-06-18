<?php
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
if ( defined( 'ABSPATH' ) ) {
    /**
     * Class DT_Metrics_Export_CSV_COTW
     */
    class DT_Metrics_Export_CSV_COTW extends DT_Metrics_Export_Format_Base {

        public $token = 'csv_ishare';
        public $label = 'CSV (iShare)';


        private static $_instance = null;
        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * DT_Metrics_Export_CSV_COTW constructor.
         */
        public function __construct() {
            parent::__construct();
            add_filter( 'dt_metrics_export_format', [ $this, 'format' ], 10, 1 );
            add_filter( 'dt_metrics_export_register_format_class', [ $this, 'format_class' ], 10, 1 );
        } // End __construct()

        public function format( $formats ) {
            $formats[$this->token] = [
                'key' => $this->token,
                'label' => $this->label,
                'selectable_types' => [],
            ];
            return $formats;
        }

        public function format_class( $classes ) {
            $classes[$this->token] = __CLASS__;
            return $classes;
        }

        public function export( $response ) {
            global $wpdb;

            if ( ! isset( $response['all_locations'] ) || empty( $response['all_locations'] ) ) {
                return new WP_Error( __METHOD__, 'All locations parameter not set.' );
            }

            // PRE-QUERY PARAMETER PREP
            $preferences = [];
            $preferences['subject_dataType'] = 'entity';
            $preferences['site_id'] = dt_get_site_id();
            $preferences['source_code'] = 'Disciple.Tools';
            $preferences['source_name'] = 'Disciple.Tools';
            $preferences['creator_organization'] = null;
            $preferences['creator_organizationCode'] = dt_get_site_id();
            $preferences['creator_subOrganization'] = null;
            $preferences['creator_subOrganizationCode'] = null;
            $preferences['rights_dataOwner'] = get_current_user();
            $preferences['accessRights'] = 'private';

            /**
             * PROCESS ALL LOCATIONS AT THE SAME ADMIN LEVEL OR PROCESS COUNTRY BY COUNTRY
             *
             * If processed country by country, the same query is looped over all the countries selected and returns
             * the query according to the country level. @todo confirm on large systems with many countries that this does not time out.
             */
            $results = [];
            if ( 'country_by_country' === $response['all_locations'] ) {
                /**
                 *  INDIVIDUAL ADMIN LEVELS SET FOR EACH COUNTRY
                 */
                if ( ! isset( $response['selected_locations'] ) || empty( $response['selected_locations'] ) ) {
                    return new WP_Error( __METHOD__, 'Selected locations parameter not set.' );
                }
                foreach ( $response['selected_locations'] as $grid_id => $level ) {
                    if ( 'disabled' === $level ) {
                        continue;
                    }

                    $intent_level = $level;
                    switch ($intent_level) {
                        case 'admin0':
                            $preferences['spatial_intentLevel'] = 'adminLevel0';
                            $intent_level_int = 0;
                            break;
                        case 'admin1':
                            $preferences['spatial_intentLevel'] = 'adminLevel1';
                            $intent_level_int = 1;
                            break;
                        case 'admin3':
                            $preferences['spatial_intentLevel'] = 'adminLevel3';
                            $intent_level_int = 3;
                            break;
                        case 'admin4':
                            $preferences['spatial_intentLevel'] = 'adminLevel4';
                            $intent_level_int = 4;
                            break;
                        case 'admin5':
                            $preferences['spatial_intentLevel'] = 'adminLevel5';
                            $intent_level_int = 5;
                            break;
                        case 'raw':
                            $preferences['spatial_intentLevel'] = 'adminLevel5';
                            $intent_level_int = 10;
                            break;
                        case 'admin2':
                        default:
                            $preferences['spatial_intentLevel'] = 'adminLevel2';
                            $intent_level_int = 2;
                            break;
                    }

                    // phpcs:disable
                    $loop_results = $wpdb->get_results( $wpdb->prepare( "
                            SELECT
                                SHA2( p.post_title, 256) as title,
                                pm2.meta_value as extent_size, /* member count */
                                DATE_FORMAT( FROM_UNIXTIME( pm3.meta_value ), '%%Y-%%m-%%dT%%TZ') as extent_start_date,
                                DATE_FORMAT( FROM_UNIXTIME( pm4.meta_value ), '%%Y-%%m-%%dT%%TZ') as extent_end_date,
                                %s as subject_dataType, /* entity or event */
                                pm5.meta_value as subject_dataSubtype, /* church or group */
                                NULL as subject_peopleGroupCode,
                                NULL as subject_peopleGroupReference,
                                SHA2(CONCAT(p.ID, %s), 256) as identifier, /* placeholder for SHA key in post-processing */
                                %s as source_code,
                                %s as source_name,
                                %s as creator_organization,
                                %s as creator_organizationCode,
                                %s as creator_subOrganization,
                                %s as creator_subOrganizationCode,
                                %s as rights_dataOwner,
                                   CASE
                                    WHEN pm1.meta_value = 'inactive' THEN 'deleted'
                                    WHEN pm1.meta_value = 'active' THEN 'updated'
                                END as isVersionOf,
                                DATE_FORMAT( FROM_UNIXTIME( pm6.meta_value ), '%%Y-%%m-%%dT%%TZ') as dateSubmitted,
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
                                ) as coverage_latitude,
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
                                ) as coverage_longitude,
                                'EPSG4326' as coverage_spatialReference,
                                lg.admin0_code as coverage_countryCode,
                                IF (
                                    lg.level <= '{$intent_level_int}', /* condition */
                                    CASE
                                    WHEN lg.level_name = 'admin0' THEN 'adminLevel0'
                                    WHEN lg.level_name = 'admin1' THEN 'adminLevel1'
                                    WHEN lg.level_name = 'admin2' THEN 'adminLevel2'
                                    WHEN lg.level_name = 'admin3' THEN 'adminLevel3'
                                    WHEN lg.level_name = 'admin4' THEN 'adminLevel4'
                                    WHEN lg.level_name = 'admin5' THEN 'adminLevel5'
                                END /* true */,
                                CASE
                                    WHEN '{$intent_level}' = 'admin0' THEN
                                        ( SELECT
                                            CASE
                                                WHEN lg3.level_name = 'admin0' THEN 'adminLevel0'
                                                WHEN lg3.level_name = 'admin1' THEN 'adminLevel1'
                                                WHEN lg3.level_name = 'admin2' THEN 'adminLevel2'
                                                WHEN lg3.level_name = 'admin3' THEN 'adminLevel3'
                                                WHEN lg3.level_name = 'admin4' THEN 'adminLevel4'
                                                WHEN lg3.level_name = 'admin5' THEN 'adminLevel5'
                                            END
                                            FROM $wpdb->dt_location_grid as lg3
                                        WHERE lg3.grid_id = lg.admin0_grid_id )
                                    WHEN '{$intent_level}' = 'admin1' THEN
                                        ( SELECT
                                             CASE
                                                WHEN lg3.level_name = 'admin0' THEN 'adminLevel0'
                                                WHEN lg3.level_name = 'admin1' THEN 'adminLevel1'
                                                WHEN lg3.level_name = 'admin2' THEN 'adminLevel2'
                                                WHEN lg3.level_name = 'admin3' THEN 'adminLevel3'
                                                WHEN lg3.level_name = 'admin4' THEN 'adminLevel4'
                                                WHEN lg3.level_name = 'admin5' THEN 'adminLevel5'
                                            END
                                        FROM $wpdb->dt_location_grid as lg3
                                        WHERE lg3.grid_id = lg.admin1_grid_id )
                                    WHEN '{$intent_level}' = 'admin2' THEN
                                        ( SELECT
                                             CASE
                                                WHEN lg3.level_name = 'admin0' THEN 'adminLevel0'
                                                WHEN lg3.level_name = 'admin1' THEN 'adminLevel1'
                                                WHEN lg3.level_name = 'admin2' THEN 'adminLevel2'
                                                WHEN lg3.level_name = 'admin3' THEN 'adminLevel3'
                                                WHEN lg3.level_name = 'admin4' THEN 'adminLevel4'
                                                WHEN lg3.level_name = 'admin5' THEN 'adminLevel5'
                                            END
                                        FROM $wpdb->dt_location_grid as lg3
                                        WHERE lg3.grid_id = lg.admin2_grid_id )
                                    WHEN '{$intent_level}' = 'admin3' THEN
                                        ( SELECT
                                            CASE
                                                WHEN lg3.level_name = 'admin0' THEN 'adminLevel0'
                                                WHEN lg3.level_name = 'admin1' THEN 'adminLevel1'
                                                WHEN lg3.level_name = 'admin2' THEN 'adminLevel2'
                                                WHEN lg3.level_name = 'admin3' THEN 'adminLevel3'
                                                WHEN lg3.level_name = 'admin4' THEN 'adminLevel4'
                                                WHEN lg3.level_name = 'admin5' THEN 'adminLevel5'
                                            END
                                            FROM $wpdb->dt_location_grid as lg3
                                            WHERE lg3.grid_id = lg.admin3_grid_id )
                                    WHEN '{$intent_level}' = 'admin4' THEN
                                        ( SELECT
                                            CASE
                                                WHEN lg3.level_name = 'admin0' THEN 'adminLevel0'
                                                WHEN lg3.level_name = 'admin1' THEN 'adminLevel1'
                                                WHEN lg3.level_name = 'admin2' THEN 'adminLevel2'
                                                WHEN lg3.level_name = 'admin3' THEN 'adminLevel3'
                                                WHEN lg3.level_name = 'admin4' THEN 'adminLevel4'
                                                WHEN lg3.level_name = 'admin5' THEN 'adminLevel5'
                                            END
                                            FROM $wpdb->dt_location_grid as lg3
                                            WHERE lg3.grid_id = lg.admin4_grid_id )
                                    WHEN '{$intent_level}' = 'admin5' THEN
                                        ( SELECT
                                            CASE
                                                WHEN lg3.level_name = 'admin0' THEN 'adminLevel0'
                                                WHEN lg3.level_name = 'admin1' THEN 'adminLevel1'
                                                WHEN lg3.level_name = 'admin2' THEN 'adminLevel2'
                                                WHEN lg3.level_name = 'admin3' THEN 'adminLevel3'
                                                WHEN lg3.level_name = 'admin4' THEN 'adminLevel4'
                                                WHEN lg3.level_name = 'admin5' THEN 'adminLevel5'
                                            END
                                            FROM $wpdb->dt_location_grid as lg3
                                            WHERE lg3.grid_id = lg.admin5_grid_id )
                                END
                                ) as spatial_accuracyLevel,
                                %s as spatial_intentLevel,
                                %s as accessRights
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
                        ",
                        $preferences['subject_dataType'],
                        $preferences['site_id'],
                        $preferences['source_code'],
                        $preferences['source_name'],
                        $preferences['creator_organization'],
                        $preferences['creator_organizationCode'],
                        $preferences['creator_subOrganization'],
                        $preferences['creator_subOrganizationCode'],
                        $preferences['rights_dataOwner'],
                        $preferences['spatial_intentLevel'],
                        $preferences['accessRights'],
                        $grid_id
                    ), ARRAY_A);
                    // phpcs:enable

                    if ( empty( $loop_results ) ) {
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
                        $preferences['spatial_intentLevel'] = 'adminLevel0';
                        $intent_level_int = 0;
                        break;
                    case 'admin1':
                        $preferences['spatial_intentLevel'] = 'adminLevel1';
                        $intent_level_int = 1;
                        break;
                    case 'admin3':
                        $preferences['spatial_intentLevel'] = 'adminLevel3';
                        $intent_level_int = 3;
                        break;
                    case 'admin4':
                        $preferences['spatial_intentLevel'] = 'adminLevel4';
                        $intent_level_int = 4;
                        break;
                    case 'admin5':
                        $preferences['spatial_intentLevel'] = 'adminLevel5';
                        $intent_level_int = 5;
                        break;
                    case 'raw':
                        $preferences['spatial_intentLevel'] = 'adminLevel5';
                        $intent_level_int = 10;
                        break;
                    case 'admin2':
                    default:
                        $preferences['spatial_intentLevel'] = 'adminLevel2';
                        $intent_level_int = 2;
                        break;
                }

                // phpcs:disable
                $results = $wpdb->get_results( $wpdb->prepare( "
                SELECT
                    SHA2( p.post_title, 256) as title,
                    pm2.meta_value as extent_size, /* member count */
                    DATE_FORMAT( FROM_UNIXTIME( pm3.meta_value ), '%%Y-%%m-%%dT%%TZ') as extent_start_date,
                    DATE_FORMAT( FROM_UNIXTIME( pm4.meta_value ), '%%Y-%%m-%%dT%%TZ') as extent_end_date,
                    %s as subject_dataType, /* entity or event */
                    pm5.meta_value as subject_dataSubtype, /* church or group */
                    NULL as subject_peopleGroupCode,
                    NULL as subject_peopleGroupReference,
                    SHA2(CONCAT(p.ID, %s), 256) as identifier, /* placeholder for SHA key in post-processing */
                    %s as source_code,
                    %s as source_name,
                    %s as creator_organization,
                    %s as creator_organizationCode,
                    %s as creator_subOrganization,
                    %s as creator_subOrganizationCode,
                    %s as rights_dataOwner,
                       CASE
                        WHEN pm1.meta_value = 'inactive' THEN 'deleted'
                        WHEN pm1.meta_value = 'active' THEN 'updated'
                    END as isVersionOf,
                    DATE_FORMAT( FROM_UNIXTIME( pm6.meta_value ), '%%Y-%%m-%%dT%%TZ') as dateSubmitted,
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
                    ) as coverage_latitude,
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
                    ) as coverage_longitude,
                    'EPSG4326' as coverage_spatialReference,
                    lg.admin0_code as coverage_countryCode,
                    IF (
                		lg.level <= '{$intent_level_int}', /* condition */
                		CASE
                        WHEN lg.level_name = 'admin0' THEN 'adminLevel0'
                        WHEN lg.level_name = 'admin1' THEN 'adminLevel1'
                        WHEN lg.level_name = 'admin2' THEN 'adminLevel2'
                        WHEN lg.level_name = 'admin3' THEN 'adminLevel3'
                        WHEN lg.level_name = 'admin4' THEN 'adminLevel4'
                        WHEN lg.level_name = 'admin5' THEN 'adminLevel5'
                    END /* true */,
                    CASE
                        WHEN '{$intent_level}' = 'admin0' THEN
                            ( SELECT
                                CASE
                                    WHEN lg3.level_name = 'admin0' THEN 'adminLevel0'
                                    WHEN lg3.level_name = 'admin1' THEN 'adminLevel1'
                                    WHEN lg3.level_name = 'admin2' THEN 'adminLevel2'
                                    WHEN lg3.level_name = 'admin3' THEN 'adminLevel3'
                                    WHEN lg3.level_name = 'admin4' THEN 'adminLevel4'
                                    WHEN lg3.level_name = 'admin5' THEN 'adminLevel5'
                                END
                                FROM $wpdb->dt_location_grid as lg3
                            WHERE lg3.grid_id = lg.admin0_grid_id )
                        WHEN '{$intent_level}' = 'admin1' THEN
                            ( SELECT
                                 CASE
                                    WHEN lg3.level_name = 'admin0' THEN 'adminLevel0'
                                    WHEN lg3.level_name = 'admin1' THEN 'adminLevel1'
                                    WHEN lg3.level_name = 'admin2' THEN 'adminLevel2'
                                    WHEN lg3.level_name = 'admin3' THEN 'adminLevel3'
                                    WHEN lg3.level_name = 'admin4' THEN 'adminLevel4'
                                    WHEN lg3.level_name = 'admin5' THEN 'adminLevel5'
                                END
                            FROM $wpdb->dt_location_grid as lg3
                            WHERE lg3.grid_id = lg.admin1_grid_id )
                        WHEN '{$intent_level}' = 'admin2' THEN
                            ( SELECT
                                 CASE
                                    WHEN lg3.level_name = 'admin0' THEN 'adminLevel0'
                                    WHEN lg3.level_name = 'admin1' THEN 'adminLevel1'
                                    WHEN lg3.level_name = 'admin2' THEN 'adminLevel2'
                                    WHEN lg3.level_name = 'admin3' THEN 'adminLevel3'
                                    WHEN lg3.level_name = 'admin4' THEN 'adminLevel4'
                                    WHEN lg3.level_name = 'admin5' THEN 'adminLevel5'
                                END
                            FROM $wpdb->dt_location_grid as lg3
                            WHERE lg3.grid_id = lg.admin2_grid_id )
                        WHEN '{$intent_level}' = 'admin3' THEN
                            ( SELECT
                                CASE
                                    WHEN lg3.level_name = 'admin0' THEN 'adminLevel0'
                                    WHEN lg3.level_name = 'admin1' THEN 'adminLevel1'
                                    WHEN lg3.level_name = 'admin2' THEN 'adminLevel2'
                                    WHEN lg3.level_name = 'admin3' THEN 'adminLevel3'
                                    WHEN lg3.level_name = 'admin4' THEN 'adminLevel4'
                                    WHEN lg3.level_name = 'admin5' THEN 'adminLevel5'
                                END
                                FROM $wpdb->dt_location_grid as lg3
                                WHERE lg3.grid_id = lg.admin3_grid_id )
                        WHEN '{$intent_level}' = 'admin4' THEN
                            ( SELECT
                                CASE
                                    WHEN lg3.level_name = 'admin0' THEN 'adminLevel0'
                                    WHEN lg3.level_name = 'admin1' THEN 'adminLevel1'
                                    WHEN lg3.level_name = 'admin2' THEN 'adminLevel2'
                                    WHEN lg3.level_name = 'admin3' THEN 'adminLevel3'
                                    WHEN lg3.level_name = 'admin4' THEN 'adminLevel4'
                                    WHEN lg3.level_name = 'admin5' THEN 'adminLevel5'
                                END
                                FROM $wpdb->dt_location_grid as lg3
                                WHERE lg3.grid_id = lg.admin4_grid_id )
                        WHEN '{$intent_level}' = 'admin5' THEN
                            ( SELECT
                                CASE
                                    WHEN lg3.level_name = 'admin0' THEN 'adminLevel0'
                                    WHEN lg3.level_name = 'admin1' THEN 'adminLevel1'
                                    WHEN lg3.level_name = 'admin2' THEN 'adminLevel2'
                                    WHEN lg3.level_name = 'admin3' THEN 'adminLevel3'
                                    WHEN lg3.level_name = 'admin4' THEN 'adminLevel4'
                                    WHEN lg3.level_name = 'admin5' THEN 'adminLevel5'
                                END
                                FROM $wpdb->dt_location_grid as lg3
                                WHERE lg3.grid_id = lg.admin5_grid_id )
                    END
                    ) as spatial_accuracyLevel,
                    %s as spatial_intentLevel,
                    %s as accessRights
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
            ",
                    $preferences['subject_dataType'],
                    $preferences['site_id'],
                    $preferences['source_code'],
                    $preferences['source_name'],
                    $preferences['creator_organization'],
                    $preferences['creator_organizationCode'],
                    $preferences['creator_subOrganization'],
                    $preferences['creator_subOrganizationCode'],
                    $preferences['rights_dataOwner'],
                    $preferences['spatial_intentLevel'],
                    $preferences['accessRights']
                ), ARRAY_A);
                // phpcs:enable
            }


            // kill if no results
            if ( empty( $results ) ) {
                echo '<div class="notice notice-warning is-dismissible">
                     <p>No results found for this configuration. Likely, there are no records for the countries you specified. Could not generate csv file.</p>
                 </div>';
                return $response['configuration'] ?? 0;
            }

            // Setup columns
            $columns = [
                'title',
                'extent:size',
                'extent:startDate',
                'extent:endDate',
                'subject:dataType',
                'subject:dataSubtype',
                'subject:peopleGroupCode',
                'subject:peopleGroupReference',
                'identifier',
                'source:code',
                'source:name',
                'creator:organization',
                'creator:organizationCode',
                'creator:subOrganization',
                'creator:subOrganizationCode',
                'rights:dataOwner',
                'isVersionOf',
                'dateSubmitted',
                'coverage:latitude',
                'coverage:longitude',
                'coverage:spatialReference',
                'coverage:countryCode',
                'spatial:accuracyLevel',
                'spatial:intentLevel',
                'accessRights'
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
                     <p>One time download link (expires in 48 hours):<br> <a href="'. esc_url( plugin_dir_url( __FILE__ ) ).'format-csv-ishare.php?csv='.esc_attr( $one_time_key ).'" target="_blank">'.esc_url( plugin_dir_url( __FILE__ ) ).'format-csv-ishare.php?csv='.esc_attr( $one_time_key ).'</a></p>
                 </div>';

            // return configuration selection from before export
            return $response['configuration'] ?? 0; // return int config id, so ui reloads on same config
        }
    }
    DT_Metrics_Export_CSV_COTW::instance();

}


/**
 * CREATE CSV FILE
 */
if ( ! defined( 'ABSPATH' ) ) {

   // @codingStandardsIgnoreLine
    require( $_SERVER[ 'DOCUMENT_ROOT' ] . '/wp-load.php' ); // loads the wp framework when called

    if ( ! isset( $_GET['csv'] ) ) {
        wp_die( 'No parameter found' );
    }

    $token = sanitize_text_field( wp_unslash( $_GET['csv'] ) );
    $results = get_transient( $token );
    if ( empty( $results ) ) {
        echo 'Link no longer available';
        return;
    }

    delete_transient( $token );

    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=ishare-'.$results['timestamp'].'.csv' );

    $output = fopen( 'php://output', 'w' );

    fputcsv( $output, $results['columns'] );

    foreach ($results['rows'] as $row ) {
        fputcsv( $output, $row );
    }

    fpassthru( $output );
}



