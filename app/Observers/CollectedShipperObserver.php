<?php

namespace App\Observers;

use App\Models\CollectedShipper;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class CollectedShipperObserver
{
    /**
     * Handle the CollectedShipper "created" event.
     */
    public function created(CollectedShipper $collectedShipper): void
    {
        // إشعار للأدمن بطلب التحصيل الجديد
        $admins = User::getAdmins();
        $shipperName = $collectedShipper->shipper->name ?? 'Shipper';
        
        Notification::make()
            ->title('طلب توريد جديد (كابتن)')
            ->body("الكابتن **{$shipperName}** طلب توريد مبلغ **{$collectedShipper->net_amount} ج.م**")
            ->icon('heroicon-o-banknotes')
            ->color('warning')
            ->actions([
                Action::make('view')
                    ->label('عرض التفاصيل')
                    ->url("/admin/collected-shippers/{$collectedShipper->id}")
                    ->markAsRead(),
            ])
            ->sendToDatabase($admins);
    }

    /**
     * Handle the CollectedShipper "updated" event.
     */
    public function updated(CollectedShipper $collectedShipper): void
    {
        // لو Status تغيرت -> إشعار للShipper
        if ($collectedShipper->isDirty('status')) {
            $statusLabel = match($collectedShipper->status) {
                'completed' => 'تم الاعتماد ✅',
                'cancelled' => 'ملغى ❌',
                'pending' => 'قيد المراجعة ⏳',
                default => $collectedShipper->status,
            };

            $color = match($collectedShipper->status) {
                'completed' => 'success',
                'cancelled' => 'danger',
                default => 'info',
            };

            if ($collectedShipper->shipper) {
                Notification::make()
                    ->title('تحديث حالة طلب التوريد')
                    ->body("حالة طلب التوريد رقم **{$collectedShipper->id}** اتغيرت لـ **{$statusLabel}**")
                    ->icon('heroicon-o-currency-dollar')
                    ->color($color)
                    ->actions([
                        Action::make('view')
                            ->label('عرض التفاصيل')
                            ->url("/admin/collected-shippers/{$collectedShipper->id}")
                            ->markAsRead(),
                    ])
                    ->sendToDatabase($collectedShipper->shipper);
            }
        }
    }
}
