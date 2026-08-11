@extends('emails.layouts.master')

@section('title', 'New Order Enquiry #' . $enquiry->enquiry_number)

@section('content')
    <h2>New Order Enquiry Received</h2>
    <p>A customer has submitted a checkout enquiry from outside the online payment delivery zone. Details are provided below:</p>

    <h3 style="border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 20px;">Enquiry Details</h3>
    <table cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 14px;">
        <tr>
            <td style="width: 150px; font-weight: bold; padding: 6px 0;">Enquiry Ref:</td>
            <td>#{{ $enquiry->enquiry_number }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 6px 0;">Customer Name:</td>
            <td>{{ $enquiry->customer_name }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 6px 0;">Email:</td>
            <td>{{ $enquiry->customer_email }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 6px 0;">Phone:</td>
            <td>{{ $enquiry->customer_phone }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 6px 0; vertical-align: top;">Delivery Address:</td>
            <td>
                {{ $enquiry->address }}<br>
                @if($enquiry->apartment)
                    {{ $enquiry->apartment }}<br>
                @endif
                {{ $enquiry->city }}{{ $enquiry->state ? ', ' . $enquiry->state : '' }} {{ $enquiry->pin_code }}<br>
                <strong>{{ $enquiry->country }}</strong>
            </td>
        </tr>
        @if($enquiry->notes)
            <tr>
                <td style="font-weight: bold; padding: 6px 0; vertical-align: top;">Notes:</td>
                <td>{{ $enquiry->notes }}</td>
            </tr>
        @endif
    </table>

    <h3 style="border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 25px;">Items Requested</h3>
    <table cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 10px;">
        <thead>
            <tr style="background-color: #f7f7f7; text-align: left;">
                <th style="border: 1px solid #eee; padding: 8px;">Product</th>
                <th style="border: 1px solid #eee; padding: 8px;">Variant / Size</th>
                <th style="border: 1px solid #eee; padding: 8px; text-align: center;">Qty</th>
                <th style="border: 1px solid #eee; padding: 8px; text-align: right;">Unit Price</th>
                <th style="border: 1px solid #eee; padding: 8px; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($enquiry->items as $item)
                <tr>
                    <td style="border: 1px solid #eee; padding: 8px;">{{ $item->product_name ?? 'Product' }}</td>
                    <td style="border: 1px solid #eee; padding: 8px;">{{ $item->variant_details ?: 'N/A' }}</td>
                    <td style="border: 1px solid #eee; padding: 8px; text-align: center;">{{ $item->quantity }}</td>
                    <td style="border: 1px solid #eee; padding: 8px; text-align: right;">£{{ number_format($item->price, 2) }}</td>
                    <td style="border: 1px solid #eee; padding: 8px; text-align: right;">£{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table cellpadding="5" cellspacing="0" style="width: 100%; font-size: 14px; margin-top: 20px; text-align: right;">
        <tr>
            <td style="font-weight: bold; width: 85%;">Subtotal:</td>
            <td style="width: 15%; font-weight: bold;">£{{ number_format($enquiry->subtotal, 2) }}</td>
        </tr>
        @if($enquiry->discount > 0)
            <tr>
                <td style="font-weight: bold; color: red;">Discount:</td>
                <td style="font-weight: bold; color: red;">-£{{ number_format($enquiry->discount, 2) }}</td>
            </tr>
        @endif
        <tr style="font-size: 16px;">
            <td style="font-weight: bold; padding-top: 10px; border-top: 2px solid #333;">Total Value:</td>
            <td style="font-weight: bold; padding-top: 10px; border-top: 2px solid #333;">£{{ number_format($enquiry->grand_total, 2) }}</td>
        </tr>
    </table>
@endsection
