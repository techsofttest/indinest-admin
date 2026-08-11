@extends('emails.layouts.master')

@section('title', 'Welcome - ' . config('app.name'))

@section('content')
    <h2>Welcome, {{ $customer->name }}.</h2>
    <p>Your email address has been verified and your account is now active.</p>
    <p style="text-align: center;">
        <a href="{{ env('FRONTEND_URL', 'http://localhost:3000') }}/login" class="button">Login</a>
    </p>
    <p>If you need help, reply to this email or contact support.</p>
@endsection
