<?php

namespace App\Http\Controllers\Api;

use App\Events\PasswordResetCompleted;
use App\Events\PasswordResetRequested;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;

class CustomerPasswordResetController extends Controller
{
    public function sendLink(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        $customer = Customer::where('email', $data['email'])->first();
        if ($customer) {
            $token = Password::broker('customers')->createToken($customer);
            $resetUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/') . '/reset-password?token=' . urlencode($token) . '&email=' . urlencode($customer->email);
            event(new PasswordResetRequested($customer, $resetUrl));
        }

        return response()->json(['message' => 'If the email exists, a reset link has been sent.']);
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::broker('customers')->reset(
            $data,
            function (Customer $customer, string $password) {
                $customer->forceFill([
                    'password' => Hash::make($password),
                ])->save();
                event(new PasswordResetCompleted($customer, now()->toDateTimeString()));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password reset successfully.'])
            : response()->json(['message' => __($status)], 422);
    }
}
