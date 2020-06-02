<?php
/**
 * Export Format: CSV export in the COTW Standard
 */

add_filter( 'dt_metrics_export_formats', 'dt_metrics_export_format_csv_cotw', 10, 1 );
function dt_metrics_export_format_csv_cotw( $formats ) {

    $formats['csv_cotw'] = [
        'key' => 'csv_cotw',
        'label' => 'CSV (COTW Standard)',
        'selectable_types' => [],
    ];

    return $formats;
}

