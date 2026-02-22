<?php

namespace App\Filament\Resources\Scanner\Pages;

use App\Filament\Resources\Scanner\ScannerResource;
use App\Models\Order;
use App\Models\User;
use App\Models\Setting;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action as TableAction;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Illuminate\Contracts\View\View;

class ListScanners extends ListRecords
{
    protected static string $resource = ScannerResource::class;

    public array $scannedOrders = [];
    public bool $autoProcess = true;
    public bool $autoSubmit = true;
    public string $selectedAction = 'view';
    public ?int $targetShipperId = null;

    public function getHeader(): ?View
    {
        return view('filament.resources.scanner.header', [
            'scannedOrders' => $this->scannedOrders,
            'autoProcess' => $this->autoProcess,
            'autoSubmit' => $this->autoSubmit,
            'selectedAction' => $this->selectedAction,
            'targetShipperId' => $this->targetShipperId,
        ]);
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $ids = collect($this->scannedOrders)->pluck('id')->toArray();
        
        if (empty($ids)) {
            return Order::query()->whereRaw('1 = 0');
        }

        return Order::query()->whereIn('id', $ids);
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('الكود')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('الهاتف'),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'deliverd' => 'success',
                        'out for delivery' => 'info',
                        'hold' => 'warning',
                        'undelivered' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('total_amount')
                    ->label('المبلغ')
                    ->money('EGP'),
                TextColumn::make('shipper.name')
                    ->label('المندوب'),
            ])
            ->recordActions([
                // زر إزالة - يحتاج صلاحية RemoveOrder:Scanner أو admin
                TableAction::make('remove')
                    ->label('إزالة')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn () => $user->isAdmin() || $user->can('RemoveOrder:Scanner'))
                    ->action(fn ($record) => $this->removeOrder($record->id)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([

                    // تغيير حالة - يحتاج صلاحية ChangeStatus:Scanner أو admin
                    BulkAction::make('changeStatus')
                        ->label('تغيير الحالة')
                        ->icon('heroicon-o-arrow-path')
                        ->visible(fn () => $user->isAdmin() || $user->can('ChangeStatus:Scanner'))
                        ->form([
                            Select::make('status')
                                ->label('الحالة الجديدة')
                                ->options([
                                    'out for delivery' => '🚚 جاري التوصيل',
                                    'deliverd' => '✅ تم التسليم',
                                    'undelivered' => '❌ مرتجع',
                                    'hold' => '⏸️ معلق',
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each->update(['status' => $data['status']]);
                            Notification::make()->title('تم تحديث الحالات بنجاح')->success()->send();
                        }),

                    // إسناد لمندوب - يحتاج صلاحية AssignShipper:Scanner أو admin
                    BulkAction::make('assignShipper')
                        ->label('إسناد لمندوب')
                        ->icon('heroicon-o-truck')
                        ->visible(fn () => $user->isAdmin() || $user->can('AssignShipper:Scanner'))
                        ->form([
                            Select::make('shipper_id')
                                ->label('المندوب')
                                ->relationship(
                                    name: 'shipper',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn ($query) => $query->role('shipper')
                                )
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each->update([
                                'shipper_id' => $data['shipper_id'],
                                'status' => 'out for delivery'
                            ]);
                            Notification::make()->title('تم إسناد الطلبات للمندوب')->success()->send();
                        }),

                    // إزالة من القائمة - يحتاج صلاحية ClearList:Scanner أو admin
                    BulkAction::make('clearFromList')
                        ->label('إزالة من القائمة')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn () => $user->isAdmin() || $user->can('ClearList:Scanner'))
                        ->action(function ($records) {
                            $idsToRemove = $records->pluck('id')->toArray();
                            $this->scannedOrders = array_values(
                                array_filter($this->scannedOrders, fn($o) => !in_array($o['id'], $idsToRemove))
                            );
                            Notification::make()->title('تمت الإزالة من القائمة')->success()->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('لا توجد طلبات ممسوحة')
            ->emptyStateDescription('ابدأ بمسح الباركود لإضافة الطلبات للقائمة')
            ->emptyStateIcon('heroicon-o-qr-code');
    }

    public function processScannedCode(string $code): void
    {
        $code = trim($code);
        if (empty($code)) return;

        $order = Order::where('code', $code)
            ->orWhere('external_code', $code)
            ->first();

        if (!$order) {
            Notification::make()->title('❌ الأوردر غير موجود')->danger()->send();
            $this->dispatch('play-error-sound');
            return;
        }

        if (collect($this->scannedOrders)->contains('id', $order->id)) {
            Notification::make()->title('⚠️ الأوردر مضاف بالفعل')->warning()->send();
            $this->dispatch('play-error-sound');
            return;
        }

        $this->scannedOrders[] = ['id' => $order->id];
        
        Notification::make()->title("✅ تم إضافة أوردر #{$order->code}")->success()->send();
        $this->dispatch('play-scan-sound');

        if ($this->autoProcess && $this->selectedAction !== 'view') {
            $this->quickAction($order->id, $this->selectedAction);
        }
    }

    public function removeOrder(int $orderId): void
    {
        $this->scannedOrders = array_values(
            array_filter($this->scannedOrders, fn($o) => $o['id'] !== $orderId)
        );
    }

    public function clearAll(): void
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->can('ClearList:Scanner')) {
            Notification::make()->title('❌ لا تملك صلاحية مسح القائمة')->danger()->send();
            $this->dispatch('play-error-sound');
            return;
        }
        $this->scannedOrders = [];
        Notification::make()->title('تم مسح القائمة')->success()->send();
    }

    public function quickAction(int $orderId, string $action): void
    {
        $order = Order::find($orderId);
        if (!$order) return;

        $user = auth()->user();

        switch ($action) {
            case 'delivered':
                // يستخدم صلاحية Order أو Scanner
                if ($user->can('ChangeStatus:Scanner') || $user->can('ChangeStatus:Order') || $user->isAdmin()) {
                    $order->update(['status' => 'deliverd', 'deliverd_at' => now()]);
                }
                break;

            case 'return_shipper':
                // يستخدم صلاحية Scanner أو Order
                if ($user->can('ReturnShipper:Scanner') || $user->can('ManageShipperReturnAction:Order') || $user->isAdmin()) {
                    $order->update([
                        'return_shipper' => true,
                        'return_shipper_date' => now(),
                        'status' => 'undelivered'
                    ]);
                    Notification::make()->title('✅ تم تسجيل مرتجع المندوب')->success()->send();
                } else {
                    Notification::make()->title('❌ لا تملك صلاحية تسجيل مرتجع المندوب')->danger()->send();
                }
                break;

            case 'return_client':
                // يستخدم صلاحية Scanner أو Order
                if ($user->can('ReturnClient:Scanner') || $user->can('ManageClientReturnAction:Order') || $user->isAdmin()) {
                    $order->update([
                        'return_client' => true,
                        'return_client_date' => now(),
                        'status' => 'undelivered'
                    ]);
                    Notification::make()->title('✅ تم تسجيل مرتجع العميل')->success()->send();
                } else {
                    Notification::make()->title('❌ لا تملك صلاحية تسجيل مرتجع العميل')->danger()->send();
                }
                break;

            case 'assign_shipper':
                if (($user->can('AssignShipper:Scanner') || $user->can('AssignShipper:Order') || $user->isAdmin()) && $this->targetShipperId) {
                    $order->update([
                        'shipper_id' => $this->targetShipperId,
                        'status' => 'out for delivery'
                    ]);
                } else {
                    Notification::make()->title('❌ لا تملك صلاحية إسناد المندوب')->danger()->send();
                }
                break;
        }
    }

    public function getShippers(): array
    {
        return User::role('shipper')->pluck('name', 'id')->toArray();
    }
}
