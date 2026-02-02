<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        $user = auth()->user();
        
        // ✅ تسجيل إنشاء الأوردر في السجل
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $order->status,
            'old_status' => null,
            'note' => 'تم إنشاء أوردر جديد',
            'changed_by' => $user?->id,
            'action_type' => 'created',
        ]);

        // 1. لو Client هو اللي عمل الأوردر -> إشعار للأدمن
        if ($user && $user->isClient()) {
            $admins = User::getAdmins();
            
            Notification::make()
                ->title('أوردر جديد من عميل')
                ->body("العميل **{$user->name}** عمل أوردر جديد برقم **{$order->code}**")
                ->icon('heroicon-o-shopping-bag')
                ->color('primary')
                ->actions([
                    Action::make('view')
                        ->label('عرض الأوردر')
                        ->url("/admin/orders/{$order->id}")
                        ->markAsRead(),
                ])
                ->sendToDatabase($admins);
        }

        // 2. لو تم تعيين كابتن (سواء عند الإنشاء أو التحديث) -> إشعار للكابتن
        if ($order->shipper_id) {
            $this->notifyShipper($order);
        }

        // 3. ✅ فحص Plan - إرسال إشعار للأدمن إذا تجاوز Client الحد المسموح
        $this->checkPlanLimit($order);
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        $user = auth()->user();
        $changes = [];

        // 1. لو تغيرت Status
        if ($order->isDirty('status')) {
            $oldStatus = $order->getOriginal('status');
            $newStatus = $order->status;
            
            // ✅ تسجيل تغيير الحالة في السجل
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $newStatus,
                'old_status' => $oldStatus,
                'note' => "تم تغيير الحالة من ({$oldStatus}) إلى ({$newStatus})",
                'changed_by' => $user?->id,
                'action_type' => 'status_changed',
            ]);

            // إشعار للعميل
            if ($order->client) {
                Notification::make()
                    ->title('تحديث حالة الأوردر')
                    ->body("حالة الأوردر بتاعك رقم **{$order->code}** اتغيرت لـ **{$order->status}**")
                    ->icon('heroicon-o-arrow-path')
                    ->color($order->status_color ?? 'info')
                    ->actions([
                        Action::make('view')
                            ->label('عرض الأوردر')
                            ->url("/admin/orders/{$order->id}")
                            ->markAsRead(),
                    ])
                    ->sendToDatabase($order->client);
            }

            // لو Shipper هو اللي غير Status -> إشعار للأدمن
            if ($user && $user->isShipper()) {
                $admins = User::getAdmins();
                
                Notification::make()
                    ->title('تحديث من الكابتن')
                    ->body("الكابتن **{$user->name}** غيّر حالة الأوردر **{$order->code}** لـ **{$order->status}**")
                    ->icon('heroicon-o-truck')
                    ->color('warning')
                    ->actions([
                        Action::make('view')
                            ->label('عرض الأوردر')
                            ->url("/admin/orders/{$order->id}")
                            ->markAsRead(),
                    ])
                    ->sendToDatabase($admins);
            }
        }

        // 2. لو تم Assign Shipper جديد (أو تغير Shipper)
        if ($order->isDirty('shipper_id') && $order->shipper_id) {
            $oldShipperId = $order->getOriginal('shipper_id');
            $oldShipper = $oldShipperId ? User::find($oldShipperId)?->name : null;
            $newShipper = $order->shipper?->name;

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $order->status,
                'old_status' => null,
                'note' => $oldShipper 
                    ? "تم تغيير الكابتن من ({$oldShipper}) إلى ({$newShipper})"
                    : "تم تعيين الكابتن ({$newShipper})",
                'changed_by' => $user?->id,
                'action_type' => 'shipper_assigned',
            ]);

            $this->notifyShipper($order);
        }

        // 3. تسجيل التحصيل من الكابتن
        if ($order->isDirty('collected_shipper') && $order->collected_shipper) {
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $order->status,
                'old_status' => null,
                'note' => 'تم تحصيل المبلغ من الكابتن',
                'changed_by' => $user?->id,
                'action_type' => 'collected_shipper',
            ]);
        }

        // 4. تسجيل الCollect for Client
        if ($order->isDirty('collected_client') && $order->collected_client) {
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $order->status,
                'old_status' => null,
                'note' => 'تم تحصيل المبلغ للعميل',
                'changed_by' => $user?->id,
                'action_type' => 'collected_client',
            ]);
        }

        // 5. تسجيل مرتجع الكابتن
        if ($order->isDirty('return_shipper') && $order->return_shipper) {
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $order->status,
                'old_status' => null,
                'note' => 'تم تفعيل مرتجع الكابتن',
                'changed_by' => $user?->id,
                'action_type' => 'return_shipper',
            ]);
        }

        // 6. تسجيل مرتجع العميل
        if ($order->isDirty('return_client') && $order->return_client) {
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $order->status,
                'old_status' => null,
                'note' => 'تم تفعيل مرتجع العميل',
                'changed_by' => $user?->id,
                'action_type' => 'return_client',
            ]);
        }

        // ✅ 7. تسجيل كل التغييرات الأخرى (أي حقل تغير)
        $this->logAllChanges($order, $user);

        // ✅ 8. إشعار للأدمن بأي تعديل حصل (لو مش هو اللي عدل)
        $this->notifyAdminsOfGeneralUpdate($order, $user);
    }

    /**
     * إشعار الأدمن بأي تعديل عام
     */
    private function notifyAdminsOfGeneralUpdate(Order $order, $user): void
    {
        // لو مش حالة تغيرت (عشان ليها إشعار خاص)
        if (!$order->isDirty('status')) {
            $admins = User::getAdmins();
            $changedBy = $user?->name ?? 'النظام';
            
            // تحديد الدور
            $role = 'مستخدم';
            if ($user) {
                if ($user->isAdmin()) $role = 'مدير';
                elseif ($user->isShipper()) $role = 'كابتن';
                elseif ($user->isClient()) $role = 'عميل';
            }

            Notification::make()
                ->title("تعديل على أوردر من قبل {$role}")
                ->body("قام {$role} **{$changedBy}** بتعديل بيانات في الأوردر **{$order->code}**")
                ->icon('heroicon-o-pencil-square')
                ->color('info')
                ->actions([
                    Action::make('view')
                        ->label('عرض التفاصيل')
                        ->url("/admin/orders/{$order->id}")
                        ->markAsRead(),
                ])
                ->sendToDatabase($admins);
        }
    }

    /**
     * تسجيل كل التغييرات في الأوردر
     */
    private function logAllChanges(Order $order, $user): void
    {
        $dirtyFields = $order->getDirty();
        $fieldsToLog = [
            'code' => 'الكود',
            'external_code' => 'كود برة',
            'name' => 'الاسم',
            'phone' => 'رقم التليفون',
            'phone_2' => 'رقم التليفون التاني',
            'address' => 'العنوان',
            'governorate_id' => 'المحافظة',
            'city_id' => 'المدينة / المنطقة',
            'total_amount' => 'إجمالي المبلغ',
            'fees' => 'رسوم الشركة',
            'shipper_fees' => 'عمولة الكابتن',
            'cop' => 'مبلغ التحصيل (COP)',
            'cod' => 'مبلغ التوصيل (COD)',
            'status_note' => 'ملاحظات الحالة',
            'order_note' => 'ملاحظات الأوردر',
            'allow_open' => 'السماح بالفتح',
            'client_id' => 'العميل',
            'has_return' => 'يوجد مرتجع',
        ];

        foreach ($dirtyFields as $field => $newValue) {
            // تخطي الحقول اللي تم تسجيلها بالفعل
            if (in_array($field, ['status', 'shipper_id', 'collected_shipper', 'collected_client', 'return_shipper', 'return_client', 'updated_at'])) {
                continue;
            }

            // تخطي الحقول غير المهمة
            if (!isset($fieldsToLog[$field])) {
                continue;
            }

            $oldValue = $order->getOriginal($field);
            $fieldLabel = $fieldsToLog[$field];

            // تنسيق القيم
            $oldValueFormatted = $this->formatValue($field, $oldValue, $order);
            $newValueFormatted = $this->formatValue($field, $newValue, $order);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $order->status,
                'old_status' => null,
                'note' => "تم تغيير {$fieldLabel} من ({$oldValueFormatted}) إلى ({$newValueFormatted})",
                'changed_by' => $user?->id,
                'action_type' => 'field_updated',
            ]);
        }
    }

    /**
     * تنسيق Value للعرض
     */
    private function formatValue($field, $value, Order $order)
    {
        if (is_null($value)) {
            return 'فارغ';
        }

        // تنسيق حسب نوع الحقل
        switch ($field) {
            case 'governorate_id':
                return $value ? (\App\Models\Governorate::find($value)?->name ?? $value) : 'فارغ';
            
            case 'city_id':
                return $value ? (\App\Models\City::find($value)?->name ?? $value) : 'فارغ';
            
            case 'client_id':
                return $value ? (User::find($value)?->name ?? $value) : 'فارغ';
            
            case 'allow_open':
            case 'has_return':
                return $value ? 'Yes' : 'No';
            
            case 'total_amount':
            case 'fees':
            case 'shipper_fees':
            case 'cop':
            case 'cod':
                return number_format($value, 2) . ' ج.م';
            
            case 'status_note':
                return is_array($value) ? implode(', ', $value) : $value;
            
            default:
                return $value;
        }
    }

    protected function notifyShipper(Order $order): void
    {
        if (!$order->shipper) return;

        Notification::make()
            ->title('تم تعيين أوردر جديد لك')
            ->body("تم تعيين أوردر رقم **{$order->code}** لك للتوصيل")
            ->icon('heroicon-o-truck')
            ->color('info')
            ->actions([
                Action::make('view')
                    ->label('عرض الأوردر')
                    ->url("/admin/orders/{$order->id}")
                    ->markAsRead(),
            ])
            ->sendToDatabase($order->shipper);
    }

    /**
     * ✅ فحص Plan وإرسال إشعار للأدمن إذا تجاوز Client الحد المسموح
     * يرسل إشعار عند: أول تجاوز، ثم عند كل 10 أوردرات إضافية
     */
    private function checkPlanLimit(Order $order): void
    {
        // التحقق من وجود عميل
        if (!$order->client_id) return;
        
        $client = User::with('plan')->find($order->client_id);
        if (!$client || !$client->plan) return;
        
        $plan = $client->plan;
        $maxOrders = $plan->order_count;
        
        // إذا كان الحد 0 أو null يعني غير محدود
        if (!$maxOrders || $maxOrders <= 0) return;
        
        // حساب عدد أوردرات Client هذا الشهر
        $monthlyOrderCount = Order::where('client_id', $client->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        
        // التحقق من تجاوز الحد
        if ($monthlyOrderCount > $maxOrders) {
            $exceededBy = $monthlyOrderCount - $maxOrders;
            
            // ✅ إرسال إشعار فقط عند:
            // 1. أول تجاوز (exceeded by 1)
            // 2. كل 10 أوردرات إضافية (10, 20, 30...)
            $shouldNotify = ($exceededBy === 1) || ($exceededBy % 10 === 0);
            
            if (!$shouldNotify) return;
            
            // Search عن Plan المقترحة (أول خطة بحد أكبر من عدد الأوردرات الحالي)
            $suggestedPlan = \App\Models\Plan::where('order_count', '>', $monthlyOrderCount)
                ->orderBy('order_count', 'asc')
                ->first();

            $admins = User::getAdmins();
            
            // تحديد نوع الإشعار
            $isFirstExceed = ($exceededBy === 1);
            $title = $isFirstExceed 
                ? '⚠️ عميل تجاوز حد Plan' 
                : "🔔 تذكير: عميل متجاوز بـ {$exceededBy} طلب";
            
            $bodyText = "Client **{$client->name}** ";
            $bodyText .= $isFirstExceed ? "تجاوز" : "No يزال متجاوزاً";
            $bodyText .= " حد خطته الحالية ({$plan->name})\n";
            $bodyText .= "📊 عدد Orderات هذا الشهر: **{$monthlyOrderCount}**\n";
            $bodyText .= "📦 الحد المسموح: **{$maxOrders}** طلب\n";
            $bodyText .= "❌ التجاوز: **{$exceededBy}** طلب\n";
            
            if ($suggestedPlan) {
                $bodyText .= "💡 Plan المقترحة: **{$suggestedPlan->name}** (حتى {$suggestedPlan->order_count} طلب)";
            } else {
                $bodyText .= "⚠️ No توجد خطة أعلى متاحة - يُفضل إنشاء خطة مخصصة";
            }
            
            $admins = User::getAdmins();
            
            Notification::make()
                ->title($title)
                ->body($bodyText)
                ->icon($isFirstExceed ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-bell-alert')
                ->color($isFirstExceed ? 'warning' : 'danger')
                ->actions([
                    Action::make('viewClient')
                        ->label('عرض Client')
                        ->url("/admin/users/{$client->id}/edit")
                        ->markAsRead(),
                    Action::make('viewOrders')
                        ->label('طلبات Client')
                        ->url("/admin/orders?tableFilters[client_id][value]={$client->id}")
                        ->openUrlInNewTab(),
                ])
                ->sendToDatabase($admins);
        }
    }
}

