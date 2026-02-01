<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Tajawal', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            direction: rtl;
        }
        
        .container {
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #3b82f6;
        }
        
        .header h2 {
            font-size: 18px;
            color: #1e40af;
        }
        
        .header .info {
            text-align: left;
        }
        
        .header .info p {
            font-size: 13px;
            color: #666;
            margin: 3px 0;
        }
        
        .stats {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-card.primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .stat-card .label {
            font-size: 11px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .stat-card .value {
            font-size: 20px;
            font-weight: bold;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .orders-table th {
            background: #3b82f6;
            color: white;
            padding: 10px 8px;
            text-align: right;
            font-weight: 600;
            font-size: 12px;
        }
        
        .orders-table td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: right;
        }
        
        .orders-table tr:nth-child(even) {
            background: #f9fafb;
        }
        
        .orders-table tr:hover {
            background: #eff6ff;
        }
        
        .code {
            background: #dbeafe;
            color: #1e40af;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 10px;
        }
        
        .badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 500;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .print-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            display: block;
            margin: 20px auto 0;
            transition: background 0.3s;
        }
        
        .print-btn:hover {
            background: #2563eb;
        }
        
        .no-orders {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
        
        .no-orders svg {
            width: 64px;
            height: 64px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                padding: 0;
            }
            
            .container {
                padding: 10px;
            }
            
            .orders-table {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h2>🚚 أوردرات Shipper</h2>
                <p style="color: #666; font-size: 12px; margin-top: 5px;">{{ $shipper->name }}</p>
            </div>
            <div class="info">
                <p><strong>Date:</strong> {{ now()->format('Y-m-d') }}</p>
                <p><strong>الوقت:</strong> {{ now()->format('H:i') }}</p>
            </div>
        </div>
        
        @if($orders->count() > 0)
            <div class="stats">
                <div class="stat-card primary">
                    <div class="label">إجمالي Orderات</div>
                    <div class="value">{{ $orders->count() }}</div>
                </div>
                <div class="stat-card success">
                    <div class="label">Total Amount</div>
                    <div class="value">{{ number_format($orders->sum('total_amount'), 0) }} ج.م</div>
                </div>
                <div class="stat-card">
                    <div class="label">رسوم Shipper</div>
                    <div class="value">{{ number_format($orders->sum('shipper_fees'), 0) }} ج.م</div>
                </div>
            </div>
            
            <table class="orders-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>الكود</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Governorate</th>
                        <th>City</th>
                        <th>Address</th>
                        <th>المبلغ</th>
                        <th>Fees</th>
                        <th>مرتجع</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $index => $order)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><span class="code">{{ $order->code }}</span></td>
                            <td>{{ $order->name }}</td>
                            <td dir="ltr">{{ $order->phone }}</td>
                            <td>{{ $order->governorate->name ?? '-' }}</td>
                            <td>{{ $order->city->name ?? '-' }}</td>
                            <td style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $order->address }}
                            </td>
                            <td><strong>{{ number_format($order->total_amount, 2) }}</strong></td>
                            <td>{{ number_format($order->shipper_fees, 2) }}</td>
                            <td>
                                @if($order->has_return)
                                    <span class="badge badge-warning">Yes</span>
                                @else
                                    <span class="badge badge-success">No</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: #f3f4f6; font-weight: bold;">
                        <td colspan="7" style="text-align: left; padding: 10px;">Total</td>
                        <td>{{ number_format($orders->sum('total_amount'), 2) }}</td>
                        <td>{{ number_format($orders->sum('shipper_fees'), 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            
            <button class="print-btn no-print" onclick="window.print()">Print القائمة</button>
        @else
            <div class="no-orders">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <h3 style="margin-bottom: 10px;">No توجد أوردرات</h3>
                <p>No توجد أوردرات في حالة "Out for Delivery" حالياً</p>
            </div>
        @endif
    </div>
</body>
</html>
