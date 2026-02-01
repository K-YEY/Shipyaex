<div class="p-4 overflow-x-auto">
    <table class="w-full text-sm text-right border-collapse">
        <thead>
            <tr class="bg-gray-100 dark:bg-gray-700">
                <th class="px-4 py-2 border border-gray-200 dark:border-gray-600">الكود</th>
                <th class="px-4 py-2 border border-gray-200 dark:border-gray-600">
                    @if($type === 'shipper')
                        Receiver
                    @else
                        Receiver
                    @endif
                </th>
                <th class="px-4 py-2 border border-gray-200 dark:border-gray-600">Phone</th>
                <th class="px-4 py-2 border border-gray-200 dark:border-gray-600">Status</th>
                <th class="px-4 py-2 border border-gray-200 dark:border-gray-600">مرتجع</th>
                <th class="px-4 py-2 border border-gray-200 dark:border-gray-600">المبلغ</th>
                <th class="px-4 py-2 border border-gray-200 dark:border-gray-600">Fees</th>
                @if($type === 'shipper')
                    <th class="px-4 py-2 border border-gray-200 dark:border-gray-600">رسوم Shipper</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-2 border border-gray-200 dark:border-gray-600">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-800 dark:text-primary-100">
                            {{ $order->code }}
                        </span>
                    </td>
                    <td class="px-4 py-2 border border-gray-200 dark:border-gray-600">{{ $order->name }}</td>
                    <td class="px-4 py-2 border border-gray-200 dark:border-gray-600" dir="ltr">{{ $order->phone }}</td>
                    <td class="px-4 py-2 border border-gray-200 dark:border-gray-600">
                        @php
                            $statusColor = match($order->status) {
                                'deliverd' => 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
                                'undelivered' => 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100',
                            };
                            $statusText = match($order->status) {
                                'deliverd' => 'تم التسليم',
                                'undelivered' => 'لم يتم التسليم',
                                'out for delivery' => 'خارج للتوصيل',
                                'hold' => 'معلق',
                                default => $order->status,
                            };
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                            {{ $statusText }}
                        </span>
                    </td>
                    <td class="px-4 py-2 border border-gray-200 dark:border-gray-600 text-center">
                        @if($order->has_return)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                Yes
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                No
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-2 border border-gray-200 dark:border-gray-600 font-semibold text-primary-600">
                        {{ number_format($order->cod, 2) }} ج.م
                    </td>
                    <td class="px-4 py-2 border border-gray-200 dark:border-gray-600">
                        {{ number_format($order->fees, 2) }} ج.م
                    </td>
                    @if($type === 'shipper')
                        <td class="px-4 py-2 border border-gray-200 dark:border-gray-600">
                            {{ number_format($order->shipper_fees, 2) }} ج.م
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $type === 'shipper' ? 8 : 7 }}" class="px-4 py-8 text-center text-gray-500 border border-gray-200 dark:border-gray-600">
                        No توجد طلبات
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($orders->count() > 0)
            <tfoot class="bg-gray-50 dark:bg-gray-700 font-bold">
                <tr>
                    <td colspan="{{ $type === 'shipper' ? 5 : 4 }}" class="px-4 py-2 border border-gray-200 dark:border-gray-600 text-left">
                        Total ({{ $orders->count() }} طلب)
                    </td>
                    <td class="px-4 py-2 border border-gray-200 dark:border-gray-600 text-primary-600">
                        {{ number_format($orders->sum('cod'), 2) }} ج.م
                    </td>
                    <td class="px-4 py-2 border border-gray-200 dark:border-gray-600">
                        {{ number_format($orders->sum('fees'), 2) }} ج.م
                    </td>
                    @if($type === 'shipper')
                        <td class="px-4 py-2 border border-gray-200 dark:border-gray-600">
                            {{ number_format($orders->sum('shipper_fees'), 2) }} ج.م
                        </td>
                    @endif
                </tr>
            </tfoot>
        @endif
    </table>
</div>
