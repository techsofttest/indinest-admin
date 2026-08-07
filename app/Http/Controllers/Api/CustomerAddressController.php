<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CustomerAddressController extends Controller
{
    /**
     * Helper to get the authenticated customer using guard / session fallback
     */
    private function getAuthenticatedCustomer(Request $request): ?Customer
    {
        $user = Auth::guard('customer')->user();

        return $user instanceof Customer ? $user : null;
    }

    private function syncCustomerDefaultFlags(Customer $customer): void
    {
        CustomerAddress::where('customer_id', $customer->id)
            ->update([
                'is_default_shipping' => false,
                'is_default_billing' => false,
            ]);

        if ($customer->default_shipping_address_id) {
            CustomerAddress::where('customer_id', $customer->id)
                ->whereKey($customer->default_shipping_address_id)
                ->update(['is_default_shipping' => true]);
        }

        if ($customer->default_billing_address_id) {
            CustomerAddress::where('customer_id', $customer->id)
                ->whereKey($customer->default_billing_address_id)
                ->update(['is_default_billing' => true]);
        }
    }

    private function setAsDefaultAddress(Customer $customer, CustomerAddress $address): void
    {
        DB::beginTransaction();
        try {
            CustomerAddress::where('customer_id', $customer->id)
                ->update([
                    'is_default_shipping' => false,
                    'is_default_billing' => false,
                ]);

            $address->update([
                'is_default_shipping' => true,
                'is_default_billing' => true,
            ]);

            $customer->update([
                'default_shipping_address_id' => $address->id,
                'default_billing_address_id' => $address->id,
            ]);

            $this->syncCustomerDefaultFlags($customer->fresh());
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Customer Login
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'regex:/^[^\s@\/]+@[^\s@\/]+\.[^\s@\/]+$/'],
            'password' => 'required',
        ]);

        $customer = Customer::where('email', $credentials['email'])->first();

        if (!$customer || !Hash::check($credentials['password'], $customer->password)) {
            return response()->json(['message' => 'Invalid email or password'], 422);
        }

        Auth::guard('customer')->login($customer);
        session()->put('customer_id', $customer->id);

        return response()->json([
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'isLoggedIn' => true
        ]);
    }

    /**
     * Customer Register
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'unique:customers,email', 'regex:/^[^\s@\/]+@[^\s@\/]+\.[^\s@\/]+$/'],
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
        ]);

        $customer = Customer::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? '',
            'password' => Hash::make($data['password']),
            'status' => 1,
        ]);

        Auth::guard('customer')->login($customer);
        session()->put('customer_id', $customer->id);

        return response()->json([
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'isLoggedIn' => true
        ]);
    }

    /**
     * Get Address List
     */
    public function index(Request $request): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer($request);
        if (!$customer) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $addresses = CustomerAddress::where('customer_id', $customer->id)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($addresses);
    }

    /**
     * Store Address
     */
    public function store(Request $request): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer($request);
        if (!$customer) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Limit to 20 addresses
        $existingCount = CustomerAddress::where('customer_id', $customer->id)->count();
        if ($existingCount >= 20) {
            return response()->json(['message' => 'Maximum limit of 20 saved addresses reached.'], 422);
        }

        $data = $request->validate([
            'label' => 'nullable|string|max:50',
            'contact_name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'suburb' => 'nullable|string|max:100',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postcode' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'google_place_id' => 'nullable|string|max:255',
            'delivery_notes' => 'nullable|string',
        ]);

        $hasDefault = (bool) $customer->default_shipping_address_id || (bool) $customer->default_billing_address_id || CustomerAddress::where('customer_id', $customer->id)
            ->where(function ($query) {
                $query->where('is_default_shipping', true)
                    ->orWhere('is_default_billing', true);
            })
            ->exists();
        $shouldBeDefault = ($existingCount === 0) || !$hasDefault;

        try {
            $address = new CustomerAddress($data);
            $address->customer_id = $customer->id;

            if ($shouldBeDefault) {
                $address->is_default_shipping = true;
                $address->is_default_billing = true;
            } else {
                $address->is_default_shipping = false;
                $address->is_default_billing = false;
            }

            $address->save();

            if ($shouldBeDefault) {
                $customer->refresh();
                $this->setAsDefaultAddress($customer, $address);
            }

            return response()->json($address->fresh(), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to save address: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update Address
     */
    public function update(Request $request, $id): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer($request);
        if (!$customer) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $address = CustomerAddress::where('customer_id', $customer->id)->findOrFail($id);

        $data = $request->validate([
            'label' => 'nullable|string|max:50',
            'contact_name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'suburb' => 'nullable|string|max:100',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postcode' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'google_place_id' => 'nullable|string|max:255',
            'delivery_notes' => 'nullable|string',
        ]);

        $address->update($data);

        return response()->json($address);
    }

    /**
     * Delete Address
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer($request);
        if (!$customer) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $address = CustomerAddress::where('customer_id', $customer->id)->findOrFail($id);

        // Cannot delete if used as default shipping or default billing
        if ($address->id === $customer->default_shipping_address_id || $address->id === $customer->default_billing_address_id) {
            return response()->json([
                'message' => 'Cannot delete default address. Please select another default shipping and billing address first.'
            ], 422);
        }

        $address->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Set Default Shipping
     */
    public function setDefaultShipping(Request $request, $id): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer($request);
        if (!$customer) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $address = CustomerAddress::where('customer_id', $customer->id)->findOrFail($id);

        try {
            $this->setAsDefaultAddress($customer, $address);
            return response()->json(['success' => true, 'address' => $address->fresh()]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Set Default Billing
     */
    public function setDefaultBilling(Request $request, $id): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer($request);
        if (!$customer) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $address = CustomerAddress::where('customer_id', $customer->id)->findOrFail($id);

        try {
            $this->setAsDefaultAddress($customer, $address);
            return response()->json(['success' => true, 'address' => $address->fresh()]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
