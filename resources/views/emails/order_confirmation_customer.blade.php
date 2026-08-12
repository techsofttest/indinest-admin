@extends('emails.layouts.master')

@section('title', 'Order Confirmed – ' . $order->order_number)

@section('content')
    <h2 style="color: #010526; margin-bottom: 5px;">Payment Successful &amp; Order Confirmed!</h2>
    <p>Hi {{ $order->customer_name ?? $order->first_name ?? 'Customer' }},</p>
    <p>Thank you for your order! Your payment for <strong>{{ $order->order_number }}</strong> was successful and your order is now confirmed.</p>

    <!-- Order Info -->
    <div class="info-box">
        <div class="info-row"><span class="info-label">Order Number:</span> {{ $order->order_number }}</div>
        <div class="info-row"><span class="info-label">Order Date:</span> {{ optional($order->created_at)->format('d M, Y H:i') }}</div>
        <div class="info-row"><span class="info-label">Payment Status:</span> Paid</div>
        @if($order->shipping_method)
        <div class="info-row"><span class="info-label">Shipping:</span> {{ ucfirst($order->shipping_method) }}</div>
        @endif
    </div>

    <!-- Delivery Address -->
    <div style="margin: 20px 0; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden;">
        <div style="background-color: #f8fafc; padding: 10px 15px; border-bottom: 1px solid #e2e8f0; font-weight: bold;">
            Delivery Address
        </div>
        <div style="padding: 15px; font-size: 14px; line-height: 1.7;">
            <strong>{{ $order->shipping_name }}</strong><br>
            {{ $order->shipping_address_line_1 }}
            @if($order->shipping_address_line_2)<br>{{ $order->shipping_address_line_2 }}@endif
            <br>{{ $order->shipping_city }}@if($order->shipping_state && $order->shipping_state !== 'N/A'), {{ $order->shipping_state }}@endif {{ $order->shipping_postcode }}
            <br>{{ $order->shipping_country }}
            @if($order->shipping_phone)<br>Phone: {{ $order->shipping_phone }}@endif
        </div>
    </div>

    <!-- Items Ordered -->
    <h3 style="margin-top: 20px; margin-bottom: 10px; font-size: 15px; border-bottom: 2px solid #010526; padding-bottom: 5px; color: #010526;">
        Items Ordered
    </h3>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px;">
        <thead>
            <tr style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;">
                <th style="padding: 10px;">Item</th>
                <th style="padding: 10px;">Variant</th>
                <th style="padding: 10px; text-align: center;">Qty</th>
                <th style="padding: 10px; text-align: right;">Price</th>
                <th style="padding: 10px; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
            <tr style="border-bottom: 1px solid #edf2f7;">
                <td style="padding: 10px; font-weight: 500;">{{ $item->product_name }}</td>
                <td style="padding: 10px; color: #64748b; font-size: 13px;">{{ $item->variant_details ?: '—' }}</td>
                <td style="padding: 10px; text-align: center;">{{ $item->quantity }}</td>
                <td style="padding: 10px; text-align: right;">£{{ number_format((float) $item->price, 2) }}</td>
                <td style="padding: 10px; text-align: right; font-weight: 500;">£{{ number_format((float) $item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <div style="width: 280px; margin-left: auto; font-size: 14px; line-height: 2.0; border-top: 1px solid #e2e8f0; padding-top: 12px;">
        <div style="display: flex; justify-content: space-between;">
            <span style="color: #64748b;">Subtotal:</span>
            <span>£{{ number_format((float) $order->subtotal, 2) }}</span>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span style="color: #64748b;">Shipping:</span>
            <span>{{ $order->shipping_cost > 0 ? '£' . number_format((float) $order->shipping_cost, 2) : 'Free' }}</span>
        </div>
        @if($order->discount > 0)
        <div style="display: flex; justify-content: space-between; color: #dc2626;">
            <span>Discount ({{ $order->coupon_code }}):</span>
            <span>-£{{ number_format((float) $order->discount, 2) }}</span>
        </div>
        @endif
        <div style="display: flex; justify-content: space-between; border-top: 2px solid #e2e8f0; padding-top: 10px; margin-top: 5px; font-size: 16px; font-weight: bold; color: #010526;">
            <span>Total Paid:</span>
            <span>£{{ number_format((float) $order->grand_total, 2) }}</span>
        </div>
    </div>

    <p style="margin-top: 25px; color: #555;">If you have any questions about your order, please contact us and reference your order number <strong>{{ $order->order_number }}</strong>.</p>
    <p>Thank you for shopping with us!<br><strong>The IndiNest Team</strong></p>
@endsection
