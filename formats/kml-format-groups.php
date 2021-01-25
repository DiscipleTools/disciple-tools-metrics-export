<?php

/**
 * LOAD DATA TYPE FORMAT
 */
if (defined( 'ABSPATH' )) {
    /**
     * Class DT_Metrics_Export_CSV
     */
    class DT_Metrics_Export_KML_Groups
    {

        public $token = 'kml_groups';
        public $label = 'KML (Groups)';

        public function format( $format ) {
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

            $format[$this->token]['types'] = [
                'groups' => [
                    'active' => [
                        'key' => 'active',
                        'label' => 'All active groups. Fields: [name, status, member_count, leader_count, group_type, lng, lat]'
                    ],
                    'basic' => [
                        'key' => 'basic',
                        'label' => 'All groups. Fields: [name, status, member_count, leader_count, group_type, lng, lat]'
                    ],
                ],
            ];

            return $format;
        }

        public function create( $response) {
            if ( ! isset( $response['type']['groups'], $response['configuration'], $response['destination'] ) ){
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
            if ( 'basic' === $args['export']['type']['groups'] ) {
                $args['rows'] = $this->query_basic();
                $args['columns'] = array_keys( $args['rows'][0] );
            }
            else if ( 'active' === $args['export']['type']['groups'] ) {
                $args['rows'] = $this->query_active();
                $args['columns'] = array_keys( $args['rows'][0] );
            }


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
            switch ( $response['destination'] ) {
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

        public function update( $key, array $args ) {
            if ( empty( $key ) ){
                return false;
            }
            if ( ! isset( $args['timestamp'], $args['link'], $args['export'], $args['export']['configuration'], $args['export']['destination'], $args['export']['type']['groups'] ) ) {
                return false;
            }

            $args['timestamp'] = current_time( 'Y-m-d H:i:s' );

            /**
             * Create results according to selected type
             */
            if ( 'basic' === $args['export']['type']['groups'] ) {
                $args['rows'] = $this->query_basic();
                $args['columns'] = array_keys( $args['rows'][0] );
            }
            else if ( 'active' === $args['export']['type']['groups'] ) {
                $args['rows'] = $this->query_active();
                $args['columns'] = array_keys( $args['rows'][0] );
            }


            // update destination
            $postid = $args['export']['configuration'];
            switch ( $args['export']['destination'] ) {
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

        public function query_active() {
            global $wpdb;
            if ( DT_Mapbox_API::get_key() ) {
                $results = $wpdb->get_results("
                    SELECT
                    p.ID,
                    p.post_title as name,
                    pm.meta_value as status,
                    pm1.meta_value as member_count,
                    pm2.meta_value as leader_count,
                    pm3.meta_value as group_type,
                    IF ( lgm.lng, lgm.lng, NULL ) as lng,
                    IF ( lgm.lng, lgm.lat, NULL) as lat
                    FROM $wpdb->posts as p
                    JOIN $wpdb->postmeta as pmlgm ON p.ID=pmlgm.post_id AND pmlgm.meta_key = 'location_grid_meta'
                    JOIN $wpdb->dt_location_grid_meta as lgm ON pmlgm.meta_value=lgm.grid_meta_id
                    LEFT JOIN $wpdb->postmeta as pm ON p.ID=pm.post_id AND pm.meta_key = 'group_status'
                    LEFT JOIN $wpdb->postmeta as pm1 ON pm1.post_id=p.ID AND pm1.meta_key = 'member_count'
                    LEFT JOIN $wpdb->postmeta as pm2 ON pm2.post_id=p.ID AND pm2.meta_key = 'leader_count'
                    LEFT JOIN $wpdb->postmeta as pm3 ON pm3.post_id=p.ID AND pm3.meta_key = 'group_type'
                    WHERE p.post_type = 'groups' AND pm.meta_value = 'active';
                ", ARRAY_A);
            } else {
                $results = $wpdb->get_results("
                    SELECT
                    p.ID,
                    p.post_title as name,
                    pm.meta_value as status,
                    pm1.meta_value as member_count,
                    pm2.meta_value as leader_count,
                    pm3.meta_value as group_type,
                    IF ( lg.longitude, lg.longitude, NULL ) as lng,
                    IF ( lg.latitude, lg.latitude, NULL) as lat
                    FROM $wpdb->posts as p
                    JOIN $wpdb->postmeta as pmlg ON p.ID=pmlg.post_id AND pmlg.meta_key = 'location_grid'
                    JOIN $wpdb->dt_location_grid as lg ON pmlg.meta_value=lg.grid_id
                    LEFT JOIN $wpdb->postmeta as pm ON p.ID=pm.post_id AND pm.meta_key = 'group_status'
                    LEFT JOIN $wpdb->postmeta as pm1 ON pm1.post_id=p.ID AND pm1.meta_key = 'member_count'
                    LEFT JOIN $wpdb->postmeta as pm2 ON pm2.post_id=p.ID AND pm2.meta_key = 'leader_count'
                    LEFT JOIN $wpdb->postmeta as pm3 ON pm3.post_id=p.ID AND pm3.meta_key = 'group_type'
                    WHERE p.post_type = 'groups' AND pm.meta_value = 'active';
                ", ARRAY_A);
            }
            return $results;
        }

        public function query_basic() {
            global $wpdb;
            if ( DT_Mapbox_API::get_key() ) {
                $results = $wpdb->get_results("
                    SELECT
                    p.ID,
                    p.post_title as name,
                    pm.meta_value as status,
                    pm1.meta_value as member_count,
                    pm2.meta_value as leader_count,
                    pm3.meta_value as group_type,
                    IF ( lgm.lng, lgm.lng, NULL ) as lng,
                    IF ( lgm.lng, lgm.lat, NULL) as lat
                    FROM $wpdb->posts as p
                    JOIN $wpdb->postmeta as pmlgm ON p.ID=pmlgm.post_id AND pmlgm.meta_key = 'location_grid_meta'
                    JOIN $wpdb->dt_location_grid_meta as lgm ON pmlgm.meta_value=lgm.grid_meta_id
                    LEFT JOIN $wpdb->postmeta as pm ON p.ID=pm.post_id AND pm.meta_key = 'group_status'
                    LEFT JOIN $wpdb->postmeta as pm1 ON pm1.post_id=p.ID AND pm1.meta_key = 'member_count'
                    LEFT JOIN $wpdb->postmeta as pm2 ON pm2.post_id=p.ID AND pm2.meta_key = 'leader_count'
                    LEFT JOIN $wpdb->postmeta as pm3 ON pm3.post_id=p.ID AND pm3.meta_key = 'group_type'
                    WHERE p.post_type = 'groups';
                ", ARRAY_A);
            } else {
                $results = $wpdb->get_results("
                    SELECT
                    p.ID,
                    p.post_title as name,
                    pm.meta_value as status,
                    pm1.meta_value as member_count,
                    pm2.meta_value as leader_count,
                    pm3.meta_value as group_type,
                    IF ( lg.longitude, lg.longitude, NULL ) as lng,
                    IF ( lg.latitude, lg.latitude, NULL) as lat
                    FROM $wpdb->posts as p
                    JOIN $wpdb->postmeta as pmlg ON p.ID=pmlg.post_id AND pmlg.meta_key = 'location_grid'
                    JOIN $wpdb->dt_location_grid as lg ON pmlg.meta_value=lg.grid_id
                    LEFT JOIN $wpdb->postmeta as pm ON p.ID=pm.post_id AND pm.meta_key = 'group_status'
                    LEFT JOIN $wpdb->postmeta as pm1 ON pm1.post_id=p.ID AND pm1.meta_key = 'member_count'
                    LEFT JOIN $wpdb->postmeta as pm2 ON pm2.post_id=p.ID AND pm2.meta_key = 'leader_count'
                    LEFT JOIN $wpdb->postmeta as pm3 ON pm3.post_id=p.ID AND pm3.meta_key = 'group_type'
                    WHERE p.post_type = 'groups';
                ", ARRAY_A);
            }
            return $results;
        }

        public function format_class( $classes) {
            $classes[$this->token] = __CLASS__;
            return $classes;
        }

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
        } // End __construct()
    }

    DT_Metrics_Export_KML_Groups::instance();
}



/**
 * CREATE KML FILE
 */
if ( !defined( 'ABSPATH' )) {

    // phpcs:disable
    require($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php'); // loads the wp framework when called

    if (isset( $_GET['expiring48'] ) || isset( $_GET['expiring360'] )) {

        $token = isset( $_GET['expiring48'] ) ? sanitize_text_field( wp_unslash( $_GET['expiring48'] ) ) : sanitize_text_field( wp_unslash( $_GET['expiring360'] ) );
        $results = get_transient( 'metrics_exports_' . $token );

        header( 'Content-type: application/vnd.google-earth.kml+xml' );
        header( 'Content-Disposition: attachment; filename=dt-kml-' . strtotime( $results['timestamp'] ) . '.kml' );

        if (empty( $results )) {
            return;
        }
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2" xmlns:kml="http://www.opengis.net/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom">';
        echo '<Document>';

        // Now iterate over all placemarks (rows)
        foreach ($results['rows'] as $row ) {
            if ( empty( $row['lng'] ) ){
                continue;
            }
            echo '<Placemark>';
            echo '<name>'.$row['name'].'</name>';
            echo '<description></description>';
            echo '<Point>';
            echo '<coordinates>'.$row['lng'].' , '.$row['lat'].'</coordinates>';
            echo '</Point>';
            echo '</Placemark>';
        };

        echo '</Document>';
        echo '</kml>';

        exit;
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


        header( 'Content-type: application/vnd.google-earth.kml+xml' );
        header( 'Content-Disposition: attachment; filename=dt-kml-' . strtotime( $results['timestamp'] ) . '.kml' );

        if (empty( $results )) {
            return;
        }
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2" xmlns:kml="http://www.opengis.net/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom">';
        echo '<Document>';

        // Now iterate over all placemarks (rows)
        foreach ($results['rows'] as $row ) {
            if ( empty( $row['lng'] ) ){
                continue;
            }
            echo '<Placemark>';
            echo '<name>'.$row['name'].'</name>';
            echo '<description></description>';
            echo '<Point>';
            echo '<coordinates>'.$row['lng'].' , '.$row['lat'].'</coordinates>';
            echo '</Point>';
            echo '</Placemark>';
        };

        echo '</Document>';
        echo '</kml>';

        exit;
    } else if (isset( $_GET['permanent'] )) {
        global $wpdb;

        // test if key exists
        $token = sanitize_text_field( wp_unslash( $_GET['permanent'] ) );
        $raw = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = %s", 'permanent_' . $token ) );
        if (empty( $raw )) {
            echo 'No link found';
            return;
        }

        // refresh data
        require_once( 'kml-format-groups.php' );
        $raw = maybe_unserialize( $raw );
        $results = DT_Metrics_Export_KML_Groups::instance()->update( $token, $raw );

        header( 'Content-type: application/vnd.google-earth.kml+xml' );

        if (empty( $results )) {
            return;
        }
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2" xmlns:kml="http://www.opengis.net/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom">';
        echo '<Document>';

        // Now iterate over all placemarks (rows)
        foreach ($results['rows'] as $row ) {
            if ( empty( $row['lng'] ) ){
                continue;
            }
            echo '<Placemark>';
            echo '<name>'.$row['name'].'</name>';
            echo '<description></description>';
            echo '<Point>';
            echo '<coordinates>'.$row['lng'].' , '.$row['lat'].'</coordinates>';
            echo '</Point>';
            echo '</Placemark>';
        };

        echo '</Document>';
        echo '</kml>';

        exit;
    } else {
        echo 'parameters not set correctly';
        return;
    }
    // phpcs:enable
}
