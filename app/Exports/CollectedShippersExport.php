<?php

namespace App\Exports;

use App\Models\CollectedShipper;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CollectedShippersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $query;
    protected ?array $ids;

    public function __construct($query = null, ?array $ids = null)
    {
        $this->query = $query;
        $this->ids = $ids;
    }

    public function query()
    {
        if ($this->ids && count($this->ids) > 0) {
            return CollectedShipper::query()
                ->with(['shipper'])
                ->whereIn('id', $this->ids)
                ->latest();
        }

        return $this->query ?: CollectedShipper::query()->with(['shipper'])->latest();
    }

    public function headings(): array
    {
        return [
            '#',
            'المندوب',
            'تاريخ التحصيل',
            'عدد الطلبات',
            'الإجمالي',
            'عمولة المندوب',
            'الصافي للمندوب',
            'الحالة',
            'تاريخ الإنشاء',
        ];
    }

    public function map($record): array
    {
        return [
            $record->id,
            $record->shipper?->name,
            $record->collection_date,
            $record->number_of_orders,
            $record->total_amount,
            $record->shipper_fees,
            $record->net_amount,
            $record->status,
            $record->created_at?->format('Y-m-d H:i'),
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
