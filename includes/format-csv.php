<?php
/**
 * Export Format: CSV export
 */

add_filter( 'dt_metrics_export_format', 'dt_metrics_export_format_csv', 10, 1 );
function dt_metrics_export_format_csv( $formats ) {

    $types = get_dt_metrics_export_types();

    $formats['csv'] = [
        'key' => 'csv',
        'label' => 'CSV',
        'selectable_types' => $types,
    ];

    return $formats;
}
