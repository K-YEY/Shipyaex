<?php

namespace Database\Seeders;

use App\Models\OrderStatus;
use App\Models\RefusedReason;
use Illuminate\Database\Seeder;

class OrderStatusAndReasonsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Order Statuses
        $statuses = [
            [
                'name' => 'Out for Delivery',
                'slug' => 'out-for-delivery',
                'color' => 'info',
                'icon' => 'heroicon-o-truck',
                'is_active' => true,
                'sort_order' => 1,
                'clear_refused_reasons' => false,
            ],
            [
                'name' => 'Delivered',
                'slug' => 'deliverd',
                'color' => 'success',
                'icon' => 'heroicon-o-check-circle',
                'is_active' => true,
                'sort_order' => 2,
                'clear_refused_reasons' => true, // Clear reasons when delivered
            ],
            [
                'name' => 'Undelivered',
                'slug' => 'undelivered',
                'color' => 'danger',
                'icon' => 'heroicon-o-x-circle',
                'is_active' => true,
                'sort_order' => 3,
                'clear_refused_reasons' => false,
            ],
            [
                'name' => 'Hold',
                'slug' => 'hold',
                'color' => 'warning',
                'icon' => 'heroicon-o-pause-circle',
                'is_active' => true,
                'sort_order' => 4,
                'clear_refused_reasons' => false,
            ],
        ];

        foreach ($statuses as $status) {
            OrderStatus::updateOrCreate(
                ['slug' => $status['slug']],
                $status
            );
        }

        // Seed Refused Reasons
        $reasons = [
            [
                'name' => 'Customer Not Answering',
                'slug' => 'customer-not-answering',
                'color' => 'warning',
                'icon' => 'heroicon-o-phone-x-mark',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Wrong Address',
                'slug' => 'wrong-address',
                'color' => 'danger',
                'icon' => 'heroicon-o-map-pin',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Delayed',
                'slug' => 'delayed',
                'color' => 'warning',
                'icon' => 'heroicon-o-clock',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Rescheduled',
                'slug' => 'rescheduled',
                'color' => 'info',
                'icon' => 'heroicon-o-calendar',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Fragile',
                'slug' => 'fragile',
                'color' => 'warning',
                'icon' => 'heroicon-o-exclamation-triangle',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Returned Back',
                'slug' => 'returned-back',
                'color' => 'danger',
                'icon' => 'heroicon-o-arrow-uturn-left',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Customer Refused',
                'slug' => 'customer-refused',
                'color' => 'danger',
                'icon' => 'heroicon-o-hand-raised',
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Out of Stock',
                'slug' => 'out-of-stock',
                'color' => 'warning',
                'icon' => 'heroicon-o-archive-box-x-mark',
                'is_active' => true,
                'sort_order' => 8,
            ],
        ];

        foreach ($reasons as $reason) {
            RefusedReason::updateOrCreate(
                ['slug' => $reason['slug']],
                $reason
            );
        }

        $this->command->info('✅ Order Statuses and Refused Reasons seeded successfully!');
    }
}
