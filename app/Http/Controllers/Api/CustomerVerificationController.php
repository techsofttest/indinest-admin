<?php

namespace App\Http\Controllers\Api;

use App\Events\CustomerVerified;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class CustomerVerificationController extends Controller
{
    public function verify(Request $request, int $id, string $hash)
    {
        $customer = Customer::findOrFail($id);

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');

        if (! hash_equals($hash, sha1($customer->getEmailForVerification()))) {
            return redirect($frontendUrl . '/login?verified=error');
        }

        if ($customer->hasVerifiedEmail()) {
            return redirect($frontendUrl . '/login?verified=already');
        }

        $customer->forceFill(['email_verified_at' => now()])->save();

        event(new Verified($customer));
        event(new CustomerVerified($customer));

        return redirect($frontendUrl . '/login?verified=success');
    }
}
