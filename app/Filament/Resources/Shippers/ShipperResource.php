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
        return __('app.shipper');
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
                    ->label(__('app.name'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewNameColumn:Shippers'))
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditNameField:Shippers'))
                    ->required()
                    ->maxLength(255),
                    
                TextInput::make('phone')
                    ->label(__('app.phone'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewPhoneColumn:Shippers'))
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditPhoneField:Shippers'))
                    ->required()
                    ->tel()
                    ->maxLength(20)
                    ->unique(
                        table: User::class,
                        column: 'phone',
                        ignoreRecord: true
                    )
                    ->validationMessages([
                        'unique' => __('app.unique_phone'),
                    ]),
                    
                TextInput::make('address')
                    ->label(__('app.address'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewAddressColumn:Shippers'))
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditAddressField:Shippers'))
                    ->maxLength(500),

                TextInput::make('username')
                    ->label(__('app.username'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewUsernameField:Shippers'))
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditUsernameField:Shippers'))
                    ->required()
                    ->alphaDash()
                    ->maxLength(50)
                    ->unique(
                        table: User::class,
                        column: 'username',
                        ignoreRecord: true
                    )
                    ->validationMessages([
                        'unique' => __('app.unique_username'),
                    ]),
                    
                TextInput::make('password')
                    ->label(__('app.password'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewPasswordField:Shippers'))
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditPasswordField:Shippers'))
                    ->password()
                    ->revealable()
                    ->required(fn (?object $record) => $record === null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->minLength(6)
                    ->validationMessages([
                        'min' => __('app.min_password'),
                    ]),
                    
                TextInput::make('commission')
                    ->label(__('app.total_commission'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewCommissionColumn:Shippers'))
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditCommissionField:Shippers'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0.0)
                    ->suffix(__('statuses.currency')),
                    
                Toggle::make('is_blocked')
                    ->label(__('app.is_blocked'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('BlockUser:Shippers'))
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('BlockUser:Shippers'))
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(User::role('shipper'))
            ->columns([
                TextColumn::make('id')
                    ->label(__('app.shipper') . ' ID')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewIdColumn:Shippers'))
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('name')
                    ->label(__('app.name'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewNameColumn:Shippers'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label(__('app.phone'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewPhoneColumn:Shippers'))
                    ->searchable()
                    ->copyable()
                    ->copyMessage(__('app.copied')),
                TextColumn::make('commission')
                    ->label(__('app.total_commission'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewCommissionColumn:Shippers'))
                    ->state(fn ($record) => number_format($record->commission, 2) . ' ' . __('statuses.currency'))
                    ->sortable(),
                TextColumn::make('address')
                    ->label(__('app.address'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewAddressColumn:Shippers'))
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('app.created_at'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewDatesColumn:Shippers'))
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make()
                    ->label(__('app.edit'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('Update:Shippers')),
                \Filament\Actions\DeleteAction::make()
                    ->label(__('app.delete'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('Delete:Shippers')),
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make()
                    ->label(__('app.new') . ' ' . __('app.shipper'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('Create:Shippers'))
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
