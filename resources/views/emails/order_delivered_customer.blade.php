@extends('emails.layouts.master')

@section('title', 'Your Order Has Been Delivered – ' . $order->order_number)

@section('content')
    <h2 style="color: #010526; margin-bottom: 5px;">Your Order Has Been Delivered!</h2>
    <p>Hi {{ $order->customer_name ?? $order->first_name ?? 'Customer' }},</p>
    <p>Your order <strong>{{ $order->order_number }}</strong> has been successfully delivered.</p>

    @include('emails.partials.order_details')

    <p style="margin-top: 25px; color: #555;">We hope you love your purchase! If you have any questions or feedback, please feel free to reach out to us referencing order number <strong>{{ $order->order_number }}</strong>.</p>
    <p>Thank you for shopping with us!<br><strong>The IndiNest Team</strong></p>
@endsection
