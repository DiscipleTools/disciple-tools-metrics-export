<?php
/**
 * @todo 1. Rename DT_Metrics_Export_Simple_Template
 * @todo 2. Rename $token
 * @todo 3. Rename $label
 * @todo 4. Replace MYSQL query in the query function
 * @todo 5. Update required_once file name to the name of this file.
 */

/**
 * @todo 1. Rename DT_Metrics_Export_Simple_Template (3 uses)
 */
if ( defined( 'ABSPATH' ) ) { // confirm wp is loaded

    class DT_Metrics_Export_Simple_Template
    {
        /**
         * @todo 2. Rename $token
         * @todo 3. Rename $label
         */
        public $token = 'simple_csv_template';
        public $label = 'Simple CSV Template';

        /**
         * The format function builds the template of the format. From this format template, multiple configurations can
         * be created and stored.
         *
         * @note This function does not need modified for the simplest use of the export template.
         *
         * @link https://github.com/DiscipleTools/disciple-tools-metrics-export/master/includes/format-utilities.php:27 get_dt_metrics_export_base_format():
         *
         * @param $format
         * @return mixed
         */
        public function format( $format) {
            /* Build base template of a format*/
            $format[$this->token] = get_dt_metrics_export_base_format();

            /* Add key and label for format */
            $format[$this->token]['key'] = $this->token;
            $format[$this->token]['label'] = $this->label;

            // remove raw
            $format[$this->token]['locations'] = [
                'all' => [
                    'admin2' => 'All',
                ],
            ];

            $format[$this->token]['types'] = [];

            return $format;
        }

        /**
         * This function is the create link function called by the tab "creat links".
         *
         * @note This function does not need modified for the simplest use of the export template.
         *
         * @param $response
         * @return false|int|mixed
         */
        public function export( $response) {
            if ( !isset( $response['configuration'], $response['destination'] ) ) {
                return false;
            }

            $args = [
                'timestamp' => current_time( 'Y-m-d H:i:s' ),
                'columns' => [],
                'rows' => [],
                'export' => $response,
                'link' => '',
                'key' => '',
            ];

            /**
             * Create results according to selected type
             */
            $args['rows'] = $this->query();
            $args['columns'] = array_keys( $args['rows'][0] );

            // kill if no results
            if (empty( $args['rows'] )) {
                echo '<div class="notice notice-warning is-dismissible">
                     <p>No results found for this configuration. Likely, there are no records for the countries you specified. Could not generate csv file.</p>
                 </div>';
                return $response['configuration'] ?? 0;
            }

            // destination
            $one_time_key = hash( 'sha256', get_current_user_id() . time() . dt_get_site_id() . rand( 0, 999 ) );
            $postid = $response['configuration'];
            switch ($response['destination']) {
                case 'expiring48':
                    $args['link'] = esc_url( plugin_dir_url( __FILE__ ) ) . esc_url( basename( __FILE__ ) ) . '?expiring48=' . esc_attr( $one_time_key );
                    $args['key'] = $one_time_key;
                    set_transient( 'metrics_exports_' . $one_time_key, $args, 60 . 60 . 48 );
                    echo '<div class="notice notice-warning is-dismissible">
                             <p>
                                 Link expiring in 48 hours:<br>
                                 <a href="' . esc_url( plugin_dir_url( __FILE__ ) ) . esc_url( basename( __FILE__ ) ) . '?expiring48=' . esc_attr( $one_time_key ) . '"
                                 target="_blank">' . esc_url( plugin_dir_url( __FILE__ ) ) . esc_url( basename( __FILE__ ) ) . '?expiring48=' . esc_attr( $one_time_key ) . '
                                 </a>
                             </p>
                         </div>';
                    break;
                case 'expiring360':
                    $args['link'] = esc_url( plugin_dir_url( __FILE__ ) ) . esc_url( basename( __FILE__ ) ) . '?expiring360=' . esc_attr( $one_time_key );
                    $args['key'] = $one_time_key;
                    set_transient( 'metrics_exports_' . $one_time_key, $args, 60 . 60 . 360 );
                    echo '<div class="notice notice-warning is-dismissible">
                             <p>
                                 Link expiring in 15 days:<br>
                                 <a href="' . esc_url( plugin_dir_url( __FILE__ ) ) . esc_url( basename( __FILE__ ) ) . '?expiring360=' . esc_attr( $one_time_key ) . '"
                                 target="_blank">' . esc_url( plugin_dir_url( __FILE__ ) ) . esc_url( basename( __FILE__ ) ) . '?expiring360=' . esc_attr( $one_time_key ) . '
                                 </a>
                             </p>
                         </div>';
                    break;
                case 'download':
                    $args['link'] = esc_url( plugin_dir_url( __FILE__ ) ) . esc_url( basename( __FILE__ ) ) . '?download=' . esc_attr( $one_time_key );
                    $args['key'] = $one_time_key;
                    update_post_meta( $postid, 'download_' . $one_time_key, $args );
                    echo '<div class="notice notice-warning is-dismissible">
                             <p>
                                 One time download link:<br>
                                 ' . esc_url( plugin_dir_url( __FILE__ ) ) . esc_url( basename( __FILE__ ) ) . '?download=' . esc_attr( $one_time_key ) . '
                             </p>
                         </div>';
                    break;
                case 'permanent':
                    $args['link'] = esc_url( plugin_dir_url( __FILE__ ) ) . esc_url( basename( __FILE__ ) ) . '?permanent=' . esc_attr( $one_time_key );
                    $args['key'] = $one_time_key;
                    update_post_meta( $postid, 'permanent_' . $one_time_key, $args );
                    echo '<div class="notice notice-warning is-dismissible">
                             <p>
                                 Permanent link (must be deleted manually):<br>
                                 <a href="' . esc_url( plugin_dir_url( __FILE__ ) ) . esc_url( basename( __FILE__ ) ) . '?permanent=' . esc_attr( $one_time_key ) . '"
                                 target="_blank">' . esc_url( plugin_dir_url( __FILE__ ) ) . esc_url( basename( __FILE__ ) ) . '?permanent=' . esc_attr( $one_time_key ) . '
                                 </a>
                             </p>
                         </div>';
                    break;
            }

            // return configuration selection from before export
            return $response['configuration'] ?? 0; // return int config id, so ui reloads on same config
        }

        /**
         * This function is mainly used by the permanent link, which rebuilds each time requested.
         *
         * @note This function does not need modified for the simplest use of the export template.
         *
         * @param $key
         * @param array $args
         * @return array|false
         */
        public function update( $key, array $args) {
            if ( !isset( $args['timestamp'], $args['link'], $args['export'], $args['export']['configuration'], $args['export']['destination'] ) ) {
                return false;
            }

            // timestamp
            $args['timestamp'] = current_time( 'Y-m-d H:i:s' );

            // Create results according to selected type
            $args['rows'] = $this->query();
            $args['columns'] = array_keys( $args['rows'][0] );

            // update destination
            $postid = $args['export']['configuration'];
            switch ($args['export']['destination']) {
                case 'expiring48':
                    set_transient( 'metrics_exports_' . $key, $args, 60 . 60 . 48 );
                    break;
                case 'expiring360':
                    set_transient( 'metrics_exports_' . $key, $args, 60 . 60 . 360 );
                    break;
                case 'download':
                    update_post_meta( $postid, 'download_' . $key, $args );
                    break;
                case 'permanent':
                    update_post_meta( $postid, 'permanent_' . $key, $args );
                    break;
            }

            return $args;
        }

        /**
         * @todo 4. Replace MYSQL query in the query function
         * @todo Build this query to output the columns of data you need, and it will be converted to a csv through his template
         *
         * @return array|object|null
         */
        public function query() {
            global $wpdb;
            $results = $wpdb->get_results("
                   SELECT
                        p.ID,
                        p.post_title as name,
                        pm.meta_value as status
                        FROM $wpdb->posts as p
                        LEFT JOIN $wpdb->postmeta as pm ON p.ID=pm.post_id AND pm.meta_key = 'overall_status'
                        WHERE post_type = 'contacts';
                ", ARRAY_A);
            return $results;
        }

        /**
         * This function builds the class used by the build tab.
         *
         * @note This function does not need modified for the simplest use of the export template.
         *
         * @param $classes
         * @return mixed
         */
        public function format_class( $classes) {
            $classes[$this->token] = __CLASS__;
            return $classes;
        }

        /**
         * Singleton and Construct Functions
         * @note This function does not need modified
         * @var null
         */
        private static $_instance = null;
        public static function instance() {
            if (is_null( self::$_instance )) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }
        public function __construct() {
            add_filter( 'dt_metrics_export_format', [ $this, 'format' ], 10, 1 );
            add_filter( 'dt_metrics_export_register_format_class', [ $this, 'format_class' ], 10, 1 );
        }
    }
    DT_Metrics_Export_Simple_Template::instance();
}


/**
 * CREATE CSV FILE
 * This section only loads if accessed directly.
 * These 4 sections support expiring48, expiring360, download, permanent links
 *
 * @note This function does not need modified for the simplest use of the export template.
 */
if ( !defined( 'ABSPATH' )) {

    // @codingStandardsIgnoreLine
    require($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php'); // loads the wp framework when called

    /**
     * Lookup from available transients for matching token given in the url
     */
    if (isset( $_GET['expiring48'] ) || isset( $_GET['expiring360'] )) {

        $token = isset( $_GET['expiring48'] ) ? sanitize_text_field( wp_unslash( $_GET['expiring48'] ) ) : sanitize_text_field( wp_unslash( $_GET['expiring360'] ) );
        $results = get_transient( 'metrics_exports_' . $token );
        if (empty( $results )) {
            echo 'Link no longer available';
            return;
        }

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=dt-csv-' . strtotime( $results['timestamp'] ) . '.csv' );

        $output = fopen( 'php://output', 'w' );

        fputcsv( $output, $results['columns'] );

        foreach ($results['rows'] as $row) {
            fputcsv( $output, $row );
        }

        fpassthru( $output );

    // The download link deletes itself after being collected.
    } else if (isset( $_GET['download'] )) {
        global $wpdb;

        $token = sanitize_text_field( wp_unslash( $_GET['download'] ) );

        $raw = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE meta_key = %s LIMIT 1", 'download_' . $token ), ARRAY_A );

        if (empty( $raw )) {
            echo 'No link found';
            return;
        }
        $results = maybe_unserialize( $raw['meta_value'] );

        delete_post_meta( $raw['post_id'], $raw['meta_key'] ); // delete after collection

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=dt-csv-' . strtotime( $results['timestamp'] ) . '.csv' );

        $output = fopen( 'php://output', 'w' );

        fputcsv( $output, $results['columns'] );

        foreach ($results['rows'] as $row) {
            fputcsv( $output, $row );
        }

        fpassthru( $output );

    /**
     * The permanent link requires reloading this page in the context of WP and using the update function to get a new
     * snapshot. If the permanent link is not needed, you could delete this 'if' section and the update function in the
     * class.
     *
     * @todo 5. Update required_once file name to the name of this file.
     */
    } else if (isset( $_GET['permanent'] )) {
        global $wpdb;

        // test if key exists
        $token = sanitize_text_field( wp_unslash( $_GET['permanent'] ) );
        $raw = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = %s", 'permanent_' . $token ) );
        if (empty( $raw )) {
            echo 'No link found';
            return;
        }

        /**
         * @todo 5. Update required_once file name to the name of this file.
         */
        require_once( 'simple-metrics-export-template.php' );


        $raw = maybe_unserialize( $raw );
        $results = DT_Metrics_Export_Simple_Template::instance()->update( $token, $raw );

        // load export header
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=dt-csv-' . strtotime( $results['timestamp'] ) . '.csv' );

        // build csv
        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, $results['columns'] );
        foreach ($results['rows'] as $row) {
            fputcsv( $output, $row );
        }
        fpassthru( $output );
    } else {
        echo 'parameters not set correctly';
        return;
    }
}
