<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shield Translation
    |--------------------------------------------------------------------------
    */

    'resource' => [
        'label' => [
            'role' => 'وظيفة',
            'user' => 'مستخدم',
        ],
        'plural_label' => [
            'roles' => 'الوظائف والصلاحيات',
            'users' => 'المستخدمين',
        ],
        'title' => [
            'roles' => 'إدارة الوظائف',
            'users' => 'المستخدمين',
        ],
    ],

    'column' => [
        'permissions' => 'الصلاحيات',
        'name' => 'الاسم',
        'guard_name' => 'نوع الحماية',
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'آخر تعديل',
        'roles' => 'الوظائف',
    ],

    'form' => [
        'name' => [
            'label' => 'اسم الوظيفة',
            'helper' => 'مثلاً: مشرف مبيعات، مدير عمليات، محاسب...',
        ],
        'guard_name' => [
            'label' => 'Guard Name',
        ],
        'permissions' => [
            'label' => 'الصلاحيات المتاحة',
            'select_all' => [
                'label' => 'تحديد الكل',
                'message' => 'تم تحديد كل الصلاحيات لهذه المجموعة',
            ],
        ],
    ],

    'section' => [
        'permissions' => 'تخصيص الصلاحيات',
    ],

    'resource_permission_prefixes_labels' => [
        'view' => 'عرض التفاصيل',
        'view_any' => 'الدخول للقائمة (تصفح)',
        'create' => 'إضافة جديد',
        'update' => 'تعديل البيانات',
        'restore' => 'استرجاع',
        'restore_any' => 'استرجاع الكل',
        'replicate' => 'نسخ سجل',
        'reorder' => 'إعادة ترتيب',
        'delete' => 'حذف سجل',
        'delete_any' => 'حذف مجموعة',
        'force_delete' => 'حذف نهائي',
        'force_delete_any' => 'حذف نهائي للكل',
        'lock' => 'قفل السجل',
        'unlock' => 'فك القفل',

        // Custom Column Visibility
        'view_code_column' => 'عرض عمود الكود',
        'view_external_code_column' => 'عرض عمود الكود الخارجي',
        'view_registration_date_column' => 'عرض عمود تاريخ التسجيل',
        'view_shipper_date_column' => 'عرض عمود تاريخ المندوب',
        'view_recipient_name_column' => 'عرض عمود اسم المستلم',
        'view_phone_column' => 'عرض عمود الهاتف',
        'view_address_column' => 'عرض عمود العنوان',
        'view_governorate_column' => 'عرض عمود المحافظة',
        'view_city_column' => 'عرض عمود المدينة',
        'view_total_amount_column' => 'عرض عمود المبلغ الكلي',
        'view_shipping_fees_column' => 'عرض عمود مصاريف الشحن',
        'view_shipper_commission_column' => 'عرض عمود عمولة المندوب',
        'view_net_amount_column' => 'عرض عمود المبلغ الصافي',
        'view_company_share_column' => 'عرض عمود حصة الشركة',
        'view_collection_amount_column' => 'عرض عمود مبلغ التحصيل',
        'view_status_column' => 'عرض عمود الحالة',
        'view_status_notes_column' => 'عرض عمود ملاحظات الحالة',
        'view_order_notes_column' => 'عرض عمود ملاحظات الطلب',
        'view_shipper_column' => 'عرض عمود المندوب',
        'view_client_column' => 'عرض عمود العميل',
        'view_dates_column' => 'عرض عمود التواريخ',

        // Custom Filters
        'view_delayed_follow_up_filter' => 'فلتر متابعة مؤجلة',
        'view_status_filter' => 'فلتر الحالة',
        'view_collected_from_shipper_filter' => 'فلتر محصل من المندوب',
        'view_returned_from_shipper_filter' => 'فلتر مرتجع من المندوب',
        'view_has_return_filter' => 'فلتر لديه مرتجع',
        'view_settled_with_client_filter' => 'فلتر تمت التسوية مع العميل',
        'view_returned_to_client_filter' => 'فلتر مرتجع للعميل',

        // Custom Actions
        'export_selected_action' => 'تصدير المحدد',
        'export_external_codes_action' => 'تصدير الأكواد الخارجية',
        'print_labels_action' => 'طباعة البوليصات',
        'assign_shipper_action' => 'تعيين مندوب',
        'bulk_change_status_action' => 'تغيير حالة مجمع',
        'manage_shipper_collection_action' => 'إدارة تحصيل المندوب',
        'manage_client_collection_action' => 'إدارة تحصيل العميل',
        'manage_shipper_return_action' => 'إدارة مرتجع المندوب',
        'manage_client_return_action' => 'إدارة مرتجع العميل',
        'view_my_orders_action' => 'عرض طلباتي',
        'view_timeline_action' => 'عرض الجدول الزمني',
        'barcode_scanner_action' => 'ماسح الباركود',
        'print_label_action' => 'طباعة البوليصة',
        'change_status_action' => 'تغيير الحالة',

        // Custom Form Fields & Sections
        'view_external_code_field' => 'عرض حقل الكود الخارجي',
        'edit_external_code_field' => 'تعديل حقل الكود الخارجي',
        'edit_client_field' => 'تعديل حقل العميل',
        'assign_shipper_field' => 'حقل تعيين المندوب',
        'change_status_field' => 'حقل تغيير الحالة',
        'view_order_notes_field' => 'عرض حقل ملاحظات الطلب',
        'edit_order_notes_field' => 'تعديل حقل ملاحظات الطلب',
        'view_customer_details_section' => 'عرض قسم تفاصيل العميل',
        'edit_customer_details_field' => 'تعديل حقل تفاصيل العميل',
        'view_financial_summary_section' => 'عرض قسم الملخص المالي',
        'edit_financial_summary_field' => 'تعديل حقل الملخص المالي',
        'view_shipper_fees_field' => 'عرض حقل رسوم المندوب',
        'edit_shipper_fees_field' => 'تعديل حقل رسوم المندوب',
        'view_cop_field' => 'عرض حقل الدفع عند الاستلام',
        'edit_cop_field' => 'تعديل حقل الدفع عند الاستلام',

        // Legacy / Scoping
        'view_all' => 'عرض الكل',
        'view_own' => 'عرض الخاص بي',
        'view_assigned' => 'عرض الموكل لي',
        'edit_locked' => 'تعديل المقفل',
        'edit_client' => 'تعديل العميل',

        // Additional Column/Field Permissions
        'view_id_column' => 'عرض عمود المعرف (ID)',
        'view_username_column' => 'عرض عمود اسم المستخدم',
        'view_roles_column' => 'عرض عمود الصلاحيات/الأدوار',
        'view_key_column' => 'عرض عمود المفتاح',
        'view_value_column' => 'عرض عمود القيمة',
        'edit_value_field' => 'تعديل حقل القيمة',

        // Clients/Shippers specific
        'view_phone_column' => 'عرض عمود الهاتف',
        'view_commission_column' => 'عرض عمود العمولة',
        'view_plan_column' => 'عرض عمود الباقة',
        'view_username_field' => 'عرض حقل اسم المستخدم',
        'view_password_field' => 'عرض حقل كلمة المرور',
        'edit_phone_field' => 'تعديل حقل الهاتف',
        'edit_username_field' => 'تعديل حقل اسم المستخدم',
        'edit_password_field' => 'تعديل حقل كلمة المرور',
        'edit_plan_field' => 'تعديل حقل الباقة',
        'edit_commission_field' => 'تعديل حقل العمولة',
        'block_user' => 'حظر/إلغاء حظر المستخدم',
    ],

    'navigation' => [
        'group' => 'الإعدادات والبيانات',
        'label' => 'الصلاحيات والوظائف',
    ],
];
