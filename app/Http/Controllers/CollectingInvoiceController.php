<?php

namespace App\Http\Controllers;

use App\Models\CollectedClient;
use App\Models\CollectedShipper;
use Illuminate\Http\Request;

class CollectingInvoiceController extends Controller
{
    /**
     * عرض فاتورة تحصيل Shipper
     */
    public function shipperInvoice(int $id)
    {
        $user = auth()->user();
        
        $collection = CollectedShipper::with(['shipper', 'orders.governorate'])
            ->findOrFail($id);

        // التحقق من الصNoحيات
        if (!$user->isAdmin() && $collection->shipper_id !== $user->id) {
            abort(403, 'غير مصرح لك بعرض هذه الفاتورة');
        }

        // التحقق من أن التحصيل Completed
        if ($collection->status !== 'completed') {
            abort(403, 'No يمكن Print فاتورة لتحصيل غير Completed');
        }

        return view('collecting.shipper-invoice', compact('collection'));
    }

    /**
     * عرض فاتورة تحصيل Client
     */
    public function clientInvoice(int $id)
    {
        $user = auth()->user();
        
        $collection = CollectedClient::with(['client', 'orders.governorate'])
            ->findOrFail($id);

        // التحقق من الصNoحيات
        if (!$user->isAdmin() && $collection->client_id !== $user->id) {
            abort(403, 'غير مصرح لك بعرض هذه الفاتورة');
        }

        // التحقق من أن التحصيل Completed
        if ($collection->status !== 'completed') {
            abort(403, 'No يمكن Print فاتورة لتحصيل غير Completed');
        }

        return view('collecting.client-invoice', compact('collection'));
    }
}
