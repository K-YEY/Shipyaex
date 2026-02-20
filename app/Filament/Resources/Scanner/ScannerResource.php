<?php

namespace App\Filament\Resources\Scanner;

use App\Filament\Resources\Scanner\Pages\ListScanners;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class ScannerResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'ماسح الباركود';

    protected static ?string $slug = 'scanner';

    public static function getNavigationLabel(): string
    {
        return 'ماسح الباركود';
    }

    /**
     * من يستطيع رؤية الريسورس في القائمة الجانبية
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->can('ViewAny:Scanner'));
    }

    /**
     * من يستطيع الدخول على الصفحة
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->can('ViewAny:Scanner'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScanners::route('/'),
        ];
    }
}
