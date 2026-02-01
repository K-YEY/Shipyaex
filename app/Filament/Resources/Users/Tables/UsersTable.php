<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('كود العضو')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->copyMessage('الكود اتنسخ يا ريس!')
                    ->copyMessageDuration(1500)
                    ->badge()
                    ->color('primary'),
                TextColumn::make('username')
                    ->label('اسم المستخدم')
                    ->searchable()->icon('heroicon-o-hashtag')
                    ->copyable()
                    ->copyMessage('اتنسخ!')
                    ->copyMessageDuration(1500)
                    ->iconPosition(IconPosition::Before)->color('warning')->iconColor('warning'),
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
                TextColumn::make('roles.name')->color('info')
                    ->label('الصلاحية')->toggleable()->badge(),
            
                ToggleColumn::make('is_blocked')
                    ->label('محظور؟')
                    ->onColor('danger')    // Color when status is 1 (Blocked)
                    ->offColor('success')  // Color when status is 0 (Unblocked)
                    ->onIcon('heroicon-s-lock-closed')   // icon for blocking
                    ->offIcon('heroicon-s-lock-open'),
                TextColumn::make('deleted_at')
                    ->label('تاريخ المسح')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('d M, Y H:i')
                    ->since()
                    ->toggleable()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('المحذوفات'),
                SelectFilter::make('roles')
                    ->relationship('roles', 'name', fn ($query) => $query->where('name', '!=', 'super_admin'))
                    ->label('فلترة بالصلاحية'),
                SelectFilter::make('is_blocked')
                    ->label('حالة الحظر')
                    ->options([
                        '1' => 'محظور',
                        '0' => 'شغال تمام',
                    ]),

            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
