<?php

namespace App\Filament\Resources\ReturnedShipper\Pages;

use App\Filament\Resources\ReturnedShipperResource;
use App\Services\ReturnedShipperService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditReturnedShipper extends EditRecord
{
    protected static string $resource = ReturnedShipperResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Save واعتماد')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => auth()->user()->isAdmin() && $this->record->status === 'pending')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        $data = $this->form->getState();
                        $orderIds = $data['selected_orders'] ?? [];
                        
                        if (empty($orderIds)) {
                            Notification::make()
                                ->title('خطأ')
                                ->body('يجب اختيار طلب واحد على الأقل')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        $service = new ReturnedShipperService();
                        $updatedRecord = $service->updateReturn($this->record, $orderIds);
                        $service->approveReturn($updatedRecord);
                        
                        Notification::make()
                            ->title('تم الSave واNoعتماد بنجاح ✅')
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

            DeleteAction::make()
                ->visible(fn () => $this->record->status === 'pending'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['selected_orders'] = $this->record->orders->pluck('id')->toArray();
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $service = new ReturnedShipperService();
        $orderIds = $data['selected_orders'] ?? [];
        return $service->updateReturn($record, $orderIds);
    }
}
