<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderStatusResource\Pages;
use App\Models\OrderStatus;
use BackedEnum, UnitEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Schemas\Components\Section;

class OrderStatusResource extends Resource
{
    protected static ?string $model = OrderStatus::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function getNavigationLabel(): string
    {
        return __('statuses.order_statuses');
    }

    public static function getModelLabel(): string
    {
        return __('statuses.order_status');
    }

    public static function getPluralModelLabel(): string
    {
        return __('statuses.order_statuses');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'الإعدادات والبيانات';
    }

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('statuses.status_information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('statuses.status_name'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('statuses.placeholder_status_name'))
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set) => $set('slug', \Illuminate\Support\Str::slug($state))),

                        Forms\Components\Hidden::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\Select::make('color')
                            ->label(__('statuses.badge_color'))
                            ->options([
                                'primary' => __('statuses.color_primary'),
                                'success' => __('statuses.color_success'),
                                'warning' => __('statuses.color_warning'),
                                'danger' => __('statuses.color_danger'),
                                'info' => __('statuses.color_info'),
                                'gray' => __('statuses.color_gray'),
                            ])
                            ->default('gray')
                            ->required(),



                        Forms\Components\TextInput::make('sort_order')
                            ->label(__('statuses.sort_order'))
                            ->numeric()
                            ->default(0)
                            ->helperText(__('statuses.sort_order_helper')),
                    ])
                    ->columns(2),

                Section::make(__('statuses.settings'))
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('statuses.active'))
                            ->helperText(__('statuses.active_helper'))
                            ->default(true),

                        Forms\Components\Toggle::make('clear_refused_reasons')
                            ->label(__('statuses.clear_reasons_label'))
                            ->helperText(__('statuses.clear_reasons_helper'))
                            ->default(false),
                    ])
                    ->columns(2),

                Section::make(__('statuses.refused_reasons_section'))
                    ->description(__('statuses.refused_reasons_desc'))
                    ->schema([
                        Forms\Components\Select::make('refusedReasons')
                            ->label(__('statuses.applicable_refused_reasons'))
                            ->relationship('refusedReasons', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText(__('statuses.applicable_refused_reasons_helper')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('statuses.status_name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('slug')
                    ->label(__('statuses.slug'))
                    ->searchable()
                    ->sortable()
                    ->color('gray')
                    ->badge(),

                Tables\Columns\TextColumn::make('color')
                    ->label(__('statuses.color'))
                    ->badge()
                    ->color(fn ($record) => $record->color),

                Tables\Columns\IconColumn::make('clear_refused_reasons')
                    ->label('Clear Reasons')
                    ->boolean()
                    ->alignCenter()
                    ->tooltip('Clears refused reasons when selected'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('statuses.active'))
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('statuses.order'))
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('refusedReasons_count')
                    ->label('Refused Reasons')
                    ->counts('refusedReasons')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('statuses.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('statuses.active_status'))
                    ->placeholder(__('statuses.all'))
                    ->trueLabel(__('statuses.active_only'))
                    ->falseLabel(__('statuses.inactive_only')),

                Tables\Filters\TernaryFilter::make('clear_refused_reasons')
                    ->label('Clear Refused Reasons')
                    ->placeholder('All')
                    ->trueLabel('Clears Reasons')
                    ->falseLabel('Keeps Reasons'),
            ])
            ->recordActions([
                EditAction::make(),
                \Filament\Actions\Action::make('manageRefusedReasons')
                    ->label('Manage Reasons')
                    ->icon('heroicon-o-list-bullet')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('refusedReasons')
                            ->label('Refused Reasons')
                            ->relationship('refusedReasons', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Select which refused reasons apply to this status'),
                    ])
                    ->fillForm(fn ($record) => [
                        'refusedReasons' => $record->refusedReasons->pluck('id')->toArray(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->refusedReasons()->sync($data['refusedReasons'] ?? []);
                        \Filament\Notifications\Notification::make()
                            ->title('Refused reasons updated successfully')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrderStatuses::route('/'),
            'create' => Pages\CreateOrderStatus::route('/create'),
            'edit' => Pages\EditOrderStatus::route('/{record}/edit'),
        ];
    }
}
