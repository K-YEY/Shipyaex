<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Shield Resource
    |--------------------------------------------------------------------------
    |
    | Here you may configure the built-in role management resource. You can
    | customize the URL, choose whether to show model paths, group it under
    | a cluster, and decide which permission tabs to display.
    |
    */

    'shield_resource' => [
        'slug' => 'shield/roles',
        'show_model_path' => false,
        'cluster' => null,
        'tabs' => [
            'pages' => true,
            'widgets' => true,
            'resources' => true,
            'custom_permissions' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy
    |--------------------------------------------------------------------------
    |
    | When your application supports teams, Shield will automatically detect
    | and configure the tenant model during setup. This enables tenant-scoped
    | roles and permissions throughout your application.
    |
    */

    'tenant_model' => null,

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | This value contains the class name of your user model. This model will
    | be used for role assignments and must implement the HasRoles trait
    | provided by the Spatie\Permission package.
    |
    */

    'auth_provider_model' => 'App\\Models\\User',

    /*
    |--------------------------------------------------------------------------
    | Super Admin
    |--------------------------------------------------------------------------
    |
    | Here you may define a super admin that has unrestricted access to your
    | application. You can choose to implement this via Laravel's gate system
    | or as a traditional role with all permissions explicitly assigned.
    |
    */

    'super_admin' => [
        'enabled' => true,
        'name' => 'super_admin',
        'define_via_gate' => false,
        'intercept_gate' => 'before',
    ],

    /*
    |--------------------------------------------------------------------------
    | Panel User
    |--------------------------------------------------------------------------
    |
    | When enabled, Shield will create a basic panel user role that can be
    | assigned to users who should have access to your Filament panels but
    | don't need any specific permissions beyond basic authentication.
    |
    */

    'panel_user' => [
        'enabled' => true,
        'name' => 'panel_user',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Builder
    |--------------------------------------------------------------------------
    |
    | You can customize how permission keys are generated to match your
    | preferred naming convention and organizational standards. Shield uses
    | these settings when creating permission names from your resources.
    |
    | Supported formats: snake, kebab, pascal, camel, upper_snake, lower_snake
    |
    */

    'permissions' => [
        'separator' => ':',
        'case' => 'pascal',
        'generate' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Permissions
    |--------------------------------------------------------------------------
    |
    | Sometimes you need permissions that don't map to resources, pages, or
    | widgets. Define any custom permissions here and they'll be available
    | when editing roles in your application.
    |
    */

    'custom_permissions' => [
        'Access:Admin',
        'Access:Client',
        'Access:Shipper',
        'ViewWidget:OrdersStatsOverview',
        'ViewWidget:OrdersStatusChart',
        'ViewWidget:OrdersReport',
        'ViewWidget:CompanyStats',
        'ViewWidget:ClientStats',
        'ViewWidget:ShipperStats',
        'ViewWidget:MonthlyRevenue',
        'ViewWidget:OrdersByGovernorate',
    ],

    /*
    |--------------------------------------------------------------------------
    | Policies
    |--------------------------------------------------------------------------
    |
    | Shield can automatically generate Laravel policies for your resources.
    | When merge is enabled, the methods below will be combined with any
    | resource-specific methods you define in the resources section.
    |
    */

    'policies' => [
        'path' => app_path('Policies'),
        'merge' => true,
        'generate' => true,
        'methods' => [
            'viewAny', 'view', 'create', 'update', 'delete', 'restore',
            'forceDelete', 'forceDeleteAny', 'restoreAny', 'replicate', 'reorder',
        ],
        'single_parameter_methods' => [
            'viewAny',
            'create',
            'deleteAny',
            'forceDeleteAny',
            'restoreAny',
            'reorder',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    |
    | Shield supports multiple languages out of the box. When enabled, you
    | can provide translated labels for permissions to create a more
    | localized experience for your international users.
    |
    */

    'localization' => [
        'enabled' => true,
        'key' => 'filament-shield::filament-shield.resource_permission_prefixes_labels',
    ],

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | Here you can fine-tune permissions for specific Filament resources.
    | Use the 'manage' array to override the default policy methods for
    | individual resources, giving you granular control over permissions.
    |
    */

    'resources' => [
        'subject' => 'model',
        'manage' => [
            \BezhanSalleh\FilamentShield\Resources\Roles\RoleResource::class => [
                'view_any',
                'view',
                'create',
                'update',
                'delete',
            ],
            \App\Filament\Resources\Orders\OrderResource::class => [
                'view_any',
                'view',
                'create',
                'update',
                'delete',
                'delete_any',
                'restore',
                'restore_any',
                'force_delete',
                'force_delete_any',

                // Behavior / Scoping
                'view_all',
                'view_own',
                'view_assigned',

                // Column Visibility
                'view_code_column',
                'view_external_code_column',
                'view_registration_date_column',
                'view_shipper_date_column',
                'view_recipient_name_column',
                'view_phone_column',
                'view_address_column',
                'view_governorate_column',
                'view_city_column',
                'view_total_amount_column',
                'view_shipping_fees_column',
                'view_shipper_commission_column',
                'view_net_amount_column',
                'view_company_share_column',
                'view_collection_amount_column',
                'view_status_column',
                'view_status_notes_column',
                'view_order_notes_column',
                'view_shipper_column',
                'view_client_column',
                'view_dates_column',

                // Filters
                'view_delayed_follow_up_filter',
                'view_status_filter',
                'view_collected_from_shipper_filter',
                'view_returned_from_shipper_filter',
                'view_has_return_filter',
                'view_settled_with_client_filter',
                'view_returned_to_client_filter',

                // Actions
                'export_selected_action',
                'export_external_codes_action',
                'print_labels_action',
                'assign_shipper_action',
                'bulk_change_status_action',
                'manage_shipper_collection_action',
                'manage_client_collection_action',
                'manage_shipper_return_action',
                'manage_client_return_action',
                'view_my_orders_action',
                'barcode_scanner_action',
                'view_timeline_action',
                'print_label_action',
                'change_status_action',

                // Form Fields & Sections
                'view_external_code_field',
                'edit_external_code_field',
                'edit_client_field',
                'assign_shipper_field',
                'change_status_field',
                'view_order_notes_field',
                'edit_order_notes_field',
                'view_customer_details_section',
                'edit_customer_details_field',
                'view_financial_summary_section',
                'edit_financial_summary_field',
                'view_shipper_fees_field',
                'edit_shipper_fees_field',
                'view_cop_field',
                'edit_cop_field',

                // Legacy / Scoping
                'edit_locked',
                'edit_client',
            ],
            \App\Filament\Resources\Users\UserResource::class => [
                'view_any',
                'view',
                'create',
                'update',
                'delete',
                'edit_commission',
                'edit_plan',
                'edit_roles',
                'block_user',
            ],
            \App\Filament\Resources\Cities\CityResource::class => [
                'view_any',
                'view',
                'create',
                'update',
                'delete',
            ],
            \App\Filament\Resources\Governorates\GovernorateResource::class => [
                'view_any',
                'view',
                'create',
                'update',
                'delete',
            ],
            \App\Filament\Resources\Plans\PlanResource::class => [
                'view_any',
                'view',
                'create',
                'update',
                'delete',
            ],
            \App\Filament\Resources\PlanPrices\PlanPriceResource::class => [
                'view_any',
                'view',
                'create',
                'update',
                'delete',
            ],
            \App\Filament\Resources\Expenses\ExpenseResource::class => [
                'view_any',
                'view',
                'create',
                'update',
                'delete',
            ],
            \App\Filament\Resources\Settings\SettingResource::class => [
                'view_any',
                'view',
                'create',
                'update',
                'delete',
            ],
            \App\Filament\Resources\Shippingcontents\ShippingContentResource::class => [
                'view_any',
                'view',
                'create',
                'update',
                'delete',
                'delete_any',
                'view_name_column',
                'edit_name_field',
                'export_data',
            ],
            \App\Filament\Resources\OrderStatusResource::class => [
                'view_any',
                'view',
                'create',
                'update',
                'delete',
            ],
            \App\Filament\Resources\RefusedReasonResource::class => [
                'view_any',
                'view',
                'create',
                'update',
                'delete',
            ],
            \App\Filament\Resources\CollectedShippers\CollectedShipperResource::class => [
                'view_any',
                'view',
                'update',
                'delete',
                'view_all',
                'view_own',
                'view_id_column',
                'view_shipper_column',
                'view_collection_date_column',
                'view_orders_count_column',
                'view_total_amount_column',
                'view_fees_column',
                'view_net_amount_column',
                'view_status_column',
                'view_dates_column',
                'view_shipper_filter',
                'view_status_filter',
                'view_orders_action',
                'approve_action',
                'cancel_action',
                'print_invoice_action',
                'bulk_approve_action',
                'view_orders_count_field',
                'view_total_amount_field',
                'view_shipper_fees_field',
                'view_net_amount_field',
            ],
            \App\Filament\Resources\CollectedClients\CollectedClientResource::class => [
                'view_any',
                'view',
                'update',
                'delete',
                'view_all',
                'view_own',
                'view_id_column',
                'view_client_column',
                'view_collection_date_column',
                'view_orders_count_column',
                'view_total_amount_column',
                'view_fees_column',
                'view_net_amount_column',
                'view_status_column',
                'view_dates_column',
                'view_client_filter',
                'view_status_filter',
                'view_orders_action',
                'approve_action',
                'cancel_action',
                'print_invoice_action',
                'bulk_approve_action',
                'view_orders_count_field',
                'view_total_amount_field',
                'view_fees_field',
                'view_net_amount_field',
            ],
            \App\Filament\Resources\ReturnedShipperResource::class => [
                'view_any',
                'view',
                'update',
                'delete',
                'view_all',
                'view_own',
                'view_id_column',
                'view_shipper_column',
                'view_return_date_column',
                'view_orders_count_column',
                'view_total_amount_column',
                'view_fees_column',
                'view_net_amount_column',
                'view_status_column',
                'view_dates_column',
                'view_shipper_filter',
                'view_status_filter',
                'view_orders_action',
                'approve_action',
                'print_invoice_action',
                'view_orders_count_field',
                'view_total_amount_field',
                'view_shipper_fees_field',
                'view_net_amount_field',
            ],
            \App\Filament\Resources\ReturnedClientResource::class => [
                'view_any',
                'view',
                'update',
                'delete',
                'view_all',
                'view_own',
                'view_id_column',
                'view_client_column',
                'view_return_date_column',
                'view_orders_count_column',
                'view_total_amount_column',
                'view_fees_column',
                'view_net_amount_column',
                'view_status_column',
                'view_dates_column',
                'view_client_filter',
                'view_status_filter',
                'view_orders_action',
                'approve_action',
                'print_invoice_action',
                'view_orders_count_field',
                'view_total_amount_field',
                'view_fees_field',
                'view_net_amount_field',
            ],
        ],
        'exclude' => [
            //
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    |
    | Most Filament pages only require view permissions. Pages listed in the
    | exclude array will be skipped during permission generation and won't
    | appear in your role management interface.
    |
    */

    'pages' => [
        'subject' => 'class',
        'prefix' => 'view',
        'exclude' => [
            \Filament\Pages\Dashboard::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Widgets
    |--------------------------------------------------------------------------
    |
    | Like pages, widgets typically only need view permissions. Add widgets
    | to the exclude array if you don't want them to appear in your role
    | management interface.
    |
    */

    'widgets' => [
        'subject' => 'class',
        'prefix' => 'view',
        'exclude' => [
            \Filament\Widgets\AccountWidget::class,
            \Filament\Widgets\FilamentInfoWidget::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Entity Discovery
    |--------------------------------------------------------------------------
    |
    | By default, Shield only looks for entities in your default Filament
    | panel. Enable these options if you're using multiple panels and want
    | Shield to discover entities across all of them.
    |
    */

    'discovery' => [
        'discover_all_resources' => true,
        'discover_all_widgets' => true,
        'discover_all_pages' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Policy
    |--------------------------------------------------------------------------
    |
    | Shield can automatically register a policy for role management itself.
    | This lets you control who can manage roles using Laravel's built-in
    | authorization system. Requires a RolePolicy class in your app.
    |
    */

    'register_role_policy' => true,

];
