<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Artisan;

class SyncShieldPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shield:sync-custom';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync custom permissions and fix casing for production/host environment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting Shield Custom Sync...");

        // 1. Fix Casing for ShippingContent
        $this->info("Fixing ShippingContent casing...");
        $perms = Permission::where('name', 'like', '%Shippingcontent%')->get();
        foreach ($perms as $p) {
            $newName = str_replace('Shippingcontent', 'ShippingContent', $p->name);
            if (!Permission::where('name', $newName)->where('guard_name', $p->guard_name)->exists()) {
                $oldName = $p->name;
                $p->update(['name' => $newName]);
                $this->line("Renamed $oldName -> $newName");
            } else {
                $p->delete();
                $this->warn("Deleted duplicate: $p->name");
            }
        }

        // 2. Ensure Custom Column/Field Permissions
        $this->info("Ensuring custom column/field permissions...");
        $customPerms = [
            // Users
            'ViewIdColumn:User', 'ViewUsernameColumn:User', 'ViewNameColumn:User', 'ViewRolesColumn:User', 'ViewDatesColumn:User',
            'BlockUser:User', 'EditCommission:User', 'EditPlan:User', 'EditRoles:User',
            // Settings
            'ViewKeyColumn:Setting', 'ViewValueColumn:Setting', 'ViewDatesColumn:Setting', 'EditValueField:Setting',
            // ShippingContent
            'ViewNameColumn:ShippingContent', 'EditNameField:ShippingContent', 'ExportData:ShippingContent',
            // Widgets
            'ViewWidget:OrdersStatsOverview', 'ViewWidget:OrdersStatusChart', 'ViewWidget:OrdersReport',
            'ViewWidget:CompanyStats', 'ViewWidget:ClientStats', 'ViewWidget:ShipperStats',
            'ViewWidget:MonthlyRevenue', 'ViewWidget:OrdersByGovernorate',
        ];

        // Combine with standard CRUD for problematic resources
        $crud = ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny', 'Restore', 'RestoreAny', 'ForceDelete', 'ForceDeleteAny', 'Replicate', 'Reorder'];
        $models = ['User', 'Setting', 'ShippingContent', 'Role'];

        foreach ($models as $model) {
            foreach ($crud as $action) {
                $customPerms[] = "$action:$model";
            }
        }

        foreach (array_unique($customPerms) as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        $this->line("Custom permissions ensured.");

        // 3. Sync everything to Admin Roles
        $this->info("Syncing all permissions to admin/super_admin roles...");
        $adminRoles = ['super_admin', 'admin'];
        foreach ($adminRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo(Permission::all());
                $this->info("Synced all to $roleName");
            }
        }

        // 4. Clear Caches
        $this->info("Clearing caches...");
        Artisan::call('optimize:clear');
        Artisan::call('permission:cache-reset');

        $this->info("Sync Completed Successfully!");
    }
}
