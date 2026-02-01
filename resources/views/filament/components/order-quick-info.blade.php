<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; margin: 10px 0;">
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
        <div>
            <div style="font-size: 11px; opacity: 0.9; margin-bottom: 3px;">الكود</div>
            <div style="font-size: 18px; font-weight: bold; font-family: monospace;">{{ $order['code'] }}</div>
        </div>
        <div>
            <div style="font-size: 11px; opacity: 0.9; margin-bottom: 3px;">Status</div>
            <div style="font-size: 14px; font-weight: 600;">
                @if($order['status'] === 'deliverd')
                    <span style="background: #10b981; padding: 4px 12px; border-radius: 4px;">تم التسليم</span>
                @elseif($order['status'] === 'undelivered')
                    <span style="background: #ef4444; padding: 4px 12px; border-radius: 4px;">لم يتم التسليم</span>
                @elseif($order['status'] === 'hold')
                    <span style="background: #f59e0b; padding: 4px 12px; border-radius: 4px;">معلق</span>
                @else
                    <span style="background: #3b82f6; padding: 4px 12px; border-radius: 4px;">{{ $order['status'] }}</span>
                @endif
            </div>
        </div>
        <div>
            <div style="font-size: 11px; opacity: 0.9; margin-bottom: 3px;">Name</div>
            <div style="font-size: 14px; font-weight: 600;">{{ $order['name'] }}</div>
        </div>
        <div>
            <div style="font-size: 11px; opacity: 0.9; margin-bottom: 3px;">Phone</div>
            <div style="font-size: 14px; font-weight: 600; direction: ltr; text-align: right;">{{ $order['phone'] }}</div>
        </div>
        <div>
            <div style="font-size: 11px; opacity: 0.9; margin-bottom: 3px;">المبلغ</div>
            <div style="font-size: 16px; font-weight: bold;">{{ number_format($order['total_amount'], 2) }} ج.م</div>
        </div>
        <div>
            <div style="font-size: 11px; opacity: 0.9; margin-bottom: 3px;">Shipper</div>
            <div style="font-size: 14px; font-weight: 600;">{{ $order['shipper_name'] }}</div>
        </div>
    </div>
    
    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.2); display: flex; gap: 10px; flex-wrap: wrap;">
        @if($order['collected_shipper'])
            <span style="background: rgba(16, 185, 129, 0.3); padding: 4px 10px; border-radius: 4px; font-size: 12px;">
                ✓ تم تحصيل Shipper
            </span>
        @endif
        
        @if($order['collected_client'])
            <span style="background: rgba(59, 130, 246, 0.3); padding: 4px 10px; border-radius: 4px; font-size: 12px;">
                ✓ تم تحصيل Client
            </span>
        @endif
        
        @if($order['has_return'])
            <span style="background: rgba(245, 158, 11, 0.3); padding: 4px 10px; border-radius: 4px; font-size: 12px;">
                ⚠ مرتجع
            </span>
        @endif
    </div>
</div>
