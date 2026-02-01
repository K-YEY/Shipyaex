<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة مرتجع عميل #{{ $record->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Tajawal', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            background: #fff;
            direction: rtl;
            padding: 15px;
        }
        
        .invoice {
            max-width: 800px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #fb2c36;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        
        .company h1 {
            font-size: 18px;
            color: #fb2c36;
            margin-bottom: 3px;
        }
        
        .company p {
            color: #666;
            font-size: 10px;
        }
        
        .invoice-title {
            text-align: left;
        }
        
        .invoice-title h2 {
            font-size: 14px;
            color: #333;
            margin-bottom: 4px;
        }
        
        .invoice-num {
            background: #fb2c36;
            color: #fff;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        
        /* Info Cards */
        .info-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .info-card {
            flex: 1;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px;
        }
        
        .info-card h3 {
            color: #fb2c36;
            font-size: 12px;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .info-card table {
            width: 100%;
        }
        
        .info-card td {
            padding: 2px 0;
            font-size: 10px;
        }
        
        .info-card td:first-child {
            font-weight: bold;
            color: #666;
            width: 35%;
        }
        
        /* Orders Table */
        .orders-title {
            font-size: 13px;
            margin-bottom: 8px;
            color: #333;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }
        
        .orders-table th {
            background: #fb2c36;
            color: #fff;
            padding: 6px 4px;
            text-align: right;
            font-weight: bold;
        }
        
        .orders-table td {
            border: 1px solid #e2e8f0;
            padding: 5px 4px;
            text-align: right;
        }
        
        .orders-table tr:nth-child(even) {
            background: #f8fafc;
        }
        
        .code {
            background: #e0e7ff;
            color: #fb2c36;
            padding: 1px 6px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 9px;
        }
        
        /* Summary */
        .summary-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 15px;
        }
        
        .summary-box {
            width: 250px;
            border: 2px solid #fb2c36;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .summary-box table {
            width: 100%;
        }
        
        .summary-box td {
            padding: 6px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
        }
        
        .summary-box td:first-child {
            font-weight: bold;
        }
        
        .summary-box td:last-child {
            text-align: left;
        }
        
        .summary-box tr:last-child td {
            background: #fb2c36;
            color: #fff;
            font-size: 12px;
            font-weight: bold;
            border: none;
        }
        
        /* Signatures */
        .signatures {
            display: flex;
            justify-content: space-around;
            margin: 25px 0 15px;
        }
        
        .sig-box {
            text-align: center;
        }
        
        .sig-box .line {
            width: 120px;
            border-bottom: 1px solid #333;
            margin: 30px auto 6px;
        }
        
        .sig-box p {
            font-size: 10px;
        }
        
        /* Footer */
        .footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            text-align: center;
            color: #666;
            font-size: 9px;
        }
        
        /* Print Button */
        .print-btn {
            background: #fb2c36;
            color: #fff;
            border: none;
            padding: 8px 25px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            display: block;
            margin: 15px auto 0;
        }
        
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            .invoice { max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="invoice">
        <!-- Header -->
        <div class="header">
            <div class="company">
                <img src="{{ asset('logo.png') }}" alt="Logo" style="max-width: 100px; max-height: 60px;" onerror="this.style.display='none'">
                <h1>{{ config('app.name', 'شركة الشحن') }}</h1>
                <p>نظام إدارة الشحن المتكامل</p>
            </div>
            <div class="invoice-title">
                <h2>كشف المرتجعات للعميل</h2>
                <span class="invoice-num">#{{ $record->id }}</span>
            </div>
        </div>
        
        <!-- Info Cards -->
        <div class="info-row">
            <div class="info-card">
                <h3>📋 بيانات الكشف</h3>
                <table>
                    <tr>
                        <td>رقم الكشف:</td>
                        <td>#{{ $record->id }}</td>
                    </tr>
                    <tr>
                        <td>التاريخ:</td>
                        <td>{{ $record->return_date->format('Y-m-d') }}</td>
                    </tr>
                    <tr>
                        <td>الحالة:</td>
                        <td>
                            @if($record->status === 'completed')
                                <span style="color: #15803d;">✅ تم التسليم للعميل</span>
                            @elseif($record->status === 'pending')
                                <span style="color: #b45309;">⏳ قيد المراجعة</span>
                            @else
                                <span style="color: #dc2626;">❌ ملغى</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="info-card">
                <h3>👤 بيانات العميل</h3>
                <table>
                    <tr>
                        <td>الاسم:</td>
                        <td>{{ $record->client->name }}</td>
                    </tr>
                    <tr>
                        <td>الهاتف:</td>
                        <td dir="ltr">{{ $record->client->phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>عدد المرتجعات:</td>
                        <td>{{ $record->number_of_orders }} طلب</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Orders Table -->
        <h3 class="orders-title">📦 قائمة الأوردرات المرتجعة للعميل</h3>
        <table class="orders-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>كود الطلب</th>
                    <th>المستلم</th>
                    <th>المحافظة</th>
                    <th>النوع</th>
                    <th>تاريخ التسليم</th>
                </tr>
            </thead>
            <tbody>
                @foreach($record->orders as $index => $order)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><span class="code">{{ $order->code }}</span></td>
                        <td>{{ $order->name }}</td>
                        <td>{{ $order->governorate->name ?? '-' }}</td>
                        <td>
                            @if($order->status === 'undelivered')
                                مرتجع كامل
                            @elseif($order->has_return)
                                مرتجع جزئي
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $record->return_date->format('Y-m-d') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Summary -->
        <div class="summary-row">
            <div class="summary-box">
                <table>
                    <tr>
                        <td>إجمالي عدد المرتجعات:</td>
                        <td>{{ $record->number_of_orders }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="background: #fb2c36; color: white; text-align: center;">إقرار من العميل باستلام كافة المرتجعات المذكورة</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Signatures -->
        <div class="signatures">
            <div class="sig-box">
                <div class="line"></div>
                <p>توقيع العميل</p>
            </div>
            <div class="sig-box">
                <div class="line"></div>
                <p>توقيع الإدارة</p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>{{ config('app.name') }} © {{ date('Y') }} - طبع بتاريخ {{ now()->format('Y-m-d H:i') }}</p>
        </div>
        
        <!-- Print Button -->
        <button class="print-btn no-print" onclick="window.print()">🖨️ طباعة فاتورة المرتجعات</button>
    </div>
</body>
</html>
