<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    private function getCart(): array
    {
        return session('cart', []);
    }

    private function saveCart(array $cart): void
    {
        session(['cart' => $cart]);
    }

    private function normalizeItem(array $item): array
    {
        $quantity = max(1, (int) ($item['quantity'] ?? 1));
        $price = (float) ($item['price'] ?? 0);

        return [
            'id' => $item['id'] ?? $this->generateItemId($item),
            'product_id' => isset($item['product_id']) ? (int) $item['product_id'] : null,
            'variant_id' => isset($item['variant_id']) ? (int) $item['variant_id'] : null,
            'name' => trim((string) ($item['name'] ?? 'Product')),
            'brand' => trim((string) ($item['brand'] ?? '')),
            'image' => trim((string) ($item['image'] ?? '')),
            'price' => $price,
            'quantity' => $quantity,
            'size' => isset($item['size']) ? trim((string) $item['size']) : null,
            'colour' => isset($item['colour']) ? trim((string) $item['colour']) : null,
            'variant_name' => isset($item['variant_name']) ? trim((string) $item['variant_name']) : null,
        ];
    }

    private function generateItemId(array $item): string
    {
        $parts = [
            'product',
            isset($item['product_id']) ? (string) $item['product_id'] : 'generic',
            isset($item['variant_id']) ? (string) $item['variant_id'] : 'base',
            preg_replace('/[^a-z0-9]+/', '-', strtolower(trim((string) ($item['size'] ?? '')))) ?: 'default',
            preg_replace('/[^a-z0-9]+/', '-', strtolower(trim((string) ($item['colour'] ?? '')))) ?: 'default',
        ];

        return implode('-', array_filter($parts, fn ($value) => $value !== ''));
    }

    private function calculateSummary(array $cart): array
    {
        $itemCount = 0;
        $subtotal = 0.0;

        foreach ($cart as $item) {
            $itemCount += (int) ($item['quantity'] ?? 0);
            $subtotal += ((float) ($item['price'] ?? 0)) * ((int) ($item['quantity'] ?? 0));
        }

        return [
            'count' => $itemCount,
            'subtotal' => $subtotal,
        ];
    }

    private function findExistingItemIndex(array $cart, array $item): ?int
    {
        foreach ($cart as $index => $cartItem) {
            if (isset($cartItem['product_id'], $item['product_id'])
                && $cartItem['product_id'] === (int) $item['product_id']
                && ($cartItem['variant_id'] ?? null) === (isset($item['variant_id']) ? (int) $item['variant_id'] : null)
                && ($cartItem['size'] ?? null) === ($item['size'] ?? null)
                && ($cartItem['colour'] ?? null) === ($item['colour'] ?? null)
            ) {
                return $index;
            }
        }

        return null;
    }

    public function index(Request $request): JsonResponse
    {
        $cart = $this->getCart();
        $summary = $this->calculateSummary($cart);

        return response()->json([
            'cart' => $cart,
            'count' => $summary['count'],
            'subtotal' => $summary['subtotal'],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'nullable|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'quantity' => 'nullable|integer|min:1',
            'price' => 'required|numeric|min:0',
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'image' => 'nullable|string|max:1000',
            'size' => 'nullable|string|max:100',
            'colour' => 'nullable|string|max:100',
            'variant_name' => 'nullable|string|max:100',
            'id' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $item = $validator->validated();
        $item['quantity'] = $item['quantity'] ?? 1;
        $item['id'] = $item['id'] ?? $this->generateItemId($item);

        $cart = $this->getCart();
        $existingIndex = $this->findExistingItemIndex($cart, $item);

        $maxStock = 99;
        if (!empty($item['variant_id'])) {
            $variant = \App\Models\ProductVariant::find($item['variant_id']);
            if ($variant) {
                $maxStock = $variant->stock ?? 0;
            }
        }

        if ($existingIndex !== null) {
            $newQty = $cart[$existingIndex]['quantity'] + (int) $item['quantity'];
            $cart[$existingIndex]['quantity'] = min($newQty, $maxStock);
            $cart[$existingIndex]['price'] = (float) $item['price'];
        } else {
            $normalized = $this->normalizeItem($item);
            $normalized['quantity'] = min($normalized['quantity'], $maxStock);
            $cart[] = $normalized;
        }

        $this->saveCart($cart);
        $summary = $this->calculateSummary($cart);

        return response()->json([
            'valid' => true,
            'cart' => $cart,
            'count' => $summary['count'],
            'subtotal' => $summary['subtotal'],
        ]);
    }

    public function replace(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cart' => 'required|array',
            'cart.*.price' => 'required|numeric|min:0',
            'cart.*.quantity' => 'required|integer|min:1',
            'cart.*.name' => 'required|string|max:255',
            'cart.*.product_id' => 'nullable|integer|exists:products,id',
            'cart.*.variant_id' => 'nullable|integer|exists:product_variants,id',
            'cart.*.id' => 'nullable|string|max:255',
            'cart.*.brand' => 'nullable|string|max:255',
            'cart.*.image' => 'nullable|string|max:1000',
            'cart.*.size' => 'nullable|string|max:100',
            'cart.*.colour' => 'nullable|string|max:100',
            'cart.*.variant_name' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cart = array_map(fn ($item) => $this->normalizeItem($item), $request->input('cart', []));
        $this->saveCart($cart);
        $summary = $this->calculateSummary($cart);

        return response()->json([
            'valid' => true,
            'cart' => $cart,
            'count' => $summary['count'],
            'subtotal' => $summary['subtotal'],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'nullable|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'size' => 'nullable|string|max:100',
            'colour' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cart = $this->getCart();
        $found = false;

        foreach ($cart as $index => $item) {
            if ((string) $item['id'] === $id) {
                $updates = $validator->validated();
                if (isset($updates['quantity'])) {
                    $maxStock = 99;
                    if (!empty($item['variant_id'])) {
                        $variant = \App\Models\ProductVariant::find($item['variant_id']);
                        if ($variant) {
                            $maxStock = $variant->stock ?? 0;
                        }
                    }
                    $cart[$index]['quantity'] = min((int) $updates['quantity'], $maxStock);
                }
                if (isset($updates['price'])) {
                    $cart[$index]['price'] = (float) $updates['price'];
                }
                if (array_key_exists('size', $updates)) {
                    $cart[$index]['size'] = $updates['size'];
                }
                if (array_key_exists('colour', $updates)) {
                    $cart[$index]['colour'] = $updates['colour'];
                }
                $found = true;
                break;
            }
        }

        if (! $found) {
            return response()->json(['error' => 'Cart item not found.'], 404);
        }

        $this->saveCart($cart);
        $summary = $this->calculateSummary($cart);

        return response()->json([
            'valid' => true,
            'cart' => $cart,
            'count' => $summary['count'],
            'subtotal' => $summary['subtotal'],
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $cart = $this->getCart();
        $cart = array_values(array_filter($cart, fn ($item) => (string) $item['id'] !== $id));
        $this->saveCart($cart);
        $summary = $this->calculateSummary($cart);

        return response()->json([
            'valid' => true,
            'cart' => $cart,
            'count' => $summary['count'],
            'subtotal' => $summary['subtotal'],
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        session()->forget('cart');

        return response()->json([
            'valid' => true,
            'cart' => [],
            'count' => 0,
            'subtotal' => 0,
        ]);
    }
}
