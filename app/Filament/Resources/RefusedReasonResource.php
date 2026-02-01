<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RefusedReasonResource\Pages;
use App\Models\RefusedReason;
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

class RefusedReasonResource extends Resource
{
    protected static ?string $model = RefusedReason::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedXCircle;

    public static function getNavigationLabel(): string
    {
        return __('statuses.refused_reasons');
    }

    public static function getModelLabel(): string
    {
        return __('statuses.refused_reason');
    }

    public static function getPluralModelLabel(): string
    {
        return __('statuses.refused_reasons');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'الإعدادات والبيانات';
    }

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
              Section::make(__('statuses.reason_information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('statuses.reason_name'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('statuses.placeholder_reason_name'))
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
                            ->default('warning')
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
                    ])
                    ->columns(1),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('statuses.reason_name'))
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

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('statuses.active'))
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('statuses.order'))
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('orderStatuses_count')
                    ->label('Used in Statuses')
                    ->counts('orderStatuses')
                    ->badge()
                    ->color('success')
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
            ])
            ->recordActions([
                EditAction::make(),
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
            'index' => Pages\ListRefusedReasons::route('/'),
            'create' => Pages\CreateRefusedReason::route('/create'),
            'edit' => Pages\EditRefusedReason::route('/{record}/edit'),
        ];
    }
}
