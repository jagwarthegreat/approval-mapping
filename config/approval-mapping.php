<?php

return [
    'connection' => env('APPROVAL_MAPPING_DB_CONNECTION', null),

    'table_names' => [
        'approval_mapping_versions' => 'AMVPM',
        'approval_mappings'         => 'AMPMA',
        'approval_mapping_levels'   => 'AMLPM',
        'approval_requests'         => 'APPRO',
        'approval_request_logs'     => 'ARLPE',
    ],

    'route' => [
        'api_prefix'     => 'api/v1/approval-mapping',
        'web_prefix'     => 'approval-mapping',
        'api_middleware' => ['api', 'auth:sanctum'],
        'web_middleware' => ['web', 'auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    | Toggle each organizational dimension on or off. When disabled the
    | related dropdown never appears in the UI and lookup endpoints return [].
    | The database columns remain nullable so disabling is non-destructive.
    */
    'features' => [
        'company'       => true,
        'business_unit' => true,
        'branch'        => true,
        'department'    => true,
        'module'        => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Bindings
    |--------------------------------------------------------------------------
    | Set a key to null (or omit the model class) to disable that dimension.
    | Only `user` and `user_assign_group` are required by the core workflow.
    */
    'models' => [
        'user'                    => \App\Models\User::class,
        'user_assign_group'       => null,
        'sidebar_menu'            => null,
        'module'                  => null,
        'company'                 => null,
        'business_unit'           => null,
        'branch'                  => null,
        'department'              => null,
        'company_branch_department' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Maps
    |--------------------------------------------------------------------------
    | Map the package's expected field roles to your actual column names.
    | Only override keys that differ from the defaults shown here.
    */
    'field_maps' => [
        'company' => [
            'label' => 'name',
            'code'  => 'company_code',
        ],
        'business_unit' => [
            'label' => 'name',
            'code'  => 'bus_unit_code',
        ],
        'branch' => [
            'label' => 'name',
            'code'  => 'branch_code',
        ],
        'department' => [
            'label' => 'name',
            'code'  => 'department_code',
        ],
        'module' => [
            'label'     => 'name',
            'code'      => 'code',
            'reference' => 'reference',
            'status_col'     => 'status',
            'status_active'  => 0,
        ],
        'user_assign_group' => [
            'label' => 'group_name',
        ],
    ],
];
