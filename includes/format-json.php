<?php
/**
 * Export Format: JSON Export
 */

add_filter( 'dt_metrics_export_formats', 'dt_metrics_export_format_json', 10, 1 );
function dt_metrics_export_format_json( $formats ) {

    $types = get_dt_metrics_export_types();

    $formats['json'] = [
        'key' => 'json',
        'label' => 'JSON',
        'selectable_types' => $types,
    ];

    return $formats;
}
