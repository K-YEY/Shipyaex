<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $query;
    protected ?int $limit;
    protected ?array $orderIds;

    public function __construct($query = null, ?int $limit = null, ?array $orderIds = null)
    {
        $this->query = $query;
        $this->limit = $limit;
        $this->orderIds = $orderIds;
    }

    public function query()
    {
        // إذا تم تحديد أوردرات معينة بالـ IDs
        if ($this->orderIds && count($this->orderIds) > 0) {
            return Order::query()
                ->with(['client', 'shipper', 'governorate', 'city'])
                ->whereIn('id', $this->orderIds)
                ->latest();
        }

        $query = $this->query;
        
        if (!$query) {
            $query = Order::query()
                ->with(['client', 'shipper', 'governorate', 'city'])
                ->whereNotNull('status')
                ->latest();
        }

        // Apply limit if specified
        if ($this->limit && $this->limit > 0) {
            $query->limit($this->limit);
        }

        return $query;
    }

    /**
     * الحقول الموحدة للExport:
     * كود | كود الشركة | id Client | Name | الرقم | الرقم التاني | Governorate | المنطقة | Address | السعر | الملحوظة
     */
    public function headings(): array
    {
        return [
            'كود',
            'كود الشركة',
            'id Client',
            'Name',
            'الرقم',
            'الرقم التاني',
            'Governorate',
            'المنطقة',
            'Address',
            'السعر',
            'الملحوظة',
        ];
    }

    public function map($order): array
    {
        // تحويل المNoحظة من array إلى string (order_note هو text عادي)
        $note = $order->order_note ?: '';
        
        // تنسيق اسم Client + ID
        $clientInfo = $order->client_id;
        if ($order->client) {
            $clientInfo = $order->client->name . ' (' . $order->client_id . ')';
        }
        
        return [
            $order->code,
            $order->external_code,
            $clientInfo,
            $order->name,
            $order->phone,
            $order->phone_2,
            $order->governorate?->name,
            $order->city?->name,
            $order->address,
            $order->total_amount,
            $note,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }
}
