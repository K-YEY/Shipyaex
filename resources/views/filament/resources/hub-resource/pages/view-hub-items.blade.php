<x-filament::page>
    <div class="space-y-6">
        <h2 class="text-xl font-bold">طلبات الهب: {{ $record->name }}</h2>

        <x-filament::table>
            <x-slot name="header">
                <x-filament::table.header-cell>ID</x-filament::table.header-cell>
                <x-filament::table.header-cell>Order ID</x-filament::table.header-cell>
                <x-filament::table.header-cell>Type</x-filament::table.header-cell>
                <x-filament::table.header-cell>Status</x-filament::table.header-cell>
            </x-slot>

            @foreach ($record->hubItems as $item)
                <x-filament::table.row>
                    <x-filament::table.cell>{{ $item->id }}</x-filament::table.cell>
                    <x-filament::table.cell>{{ $item->order_id }}</x-filament::table.cell>
                    <x-filament::table.cell>{{ $item->type }}</x-filament::table.cell>
                    <x-filament::table.cell>{{ $item->status }}</x-filament::table.cell>
                </x-filament::table.row>
            @endforeach
        </x-filament::table>
    </div>
</x-filament::page>
