<?php
/**
 * Export Format: CSV export in the COTW Standard
 */

if ( defined( 'ABSPATH' ) ) {
    /**
     * Class DT_Metrics_Export_CSV_COTW
     */
    class DT_Metrics_Export_CSV_COTW extends DT_Metrics_Export_Format_Base {

        public $token = 'csv_cotw';
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
            $formats['csv_cotw'] = [
                'key' => $this->token,
                'label' => 'CSV (COTW Standard)',
                'selectable_types' => [],
            ];
            return $formats;
        }

        public function format_class( $classes ) {
            $classes[$this->token] = __CLASS__;
            return $classes;
        }

        public function export() {
            global $wpdb;

            // PRE-QUERY PARAMETER PREP
            $preferences = [];
            $preferences['subject_data_type'] = 'entity';
            $preferences['site_id'] = dt_get_site_id();
            $preferences['source_code'] = 'Disciple.Tools';
            $preferences['source_name'] = 'Disciple.Tools';
            $preferences['creator_organization'] = null;
            $preferences['creator_organizationCode'] = null;
            $preferences['creator_subOrganization'] = null;
            $preferences['creator_subOrganizationCode'] = null;
            $preferences['rights_dataOwner'] = get_current_user_id();

            $preferences['spatial_intentLevel'] = 'admin2'; // @todo add configuration variable
            $preferences['accessRights'] = 'private';

            // QUERY
            $results = $wpdb->get_results($wpdb->prepare( "
                SELECT
                    SHA2( p.post_title, 256) as title,
                    pm2.meta_value as extent_size, /* member count */
                    FROM_UNIXTIME( pm3.meta_value ) as extent_start_date,
                    FROM_UNIXTIME( pm4.meta_value ) as extent_end_date,
                    %s as subject_data_type, /* entity or event */
                    pm5.meta_value as subject_data_subtype, /* church or group */
                    SHA2(CONCAT(p.ID, %s), 256) as identifier, /* placeholder for SHA key in post-processing */
                    %s as source_code,
                    %s as source_name,
                    %s as creator_organization,
                    %s as creator_organizationCode,
                    %s as creator_subOrganization,
                    %s as creator_subOrganizationCode,
                    %s as rights_dataOwner,
                    pm1.meta_value as isVersionOf,
                    FROM_UNIXTIME( pm6.meta_value) as dateSubmitted,
                    lg.latitude as coverage_latitude,
                    lg.longitude as coverage_longitude,
                    NULL as coverage_spatialReference,
                    CASE
                        WHEN lg.level_name = 'admin0' THEN 'adminLevel0'
                        WHEN lg.level_name = 'admin1' THEN 'adminLevel1'
                        WHEN lg.level_name = 'admin2' THEN 'adminLevel2'
                        WHEN lg.level_name = 'admin3' THEN 'adminLevel3'
                        WHEN lg.level_name = 'admin4' THEN 'adminLevel4'
                        WHEN lg.level_name = 'admin5' THEN 'adminLevel5'
                    END as spatial_accuracyLevel,
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
                $preferences['subject_data_type'],
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

            if ( empty( $results ) ) {
                return $results;
            }

            // POST-QUERY PROCESSING

            // @todo coverage_spatialReference

            // @todo isVersionOf

            $columns = [
                'title',
                'extent:size',
                'extent:startDate',
                'extent:endDate',
                'subject:dataType',
                'subject:dataSubType',
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
                'spatial:accuracyLevel',
                'spatial:intentLevel',
                'accessRights'
            ];

            $args = [
                'columns' => $columns,
                'rows' => $results
            ];

            // RETURN
            $one_time_key = hash( 'sha256', get_current_user_id() . time() . dt_get_site_id() . rand( 0, 999 ) );
            set_transient( $one_time_key, $args, 60 . 60 . 48 );

            echo '<div class="notice notice-warning is-dismissible">
             <p>One time download link (expires in 48 hours):<br> <a href="'.plugin_dir_url( __FILE__ ).'format-csv-cotw.php?csv='.$one_time_key.'" target="_blank">'.plugin_dir_url( __FILE__ ).'format-csv-cotw.php?csv='.$one_time_key.'</a></p>
         </div>';
        }
    }
    DT_Metrics_Export_CSV_COTW::instance();

}

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
    header( 'Content-Disposition: attachment; filename=data.csv' );

    $output = fopen( 'php://output', 'w' );

    fputcsv( $output, $results['columns'] );

    foreach ($results['rows'] as $row ) {
        fputcsv( $output, $row );
    }

    fpassthru( $output );
}



