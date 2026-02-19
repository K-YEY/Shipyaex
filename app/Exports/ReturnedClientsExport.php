<?php

namespace App\Exports;

use App\Models\ReturnedClient;
use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class ReturnedClientsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $query;
    protected ?array $ids;

    public function __construct($query = null, ?array $ids = null)
    {
        $this->query = $query;
        $this->ids = $ids;
    }

    public function collection(): Collection
    {
        $query = null;

        if ($this->ids && count($this->ids) > 0) {
            $query = ReturnedClient::query()
                ->with(['client', 'orders.client', 'orders.shipper', 'orders.governorate', 'orders.city'])
                ->whereIn('id', $this->ids)
                ->latest();
        } else {
            $query = $this->query
                ? $this->query->with(['client', 'orders.client', 'orders.shipper', 'orders.governorate', 'orders.city'])
                : ReturnedClient::query()
                    ->with(['client', 'orders.client', 'orders.shipper', 'orders.governorate', 'orders.city'])
                    ->latest();
        }

        $returns = $query->get();

        $rows = collect();
        foreach ($returns as $return) {
            foreach ($return->orders as $order) {
                $rows->push((object) [
                    'return_id' => $return->id,
                    'return_date' => $return->return_date?->format('Y-m-d'),
                    'return_status' => $return->status,
                    'client_name' => $return->client?->name,
                    'order_code' => $order->code,
                    'external_code' => $order->external_code,
                    'customer_name' => $order->name,
                    'phone' => $order->phone,
                    'phone_2' => $order->phone_2,
                    'address' => $order->address,
                    'governorate' => $order->governorate?->name,
                    'city' => $order->city?->name,
                    'shipper_name' => $order->shipper?->name,
                    'order_status' => $order->status,
                    'total_amount' => $order->total_amount,
                    'fees' => $order->fees,
                    'cod' => $order->cod,
                    'has_return' => $order->has_return ? 'نعم' : 'لا',
                    'order_note' => $order->order_note,
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'رقم كشف المرتجع',
            'تاريخ الارتجاع',
            'حالة الكشف',
            'العميل',
            'كود الأوردر',
            'الكود الخارجي',
            'اسم الزبون',
            'الهاتف',
            'هاتف 2',
            'العنوان',
            'المحافظة',
            'المدينة',
            'المندوب',
            'حالة الأوردر',
            'المبلغ الإجمالي',
            'المصاريف',
            'COD',
            'مرتجع',
            'ملاحظات',
        ];
    }

    public function map($row): array
    {
        return [
            $row->return_id,
            $row->return_date,
            $row->return_status,
            $row->client_name,
            $row->order_code,
            $row->external_code,
            $row->customer_name,
            $row->phone,
            $row->phone_2,
            $row->address,
            $row->governorate,
            $row->city,
            $row->shipper_name,
            $row->order_status,
            $row->total_amount,
            $row->fees,
            $row->cod,
            $row->has_return,
            $row->order_note,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'B45309'],
                ],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
