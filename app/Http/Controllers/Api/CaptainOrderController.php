<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CaptainOrderController extends Controller
{
    /**
     * View orders assigned to the captain.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->isShipper()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $orders = Order::where('shipper_id', $user->id)
            ->where('collected_shipper', false)
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->search, function ($query, $search) {
                return $query->where(function($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->with(['client:id,name', 'governorate:id,name', 'city:id,name'])
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($orders);
    }

    /**
     * Show single order details.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $order = Order::where('shipper_id', $user->id)
            ->where('collected_shipper', false)
            ->findOrFail($id);
        
        $order->load(['client', 'governorate', 'city', 'statusHistories.user']);

        return response()->json($order);
    }

    /**
     * Update order status.
     */
    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();
        $order = Order::where('shipper_id', $user->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|string',
            'status_note' => 'nullable|string',
            'has_return' => 'nullable|boolean',
            'total_amount' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldStatus = $order->status;
        $newStatus = $request->status;

        // Map dashes to spaces for enum compatibility (e.g., 'out-for-delivery' to 'out for delivery')
        $normalizedStatus = str_replace('-', ' ', $newStatus);

        // Check if status exists (check both original and normalized)
        if (!OrderStatus::where('slug', $newStatus)->orWhere('slug', $normalizedStatus)->exists()) {
             return response()->json(['message' => 'Invalid status slug'], 422);
        }

        $order->status = $normalizedStatus;
        
        // Handle status note array
        if ($request->filled('status_note')) {
            $currentNotes = is_array($order->status_note) ? $order->status_note : [];
            $currentNotes[] = [
                'note' => $request->status_note,
                'status' => $newStatus,
                'user' => $user->name,
                'date' => Carbon::now()->toDateTimeString(),
            ];
            $order->status_note = $currentNotes;
        }

        // Handle return flag
        if ($request->has('has_return')) {
            $order->has_return = (bool) $request->has_return;
            if ($order->has_return) {
                $order->has_return_date = Carbon::now();
            }
        }

        // Handle total amount (for partial delivery or updates)
        if ($request->filled('total_amount')) {
             $order->total_amount = $request->total_amount;
        }

        $order->save();

        // Log history as per app patterns
        $order->statusHistories()->create([
            'status' => $normalizedStatus,
            'old_status' => $oldStatus,
            'changed_by' => $user->id,
            'note' => $request->status_note ?? '',
            'action_type' => 'status_changed',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Order status updated successfully',
            'order' => $order->fresh()->load('statusHistories')
        ]);
    }
    
    /**
     * Get available statuses for the captain to choose from.
     */
    public function getStatuses()
    {
        return response()->json(OrderStatus::active()->ordered()->with('refusedReasons:id,name,slug')->get());
    }
}
