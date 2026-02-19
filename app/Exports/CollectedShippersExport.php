<?php

namespace App\Exports;

use App\Models\CollectedShipper;
use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class CollectedShippersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
        // جلب سجلات التحصيل مع الأوردرات وبيانات كل أوردر
        $query = null;

        if ($this->ids && count($this->ids) > 0) {
            $query = CollectedShipper::query()
                ->with(['shipper', 'orders.client', 'orders.governorate', 'orders.city', 'orders.shipper'])
                ->whereIn('id', $this->ids)
                ->latest();
        } else {
            $query = $this->query
                ? $this->query->with(['shipper', 'orders.client', 'orders.governorate', 'orders.city', 'orders.shipper'])
                : CollectedShipper::query()
                    ->with(['shipper', 'orders.client', 'orders.governorate', 'orders.city', 'orders.shipper'])
                    ->latest();
        }

        $collections = $query->get();

        // تحويل كل أوردر لصف منفصل مع بيانات الكشف
        $rows = collect();
        foreach ($collections as $collection) {
            foreach ($collection->orders as $order) {
                $rows->push((object) [
                    'collection_id' => $collection->id,
                    'collection_date' => $collection->collection_date?->format('Y-m-d'),
                    'collection_status' => $collection->status,
                    'shipper_name' => $collection->shipper?->name,
                    'order_code' => $order->code,
                    'external_code' => $order->external_code,
                    'client_name' => $order->client?->name,
                    'customer_name' => $order->name,
                    'phone' => $order->phone,
                    'phone_2' => $order->phone_2,
                    'address' => $order->address,
                    'governorate' => $order->governorate?->name,
                    'city' => $order->city?->name,
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
            'رقم الكشف',
            'تاريخ التحصيل',
            'حالة الكشف',
            'المندوب',
            'كود الأوردر',
            'الكود الخارجي',
            'العميل',
            'اسم الزبون',
            'الهاتف',
            'هاتف 2',
            'العنوان',
            'المحافظة',
            'المدينة',
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
            $row->collection_id,
            $row->collection_date,
            $row->collection_status,
            $row->shipper_name,
            $row->order_code,
            $row->external_code,
            $row->client_name,
            $row->customer_name,
            $row->phone,
            $row->phone_2,
            $row->address,
            $row->governorate,
            $row->city,
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
                    'startColor' => ['rgb' => '4F46E5'],
                ],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
