@extends('emails.layouts.master')

@section('title', 'Password Changed - ' . config('app.name'))

@section('content')
    <h2>Hello {{ $customer->name }},</h2>
    <p>Your password was changed successfully{{ $changedAt ? ' on ' . \Illuminate\Support\Carbon::parse($changedAt)->format('d M, Y H:i') : '' }}.</p>
    <p>If you did not make this change, please contact support immediately.</p>
@endsection
