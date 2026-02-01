<?php

namespace App\Http\Controllers;

use App\Models\ReturnedClient;
use App\Models\ReturnedShipper;
use Illuminate\Http\Request;

class ReturnsInvoiceController extends Controller
{
    /**
     * View Shipper Return Invoice
     */
    public function shipperInvoice(int $id)
    {
        $user = auth()->user();
        
        $record = ReturnedShipper::with(['shipper', 'orders.client', 'orders.governorate'])
            ->findOrFail($id);

        // Check Permissions
        if (!$user->isAdmin() && $record->shipper_id !== $user->id) {
            abort(403, 'غير مصرح لك بعرض هذه الفاتورة');
        }

        // Check Status
        if ($record->status !== 'completed') {
            abort(403, 'لا يمكن طباعة فاتورة لطلب غير معتمد');
        }

        return view('returns.shipper-invoice', compact('record'));
    }

    /**
     * View Client Return Invoice
     */
    public function clientInvoice(int $id)
    {
        $user = auth()->user();
        
        $record = ReturnedClient::with(['client', 'orders.governorate'])
            ->findOrFail($id);

        // Check Permissions
        if (!$user->isAdmin() && $record->client_id !== $user->id) {
            abort(403, 'غير مصرح لك بعرض هذه الفاتورة');
        }

        // Check Status
        if ($record->status !== 'completed') {
            abort(403, 'لا يمكن طباعة فاتورة لطلب غير معتمد');
        }

        return view('returns.client-invoice', compact('record'));
    }
}
