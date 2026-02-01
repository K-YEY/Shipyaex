<?php

namespace App\Filament\Resources\Clients;

use App\Filament\Resources\Clients\Pages\ManageClients;
use App\Models\Plan;
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

class ClientsResource extends Resource
{
    protected static ?string $model = User::class;
    
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $slug = 'clients';
    
    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('app.clients');
    }

    public static function getModelLabel(): string
    {
        return __('app.client');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.clients');
    }
    


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('app.name'))
                    ->required()
                    ->maxLength(255),
                    
                TextInput::make('phone')
                    ->label(__('app.phone'))
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
                    ->maxLength(500),

                TextInput::make('username')
                    ->label(__('app.username'))
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
                    ->password()
                    ->revealable()
                    ->required(fn (?object $record) => $record === null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->minLength(6)
                    ->validationMessages([
                        'min' => __('app.min_password'),
                    ]),
                    
                Select::make('plan_id')
                    ->label(__('app.plan_id'))
                    ->options(Plan::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                    
                Toggle::make('is_blocked')
                    ->label(__('app.is_blocked'))
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(User::role('client'))
            ->columns([
                TextColumn::make('id')
                    ->label(__('app.client') . ' ID')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('name')
                    ->label(__('app.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label(__('app.phone'))
                    ->searchable()
                    ->copyable()
                    ->copyMessage(__('app.copied')),
                TextColumn::make('address')
                    ->label(__('app.address'))
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->address),
                TextColumn::make('plan.name')
                    ->label(__('app.plan'))
                    ->badge()
                    ->color('success'),
                TextColumn::make('created_at')
                    ->label(__('app.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make()->label(__('app.edit')),
                \Filament\Actions\DeleteAction::make()->label(__('app.delete')),
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make()
                    ->label(__('app.new') . ' ' . __('app.client'))
                    ->mutateFormDataUsing(function (array $data): array {
                        // Add role client تلقائياً
                        return $data;
                    })
                    ->after(function ($record) {
                        // Add role client بعد الإنشاء
                        $record->assignRole('client');
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageClients::route('/'),
        ];
    }
}
