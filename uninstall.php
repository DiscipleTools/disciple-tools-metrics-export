<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Deletes the export configurations, the one-time and permanent links stored on them, and the expiring link transients.
 */
function dt_metrics_export_uninstall_site() {
    global $wpdb;
    $config_ids = get_posts( [
        'post_type' => 'dt_metrics_export',
        'post_status' => 'any',
        'numberposts' => -1,
        'fields' => 'ids',
    ] );
    foreach ( $config_ids as $config_id ) {
        wp_delete_post( $config_id, true );
    }
    $wpdb->query( $wpdb->prepare(
        "DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
        $wpdb->esc_like( '_transient_metrics_exports_' ) . '%',
        $wpdb->esc_like( '_transient_timeout_metrics_exports_' ) . '%'
    ) );
}

if ( is_multisite() ) {
    foreach ( get_sites( [ 'fields' => 'ids', 'number' => 0 ] ) as $dt_metrics_export_site_id ) {
        switch_to_blog( $dt_metrics_export_site_id );
        dt_metrics_export_uninstall_site();
        restore_current_blog();
    }
} else {
    dt_metrics_export_uninstall_site();
}
