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
    ],

    'navigation' => [
        'group' => 'الإعدادات والبيانات',
        'label' => 'الصلاحيات والوظائف',
    ],
];
