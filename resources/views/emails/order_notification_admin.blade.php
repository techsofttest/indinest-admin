@extends('emails.layouts.master')

@section('title', 'New Order Paid – ' . $order->order_number)

@section('content')
    <h2 style="color: #010526;">New Order Paid – {{ $order->order_number }}</h2>

    <div class="info-box">
        <div class="info-row"><span class="info-label">Order #:</span> {{ $order->order_number }}</div>
        <div class="info-row"><span class="info-label">Date:</span> {{ optional($order->created_at)->format('d M, Y H:i') }}</div>
        <div class="info-row"><span class="info-label">Customer:</span> {{ $order->customer_name ?? $order->first_name . ' ' . $order->last_name }}</div>
        <div class="info-row"><span class="info-label">Email:</span> {{ $order->customer_email ?? $order->email }}</div>
        <div class="info-row"><span class="info-label">Phone:</span> {{ $order->customer_phone ?? $order->phone ?? '—' }}</div>
        <div class="info-row"><span class="info-label">Shipping To:</span> {{ $order->shipping_country }}</div>
        <div class="info-row"><span class="info-label">Shipping Method:</span> {{ ucfirst($order->shipping_method ?? 'standard') }}</div>
        <div class="info-row"><span class="info-label">Grand Total:</span> £{{ number_format((float) $order->grand_total, 2) }}</div>
        <div class="info-row"><span class="info-label">Payment:</span> {{ ucfirst($order->payment_method) }} / Paid</div>
    </div>

    <!-- Items Ordered -->
    <h3 style="margin-top: 20px; margin-bottom: 10px; font-size: 15px; border-bottom: 2px solid #010526; padding-bottom: 5px; color: #010526;">
        Items
    </h3>
    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
        <thead>
            <tr style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;">
                <th style="padding: 8px;">Product</th>
                <th style="padding: 8px;">Variant</th>
                <th style="padding: 8px; text-align: center;">Qty</th>
                <th style="padding: 8px; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
            <tr style="border-bottom: 1px solid #edf2f7;">
                <td style="padding: 8px;">{{ $item->product_name }}</td>
                <td style="padding: 8px; color: #64748b;">{{ $item->variant_details ?: '—' }}</td>
                <td style="padding: 8px; text-align: center;">{{ $item->quantity }}</td>
                <td style="padding: 8px; text-align: right;">£{{ number_format((float) $item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 20px; color: #555; font-size: 13px;">Log in to the admin panel to view and manage this order.</p>
@endsection
