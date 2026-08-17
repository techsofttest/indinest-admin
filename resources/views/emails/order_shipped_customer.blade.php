@extends('emails.layouts.master')

@section('title', 'Your Order is On Its Way – ' . $order->order_number)

@section('content')
    <h2 style="color: #010526; margin-bottom: 5px;">Your Order is On Its Way!</h2>
    <p>Hi {{ $order->customer_name ?? $order->first_name ?? 'Customer' }},</p>
    <p>Great news! Your order <strong>{{ $order->order_number }}</strong> has been shipped and is now on its way to you.</p>

    @include('emails.partials.order_details')

    <p style="margin-top: 25px; color: #555;">If you have any questions about your order, please contact us and reference your order number <strong>{{ $order->order_number }}</strong>.</p>
    <p>Thank you for shopping with us!<br><strong>The IndiNest Team</strong></p>
@endsection
