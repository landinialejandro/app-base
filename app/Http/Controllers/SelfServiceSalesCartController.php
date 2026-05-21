<?php

// FILE: app/Http/Controllers/SelfServiceSalesCartController.php | V2

namespace App\Http\Controllers;

use App\Models\SelfServiceCartItem;
use App\Models\Tenant;
use App\Support\SelfServiceSales\SelfServiceCartPresenter;
use App\Support\SelfServiceSales\SelfServiceCartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class SelfServiceSalesCartController extends Controller
{
    public function __construct(
        protected SelfServiceCartService $carts,
        protected SelfServiceCartPresenter $presenter
    ) {
    }

    public function show(Request $request, Tenant $tenant): JsonResponse
    {
        return $this->respond(function () use ($request, $tenant) {
            $cart = $this->carts->currentCart($request, $tenant);

            return response()->json($this->presenter->present($cart, $tenant, 'Carrito cargado.'));
        }, $request, $tenant);
    }

    public function storeItem(Request $request, Tenant $tenant): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shop_item_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json($this->presenter->empty('El producto ya no está disponible en la tienda.'), 422);
        }

        return $this->respond(function () use ($request, $tenant, $validator) {
            $data = $validator->validated();
            $cart = $this->carts->addItem($request, $tenant, (int) $data['shop_item_id'], (int) $data['quantity']);

            return response()->json($this->presenter->present($cart, $tenant));
        }, $request, $tenant);
    }

    public function updateItem(Request $request, Tenant $tenant, SelfServiceCartItem $cartItem): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json($this->presenter->empty('El producto ya no está disponible en la tienda.'), 422);
        }

        return $this->respond(function () use ($request, $tenant, $cartItem, $validator) {
            $data = $validator->validated();
            $cart = $this->carts->updateItem($request, $tenant, $cartItem, (int) $data['quantity']);

            return response()->json($this->presenter->present($cart, $tenant));
        }, $request, $tenant);
    }

    public function destroyItem(Request $request, Tenant $tenant, SelfServiceCartItem $cartItem): JsonResponse
    {
        return $this->respond(function () use ($request, $tenant, $cartItem) {
            $cart = $this->carts->destroyItem($request, $tenant, $cartItem);

            return response()->json($this->presenter->present($cart, $tenant));
        }, $request, $tenant);
    }

    public function clear(Request $request, Tenant $tenant): JsonResponse
    {
        return $this->respond(function () use ($request, $tenant) {
            $cart = $this->carts->clear($request, $tenant);

            return response()->json($this->presenter->present($cart, $tenant, 'Carrito vaciado.'));
        }, $request, $tenant);
    }

    public function checkout(Request $request, Tenant $tenant): JsonResponse
    {
        return $this->respond(function () use ($request, $tenant) {
            $cart = $this->carts->simulateCheckout($request, $tenant);

            return response()->json($this->presenter->error(
                'Función no implementada todavía: pago final.',
                $cart,
                $tenant
            ));
        }, $request, $tenant);
    }

    private function respond(callable $callback, ?Request $request = null, ?Tenant $tenant = null): JsonResponse
    {
        try {
            return $callback();
        } catch (HttpExceptionInterface $exception) {
            if ($request && $tenant) {
                try {
                    $cart = $this->carts->currentCart($request, $tenant);

                    return response()->json(
                        $this->presenter->error($exception->getMessage(), $cart, $tenant),
                        $exception->getStatusCode()
                    );
                } catch (HttpExceptionInterface) {
                    // Si ni siquiera puede resolverse el carrito por falta de sesión/autorización,
                    // se devuelve el error vacío para no exponer estado.
                }
            }

            return response()->json(
                $this->presenter->empty($exception->getMessage()),
                $exception->getStatusCode()
            );
        }
    }
}
