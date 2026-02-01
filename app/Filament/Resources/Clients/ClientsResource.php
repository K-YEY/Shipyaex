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
        return 'عميل';
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
                    ->label('اسم العميل')
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
                    
                Select::make('plan_id')
                    ->label('الباقة / السعر')
                    ->options(Plan::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                    
                Toggle::make('is_blocked')
                    ->label('محظور؟')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(User::role('client'))
            ->columns([
                TextColumn::make('id')
                    ->label('كود العميل')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('التليفون')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('الرقم اتنسخ!'),
                TextColumn::make('address')
                    ->label('العنوان')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->address),
                TextColumn::make('plan.name')
                    ->label('الباقة')
                    ->badge()
                    ->color('success'),
                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime('Y-m-d H:i')
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
                    ->label('عميل جديد')
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
