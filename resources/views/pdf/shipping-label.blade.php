<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Label</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            size: A5 landscape;
            margin: 5mm;
        }
        
        body {
            font-family: 'Arial', 'Tahoma', sans-serif;
            font-size: 11px;
            direction: rtl;
            background: white;
        }
        
        .label {
            width: 100%;
            max-width: 210mm;
            border: 2px solid #000;
            padding: 8px;
            margin-bottom: 10px;
            page-break-inside: avoid;
            page-break-after: always;
        }
        
        .label:last-child {
            page-break-after: auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        
        .logo {
            width: 80px;
            height: auto;
        }
        
        .order-code {
            font-size: 18px;
            font-weight: bold;
            text-align: left;
            direction: ltr;
        }
        
        .barcode {
            font-family: 'Libre Barcode 39', monospace;
            font-size: 32px;
            text-align: left;
            direction: ltr;
        }
        
        .row {
            display: flex;
            border-bottom: 1px solid #ccc;
            padding: 5px 0;
        }
        
        .row:last-child {
            border-bottom: none;
        }
        
        .field {
            flex: 1;
            display: flex;
            align-items: center;
        }
        
        .field-label {
            font-weight: bold;
            min-width: 80px;
            color: #333;
        }
        
        .field-value {
            flex: 1;
            border: 1px solid #000;
            padding: 4px 8px;
            min-height: 24px;
            background: #fafafa;
        }
        
        .field-value.large {
            font-size: 14px;
            font-weight: bold;
        }
        
        .two-cols {
            display: flex;
            gap: 10px;
        }
        
        .two-cols .field {
            flex: 1;
        }
        
        .price-box {
            text-align: center;
            padding: 8px;
            border: 2px solid #000;
            font-size: 16px;
            font-weight: bold;
            background: #f0f0f0;
        }
        
        .price-box.highlight {
            background: #000;
            color: #fff;
            font-size: 20px;
        }
        
        .checkbox-section {
            display: flex;
            justify-content: center;
            gap: 30px;
            padding: 8px;
            border-top: 2px solid #000;
            margin-top: 8px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
        }
        
        .checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid #000;
            display: inline-block;
        }
        
        .checkbox.checked {
            background: #000;
            color: #fff;
            text-align: center;
            line-height: 16px;
            font-weight: bold;
        }
        
        .footer {
            text-align: center;
            font-size: 9px;
            color: #666;
            margin-top: 5px;
            padding-top: 5px;
            border-top: 1px dashed #ccc;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            body {
                background: white;
            }
        }
        
        .print-btn {
            position: fixed;
            top: 10px;
            left: 10px;
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
        }
        
        .print-btn:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">🖨️ Print</button>
    
    @foreach($orders as $order)
    <div class="label">
        <!-- Header -->
        <div class="header">
            <div>
                <img src="{{ asset('logo.png') }}" alt="Logo" class="logo" onerror="this.style.display='none'">
            </div>
            <div>
                <div class="order-code">{{ $order->code }}</div>
                <div class="barcode">*{{ $order->code }}*</div>
            </div>
        </div>
        
        <!-- Client Info -->
        <div class="row">
            <div class="field">
                <span class="field-label">اسم الشركة:</span>
                <span class="field-value large">{{ $order->client?->name ?? '-' }}</span>
            </div>
            <div class="field" style="max-width: 180px;">
                <span class="field-label">رقم الشركة:</span>
                <span class="field-value">{{ $order->client?->phone ?? '-' }}</span>
            </div>
        </div>
        
        <!-- Recipient Name -->
        <div class="row">
            <div class="field">
                <span class="field-label">اسم Receiver:</span>
                <span class="field-value large">{{ $order->name }}</span>
            </div>
        </div>
        
        <!-- Recipient Phone -->
        <div class="row">
            <div class="field">
                <span class="field-label">رقم Receiver:</span>
                <span class="field-value large">{{ $order->phone }}{{ $order->phone_2 ? ' / ' . $order->phone_2 : '' }}</span>
            </div>
        </div>
        
        <!-- Address -->
        <div class="row">
            <div class="field">
                <span class="field-label">Address:</span>
                <span class="field-value">
                    {{ $order->address }}
                    @if($order->governorate || $order->city)
                        - {{ $order->governorate?->name }} / {{ $order->city?->name }}
                    @endif
                </span>
            </div>
        </div>
        
        <!-- Prices -->
        <div class="row two-cols">
            <div class="field">
                <span class="field-label">Price بدون شحن:</span>
                <div class="price-box">{{ number_format($order->cod ?? 0, 2) }} EGP</div>
            </div>
            <div class="field">
                <span class="field-label">Price بشحن:</span>
                <div class="price-box highlight">{{ number_format($order->total_amount ?? 0, 2) }} EGP</div>
            </div>
        </div>
        
        <!-- Shipping Content -->
        <div class="row">
            <div class="field">
                <span class="field-label">محتوى الشحن:</span>
                <span class="field-value">{{ $order->order_note ?? '-' }}</span>
            </div>
        </div>
        
        <!-- Checkboxes -->
        <div class="checkbox-section">
            <div class="checkbox-item">
                <span class="checkbox {{ $order->allow_open ? 'checked' : '' }}">{{ $order->allow_open ? '✓' : '' }}</span>
                <span>مسموح بالفتح</span>
            </div>
            <div class="checkbox-item">
                <span class="checkbox {{ !$order->allow_open ? 'checked' : '' }}">{{ !$order->allow_open ? '✓' : '' }}</span>
                <span>غير مسموح بالفتح</span>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            تاريخ الPrint: {{ now()->format('Y-m-d H:i') }} | {{ config('app.name') }}
        </div>
    </div>
    @endforeach
</body>
</html>
