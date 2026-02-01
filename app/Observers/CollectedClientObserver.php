<?php

namespace App\Observers;

use App\Models\CollectedClient;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class CollectedClientObserver
{
    /**
     * Handle the CollectedClient "created" event.
     */
    public function created(CollectedClient $collectedClient): void
    {
        // إشعار للأدمن بطلب التحصيل الجديد
        $admins = User::getAdmins();
        $clientName = $collectedClient->client->name ?? 'عميل';
        
        Notification::make()
            ->title('طلب تسوية جديد (عميل)')
            ->body("العميل **{$clientName}** طلب تسوية مبلغ **{$collectedClient->net_amount} ج.م**")
            ->icon('heroicon-o-currency-dollar')
            ->color('success')
            ->actions([
                Action::make('view')
                    ->label('عرض التفاصيل')
                    ->url("/admin/collected-clients/{$collectedClient->id}")
                    ->markAsRead(),
            ])
            ->sendToDatabase($admins);
    }

    /**
     * Handle the CollectedClient "updated" event.
     */
    public function updated(CollectedClient $collectedClient): void
    {
        // لو Status تغيرت (مثNoً الأدمن وافق أو رفض) -> إشعار للعميل
        if ($collectedClient->isDirty('status')) {
            $statusLabel = match($collectedClient->status) {
                'completed' => 'تم الاعتماد ✅',
                'cancelled' => 'ملغى ❌',
                'pending' => 'قيد المراجعة ⏳',
                default => $collectedClient->status,
            };

            $color = match($collectedClient->status) {
                'completed' => 'success',
                'cancelled' => 'danger',
                default => 'info',
            };

            if ($collectedClient->client) {
                Notification::make()
                    ->title('تحديث حالة طلب التسوية')
                    ->body("حالة طلب التسوية رقم **{$collectedClient->id}** اتغيرت لـ **{$statusLabel}**")
                    ->icon('heroicon-o-banknotes')
                    ->color($color)
                    ->actions([
                        Action::make('view')
                            ->label('عرض التفاصيل')
                            ->url("/admin/collected-clients/{$collectedClient->id}")
                            ->markAsRead(),
                    ])
                    ->sendToDatabase($collectedClient->client);
            }
        }
    }
}
