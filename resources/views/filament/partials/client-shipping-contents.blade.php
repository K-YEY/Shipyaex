<div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
    <table class="min-w-full divide-y divide-gray-200 text-sm text-gray-700">
        <thead class="bg-gray-50 text-gray-600 text-xs uppercase font-semibold">
            <tr>
                <th class="px-4 py-3 text-left w-12">#</th>
                <th class="px-4 py-3 text-left">محتوى الشحن</th>
                <th class="px-4 py-3 text-left">Created At</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
            @forelse ($shippingContents as $index => $content)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-2 text-gray-800 font-medium">{{ $index + 1 }}</td>
                    <td class="px-4 py-2 flex items-center gap-2">
                        <x-heroicon-o-cube class="text-blue-500 shrink-0" style="width: 12px; height: 12px;" />
                        <span>{{ $content->name }}</span>
                    </td>
                    <td class="px-4 py-2 text-gray-500 text-xs">
                        {{ optional($content->created_at)->format('Y-m-d') ?? '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="px-4 py-6 text-center text-gray-500 text-sm">
                        <x-heroicon-o-information-circle class="inline w-4 h-4 text-gray-400 mb-0.5" />
                        No توجد محتويات شحن مرتبطة بهذا Client حالياً.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
