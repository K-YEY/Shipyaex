<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 13px;
      color: #222;
      margin: 0;
      padding: 0;
    }

    .shipping-label {
      border: 2px dashed #000;
      padding: 10px 15px;
      margin-bottom: 20px;
      width: 100%;
      box-sizing: border-box;
    }

    .top-section {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 2px solid #000;
      padding-bottom: 8px;
    }

    .logo-box img {
      max-height: 60px;
    }

    .recipient-box {
      flex: 1;
      margin-right: 15px;
    }

    .label-small {
      font-size: 12px;
      color: #666;
    }

    .company-name {
      font-weight: bold;
      font-size: 16px;
    }

    .address-text {
      line-height: 1.4;
    }

    .priority-badge {
      background: #ffc107;
      display: inline-block;
      padding: 3px 8px;
      border-radius: 5px;
      font-weight: bold;
      margin-top: 5px;
      font-size: 13px;
    }

    .sender-section {
      border-bottom: 1px dashed #aaa;
      padding: 6px 0;
    }

    .order-info, .item-section {
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      border-bottom: 1px dashed #aaa;
      padding: 6px 0;
    }

    .info-cell, .item-cell {
      width: 48%;
      margin-bottom: 5px;
    }

    .info-label {
      font-weight: bold;
      font-size: 12px;
    }

    .info-value {
      font-size: 13px;
      margin-top: 2px;
    }

    .bottom-section {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 10px;
    }

    .icons-box {
      display: flex;
      gap: 10px;
    }

    .icon {
      text-align: center;
      font-size: 20px;
    }

    .barcode-large {
      text-align: center;
      flex: 1;
    }

    .barcode-number {
      font-size: 14px;
      letter-spacing: 2px;
      font-weight: bold;
      margin-top: 5px;
    }

  </style>
</head>
<body>

@foreach($orders as $order)
  <div class="shipping-label">

    <!-- الصف العلوي -->
    <div class="top-section">
      <div class="logo-box">
        @if(file_exists(public_path('logo.png')))
          <img src="{{ public_path('logo.png') }}" alt="Logo">
        @else
          <strong>{{ config('app.name', 'LOGO') }}</strong>
        @endif
      </div>

      <div class="recipient-box">
        <div class="label-small">To:</div>
        <div class="company-name">{{ $order->customer_name ?? 'Client' }}</div>
        <div class="address-text">
          {{ $order->address ?? '-' }}<br>
          {{ $order->city?->name ?? '' }} @if($order->area) - {{ $order->area->name }} @endif
        </div>
        <div class="priority-badge">
          @if($order->is_shipping_paid) دفع عند اNoستNoم @else  مدفوع مسبقاً @endif
        </div>
      </div>
    </div>

    <!-- Sender -->
    <div class="sender-section">
      <div class="label-small">From:</div>
      <div class="info-value">
        {{ $order->client?->name ?? 'Sender' }}<br>
        Phone: {{ $order->client?->store_name ?? '-' }}
      </div>
    </div>

    <!-- معلومات Order -->
    <div class="order-info">
   
      <div class="info-cell">
        <div class="info-label">City:</div>
        <div class="info-value">{{ $order->city?->name ?? '-' }}</div>
      </div>
      <div class="info-cell">
        <div class="info-label">الFromطقة:</div>
        <div class="info-value">{{ $order->area?->name ?? '-' }}</div>
      </div>
    </div>

    <!-- Weight والمبلغ -->
    <div class="item-section">
      <div class="item-cell">
        <div class="info-label">المبلغ المطلوب:</div>
        <div class="info-value">{{ number_format($order->total_amount ?? 0, 0) }} ج.م</div>
      </div>
    </div>

    <!-- أسفل -->
    <div class="bottom-section">
      <div class="barcode-large">
        <barcode code="{{ $order->code }}" type="C128B" height="1.5" />
        <div class="barcode-number">{{ $order->code }}</div>
      </div>
    </div>
  </div>
@endforeach

</body>
</html>
