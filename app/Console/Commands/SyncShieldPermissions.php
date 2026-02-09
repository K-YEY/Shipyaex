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
            // Clients/Shippers specific
            'ViewPhoneColumn:Clients', 'ViewPlanColumn:Clients', 'EditPhoneField:Clients', 'EditPlanField:Clients', 'BlockUser:Clients',
            'ViewPhoneColumn:Shippers', 'ViewCommissionColumn:Shippers', 'EditPhoneField:Shippers', 'EditCommissionField:Shippers', 'BlockUser:Shippers',
            'ViewUsernameField:Clients', 'EditUsernameField:Clients', 'ViewPasswordField:Clients', 'EditPasswordField:Clients',
            'ViewUsernameField:Shippers', 'EditUsernameField:Shippers', 'ViewPasswordField:Shippers', 'EditPasswordField:Shippers',

            // Widgets
            'ViewWidget:OrdersStatsOverview', 'ViewWidget:OrdersStatusChart', 'ViewWidget:OrdersReport',
            'ViewWidget:CompanyStats', 'ViewWidget:ClientStats', 'ViewWidget:ShipperStats',
            'ViewWidget:MonthlyRevenue', 'ViewWidget:OrdersByGovernorate',

            // OrderStatus Custom
            'ViewNameColumn:OrderStatus', 'EditNameField:OrderStatus', 
            'ViewColorColumn:OrderStatus', 'EditColorField:OrderStatus',
            'ViewSortOrderColumn:OrderStatus', 'EditSortOrderField:OrderStatus',
            'ViewActiveColumn:OrderStatus', 'EditActiveField:OrderStatus',
            'ViewClearReasonsColumn:OrderStatus', 'EditClearReasonsField:OrderStatus',
            'ViewRefusedReasonsColumn:OrderStatus', 'EditRefusedReasonsField:OrderStatus',
            'ViewSlugColumn:OrderStatus', 'ManageReasons:OrderStatus',

            // RefusedReason Custom
            'ViewNameColumn:RefusedReason', 'EditNameField:RefusedReason',
            'ViewColorColumn:RefusedReason', 'EditColorField:RefusedReason',
            'ViewSortOrderColumn:RefusedReason', 'EditSortOrderField:RefusedReason',
            'ViewActiveColumn:RefusedReason', 'EditActiveField:RefusedReason',
            'ViewSlugColumn:RefusedReason', 'ViewOrderStatusesColumn:RefusedReason',

            // CollectedClient Visibility
            'ViewClientColumn:CollectedClient', 'EditClientField:CollectedClient',
            'ViewCollectionDateColumn:CollectedClient', 'EditCollectionDateField:CollectedClient',
            'ViewStatusColumn:CollectedClient', 'EditStatusField:CollectedClient',
            'ViewSelectedOrdersField:CollectedClient', 'EditSelectedOrdersField:CollectedClient',
            'ViewSummaryField:CollectedClient', 'ViewOrdersCountField:CollectedClient',
            'ViewTotalAmountField:CollectedClient', 'ViewFeesField:CollectedClient',
            'ViewNetAmountField:CollectedClient', 'ViewNotesField:CollectedClient', 'EditNotesField:CollectedClient',

            // CollectedShipper Visibility
            'ViewShipperColumn:CollectedShipper', 'EditShipperField:CollectedShipper',
            'ViewCollectionDateColumn:CollectedShipper', 'EditCollectionDateField:CollectedShipper',
            'ViewStatusColumn:CollectedShipper', 'EditStatusField:CollectedShipper',
            'ViewSelectedOrdersField:CollectedShipper', 'EditSelectedOrdersField:CollectedShipper',
            'ViewSummaryField:CollectedShipper', 'ViewOrdersCountField:CollectedShipper',
            'ViewTotalAmountField:CollectedShipper', 'ViewShipperFeesField:CollectedShipper',
            'ViewNetAmountField:CollectedShipper', 'ViewNotesField:CollectedShipper', 'EditNotesField:CollectedShipper',

            // ReturnedClient Visibility
            'ViewClientColumn:ReturnedClient', 'EditClientField:ReturnedClient',
            'ViewReturnDateColumn:ReturnedClient', 'EditReturnDateField:ReturnedClient',
            'ViewStatusColumn:ReturnedClient', 'EditStatusField:ReturnedClient',
            'ViewSelectedOrdersField:ReturnedClient', 'EditSelectedOrdersField:ReturnedClient',
            'ViewSummaryField:ReturnedClient', 'ViewNotesField:ReturnedClient', 'EditNotesField:ReturnedClient',

            // ReturnedShipper Visibility
            'ViewShipperColumn:ReturnedShipper', 'EditShipperField:ReturnedShipper',
            'ViewReturnDateColumn:ReturnedShipper', 'EditReturnDateField:ReturnedShipper',
            'ViewStatusColumn:ReturnedShipper', 'EditStatusField:ReturnedShipper',
            'ViewSelectedOrdersField:ReturnedShipper', 'EditSelectedOrdersField:ReturnedShipper',
            'ViewSummaryField:ReturnedShipper', 'ViewNotesField:ReturnedShipper', 'EditNotesField:ReturnedShipper',
        ];

        // Combine with standard CRUD for problematic resources
        $crud = ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny', 'Restore', 'RestoreAny', 'ForceDelete', 'ForceDeleteAny', 'Replicate', 'Reorder'];
        $models = ['User', 'Setting', 'ShippingContent', 'Role', 'Clients', 'Shippers', 'Order', 'OrderStatus', 'RefusedReason', 'CollectedClient', 'CollectedShipper', 'ReturnedClient', 'ReturnedShipper'];

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
