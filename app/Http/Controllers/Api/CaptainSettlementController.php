<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CollectedShipper;
use App\Services\CollectedShipperService;
use Illuminate\Http\Request;

class CaptainSettlementController extends Controller
{
    protected $collectionService;

    public function __construct(CollectedShipperService $collectionService)
    {
        $this->collectionService = $collectionService;
    }

    /**
     * List settlements for the captain.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->isShipper()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $collections = CollectedShipper::where('shipper_id', $user->id)
            ->withCount('orders')
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($collections);
    }

    /**
     * Show settlement details.
     */
    public function show(Request $request, $id)
    {
         $user = $request->user();
         $collection = CollectedShipper::where('shipper_id', $user->id)
             ->with(['orders.client', 'orders.governorate', 'orders.city'])
             ->findOrFail($id);
             
         return response()->json($collection);
    }

    /**
     * Get orders available for settlement (delivered but not yet collected).
     */
    public function getAvailableOrders(Request $request)
    {
        $user = $request->user();
        if (!$user->isShipper()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $orders = $this->collectionService->getAvailableOrdersForShipper($user->id);
        
        return response()->json($orders);
    }

    /**
     * Create a new settlement request.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->isShipper()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:order,id',
        ]);

        // Validate that orders are eligible
        $errors = $this->collectionService->validateOrdersForCollection($request->order_ids, $user->id);

        if (!empty($errors)) {
            return response()->json([
                'status' => 'error',
                'errors' => $errors
            ], 422);
        }

        // Create the collection
        $collection = $this->collectionService->createCollection($user->id, $request->order_ids);

        return response()->json([
            'status' => 'success',
            'message' => 'Settlement request created successfully',
            'collection' => $collection->load('orders')
        ]);
    }
}
