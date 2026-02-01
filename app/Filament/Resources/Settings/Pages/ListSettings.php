<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Resources\Settings\SettingResource;
use App\Models\Setting;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('seedDefaults')
        ->requiresConfirmation()
                ->label('Seed Default Settings')
                ->icon('heroicon-o-cog')
                ->color('success')
                ->action(function () {
                    Setting::seedDefaults();
Notification::make()
    ->title('Default settings seeded successfully')
    ->success()
    ->send();                }),
            CreateAction::make()
                ->label('Add New Setting'),
        ];
    }
}
