<?php

namespace App\Filament\Resources\CollectedClients\Pages;

use App\Filament\Resources\CollectedClients\CollectedClientResource;
use App\Services\CollectedClientService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCollectedClient extends EditRecord
{
    protected static string $resource = CollectedClientResource::class;

    protected static ?string $title = 'Edit تحصيل Client';

    protected static ?string $breadcrumb = 'Edit';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Save واعتماد')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => auth()->user()->isAdmin() && $this->record->status === 'pending')
                ->requiresConfirmation()
                ->modalHeading('Save وApprove collection')
                ->modalDescription('سيتم Save الEditات ثم Approve collection. هل أنت متأكد؟')
                ->action(function () {
                    // 1. Save الEditات أوNoً
                    try {
                        $data = $this->form->getState();
                        
                        // التحقق من وجود طلبات
                        $orderIds = $data['selected_orders'] ?? [];
                        
                        if (empty($orderIds)) {
                            Notification::make()
                                ->title('خطأ')
                                ->body('يجب اختيار طلب واحد على الأقل للتحصيل')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        // Save الEditات
                        $service = new CollectedClientService();
                        $updatedRecord = $service->updateCollection($this->record, $orderIds);
                        
                        // 2. Approve collection
                        $service->approveCollection($updatedRecord);
                        
                        Notification::make()
                            ->title('تم الSave واNoعتماد بنجاح ✅')
                            ->body('تم Save الEditات وApprove collection')
                            ->success()
                            ->send();
                        
                        $this->redirect($this->getResource()::getUrl('index'));
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('خطأ')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('cancel')
                ->label('Cancel collection')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => auth()->user()->isAdmin() && $this->record->status === 'pending')
                ->requiresConfirmation()
                ->action(function () {
                    $service = new CollectedClientService();
                    $service->cancelCollection($this->record);

                    Notification::make()
                        ->title('Collection Cancelled ❌')
                        ->danger()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            DeleteAction::make()
                ->label('Delete')
                ->visible(fn () => $this->record->status === 'pending'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // تحميل Orderات المرتبطة
        $data['selected_orders'] = $this->record->orders->pluck('id')->toArray();
        
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // التحقق من أن التحصيل No يزال Holdًا
        if ($record->status !== 'pending') {
            Notification::make()
                ->title('No يمكن Edit هذا التحصيل')
                ->body('التحصيل تم اعتماده أو إلغاؤه')
                ->danger()
                ->send();
            
            $this->halt();
        }

        $service = new CollectedClientService();

        // التحقق من وجود طلبات
        $orderIds = $data['selected_orders'] ?? [];
        
        if (empty($orderIds)) {
            Notification::make()
                ->title('خطأ')
                ->body('يجب اختيار طلب واحد على الأقل للتحصيل')
                ->danger()
                ->send();
            
            $this->halt();
        }

        // تحديث التحصيل
        return $service->updateCollection($record, $orderIds);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث التحصيل بنجاح ✅';
    }
}
