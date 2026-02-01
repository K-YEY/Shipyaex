<?php

namespace App\Imports;

use App\Models\Order;
use App\Models\User;
use App\Models\Governorate;
use App\Models\City;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\Importable;

class OrdersImport implements ToCollection
{
    use Importable;

    protected $clientId;
    protected $shipperId;
    protected $successCount = 0;
    protected $errorCount = 0;
    protected $errors = [];
    protected $nextOrderNumber = null;
    protected $orderPrefix = '';
    protected $orderDigits = 5;

    public function __construct(?int $clientId = null, ?int $shipperId = null)
    {
        $this->clientId = $clientId;
        $this->shipperId = $shipperId;
        
        // تجهيز بيانات توليد الأكواد
        $this->orderPrefix = Setting::get('order_prefix', 'SHP');
        $this->orderDigits = (int) Setting::get('order_digits', 5);
        
        $lastOrder = Order::where('code', 'like', $this->orderPrefix . '-%')
            ->orderBy('id', 'desc')
            ->first();
            
        if ($lastOrder) {
            $this->nextOrderNumber = (int) str_replace($this->orderPrefix . '-', '', $lastOrder->code) + 1;
        } else {
            $this->nextOrderNumber = 1;
        }
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) return;

        // الحصول على الهيدرز من الصف الأول
        $headers = $rows->first()->toArray();
        $dataRows = $rows->slice(1);

        // خريطة للأعمدة بناءً على Name أو الترتيب
        $map = $this->mapColumns($headers);

        // المرحلة 1: التحقق من كل الصفوف وجمع الأخطاء
        $validatedData = [];
        $allErrors = [];
        $usedCodes = [];
        $usedExternalCodes = [];

        foreach ($dataRows as $index => $row) {
            $row = $row->toArray();
            $rowNumber = $index + 2; // +2 لأن الصف الأول هيدر والـ index يبدأ من 0
            
            // جلب البيانات الأساسية
            $name = $this->getVal($row, $map, 'name');
            $phone = $this->getVal($row, $map, 'phone');
            
            // تخطي الصفوف الفارغة تماماً
            if (empty($name) && empty($phone)) {
                continue;
            }

            try {
                // 1. التحقق من الكود
                $existingCode = $this->getVal($row, $map, 'code');
                $code = null;
                
                if ($existingCode) {
                    // فحص التكرار في قاعدة البيانات
                    if (Order::where('code', $existingCode)->exists()) {
                        throw new \Exception("الكود ({$existingCode}) موجود مسبقاً في قاعدة البيانات");
                    }
                    // فحص التكرار في نفس الملف
                    if (in_array($existingCode, $usedCodes)) {
                        throw new \Exception("الكود ({$existingCode}) مكرر في الملف");
                    }
                    $usedCodes[] = $existingCode;
                    $code = $existingCode;
                } else {
                    $code = $this->generateNextCode();
                    $usedCodes[] = $code;
                }

                // 2. التحقق من External Code (external_code)
                $externalCode = $this->getVal($row, $map, 'external_code');
                if ($externalCode) {
                    // فحص التكرار في قاعدة البيانات
                    if (Order::where('external_code', $externalCode)->exists()) {
                        throw new \Exception("كود الشركة ({$externalCode}) موجود مسبقاً في قاعدة البيانات");
                    }
                    // فحص التكرار في نفس الملف
                    if (in_array($externalCode, $usedExternalCodes)) {
                        throw new \Exception("كود الشركة ({$externalCode}) مكرر في الملف");
                    }
                    $usedExternalCodes[] = $externalCode;
                }

                // 3. تحديد Governorate والمنطقة
                $govName = $this->getVal($row, $map, 'governorate');
                $cityName = $this->getVal($row, $map, 'city');
                
                $governorate = null;
                $city = null;

                if (!empty($cityName)) {
                    $city = City::where('name', 'like', "%{$cityName}%")->first();
                    if ($city) {
                        $governorate = $city->governorate;
                    }
                }

                if (!$governorate && !empty($govName)) {
                    $governorate = Governorate::where('name', 'like', "%{$govName}%")->first();
                }

                // 4. تحديد Client (إلزامي)
                $clientId = $this->clientId;
                $clientVal = $this->getVal($row, $map, 'client_id');
                
                if ($clientVal) {
                    // محاولة استخراج ID من بين الأقواس: "اسم Client (123)"
                    if (preg_match('/\((\d+)\)/', $clientVal, $matches)) {
                        $clientId = (int) $matches[1];
                    }
                    // لو مافيش أقواس، جرب لو هو رقم في البداية
                    elseif (preg_match('/^(\d+)/', $clientVal, $matches)) {
                        $clientId = (int) $matches[1];
                    }
                    // لو هو رقم بس
                    elseif (is_numeric($clientVal)) {
                        $clientId = (int) $clientVal;
                    }
                }

                if (!$clientId) {
                    $user = auth()->user();
                    if ($user && $user->isClient()) {
                        $clientId = $user->id;
                    }
                }

                if (!$clientId) {
                    throw new \Exception("لم يتم تحديد Client");
                }

                // التحقق من وجود Client
                $client = User::find($clientId);
                if (!$client) {
                    throw new \Exception("Client غير موجود (ID: {$clientId}). تأكد من إدخال ID صحيح أو اسم Client مع ID بين أقواس مثل: اسم Client ({$clientId})");
                }

                // 5. Shipper وFees
                $fees = 0;
                $shipperId = $this->shipperId;
                $shipperFees = 0;

                if ($clientId && $governorate) {
                    $client = User::find($clientId);
                    if ($client && $client->plan_id) {
                        $planPrice = \App\Models\PlanPrice::where('plan_id', $client->plan_id)
                            ->where('location_id', $governorate->id)
                            ->first();
                        
                        if ($planPrice) {
                            $fees = $planPrice->price;
                        } else {
                            $fees = Setting::get('default_fees', 0);
                        }
                    } else {
                        $fees = Setting::get('default_fees', 0);
                    }

                    // تعيين Shipper التلقائي من Governorate
                    if (!$shipperId && $governorate->shipper_id) {
                        $shipperId = $governorate->shipper_id;
                    }
                }

                if ($shipperId) {
                    $shipper = User::find($shipperId);
                    $shipperFees = $shipper?->commission ?? 0;
                }

                // Save البيانات الApprovedة
                $validatedData[] = [
                    'code' => $code,
                    'external_code' => $externalCode,
                    'name' => $name ?: 'بدون اسم',
                    'phone' => $phone ?: '000',
                    'phone_2' => $this->getVal($row, $map, 'phone_2'),
                    'address' => $this->getVal($row, $map, 'address') ?: 'بدون عنوان',
                    'governorate_id' => $governorate?->id,
                    'city_id' => $city?->id,
                    'total_amount' => (float) ($this->getVal($row, $map, 'price') ?: 0),
                    'order_note' => $this->getVal($row, $map, 'note'), // تغيير من status_note إلى order_note
                    'fees' => $fees,
                    'shipper_fees' => $shipperFees,
                    'client_id' => $clientId,
                    'shipper_id' => $shipperId,
                    'status' => 'out for delivery',
                ];

            } catch (\Exception $e) {
                $allErrors[] = "❌ صف {$rowNumber}: " . $e->getMessage();
            }
        }

        // المرحلة 2: إذا كان هناك أي أخطاء، نرفض الملف كله
        if (!empty($allErrors)) {
            $this->errorCount = count($allErrors);
            $this->errors = $allErrors;
            
            // رمي Exception لإيقاف العملية
            throw new \Exception(
                "تم رفض الملف! يوجد " . count($allErrors) . " خطأ:\n\n" . 
                implode("\n", array_slice($allErrors, 0, 10)) . 
                (count($allErrors) > 10 ? "\n... و " . (count($allErrors) - 10) . " أخطاء أخرى" : "")
            );
        }

        // المرحلة 3: Save كل البيانات (فقط إذا لم يكن هناك أخطاء)
        foreach ($validatedData as $data) {
            Order::create($data);
            $this->successCount++;
        }
    }

    private function generateNextCode()
    {
        $code = $this->orderPrefix . '-' . str_pad($this->nextOrderNumber, $this->orderDigits, '0', STR_PAD_LEFT);
        $this->nextOrderNumber++;
        return $code;
    }

    private function mapColumns($headers)
    {
        $map = [];
        $possibleKeys = [
            'code' => ['كود', 'الكود', 'code', 'serial'],
            'external_code' => ['كود الشركة', 'External Code', 'external_code', 'external'],
            'client_id' => ['id Client', 'Client', 'client_id', 'client'],
            'name' => ['Name', 'اسم Client', 'name', 'customer_name'],
            'phone' => ['الرقم', 'Phone', 'الموبايل', 'phone', 'mobile'],
            'phone_2' => ['الرقم التاني', 'Phone 2', 'phone_2', 'mobile_2'],
            'governorate' => ['Governorate', 'governorate', 'gov'],
            'city' => ['المنطقة', 'City', 'city', 'area'],
            'address' => ['Address', 'address'],
            'price' => ['السعر', 'Total Amount', 'price', 'total', 'amount'],
            'note' => ['الملحوظة', 'مNoحظة', 'note', 'comment'],
        ];

        foreach ($headers as $index => $header) {
            if (is_null($header)) continue;
            $header = trim((string)$header);
            foreach ($possibleKeys as $key => $searchTerms) {
                if (in_array($header, $searchTerms)) {
                    $map[$key] = $index;
                    break;
                }
            }
        }
        
        return $map;
    }

    private function getVal($row, $map, $key)
    {
        if (isset($map[$key]) && isset($row[$map[$key]])) {
            return $row[$map[$key]];
        }
        return null;
    }

    public function getResults()
    {
        return [
            'success' => $this->successCount,
            'errors' => $this->errorCount,
            'error_details' => $this->errors,
        ];
    }
}
