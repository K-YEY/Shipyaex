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

            // Governorate Custom
            'ViewNameColumn:Governorate', 'ViewFollowUpHoursColumn:Governorate', 'ViewShipperColumn:Governorate', 'ViewDatesColumn:Governorate',
            'EditNameField:Governorate', 'EditFollowUpHoursField:Governorate', 'EditShipperField:Governorate',

            // City Custom
            'ViewGovernorateColumn:City', 'ViewNameColumn:City', 'ViewDatesColumn:City',
            'EditGovernorateField:City', 'EditNameField:City',

            // ShippingContent Custom
            'ViewNameColumn:ShippingContent', 'EditNameField:ShippingContent', 'ExportData:ShippingContent',

            // Order Custom Fields/Sections
            'ViewExternalCodeField:Order', 'EditExternalCodeField:Order', 'EditClientField:Order', 'AssignShipperField:Order', 
            'ChangeStatusField:Order', 'ViewOrderNotesField:Order', 'EditOrderNotesField:Order', 'ViewCustomerDetailsSection:Order', 
            'EditCustomerDetails:Order', 'EditCustomerDetailsField:Order', 'ViewFinancialSummarySection:Order', 'EditFinancialSummaryField:Order', 
            'ViewShipperFeesField:Order', 'EditShipperFeesField:Order', 'ViewCopField:Order', 'EditCopField:Order',
            'BypassWorkingHours:Order', 'AssignShipper:Order', 'BarcodeScannerAction:Order',
            'ManageShipperReturnAction:Order', 'ManageClientReturnAction:Order',

            // Scanner Permissions
            'ViewAny:Scanner',           // رؤية صفحة الماسح
            'ChangeStatus:Scanner',      // تغيير حالة الطلبات من الماسح
            'AssignShipper:Scanner',     // إسناد طلبات لمندوب من الماسح
            'ReturnShipper:Scanner',     // تسجيل مرتجع مندوب من الماسح
            'ReturnClient:Scanner',      // تسجيل مرتجع عميل من الماسح
            'ClearList:Scanner',         // مسح قائمة الباركود
            'RemoveOrder:Scanner',       // إزالة طلب من القائمة
        ];

        // Combine with standard CRUD for problematic resources
        $crud = ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny', 'Restore', 'RestoreAny', 'ForceDelete', 'ForceDeleteAny', 'Replicate', 'Reorder'];
        $models = ['User', 'Setting', 'ShippingContent', 'Role', 'Clients', 'Shippers', 'Order', 'OrderStatus', 'RefusedReason', 'CollectedClient', 'CollectedShipper', 'ReturnedClient', 'ReturnedShipper', 'Governorate', 'City', 'Scanner'];

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

        // 4. Sync Client Role Permissions
        $this->info("Syncing client role permissions...");
        $clientRole = Role::where('name', 'client')->first();
        if ($clientRole) {
            $clientPermissions = [
                // CRUD الأساسي
                'ViewAny:Order', 'View:Order', 'Create:Order',

                // الحقول اللازمة لإنشاء الأوردر
                'ViewCustomerDetailsSection:Order',   // رؤية قسم بيانات الزبون
                'EditCustomerDetails:Order',           // تعديل اسم / هاتف الزبون
                'EditCustomerDetailsField:Order',      // تعديل المحافظة / المدينة / العنوان
                'ViewFinancialSummarySection:Order',   // رؤية قسم المالية
                'EditFinancialSummaryField:Order',     // تعديل الإجمالي

                // الأعمدة التي يراها العميل في الجدول
                'ViewCodeColumn:Order',
                'ViewRegistrationDateColumn:Order',
                'ViewRecipientNameColumn:Order',
                'ViewPhoneColumn:Order',
                'ViewAddressColumn:Order',
                'ViewGovernorateColumn:Order',
                'ViewCityColumn:Order',
                'ViewTotalAmountColumn:Order',
                'ViewShippingFeesColumn:Order',
                'ViewCollectionAmountColumn:Order',
                'ViewStatusColumn:Order',
                'ViewStatusNotesColumn:Order',
                'ViewOrderNotesColumn:Order',
                'ViewOrderNotesField:Order',

                // فلاتر العميل
                'ViewStatusFilter:Order',
                'ViewHasReturnFilter:Order',
                'ViewSettledWithClientFilter:Order',
                'ViewReturnedToClientFilter:Order',

                // تقارير العميل
                'ViewWidget:ClientStats',

                // تحصيلات العميل
                'ViewAny:CollectedClient', 'View:CollectedClient',

                // مرتجعات العميل
                'ViewAny:ReturnedClient', 'View:ReturnedClient',
            ];
            $clientRole->givePermissionTo(
                Permission::whereIn('name', $clientPermissions)->get()
            );
            $this->info("Synced client permissions (" . count($clientPermissions) . " perms)");
        } else {
            $this->warn("Client role not found, skipping.");
        }

        // 5. Sync Shipper Role Permissions
        $this->info("Syncing shipper role permissions...");
        $shipperRole = Role::where('name', 'shipper')->first();
        if ($shipperRole) {
            $shipperPermissions = [
                // CRUD الأساسي
                'ViewAny:Order', 'View:Order',

                // أعمدة يراها المندوب
                'ViewCodeColumn:Order',
                'ViewRegistrationDateColumn:Order',
                'ViewShipperDateColumn:Order',
                'ViewRecipientNameColumn:Order',
                'ViewPhoneColumn:Order',
                'ViewAddressColumn:Order',
                'ViewGovernorateColumn:Order',
                'ViewCityColumn:Order',
                'ViewTotalAmountColumn:Order',
                'ViewShippingFeesColumn:Order',
                'ViewShipperCommissionColumn:Order',
                'ViewCollectionAmountColumn:Order',
                'ViewStatusColumn:Order',
                'ViewStatusNotesColumn:Order',
                'ViewOrderNotesColumn:Order',
                'ViewShipperColumn:Order',

                // تغيير الحالة
                'ChangeStatusAction:Order',
                'ChangeStatus:Order',
                'ViewStatusFilter:Order',

                // مرتجع المندوب
                'ManageShipperReturnAction:Order',
                'ViewReturnedFromShipperFilter:Order',
                'ViewHasReturnFilter:Order',

                // تقارير المندوب
                'ViewWidget:ShipperStats',

                // تحصيلات المندوب
                'ViewAny:CollectedShipper', 'View:CollectedShipper',

                // مرتجعات المندوب
                'ViewAny:ReturnedShipper', 'View:ReturnedShipper',
            ];
            $shipperRole->givePermissionTo(
                Permission::whereIn('name', $shipperPermissions)->get()
            );
            $this->info("Synced shipper permissions (" . count($shipperPermissions) . " perms)");
        } else {
            $this->warn("Shipper role not found, skipping.");
        }

        // 6. Clear Caches
        $this->info("Clearing caches...");
        Artisan::call('optimize:clear');
        Artisan::call('permission:cache-reset');

        $this->info("Sync Completed Successfully!");
    }
}

