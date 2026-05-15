<?php

// FILE: app/Http/Controllers/ShopController.php | V3

namespace App\Http\Controllers;

use App\Events\OperationalRecordCreated;
use App\Events\OperationalRecordUpdated;
use App\Http\Requests\StoreShopRequest;
use App\Http\Requests\UpdateShopRequest;
use App\Models\Shop;
use App\Support\Auth\Security;
use App\Support\Shops\ShopPublishedCatalogReader;
use App\Support\Shops\ShopPublisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Shop::class);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => trim((string) $request->query('status', '')),
        ];

        $shopsQuery = app(Security::class)
            ->scope($request->user(), 'shops.viewAny', Shop::query())
            ->withCount('items');

        if ($filters['search'] !== '') {
            $shopsQuery->where(function ($query) use ($filters) {
                $query
                    ->where('name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('description', 'like', '%'.$filters['search'].'%');
            });
        }

        if ($filters['status'] !== '') {
            $shopsQuery->where('status', $filters['status']);
        }

        $shops = $shopsQuery
            ->orderByRaw("case when status = ? then 0 else 1 end", [Shop::STATUS_ACTIVE])
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('shops.index', [
            'shops' => $shops,
            'filters' => $filters,
            'statusOptions' => [
                Shop::STATUS_ACTIVE => 'Activa',
                Shop::STATUS_DRAFT => 'Borrador',
                Shop::STATUS_INACTIVE => 'Inactiva',
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Shop::class);

        return view('shops.create', [
            'shop' => new Shop([
                'status' => Shop::STATUS_DRAFT,
            ]),
        ]);
    }

    public function store(StoreShopRequest $request): RedirectResponse
    {
        $tenant = app('tenant');
        $data = $request->validatedData();

        $shop = Shop::create(array_merge($data, [
            'tenant_id' => $tenant->id,
        ]));

        if ($shop->isActive()) {
            $shop = app(ShopPublisher::class)->activate($shop);
        }

        event(new OperationalRecordCreated(
            record: $shop,
            actorUserId: $request->user()?->id,
        ));

        return redirect()
            ->route('shops.show', $shop)
            ->with('success', 'Tienda creada correctamente.');
    }

    public function show(Shop $shop): View
    {
        $this->authorize('view', $shop);

        $shop->load([
            'items.product',
        ]);

        $shop->loadCount('items');

        return view('shops.show', [
            'shop' => $shop,
            'canUpdateShop' => request()->user()?->can('update', $shop) === true,
            'canDeleteShop' => request()->user()?->can('delete', $shop) === true,
        ]);
    }

public function preview(Shop $shop, ShopPublishedCatalogReader $reader): View
{
    $this->authorize('view', $shop);

    $shop->loadMissing('tenant');

    $previewItems = $shop->items()
        ->with('product')
        ->orderBy('sort_order')
        ->orderBy('id')
        ->get();

    return view('shops.preview', [
        'shop' => $shop,
        'previewItems' => $previewItems,
        'publicVisibleItemsCount' => $reader->visibleItemsForShop($shop)->count(),
    ]);
}

    public function edit(Shop $shop): View
    {
        $this->authorize('update', $shop);

        return view('shops.edit', [
            'shop' => $shop,
        ]);
    }

    public function update(UpdateShopRequest $request, Shop $shop): RedirectResponse
    {
        $beforeAttributes = $shop->getAttributes();

        $shop->update($request->validatedData());

        if ($shop->isActive()) {
            $shop = app(ShopPublisher::class)->activate($shop);
        }

        event(new OperationalRecordUpdated(
            record: $shop,
            beforeAttributes: $beforeAttributes,
            actorUserId: $request->user()?->id,
        ));

        return redirect()
            ->route('shops.show', $shop)
            ->with('success', 'Tienda actualizada correctamente.');
    }

    public function activate(Request $request, Shop $shop): RedirectResponse
    {
        $this->authorize('activate', $shop);

        $beforeAttributes = $shop->getAttributes();

        $shop = app(ShopPublisher::class)->activate($shop);

        event(new OperationalRecordUpdated(
            record: $shop,
            beforeAttributes: $beforeAttributes,
            actorUserId: $request->user()?->id,
        ));

        return redirect()
            ->route('shops.show', $shop)
            ->with('success', 'Tienda activada correctamente.');
    }

    public function destroy(Request $request, Shop $shop): RedirectResponse
    {
        $this->authorize('delete', $shop);

        $shop->delete();

        return redirect()
            ->route('shops.index')
            ->with('success', 'Tienda eliminada correctamente.');
    }
}