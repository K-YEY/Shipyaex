<?php

namespace App\Exports;

use App\Models\Governorate;
use App\Models\City;
use App\Models\User;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;

class OrdersTemplateExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        return [
            'البيانات' => new DataSheet(),
            'Orders Template' => new OrdersTemplateSheet(),
        ];
    }
}

// Sheet 1: Orders Template
class OrdersTemplateSheet implements 
    \Maatwebsite\Excel\Concerns\FromArray,
    \Maatwebsite\Excel\Concerns\WithHeadings,
    \Maatwebsite\Excel\Concerns\WithStyles,
    \Maatwebsite\Excel\Concerns\WithColumnWidths,
    \Maatwebsite\Excel\Concerns\WithEvents,
    \Maatwebsite\Excel\Concerns\ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'كود',           // A - سيتم توليده تلقائياً
            'كود الشركة',    // B
            'id Client',    // C - Dropdown
            'Name',         // D
            'الرقم',         // E
            'الرقم التاني',  // F
            'Governorate',      // G - Dropdown
            'المنطقة',       // H - Dropdown
            'Address',       // I
            'السعر',         // J
            'الملحوظة',      // K
        ];
    }

    public function array(): array
    {
        // 100 صف فارغ للإدخال
        $rows = [];
        
        for ($i = 0; $i < 100; $i++) {
            $rows[] = ['', '', '', '', '', '', '', '', '', '', ''];
        }
        
        return $rows;
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): array
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

    public function columnWidths(): array
    {
        return [
            'A' => 15, 'B' => 15, 'C' => 12, 'D' => 20, 'E' => 15, 'F' => 15,
            'G' => 15, 'H' => 15, 'I' => 30, 'J' => 12, 'K' => 25,
        ];
    }

    public function registerEvents(): array
    {
        return [
            \Maatwebsite\Excel\Events\AfterSheet::class => function(\Maatwebsite\Excel\Events\AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $spreadsheet = $sheet->getParent();
                
                // الحصول على شيت البيانات (أصبح الآن هو الشيت الأول رقم 0)
                $dataSheet = $spreadsheet->getSheet(0);
                $dataSheetTitle = $dataSheet->getTitle();
                
                // 1. Add Dropdown للعميل (عمود C)
                $clients = User::whereHas('roles', fn($q) => $q->where('name', 'client'))
                    ->orderBy('name')->get();
                $clientCount = $clients->count();
                
                if ($clientCount > 0) {
                    $clientValidation = $sheet->getCell('C2')->getDataValidation();
                    $clientValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $clientValidation->setFormula1("'{$dataSheetTitle}'!\$A\$2:\$A\$" . ($clientCount + 1));
                    $clientValidation->setShowDropDown(true);
                    
                    for ($row = 2; $row <= 102; $row++) {
                        $sheet->getCell('C' . $row)->setDataValidation(clone $clientValidation);
                    }
                }
                
                // 2. الحصول على المحافظات لإنشاء Named Ranges
                $governorates = Governorate::with('cities')->orderBy('name')->get();
                $govCount = $governorates->count();
                
                if ($govCount > 0) {
                    // إنشاء Named Range للمحافظات
                    $spreadsheet->addNamedRange(
                        new \PhpOffice\PhpSpreadsheet\NamedRange(
                            'GovernoratesList', 
                            $dataSheet, 
                            '$B$2:$B$' . ($govCount + 1)
                        )
                    );

                    // Add Dropdown للمحافظة (عمود G)
                    $govValidation = $sheet->getCell('G2')->getDataValidation();
                    $govValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $govValidation->setFormula1('GovernoratesList');
                    $govValidation->setShowDropDown(true);
                    
                    for ($row = 2; $row <= 102; $row++) {
                        $sheet->getCell('G' . $row)->setDataValidation(clone $govValidation);
                    }

                    // 3. إنشاء Named Ranges لكل محافظة
                    $colIndex = 5; // عمود E
                    foreach ($governorates as $gov) {
                        $cityCount = $gov->cities->count();
                        if ($cityCount > 0) {
                            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                            $safeName = 'Gov_' . $gov->id;
                            
                            $spreadsheet->addNamedRange(
                                new \PhpOffice\PhpSpreadsheet\NamedRange(
                                    $safeName,
                                    $dataSheet,
                                    '$' . $colLetter . '$2:$' . $colLetter . '$' . ($cityCount + 1)
                                )
                            );
                        }
                        $colIndex++;
                    }

                    // 4. Add Dropdown الApproved للمنطقة (عمود H)
                    for ($row = 2; $row <= 102; $row++) {
                        $cityValidation = $sheet->getCell('H' . $row)->getDataValidation();
                        $cityValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                        
                        // استخدام VLOOKUP للبحث عن SafeName
                        $formula = "INDIRECT(VLOOKUP(G{$row}, '{$dataSheetTitle}'!\$B\$2:\$C\$" . ($govCount + 1) . ", 2, FALSE))";
                        
                        $cityValidation->setFormula1($formula);
                        $cityValidation->setShowDropDown(true);
                        $sheet->getCell('H' . $row)->setDataValidation($cityValidation);
                    }
                }
                
                $sheet->freezePane('A2');
            },
        ];
    }
}

// Sheet 2: Data Sheet
class DataSheet implements 
    \Maatwebsite\Excel\Concerns\FromArray,
    \Maatwebsite\Excel\Concerns\WithTitle,
    \Maatwebsite\Excel\Concerns\WithStyles
{
    public function title(): string
    {
        return 'البيانات';
    }

    public function array(): array
    {
        // جلب Clients مع الـ ID
        $clients = User::whereHas('roles', fn($q) => $q->where('name', 'client'))
            ->orderBy('name')
            ->get()
            ->map(fn($client) => $client->name . ' (' . $client->id . ')')
            ->toArray();
            
        $governorates = Governorate::with('cities')->orderBy('name')->get();
        
        $rows = [];
        // الهيدرز
        $headers = ['Clients', 'المحافظات', 'SafeName', ''];
        foreach ($governorates as $gov) {
            $headers[] = $gov->name;
        }
        $rows[] = $headers;

        // البيانات
        $maxCities = 0;
        foreach ($governorates as $gov) {
            $maxCities = max($maxCities, $gov->cities->count());
        }
        
        $maxRows = max(count($clients), $governorates->count(), $maxCities);

        for ($i = 0; $i < $maxRows; $i++) {
            $row = [];
            $row[] = $clients[$i] ?? ''; // A: Clients (Name + ID)
            $row[] = isset($governorates[$i]) ? $governorates[$i]->name : ''; // B: المحافظات
            $row[] = isset($governorates[$i]) ? 'Gov_' . $governorates[$i]->id : ''; // C: SafeName
            $row[] = ''; // D: فاصل
            
            // Add المدن لكل محافظة في أعمدة منفصلة
            foreach ($governorates as $gov) {
                $city = $gov->cities->values()[$i] ?? null;
                $row[] = $city ? $city->name : '';
            }
            
            $rows[] = $row;
        }
        
        return $rows;
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): array
    {
        $sheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
