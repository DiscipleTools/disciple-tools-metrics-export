<?php
/**
 * Export Format: KML Export
 */

add_filter( 'dt_metrics_export_format', 'dt_metrics_export_format_kml', 10, 1 );
function dt_metrics_export_format_kml( $formats ) {

    $types = get_dt_metrics_export_types();

    $formats['kml'] = [
        'key' => 'kml',
        'label' => 'KML',
        'selectable_types' => $types,
    ];

    return $formats;
}
