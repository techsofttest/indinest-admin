@extends('emails.layouts.master')

@section('title', 'Verify Your Email - ' . config('app.name'))

@section('content')
    <h2>Hello {{ $customer->name }},</h2>

    <p>Thanks for registering. Please verify your email address to activate your account.</p>

    <p style="text-align: center;">
        <a href="{{ $verificationUrl }}" class="button">Verify Email Address</a>
    </p>

    <p>If the button does not work, use this link:</p>
    <p><a href="{{ $verificationUrl }}" style="color: #2b9346; word-break: break-all;">{{ $verificationUrl }}</a></p>
@endsection
