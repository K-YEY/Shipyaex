{{-- Scanner Mode Content --}}
<div class="space-y-6">
    {{-- Scanner Section --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-qr-code class="w-6 h-6" />
                ماسح Barcode
            </div>
        </x-slot>
        
        <div class="flex flex-col md:flex-row gap-4 items-start md:items-center">
            {{-- Scanner Input --}}
            <div 
                x-data="{
                    scannedCode: '',
                    lastScan: null,
                    focusInput() {
                        this.$refs.scanInput.focus();
                    }
                }"
                x-init="focusInput()"
                @keydown.window.f2.prevent="focusInput()"
                class="flex-1 w-full"
            >
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <x-filament::input.wrapper>
                            <x-filament::input
                                x-ref="scanInput"
                                type="text"
                                x-model="scannedCode"
                                x-on:keydown.enter.prevent="
                                    if (scannedCode.trim()) {
                                        $wire.processScannedCode(scannedCode.trim());
                                        lastScan = scannedCode.trim();
                                        scannedCode = '';
                                    }
                                "
                                x-on:blur="setTimeout(() => focusInput(), 100)"
                                placeholder="📷 امسح Barcode هنا أو اكتب الكود..."
                                autocomplete="off"
                                autofocus
                            />
                        </x-filament::input.wrapper>
                    </div>
                    
                    <x-filament::button
                        type="button"
                        x-on:click="focusInput()"
                        color="primary"
                        icon="heroicon-o-viewfinder-circle"
                    >
                        تفعيل
                    </x-filament::button>

                    @if(count($scannedOrders) > 0)
                        <x-filament::button
                            type="button"
                            wire:click="clearScannedOrders"
                            color="danger"
                            icon="heroicon-o-trash"
                        >
                            مسح All
                        </x-filament::button>
                    @endif
                </div>
                
                {{-- Last Scanned Code --}}
                <div x-show="lastScan" x-cloak class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    آخر كود تم مسحه: <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400" x-text="lastScan"></span>
                </div>
            </div>

            {{-- Settings --}}
            <div class="flex flex-col md:flex-row gap-4 items-center">
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">الإجراء التلقائي:</label>
                    <select 
                        wire:model.live="selectedAction"
                        class="fi-input block w-auto rounded-lg border-gray-300 bg-white text-sm shadow-sm transition duration-75 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        @foreach($this->getActionOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input 
                        type="checkbox" 
                        wire:model.live="autoProcess"
                        class="fi-checkbox-input rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700"
                    />
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">تنفيذ تلقائي</span>
                </label>
            </div>
        </div>
        
        <div class="mt-4 text-xs text-gray-400 dark:text-gray-500">
            💡 اضغط <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">F2</kbd> للتركيز على حقل المسح | <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">Enter</kbd> لAdd Order
        </div>
    </x-filament::section>

    {{-- Summary Cards --}}
    @php $totals = $this->getTotals(); @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-4 rounded-xl text-white shadow-lg">
            <div class="text-3xl font-bold">{{ $totals['count'] }}</div>
            <div class="text-blue-100 text-sm">عدد Orderات</div>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-green-600 p-4 rounded-xl text-white shadow-lg">
            <div class="text-3xl font-bold">{{ number_format($totals['total_amount'], 2) }}</div>
            <div class="text-green-100 text-sm">Total Amount (EGP)</div>
        </div>
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 p-4 rounded-xl text-white shadow-lg">
            <div class="text-3xl font-bold">{{ number_format($totals['fees'], 2) }}</div>
            <div class="text-amber-100 text-sm">Fees (EGP)</div>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-4 rounded-xl text-white shadow-lg">
            <div class="text-3xl font-bold">{{ number_format($totals['cod'], 2) }}</div>
            <div class="text-purple-100 text-sm">COD (EGP)</div>
        </div>
    </div>

    {{-- Scanned Orders Table --}}
    @if(count($scannedOrders) > 0)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clipboard-document-list class="w-6 h-6" />
                        Orderات الممسوحة ({{ count($scannedOrders) }})
                    </div>
                    @if($selectedAction !== 'view')
                        <x-filament::button
                            wire:click="processAllOrders"
                            color="success"
                            icon="heroicon-o-check-circle"
                            size="sm"
                        >
                            معالجة All
                        </x-filament::button>
                    @endif
                </div>
            </x-slot>
            
            <div class="overflow-x-auto">
                <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-right">#</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-right">الكود</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-right">Name</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-right">Phone</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-right">Governorate</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-right">Status</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-right">المبلغ</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-right">Client</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-right">Shipper</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                        @foreach($scannedOrders as $index => $order)
                            <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5" wire:key="order-{{ $order['id'] }}">
                                <td class="fi-ta-cell px-3 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $index + 1 }}</td>
                                <td class="fi-ta-cell px-3 py-4">
                                    <x-filament::badge color="info">
                                        {{ $order['code'] }}
                                    </x-filament::badge>
                                </td>
                                <td class="fi-ta-cell px-3 py-4 text-sm font-medium text-gray-950 dark:text-white">{{ $order['name'] }}</td>
                                <td class="fi-ta-cell px-3 py-4 text-sm font-mono text-gray-600 dark:text-gray-400">{{ $order['phone'] }}</td>
                                <td class="fi-ta-cell px-3 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $order['governorate'] }}</td>
                                <td class="fi-ta-cell px-3 py-4">
                                    @php
                                        $statusColors = [
                                            'deliverd' => 'success',
                                            'out for delivery' => 'info',
                                            'hold' => 'warning',
                                            'undelivered' => 'danger',
                                        ];
                                        $color = $statusColors[$order['status']] ?? 'gray';
                                    @endphp
                                    <x-filament::badge :color="$color">
                                        {{ $order['status'] }}
                                    </x-filament::badge>
                                </td>
                                <td class="fi-ta-cell px-3 py-4 text-sm font-semibold text-gray-950 dark:text-white">{{ number_format($order['total_amount'], 2) }} EGP</td>
                                <td class="fi-ta-cell px-3 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $order['client'] }}</td>
                                <td class="fi-ta-cell px-3 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $order['shipper'] }}</td>
                                <td class="fi-ta-cell px-3 py-4">
                                    <div class="flex items-center justify-center gap-1">
                                        @if(auth()->user()->can('ChangeStatus:Order'))
                                            <x-filament::icon-button
                                                wire:click="quickAction({{ $order['id'] }}, 'delivered')"
                                                icon="heroicon-o-check-circle"
                                                color="success"
                                                size="sm"
                                                tooltip="تسليم"
                                                :disabled="$order['status'] === 'deliverd'"
                                            />
                                        @endif
                                        @if(auth()->user()->can('ManageCollections:Order'))
                                            <x-filament::icon-button
                                                wire:click="quickAction({{ $order['id'] }}, 'collected_shipper')"
                                                icon="heroicon-o-truck"
                                                color="info"
                                                size="sm"
                                                tooltip="تحصيل From دوب"
                                                :disabled="$order['collected_shipper']"
                                            />
                                        @endif
                                        @if(auth()->user()->can('ManageCollections:Order'))
                                            <x-filament::icon-button
                                                wire:click="quickAction({{ $order['id'] }}, 'collected_client')"
                                                icon="heroicon-o-banknotes"
                                                color="warning"
                                                size="sm"
                                                tooltip="تحصيل عميل"
                                                :disabled="$order['collected_client']"
                                            />
                                        @endif
                                        <x-filament::icon-button
                                            wire:click="removeOrder({{ $order['id'] }})"
                                            icon="heroicon-o-x-circle"
                                            color="danger"
                                            size="sm"
                                            tooltip="إزالة From القائمة"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <td colspan="6" class="px-3 py-4 text-sm font-semibold text-gray-950 dark:text-white text-right">Total:</td>
                            <td class="px-3 py-4 text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($totals['total_amount'], 2) }} EGP</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <div class="text-center py-12">
                <div class="mx-auto w-24 h-24 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                    <x-heroicon-o-qr-code class="w-12 h-12 text-gray-400" />
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No توجد طلبات ممسوحة</h3>
                <p class="text-gray-500 dark:text-gray-400">ابدأ بمسح Barcode أو كتابة كود Order في الحقل أعNoه</p>
            </div>
        </x-filament::section>
    @endif
</div>
