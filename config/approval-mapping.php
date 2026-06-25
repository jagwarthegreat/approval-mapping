<?php

return [
    'connection' => env('APPROVAL_MAPPING_DB_CONNECTION', null),

    'table_names' => [
        'approval_mapping_versions' => 'AMVPM',
        'approval_mappings' => 'AMPMA',
        'approval_mapping_levels' => 'AMLPM',
        'approval_requests' => 'APPRO',
        'approval_request_logs' => 'ARLPE',
    ],

    'route' => [
        'api_prefix' => 'api/v1/approval-mapping',
        'web_prefix' => 'approval-mapping',
        'api_middleware' => ['api', 'auth:sanctum'],
        'web_middleware' => ['web', 'auth'],
    ],

    'models' => [
        'user' => \App\Models\User::class,
        'user_assign_group' => \App\Models\MDB\UserAssignGroup::class,
        'sidebar_menu' => \App\Models\MDB\SidebarMenu::class,
    ],
];
