<x-filament-widgets::widget>
    <x-filament-widgets::stats-overview>
        
        <x-filament-widgets::stats-overview.card
            :stat="$allOrders"
            label="All Orders"
            icon="heroicon-o-shopping-bag"
        />

        <x-filament-widgets::stats-overview.card
            :stat="$todayOrders"
            label="Today Orders"
            icon="heroicon-o-calendar"
        />

        <x-filament-widgets::stats-overview.card
            :stat="$pendingOrders"
            label="Pending Orders"
            icon="heroicon-o-clock"
        />

        <x-filament-widgets::stats-overview.card
            :stat="$outForDelivery"
            label="Out For Delivery"
            icon="heroicon-o-truck"
        />

        <x-filament-widgets::stats-overview.card
            :stat="$holdingOrders"
            label="Holding"
            icon="heroicon-o-stop"
        />

        <x-filament-widgets::stats-overview.card
            :stat="$deliveredOrders"
            label="Delivered Orders"
            icon="heroicon-o-check-badge"
        />

        <x-filament-widgets::stats-overview.card
            :stat="$undeliveredOrders"
            label="Undelivered Orders"
            icon="heroicon-o-x-circle"
        />

        <x-filament-widgets::stats-overview.card
            :stat="$readyForCollecting"
            label="Ready for Collecting"
            icon="heroicon-o-banknotes"
        />

        <x-filament-widgets::stats-overview.card
            :stat="$readyForReturn"
            label="Ready for Return"
            icon="heroicon-o-arrow-uturn-left"
        />

        <x-filament-widgets::stats-overview.card
            :stat="$collectedOrders"
            label="Orders Collected"
            icon="heroicon-o-clipboard-document-check"
        />

        <x-filament-widgets::stats-overview.card
            :stat="$returnedOrders"
            label="Orders Returned"
            icon="heroicon-o-arrow-path"
        />

        <x-filament-widgets::stats-overview.card
            :stat="$walletAmount"
            label="Wallet"
            icon="heroicon-o-wallet"
        />

        <x-filament-widgets::stats-overview.card
            :stat="$cashReady"
            label="Cash Ready"
            icon="heroicon-o-currency-dollar"
        />

    </x-filament-widgets::stats-overview>
</x-filament-widgets::widget>
