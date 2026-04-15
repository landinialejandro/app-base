<?php

// FILE: app/Http/Controllers/InventoryController.php | V3

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Catalogs\ProductCatalog;
use App\Support\Inventory\InventoryMovementService;
use App\Support\Navigation\NavigationTrail;
use App\Support\Navigation\ProductNavigationTrail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        abort(404);
    }

    public function show(Request $request, $inventoryMovement): View
    {
        abort(404);
    }

    public function ingresar(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        abort_if(
            $product->kind !== ProductCatalog::KIND_PRODUCT,
            422,
            'No se puede ingresar stock a un servicio.'
        );

        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $result = app(InventoryMovementService::class)->ingresar(
            product: $product,
            quantity: $data['quantity'],
            notes: $data['notes'] ?? null,
            createdBy: auth()->id(),
        );

        $navigationTrail = ProductNavigationTrail::show($request, $product);

        $redirect = redirect()
            ->route('products.show', ['product' => $product] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Ingreso de stock registrado correctamente.');

        if (($result['negative_stock'] ?? false) === true) {
            $redirect->with(
                'warning',
                'El producto quedó con stock negativo. Se generó una tarea automática para revisión del owner.'
            );
        }

        return $redirect;
    }
}
