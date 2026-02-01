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

class ExternalCodesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected ?array $orderIds;

    public function __construct(?array $orderIds = null)
    {
        $this->orderIds = $orderIds;
    }

    public function query()
    {
        $query = Order::query()
            ->with(['client', 'shipper', 'governorate', 'city']);

        // إذا تم تحديد أوردرات معينة بالـ IDs
        if ($this->orderIds && count($this->orderIds) > 0) {
            $query->whereIn('id', $this->orderIds);
        }

        return $query->latest();
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
        // تحويل المNoحظة من array إلى string
        $note = $order->status_note;
        if (is_array($note)) {
            $note = implode(', ', $note);
        }
        
        return [
            $order->code,
            $order->external_code,
            $order->client_id,
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
                    'startColor' => ['rgb' => 'F59E0B'], // لون برتقالي للكود الخارجي
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }
}
