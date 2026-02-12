<?php

namespace App\Filament\Resources\Scanner;

use App\Filament\Resources\Scanner\Pages\ListScanners;
use App\Models\Order;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class ScannerResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'ماسح الباركود';

    protected static ?string $slug = 'scanner';

    public static function getNavigationLabel(): string
    {
        return 'ماسح الباركود';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScanners::route('/'),
        ];
    }
}
