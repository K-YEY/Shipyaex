<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Plan;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('الاسم بالكامل')
                    ->required()
                    ->maxLength(255),
                    
                TextInput::make('phone')
                    ->label('رقم التليفون')
                    ->required()
                    ->tel()
                    ->maxLength(20)
                    ->unique(
                        table: User::class,
                        column: 'phone',
                        ignoreRecord: true
                    )
                    ->validationMessages([
                        'unique' => 'الرقم ده مسجل فعلاً! جرب رقم تاني.',
                    ]),
                    
                TextInput::make('address')
                    ->label('العنوان')
                    ->required()
                    ->maxLength(500),

                TextInput::make('username')
                    ->label('اسم المستخدم (Username)')
                    ->required()
                    ->alphaDash()
                    ->maxLength(50)
                    ->unique(
                        table: User::class,
                        column: 'username',
                        ignoreRecord: true
                    )
                    ->validationMessages([
                        'unique' => 'الاسم ده محجوز يا ريس، اختار واحد تاني.',
                    ]),
                    
                TextInput::make('password')
                    ->label('كلمة السر')
                    ->password()
                    ->revealable()
                    ->required(fn (?object $record) => $record === null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->minLength(6)
                    ->validationMessages([
                        'min' => 'كلمة السر لازم تكون 6 حروف على الأقل.',
                    ]),
                    
                TextInput::make('commission')
                    ->label('العمولة (للكباتن)')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0.0),
                    
                Select::make('shipping_content_id')
                    ->label('أنواع الشحنات')
                    ->relationship('shippingContents', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                    
                Select::make('plan_id')
                    ->label('الباقة (للعملاء)')
                    ->options(
                        Plan::pluck('name', 'id')
                    )
                    ->searchable()
                    ->preload(),
                    
                Select::make('roles')
                    ->label('الصلاحية (دوره إيه؟)')
                    ->relationship('roles', 'name', fn ($query) => $query->where('name', '!=', 'super_admin'))
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->required()
                    ->validationMessages([
                        'required' => 'لازم تحدد صلاحية واحدة على الأقل.',
                    ]),
                    
                Toggle::make('is_blocked')
                    ->label('محظور؟')
                    ->default(false),
            ]);
    }
}
