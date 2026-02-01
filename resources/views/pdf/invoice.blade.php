<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>Invoice الشحن</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            direction: rtl;
            background-color: #f5f5f5;
            padding: 0;
        }

        .invoice-wrapper {
            max-width: 850px;
            margin: 0 auto;
            background: white;
        }

        /* Header الأحمر المميز */
        .invoice-header {
            background: linear-gradient(135deg, #fb2c36 0%, #d91f28 100%);
            color: white;
            padding: 15px 25px;
            position: relative;
            overflow: hidden;
        }

        .invoice-header::before {
            content: '';
            position: absolute;
            top: -30px;
            left: -30px;
            width: 120px;
            height: 120px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }

        .invoice-header::after {
            content: '';
            position: absolute;
            bottom: -20px;
            right: -20px;
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }

        .header-container {
            display: table;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .logo-section {
            display: table-cell;
            width: 20%;
            vertical-align: middle;
        }

        .logo-section img {
            width: 60px;
            height: 60px;
            background: white;
            padding: 8px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .header-info {
            display: table-cell;
            width: 80%;
            vertical-align: middle;
            text-align: left;
            direction: ltr;
            padding-right: 20px;
        }

        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 3px;
            letter-spacing: 1px;
        }

        .invoice-subtitle {
            font-size: 11px;
            opacity: 0.9;
        }

        /* Client Info Card */
        .client-card {
            background: #f8f9fa;
            border-right: 4px solid #fb2c36;
            padding: 15px 20px;
            margin: 15px 25px;
            border-radius: 6px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }

        .client-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }

        .client-name {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
        }

        .invoice-meta {
            text-align: left;
            direction: ltr;
            color: #6c757d;
            font-size: 11px;
        }

        .invoice-number {
            font-weight: bold;
            color: #fb2c36;
            font-size: 13px;
        }

        .client-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 10px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            font-size: 14px;
            color: #495057;
        }

        .detail-label {
            font-weight: bold;
            margin-left: 5px;
            color: #6c757d;
        }

        /* Orders Table */
        .table-container {
            padding: 15px 25px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 3px solid #fb2c36;
            display: inline-block;
        }

        .orders-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            border-radius: 6px;
            overflow: hidden;
        }

        .orders-table thead {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
        }

        .orders-table th {
            padding: 10px 8px;
            text-align: right;
            font-weight: 600;
            font-size: 11px;
            letter-spacing: 0.3px;
        }

        .orders-table tbody tr {
            background: white;
            transition: all 0.2s;
        }

        .orders-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .orders-table tbody tr:hover {
            background: #e9ecef;
        }

        .orders-table td {
            padding: 8px;
            border-bottom: 1px solid #e9ecef;
            color: #495057;
            font-size: 11px;
        }

        .order-code {
            font-weight: bold;
            color: #fb2c36;
        }

        /* Total Row */
        .total-row {
            background: linear-gradient(135deg, #fb2c36 0%, #d91f28 100%) !important;
            color: white !important;
            font-weight: bold;
        }

        .total-row td {
            border-bottom: none !important;
            font-size: 12px;
            padding: 12px 8px !important;
        }

        /* Summary Box */
        .summary-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
            padding: 15px;
            margin: 15px 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 12px;
            color: #495057;
            border-bottom: 1px dashed #dee2e6;
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-label {
            font-weight: 600;
        }

        .summary-value {
            font-weight: bold;
            color: #2c3e50;
        }

        .grand-total-row {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 13px;
        }

        .grand-total-row .summary-label,
        .grand-total-row .summary-value {
            color: white;
            font-size: 14px;
        }


        /* Page Break */
        .page-break {
            page-break-after: always;
        }

        /* Badge للعدد */
        .badge {

            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    @foreach($ordersByClient as $clientId => $orders)
    <div class="invoice-wrapper @if(!$loop->last) page-break @endif">

        <!-- Header -->
        <div class="invoice-header">
            <div class="header-container">
                <div class="logo-section">
                    @if(file_exists(public_path('logo-pdf.png')))
                    <img src="{{ public_path('logo-pdf.png') }}" alt="Logo">
                    @else
                    <div style="width: 60px; height: 60px; background: white; border-radius: 8px;"></div>
                    @endif
                </div>
                <div class="header-info">
                    <div class="invoice-title">INVOICE</div>
                    <div class="invoice-subtitle">Invoice الشحن والتوصيل</div>
                </div>
            </div>
        </div>

        @php
            $client = $orders->first()->client;
            $totalAmount = 0;
            $totalFees = 0;
            $totalCod = 0;
        @endphp

        <!-- Client Card -->
        <div class="client-card">
            <div class="client-header">
                <div class="client-name">
                    {{ $client?->name ?? 'عميل' }}
                </div>
                <div class="invoice-meta">
                    <div class="invoice-number">#INV-{{ str_pad($clientId, 6, '0', STR_PAD_LEFT) }}</div>
                    <div style="margin-top: 2px;">{{ now()->format('d/m/Y') }}</div>
                </div>
            </div>

            <div class="client-details">
                <div class="detail-item">
                    <span class="detail-label"> المتجر:</span>
                    <span>{{ $client?->clientProfile?->store_name ?? '-' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"> التليفون:</span>
                    <span>{{ $client?->phone ?? '-' }}</span>
                </div>
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <span class="detail-label"> Address:</span>
                    <span>{{ $client?->address ?? '-' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"> عدد Orderات:</span>
                    <span class="badge">{{ $orders->count() }} طلب</span>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="table-container">
            <div class="section-title">Details Orderات</div>

            <table class="orders-table">
                <thead>
                    <tr>
                        <th style="width: 12%;">الكود</th>
                        <th style="width: 35%;"> اسم Receiver | Phone Number</th>
                        <th style="width: 18%;">قيمة Order</th>
                        <th style="width: 15%;">Fees</th>
                        <th style="width: 20%;">COD</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    <tr>
                        <td class="order-code">#{{ $order->code }}</td>
                        <td>{{ $order->customer_name ?? '—' }} | {{ $order->customer_phone ?? '-' }}</td>
                        <td>{{ number_format($order->total_amount ?? 0, 2) }} ج.م</td>
                        <td>{{ number_format($order->fees ?? 0, 2) }} ج.م</td>
                        <td><strong>{{ number_format($order->cod ?? 0, 2) }} ج.م</strong></td>
                    </tr>

                    @php
                        $totalAmount += ($order->total_amount ?? 0);
                        $totalFees += ($order->fees ?? 0);
                        $totalCod += ($order->cod ?? 0);
                    @endphp
                    @endforeach

                    <tr class="total-row">
                        <td colspan="2"><strong>Total Allي</strong></td>
                        <td><strong>{{ number_format($totalAmount, 2) }} ج.م</strong></td>
                        <td><strong>{{ number_format($totalFees, 2) }} ج.م</strong></td>
                        <td><strong>{{ number_format($totalCod, 2) }} ج.م</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Summary -->
        <div class="summary-box">
            <div class="summary-row">
                <div class="summary-label">إجمالي قيمة Orderات</div>
                <div class="summary-value">{{ number_format($totalAmount, 2) }} ج.م</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">إجمالي رسوم الشحن</div>
                <div class="summary-value">{{ number_format($totalFees, 2) }} ج.م</div>
            </div>
            <div class="grand-total-row">
                <div class="summary-row" style="border: none; padding: 0;">
                    <div class="summary-label">المطلوب تحصيله (COD)</div>
                    <div class="summary-value">{{ number_format($totalCod, 2) }} ج.م</div>
                </div>
            </div>
        </div>



    </div>
    @endforeach
</body>
</html>
