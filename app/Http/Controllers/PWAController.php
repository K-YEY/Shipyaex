<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PWAController extends Controller
{
    /**
     * Subscribe to push notifications
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $user = Auth::user();
        
        if ($user) {
            // Store subscription in user settings or separate table
            $user->update([
                'push_subscription' => json_encode($validated)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscribed to push notifications'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'User not authenticated'
        ], 401);
    }

    /**
     * Unsubscribe from push notifications
     */
    public function unsubscribe(Request $request)
    {
        $user = Auth::user();
        
        if ($user) {
            $user->update([
                'push_subscription' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Unsubscribed from push notifications'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'User not authenticated'
        ], 401);
    }

    /**
     * Get manifest dynamically (optional - for dynamic values)
     */
    public function manifest()
    {
        $manifest = [
            'name' => config('app.name', 'Shipping Management System'),
            'short_name' => 'ShipManager',
            'description' => 'Professional shipping and logistics management system',
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#0066cc',
            'orientation' => 'portrait-primary',
            'icons' => [
                [
                    'src' => '/icons/icon-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ],
                [
                    'src' => '/icons/icon-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ]
            ],
            'categories' => ['business', 'productivity', 'utilities'],
            'shortcuts' => [
                [
                    'name' => 'Orders',
                    'short_name' => 'Orders',
                    'description' => 'View all orders',
                    'url' => '/admin/orders',
                    'icons' => [
                        [
                            'src' => '/icons/icon-192x192.png',
                            'sizes' => '192x192'
                        ]
                    ]
                ],
                [
                    'name' => 'Dashboard',
                    'short_name' => 'Dashboard',
                    'description' => 'Go to dashboard',
                    'url' => '/admin',
                    'icons' => [
                        [
                            'src' => '/icons/icon-192x192.png',
                            'sizes' => '192x192'
                        ]
                    ]
                ]
            ]
        ];

        return response()->json($manifest);
    }
}
