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
        return __('app.settings');
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
                            ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->can('ViewNameColumn:RefusedReason'))
                            ->disabled(fn () => !auth()->user()?->isAdmin() && !auth()->user()?->can('EditNameField:RefusedReason'))
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
                            ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->can('ViewColorColumn:RefusedReason'))
                            ->disabled(fn () => !auth()->user()?->isAdmin() && !auth()->user()?->can('EditColorField:RefusedReason'))
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
                            ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->can('ViewSortOrderColumn:RefusedReason'))
                            ->disabled(fn () => !auth()->user()?->isAdmin() && !auth()->user()?->can('EditSortOrderField:RefusedReason'))
                            ->numeric()
                            ->default(0)
                            ->helperText(__('statuses.sort_order_helper')),
                    ])
                    ->columns(2),

                Section::make(__('statuses.settings'))
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('statuses.active'))
                            ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->can('ViewActiveColumn:RefusedReason'))
                            ->disabled(fn () => !auth()->user()?->isAdmin() && !auth()->user()?->can('EditActiveField:RefusedReason'))
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
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->can('ViewNameColumn:RefusedReason'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('slug')
                    ->label(__('statuses.slug'))
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->can('ViewSlugColumn:RefusedReason'))
                    ->searchable()
                    ->sortable()
                    ->color('gray')
                    ->badge(),

                Tables\Columns\TextColumn::make('color')
                    ->label(__('statuses.color'))
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->can('ViewColorColumn:RefusedReason'))
                    ->badge()
                    ->color(fn ($record) => $record->color),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('statuses.active'))
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->can('ViewActiveColumn:RefusedReason'))
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('statuses.order'))
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->can('ViewSortOrderColumn:RefusedReason'))
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('orderStatuses_count')
                    ->label(__('statuses.order_statuses'))
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->can('ViewOrderStatusesColumn:RefusedReason'))
                    ->counts('orderStatuses')
                    ->badge()
                    ->color('success')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('statuses.created'))
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->can('ViewDatesColumn:RefusedReason'))
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
                EditAction::make()
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->can('Update:RefusedReason')),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->can('Delete:RefusedReason')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->can('DeleteAny:RefusedReason')),
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
