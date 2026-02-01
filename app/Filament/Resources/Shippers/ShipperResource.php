<?php

namespace App\Filament\Resources\Shippers;

use App\Filament\Resources\Shippers\Pages\ListShippers;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class ShipperResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?string $slug = 'shippers';
    
    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('app.shippers');
    }

    public static function getModelLabel(): string
    {
        return 'كابتن';
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.shippers');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم الكابتن')
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
                        'unique' => 'الرقم ده موجود قبل كدا! شوف غيره.',
                    ]),
                    
                TextInput::make('address')
                    ->label('العنوان')
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
                    ->label('العمولة')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0.0)
                    ->suffix('ج.م'),
                    
                Toggle::make('is_blocked')
                    ->label('محظور؟')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(User::role('shipper'))
            ->columns([
                TextColumn::make('id')
                    ->label('كود الكابتن')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('name')
                    ->label('اسم الكابتن')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('التليفون')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('الرقم اتنسخ!'),
                TextColumn::make('commission')
                    ->label('العمولة')
                    ->state(fn ($record) => number_format($record->commission, 2) . ' ج.م')
                    ->sortable(),
                TextColumn::make('address')
                    ->label('العنوان')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make()->label('تعديل'),
                \Filament\Actions\DeleteAction::make()->label('مسح'),
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make()
                    ->label('كابتن جديد')
                    ->after(function ($record) {
                        $record->assignRole('shipper');
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShippers::route('/'),
        ];
    }
}
