<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class SettingForm
{
    public static function getSchema(): array
    {
        return [
            Section::make('بيانات الإعداد')
                ->schema([
                    TextInput::make('key')
                        ->label('المفتاح (Key)')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->placeholder('مثال: require_shipper_collection_first')
                        ->helperText('المفتاح لازم يكون فريد ومفيهوش مسافات')
                        ->regex('/^[a-z0-9_]+$/')
                        ->columnSpanFull(),
                    
                    Textarea::make('value')
                        ->label('القيمة (Value)')
                        ->required()
                        ->placeholder('اكتب القيمة هنا')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(1),
            
            Section::make('إعدادات جاهزة')
                ->schema([
                    self::getPresetSettings(),
                ])
                ->collapsible()
                ->collapsed(),
        ];
    }

    private static function getPresetSettings(): Select
    {
        return Select::make('preset')
            ->label('اختار إعداد جاهز')
            ->placeholder('اختار عشان تملى البيانات بسرعة')
            ->options([
                'order_prefix' => 'بادئة رقم الأوردر (order_prefix)',
                'order_digits' => 'عدد خانات رقم الأوردر (order_digits)',
                'working_hours_orders_start' => 'بداية ساعات عمل الأوردرات',
                'working_hours_orders_end' => 'نهاية ساعات عمل الأوردرات',
                'require_shipper_collection_first' => 'تحصيل الكابتن أولاً (yes/no)',
                'pickup_min_orders' => 'الحد الأدنى لأوردرات البيك أب',
                'pickup_fee' => 'رسوم البيك أب',
                'collection_min_orders' => 'الحد الأدنى لأوردرات التحصيل',
                'collection_fee' => 'رسوم التحصيل',
                'order_follow_up_hours' => 'مدة متابعة الأوردر (بالساعات)',
            ])
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set) {
                if (!$state) return;
                
                $defaults = [
                    'order_prefix' => 'SHP',
                    'order_digits' => '5',
                    'working_hours_orders_start' => '08:00',
                    'working_hours_orders_end' => '22:00',
                    'require_shipper_collection_first' => 'yes',
                    'pickup_min_orders' => '10',
                    'pickup_fee' => '50.00',
                    'collection_min_orders' => '15',
                    'collection_fee' => '75.00',
                    'order_follow_up_hours' => '48',
                ];
                
                $set('key', $state);
                $set('value', $defaults[$state] ?? '');
            })
            ->dehydrated(false);
    }
}
