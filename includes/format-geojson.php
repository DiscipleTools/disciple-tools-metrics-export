<?php
/**
 * Export Format: GEOJSON export
 */

add_filter( 'dt_metrics_export_formats', 'dt_metrics_export_format_geojson', 10, 1 );
function dt_metrics_export_format_geojson( $formats ) {

    $types = get_dt_metrics_export_types();

    unset( $types['users_by_roles'] );
    unset( $types['groups_unformed'] );

    $formats['geojson'] = [
        'key' => 'geojson',
        'label' => 'GEOJSON',
        'selectable_types' => $types,
    ];

    return $formats;
}
