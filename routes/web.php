<?php

use App\Http\Controllers\CollectingInvoiceController;
use App\Http\Controllers\ShippingLabelController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $plans = \App\Models\Plan::with(['planPrices.governorate'])->get();
    
    // تجهيز الداتا عشان تظهر مجمعة حسب السعر
    $preparedPlans = $plans->map(function($plan) {
        $groupedPrices = $plan->planPrices->groupBy('price')->map(function($items, $price) {
            return [
                'price' => $price,
                'governorates' => $items->pluck('governorate.name')->filter()->join('، ')
            ];
        });
        
        return [
            'name' => $plan->name,
            'order_count' => $plan->order_count,
            'groups' => $groupedPrices
        ];
    });

    return view('welcome', ['plans' => $preparedPlans]);
});

// Language Switcher Route




// Collecting Invoice Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/collecting/shipper/{id}/invoice', [CollectingInvoiceController::class, 'shipperInvoice'])
        ->name('collecting.shipper.invoice');
    
    Route::get('/collecting/client/{id}/invoice', [CollectingInvoiceController::class, 'clientInvoice'])
        ->name('collecting.client.invoice');
    
    // Returns Invoice Routes
    Route::get('/returns/shipper/{id}/invoice', [App\Http\Controllers\ReturnsInvoiceController::class, 'shipperInvoice'])
        ->name('returns.shipper.invoice');
    
    Route::get('/returns/client/{id}/invoice', [App\Http\Controllers\ReturnsInvoiceController::class, 'clientInvoice'])
        ->name('returns.client.invoice');
    
    // Shipping Label Routes
    Route::get('/orders/{order}/print-label', [ShippingLabelController::class, 'printSingle'])
        ->name('orders.print-label');
    
    Route::get('/orders/print-labels', [ShippingLabelController::class, 'printMultiple'])
        ->name('orders.print-labels');
    
   
});

