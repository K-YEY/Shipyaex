<?php

namespace App\Services;

class CODCollectionService
{
    /**
     * تحديد إمكانية التحصيل (COD Collection) بناءً على حالة Order والـ flags
     *
     * @param string $orderStatus - حالة Order (delivered, undelivered)
     * @param bool $hasReturn - هل Order له return
     * @param bool $returnShipper - هل تم عمل return shipper
     * @param bool $collectShipper - هل تم التحصيل من الشيبر
     * @return bool
     */
    public static function canCollect(
        string $orderStatus,
        bool $hasReturn,
        bool $returnShipper,
        bool $collectShipper
    ): bool {
        // الشرط الأساسي: return_shipper و collect_shipper يجب أن يكونا true معاً
        $bothFlagsTrue = $returnShipper && $collectShipper;

        if ($orderStatus === 'delivered' || $orderStatus === 'undelivered') {
            // لو فيه return: يتحصل مباشرة
            if ($hasReturn) {
                $canCollect = true;
            }
            // لو مفيش return: يحتاج collect_shipper
            else {
                $canCollect = $collectShipper;
            }
        }
        // حالة غير معروفة
        else {
            $canCollect = false;
        }

        return $canCollect;
    }

    /**
     * تحديد إمكانية عمل Return Client بعد التحصيل
     * أي أوردر معمول return_shipper بعد collect_shipper، Client يقدر يعمل return_client
     *
     * @param bool $returnShipper
     * @param bool $collectShipper
     * @return bool
     */
    public static function canReturnClient(bool $returnShipper, bool $collectShipper): bool
    {
        return $returnShipper && $collectShipper;
    }
}
