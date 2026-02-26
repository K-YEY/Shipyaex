<?php

use App\Http\Controllers\Api\CaptainOrderController;
use App\Http\Controllers\Api\CaptainSettlementController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Captain Login
Route::post('/login', function (Request $request) {
    $request->validate([
        'username' => 'required',
        'password' => 'required',
        'device_name' => 'required',
    ]);

    $user = User::where('username', $request->username)
        ->orWhere('phone', $request->username)
        ->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'بيانات الدخول غير صحيحة.'
        ], 422);
    }

    if (!$user->isShipper()) {
        return response()->json([
            'message' => 'عفواً، هذا الحساب ليس حساب مناديب.'
        ], 403);
    }

    if ($user->is_blocked) {
        return response()->json([
            'message' => 'هذا الحساب محظور.'
        ], 403);
    }

    return response()->json([
        'token' => $user->createToken($request->device_name)->plainTextToken,
        'user' => $user
    ]);
});

// Authenticated Routes
Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // --- Captain Order Routes ---
    Route::prefix('captain')->group(function () {
        Route::get('/orders', [CaptainOrderController::class, 'index']);
        Route::get('/orders/statuses', [CaptainOrderController::class, 'getStatuses']);
        Route::get('/orders/{id}', [CaptainOrderController::class, 'show']);
        Route::post('/orders/{id}/status', [CaptainOrderController::class, 'updateStatus']);

        // --- Captain Settlement Routes ---
        Route::get('/settlements', [CaptainSettlementController::class, 'index']);
        Route::get('/settlements/available-orders', [CaptainSettlementController::class, 'getAvailableOrders']);
        Route::get('/settlements/{id}', [CaptainSettlementController::class, 'show']);
        Route::post('/settlements', [CaptainSettlementController::class, 'store']);
    });
});
