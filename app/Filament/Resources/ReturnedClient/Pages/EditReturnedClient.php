<?php

namespace App\Filament\Resources\ReturnedClient\Pages;

use App\Filament\Resources\ReturnedClientResource;
use App\Services\ReturnedClientService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditReturnedClient extends EditRecord
{
    protected static string $resource = ReturnedClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Save and Approve')
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
                                ->title('Error')
                                ->body('At least one order must be selected')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        $service = new ReturnedClientService();
                        $updatedRecord = $service->updateReturn($this->record, $orderIds);
                        $service->approveReturn($updatedRecord);
                        
                        Notification::make()
                            ->title('Saved and approved successfully ✅')
                            ->success()
                            ->send();
                        
                        $this->redirect($this->getResource()::getUrl('index'));
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
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
        $service = new ReturnedClientService();
        $orderIds = $data['selected_orders'] ?? [];
        return $service->updateReturn($record, $orderIds);
    }
}
