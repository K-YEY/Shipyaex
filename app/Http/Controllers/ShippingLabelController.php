<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class ShippingLabelController extends Controller
{
    /**
     * Print single order label
     */
    public function printSingle($orderId)
    {
        $order = Order::with(['client', 'governorate', 'city'])->findOrFail($orderId);
        
        return view('pdf.shipping-label', [
            'orders' => collect([$order]),
        ]);
    }

    /**
     * Print multiple order labels
     */
    public function printMultiple(Request $request)
    {
        $orderIds = $request->input('ids', []);
        
        if (is_string($orderIds)) {
            $orderIds = explode(',', $orderIds);
        }
        
        $orders = Order::with(['client', 'governorate', 'city'])
            ->whereIn('id', $orderIds)
            ->get();
        
        if ($orders->isEmpty()) {
            abort(404, 'لم يتم العثور على أوردرات');
        }
        
        return view('pdf.shipping-label', [
            'orders' => $orders,
        ]);
    }
}
