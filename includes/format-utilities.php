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
        $config_posts = get_posts( [ 'post_type' => 'dt_metrics_export' ] );
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
        return [
            'key' => '',
            'label' => '',
            'types' => [
                'contacts' => [
                    'contacts_all' => [
                        'key' => 'contacts_all',
                        'label' => 'All'
                    ],
                    'contacts_active' => [
                        'key' => 'contacts_active',
                        'label' => 'Active'
                    ],
                    'contacts_paused' => [
                        'key' => 'contacts_paused',
                        'label' => 'Paused'
                    ],
                    'contacts_closed' => [
                        'key' => 'contacts_closed',
                        'label' => 'Closed'
                    ],
                ],
                'groups' => [
                    'groups_all' => [
                        'key' => 'groups_all',
                        'label' => 'All'
                    ],
                    'groups_active' => [
                        'key' => 'groups_active',
                        'label' => 'Active'
                    ],
                    'groups_inactive' => [
                        'key' => 'groups_inactive',
                        'label' => 'Inactive'
                    ],
                    'groups_pre_groups' => [
                        'key' => 'groups_pre_groups',
                        'label' => 'Pre-Groups'
                    ],
                    'groups_groups' => [
                        'key' => 'groups_groups',
                        'label' => 'Groups'
                    ],
                    'groups_churches' => [
                        'key' => 'groups_churches',
                        'label' => 'Churches'
                    ],
                ],
                'users' => [
                    'users_all' => [
                        'key' => 'users_all',
                        'label' => 'All'
                    ],
                    'users_active' => [
                        'key' => 'users_active',
                        'label' => 'Active'
                    ],
                    'users_inactive' => [
                        'key' => 'users_inactive',
                        'label' => 'Inactive'
                    ],
                    'users_by_roles' => [
                        'key' => 'users_by_roles',
                        'label' => 'Users by Roles'
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
                    'label' => 'Download Link'
                ],
                'uploads' => [
                    'value' => 'uploads',
                    'label' => 'Uploads Folder (unrestricted public access)'
                ]
            ],
        ];
    }
}
