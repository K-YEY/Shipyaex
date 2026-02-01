<div class="space-y-2 p-2">
    @forelse ($histories as $history)
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <div class="flex items-start gap-3">
                {{-- Small Action Icon --}}
                @php
                    $iconBg = match($history->action_type) {
                        'created' => 'bg-green-500',
                        'status_changed' => 'bg-blue-500',
                        'collected_shipper' => 'bg-indigo-500',
                        'collected_client' => 'bg-purple-500',
                        'return_shipper', 'return_client' => 'bg-orange-500',
                        'delivered' => 'bg-emerald-500',
                        'shipper_assigned' => 'bg-cyan-500',
                        default => 'bg-gray-500',
                    };
                    $actionLabel = match($history->action_type) {
                        'created' => '🆕 إنشاء',
                        'status_changed' => '🔄 تغيير حالة',
                        'collected_shipper' => '📦 تحصيل Fromدوب',
                        'collected_client' => '💰 تحصيل عميل',
                        'return_shipper' => '↩️ مرتجع Fromدوب',
                        'return_client' => '↩️ مرتجع عميل',
                        'delivered' => '✅ تسليم',
                        'shipper_assigned' => '🚚 Assign Shipper',
                        default => '📝 تحديث',
                    };
                @endphp
                <div class="h-6 w-6 {{ $iconBg }} rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <span class="text-white text-xs">
                        @switch($history->action_type)
                            @case('created') + @break
                            @case('status_changed') ↻ @break
                            @case('collected_shipper') ✓ @break
                            @case('collected_client') $ @break
                            @case('shipper_assigned') → @break
                            @default ● @break
                        @endswitch
                    </span>
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <span class="text-xs font-medium px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                            {{ $actionLabel }}
                        </span>
                        <span class="text-xs text-gray-400">
                            {{ $history->created_at->format('m/d H:i') }}
                        </span>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $history->note }}
                    </p>

                    @if($history->old_status && $history->status)
                        <div class="flex items-center gap-1 text-xs mt-1">
                            <span class="text-red-600 dark:text-red-400">{{ $history->old_status }}</span>
                            <span class="text-gray-400">→</span>
                            <span class="text-green-600 dark:text-green-400">{{ $history->status }}</span>
                        </div>
                    @endif

                    <div class="text-xs text-gray-400 mt-1">
                        بواسطة: {{ $history->user?->name ?? 'النظام' }}
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-8 text-gray-500">
            <div class="text-2xl mb-2">📋</div>
            <p>No توجد سجNoت</p>
        </div>
    @endforelse
</div>