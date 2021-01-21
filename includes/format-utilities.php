<?php
if ( ! function_exists( 'dt_get_simple_postmeta' ) ) {
    function dt_get_simple_postmeta( $post_id ) {
        return array_map( function ( $a ) { return maybe_unserialize( $a[0] );
        }, get_post_meta( $post_id ) );
    }
}
if ( ! function_exists( 'get_dt_metrics_export_configurations' ) ) {
    function get_dt_metrics_export_configurations(): array
    {
        $configurations = [];
        $config_posts = get_posts( [ 'post_type' => 'dt_metrics_export', 'numberposts' => -1 ] );
        foreach ($config_posts as $key => $post) {
            $configurations[$post->ID] = dt_get_simple_postmeta( $post->ID );
            $configurations[$post->ID]['id'] = $post->ID;
        }
        return $configurations;
    }
}
if ( ! function_exists( 'get_dt_metrics_export_formats' ) ) {
    function get_dt_metrics_export_formats(): array
    {
        return apply_filters( 'dt_metrics_export_format', [] );
    }
}
if ( ! function_exists( 'get_dt_metrics_export_base_format' ) ) {
    function get_dt_metrics_export_base_format(): array
    {
        /**
         * This is the default configuration and template
         * Through the add_filter('dt_metrics_export_format') this template can have elements added or removed.
         * The key is to follow the pattern of the array, to remain consistent with the ui javascript.
         */
        return [
            'key' => '',
            'label' => '',
            'types' => [
                'contacts' => [
                    'contacts_all' => [
                        'key' => 'contacts_all',
                        'label' => 'All'
                    ],
                ],
                'groups' => [
                    'groups_all' => [
                        'key' => 'groups_all',
                        'label' => 'All'
                    ],
                ],
                'churches' => [
                    'churches_all' => [
                        'key' => 'churches_all',
                        'label' => 'All'
                    ],
                ],
                'users' => [
                    'users_all' => [
                        'key' => 'users_all',
                        'label' => 'All'
                    ],
                ]
            ],
            'locations' => [
                'all' => [
                    'admin0' => 'Admin0 (Country)',
                    'admin1' => 'Admin1 (State)',
                    'admin2' => 'Admin2 (County)',
                    'admin3' => 'Admin3 (Blocks)',
                    'admin4' => 'Admin4 (Village)',
                    'admin5' => 'Admin5',
                    'raw' => 'Raw (not recommended)',
                ],
                'country_by_country' => [
                    'disabled' => '---disabled---',
                    'admin0' => 'Admin0 (Country)',
                    'admin1' => 'Admin1 (State)',
                    'admin2' => 'Admin2 (County)',
                    'admin3' => 'Admin3 (Blocks)',
                    'admin4' => 'Admin4 (Village)',
                    'admin5' => 'Admin5',
                    'raw' => 'Raw (not recommended)',
                ]
            ],
            'destinations' => [
                'download' => [
                    'value' => 'download',
                    'label' => 'One-Time Download Link'
                ],
                'expiring48' => [
                    'value' => 'expiring48',
                    'label' => 'Expiring 48 Hour Link'
                ],
                'expiring360' => [
                    'value' => 'expiring360',
                    'label' => 'Expiring 15 Day Link'
                ],
                'permanent' => [
                    'value' => 'permanent',
                    'label' => 'Permanent Link'
                ]
            ],
        ];
    }
}
