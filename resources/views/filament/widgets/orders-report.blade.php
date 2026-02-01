<x-filament::widget>
    <x-filament::section>
        <x-slot name="heading">
            📊 تقرير Orderات والتحصيNoت
        </x-slot>
        <x-slot name="description">
            نظرة شاملة على حالة Orderات والتحصيNoت
        </x-slot>

        <div class="space-y-6">
            {{-- حاNoت Orderات --}}
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                    📦 حاNoت Orderات
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($statusData as $status)
                        <a href="{{ $status['url'] }}" 
                           class="block p-4 rounded-xl border-2 transition-all duration-200 hover:shadow-lg hover:scale-105"
                           style="border-color: {{ $status['color'] }}; background: {{ $status['color'] }}10;">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-2xl">{{ $status['icon'] }}</span>
                                <span class="text-3xl font-bold" style="color: {{ $status['color'] }};">
                                    {{ $status['count'] }}
                                </span>
                            </div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $status['label'] }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                اضغط للView →
                            </p>
                        </a>
                    @endforeach
                </div>
            </div>

            @if($isAdmin)
            {{-- إحصائيات التحصيل --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- تحصيل Shipperين --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-5 border border-blue-200 dark:border-blue-800">
                    <h4 class="text-md font-semibold text-blue-800 dark:text-blue-300 mb-4 flex items-center gap-2">
                        🚚 تحصيل Shipperين
                    </h4>
                    <div class="grid grid-cols-2 gap-4">
                        <a href="{{ route('filament.admin.resources.collected-shippers.index', ['tableFilters[status][value]' => 'pending']) }}" 
                           class="bg-white dark:bg-gray-800 rounded-lg p-3 text-center hover:shadow-md transition-shadow">
                            <p class="text-2xl font-bold text-yellow-600">{{ $collectingData['shipper']['pending'] }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">قيد اNoنتظار</p>
                            <p class="text-sm font-medium text-yellow-600 mt-1">
                                {{ number_format($collectingData['shipper']['pending_amount'], 2) }} ج.م
                            </p>
                        </a>
                        <a href="{{ route('filament.admin.resources.collected-shippers.index', ['tableFilters[status][value]' => 'completed']) }}"
                           class="bg-white dark:bg-gray-800 rounded-lg p-3 text-center hover:shadow-md transition-shadow">
                            <p class="text-2xl font-bold text-green-600">{{ $collectingData['shipper']['completed'] }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Completed</p>
                            <p class="text-sm font-medium text-green-600 mt-1">
                                {{ number_format($collectingData['shipper']['completed_amount'], 2) }} ج.م
                            </p>
                        </a>
                    </div>
                </div>

                {{-- تحصيل Clients --}}
                <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-5 border border-green-200 dark:border-green-800">
                    <h4 class="text-md font-semibold text-green-800 dark:text-green-300 mb-4 flex items-center gap-2">
                        💰 تحصيل Clients
                    </h4>
                    <div class="grid grid-cols-2 gap-4">
                        <a href="{{ route('filament.admin.resources.collected-clients.index', ['tableFilters[status][value]' => 'pending']) }}"
                           class="bg-white dark:bg-gray-800 rounded-lg p-3 text-center hover:shadow-md transition-shadow">
                            <p class="text-2xl font-bold text-yellow-600">{{ $collectingData['client']['pending'] }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">قيد اNoنتظار</p>
                            <p class="text-sm font-medium text-yellow-600 mt-1">
                                {{ number_format($collectingData['client']['pending_amount'], 2) }} ج.م
                            </p>
                        </a>
                        <a href="{{ route('filament.admin.resources.collected-clients.index', ['tableFilters[status][value]' => 'completed']) }}"
                           class="bg-white dark:bg-gray-800 rounded-lg p-3 text-center hover:shadow-md transition-shadow">
                            <p class="text-2xl font-bold text-green-600">{{ $collectingData['client']['completed'] }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Completed</p>
                            <p class="text-sm font-medium text-green-600 mt-1">
                                {{ number_format($collectingData['client']['completed_amount'], 2) }} ج.م
                            </p>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Statistics المالية --}}
            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl p-5 border border-purple-200 dark:border-purple-800">
                <h4 class="text-md font-semibold text-purple-800 dark:text-purple-300 mb-4 flex items-center gap-2">
                    📈 Statistics المالية
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center">
                        <p class="text-xs text-gray-500 mb-1">إجمالي Fees</p>
                        <p class="text-xl font-bold text-blue-600">
                            {{ number_format($financialData['total_fees'], 2) }}
                        </p>
                        <p class="text-xs text-gray-500">ج.م</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center">
                        <p class="text-xs text-gray-500 mb-1">رسوم Shipperين</p>
                        <p class="text-xl font-bold text-orange-600">
                            {{ number_format($financialData['shipper_fees'], 2) }}
                        </p>
                        <p class="text-xs text-gray-500">ج.م</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center border-2 border-green-500">
                        <p class="text-xs text-gray-500 mb-1">صافي أرباح الشركة</p>
                        <p class="text-xl font-bold text-green-600">
                            {{ number_format($financialData['total_profit'], 2) }}
                        </p>
                        <p class="text-xs text-gray-500">ج.م</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center">
                        <p class="text-xs text-gray-500 mb-1">طلبات محصلة</p>
                        <p class="text-xl font-bold text-indigo-600">
                            {{ $financialData['collected_shipper'] }}
                        </p>
                        <p class="text-xs text-gray-500">From {{ $financialData['total_orders'] }}</p>
                    </div>
                </div>
            </div>

            {{-- طلبات تحتاج تحصيل --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4 border border-yellow-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">طلبات بانتظار تحصيل Shipper</p>
                            <p class="text-2xl font-bold text-yellow-600">{{ $financialData['pending_shipper'] }}</p>
                        </div>
                        <a href="{{ route('filament.admin.resources.collected-shippers.create') }}" 
                           class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-yellow-600 transition-colors">
                            إنشاء تحصيل
                        </a>
                    </div>
                </div>
                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-4 border border-orange-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">طلبات بانتظار تحصيل Client</p>
                            <p class="text-2xl font-bold text-orange-600">{{ $financialData['pending_client'] }}</p>
                        </div>
                        <a href="{{ route('filament.admin.resources.collected-clients.create') }}" 
                           class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition-colors">
                            إنشاء تحصيل
                        </a>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </x-filament::section>
</x-filament::widget>
