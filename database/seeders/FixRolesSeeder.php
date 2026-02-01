<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class FixRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Reset Cached Roles and Permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Define Resources (Entities in the system)
        $resources = [
            'order',            // الأوردرات
            'user',             // المستخدمين
            'collected_client', // تحصيلات العملاء
            'collected_shipper',// تحصيلات المناديب
            'returned_client',  // مرتجعات العملاء
            'returned_shipper', // مرتجعات المناديب
            'city',             // المدن
            'governorate',      // المحافظات
            'setting',          // الإعدادات
            'expense'           // المصاريف
        ];

        // 3. Create Basic Permissions for each resource
        $actions = ['view_any', 'view', 'create', 'update', 'delete', 'restore', 'force_delete'];
        
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$action}_{$resource}"]);
            }
        }
        
        // Add Widget Permissions
        Permission::firstOrCreate(['name' => 'view_any_report']); // للتقارير

        // ----------------------------------------------------
        // 4. Setup Roles
        // ----------------------------------------------------

        // --- Role: Shipper (المندوب) ---
        $shipper = Role::firstOrCreate(['name' => 'shipper']);
        $shipperPermissions = [
            'view_any_order',
            'view_order',
            'update_order',         // عشان يغير الحالة (Logic prevented in code if Delivered)
            'view_any_collected_shipper',
            'view_collected_shipper',
            'view_any_returned_shipper',
            'view_returned_shipper',
            // No Delete, No Create (usually), No Settings
        ];
        $shipper->syncPermissions($shipperPermissions);

        // --- Role: Client (العميل) ---
        $client = Role::firstOrCreate(['name' => 'client']);
        $clientPermissions = [
            'view_any_order',
            'view_order',
            'create_order',         // يقدر يضيف أوردر
            // update_order removed (Client shouldn't edit after creation usually, or limited)
            'view_any_collected_client',
            'view_collected_client',
            'view_any_returned_client',
            'view_returned_client',
            // No Delete, No Settings
        ];
        $client->syncPermissions($clientPermissions);


        // --- Role: Admin (المدير) --- (Uses Super Admin usually, but let's be explicit)
        $admin = Role::firstOrCreate(['name' => 'admin']); // Or super_admin
        $admin->givePermissionTo(Permission::all()); // واخد كل الصلاحيات

        // 5. Create a Super Admin Role (Bypass all)
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        // Super Admin doesn't need permissions synced if Gate::before is set, 
        // but syncing all ensures visibility in Filament Shield
        $superAdmin->givePermissionTo(Permission::all()); 

        $this->command->info('✅ Roles and Permissions have been cleaned and organized successfully!');
        $this->command->info('👮 Admin: Full Access');
        $this->command->info('🚚 Shipper: Orders (View/Update), My Collections');
        $this->command->info('👤 Client: Orders (View/Create), My Collections');
    }
}
