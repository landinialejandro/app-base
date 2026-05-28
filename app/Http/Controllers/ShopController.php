<?php

// FILE: app/Http/Controllers/ShopController.php | V4

namespace App\Http\Controllers;

use App\Events\OperationalRecordCreated;
use App\Events\OperationalRecordUpdated;
use App\Http\Requests\StoreShopRequest;
use App\Http\Requests\UpdateShopRequest;
use App\Models\Order;
use App\Models\SelfServiceCart;
use App\Models\SelfServiceStoreCustomer;
use App\Models\Shop;
use App\Models\ShopItem;
use App\Support\Auth\Security;
use App\Support\Attachments\AttachmentSurfaceService;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Shops\ShopItemCommercialPolicyResolver;
use App\Support\Shops\ShopPublishedCatalogReader;
use App\Support\Shops\ShopPublisher;
use App\Support\Shops\ShopTokenConsumptionSummaryService;
use App\Support\Navigation\NavigationTrail;
use App\Support\Navigation\ShopNavigationTrail;
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

    public function show(Request $request, Shop $shop): View
    {
        $this->authorize('view', $shop);

        $shop->load([
            'items.product',
            'consumptionPoints',
        ]);

        $shop->loadCount('items');

        $items = $shop->items;
        $commercialConfig = $this->commercialConfigForShop($shop);
        $publicVisibleItems = $items
            ->filter(function ($item) use ($shop): bool {
                $product = $item->product;

                return $shop->isActive()
                    && $item->status === ShopItem::STATUS_PUBLISHED
                    && $item->is_visible === true
                    && $product !== null
                    && (string) $product->tenant_id === (string) $shop->tenant_id
                    && $product->is_active === true;
            })
            ->values();

        $commercialPolicyResolver = app(ShopItemCommercialPolicyResolver::class);
        $cartEnabledItemsCount = $publicVisibleItems
            ->filter(fn ($item): bool => $commercialPolicyResolver->resolve($item)['allow_cart'] === true)
            ->count();

        $operationalCustomersCount = SelfServiceStoreCustomer::query()
            ->where('tenant_id', $shop->tenant_id)
            ->where('status', SelfServiceStoreCustomer::STATUS_ACTIVE)
            ->where('operation_enabled', true)
            ->count();

        $openingStatus = [
            'shop_active' => $shop->isActive(),
            'configured_items_count' => $items->count(),
            'public_visible_items_count' => $publicVisibleItems->count(),
            'cart_enabled_items_count' => $cartEnabledItemsCount,
            'checkout_enabled' => $commercialConfig['checkout_enabled'],
            'provider_target' => $commercialConfig['provider_target'],
            'provider_target_label' => $commercialConfig['provider_target_label'],
            'operational_customers_count' => $operationalCustomersCount,
        ];

        $selfServiceOrders = Order::query()
            ->with(['items', 'party'])
            ->where('tenant_id', $shop->tenant_id)
            ->where('group', OrderCatalog::GROUP_SALE)
            ->where('record_metadata->origin', 'self_service_sales')
            ->where('record_metadata->self_service_sales->shop_id', $shop->id)
            ->latest('ordered_at')
            ->latest('id')
            ->limit(20)
            ->get();

        $selfServiceCarts = SelfServiceCart::query()
            ->with(['items.shopItem', 'storeCustomer.party', 'account'])
            ->withCount('items')
            ->where('tenant_id', $shop->tenant_id)
            ->whereHas('items.shopItem', function ($query) use ($shop) {
                $query
                    ->where('tenant_id', $shop->tenant_id)
                    ->where('self_service_shop_id', $shop->id);
            })
            ->latest('updated_at')
            ->latest('id')
            ->limit(20)
            ->get();

        $tokenConsumptionSummary = app(ShopTokenConsumptionSummaryService::class)->forShop($shop);

        $navigationTrail = ShopNavigationTrail::show(
            $request,
            $shop,
            $request->query('return_tab')
        );

        $trailQuery = NavigationTrail::toQuery($navigationTrail);

        return view('shops.show', [
            'shop' => $shop,
            'canUpdateShop' => $request->user()?->can('update', $shop) === true,
            'canDeleteShop' => $request->user()?->can('delete', $shop) === true,
            'selfServiceCarts' => $selfServiceCarts,
            'selfServiceOrders' => $selfServiceOrders,
            'tokenConsumptionSummary' => $tokenConsumptionSummary,
            'commercialConfig' => $commercialConfig,
            'openingStatus' => $openingStatus,
            'navigationTrail' => $navigationTrail,
            'trailQuery' => $trailQuery,
        ]);
    }

    public function preview(Request $request, Shop $shop, ShopPublishedCatalogReader $reader, AttachmentSurfaceService $attachmentSurface): View
    {
        $this->authorize('view', $shop);

        $shop->loadMissing('tenant');

        $previewItems = $shop->items()
            ->with([
                'product.attachments' => function ($query) {
                    $query
                        ->where('kind', 'shop')
                        ->where('is_image', true)
                        ->ordered();
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $productThumbs = collect();

        foreach ($previewItems as $item) {
            $product = $item->product;

            if (! $product) {
                continue;
            }

            $media = $attachmentSurface->mediaCollectionFor($product, [
                'kind' => 'shop',
                'is_image' => true,
                'index' => 0,
            ]);

            $productThumbs->put($product->id, $media['image'] ?? $media['firstImage'] ?? null);
        }

        $navigationTrail = ShopNavigationTrail::preview($request, $shop);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);

        return view('shops.preview', [
            'shop' => $shop,
            'previewItems' => $previewItems,
            'publicVisibleItemsCount' => $reader->visibleItemsForShop($shop)->count(),
            'productThumbs' => $productThumbs,
            'navigationTrail' => $navigationTrail,
            'trailQuery' => $trailQuery,
        ]);
    }

    public function edit(Request $request, Shop $shop): View
    {
        $this->authorize('update', $shop);

        $navigationTrail = ShopNavigationTrail::edit($request, $shop);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);

        return view('shops.edit', [
            'shop' => $shop,
            'navigationTrail' => $navigationTrail,
            'trailQuery' => $trailQuery,
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

    private function commercialConfigForShop(Shop $shop): array
    {
        $commercial = data_get($shop->meta, 'commercial', []);
        $commercial = is_array($commercial) ? $commercial : [];
        $providerLabels = [
            'simulated' => 'Entorno simulado',
            'mercado_pago' => 'Mercado Pago',
            'modo' => 'MODO',
        ];

        $providerTarget = $commercial['provider_target'] ?? 'simulated';
        $providerTarget = array_key_exists($providerTarget, $providerLabels)
            ? $providerTarget
            : 'simulated';

        return [
            'checkout_enabled' => $this->boolValue($commercial['checkout_enabled'] ?? false),
            'direct_purchase_enabled' => $this->boolValue($commercial['direct_purchase_enabled'] ?? false),
            'stock_control_enabled' => $this->boolValue($commercial['stock_control_enabled'] ?? false),
            'allow_stock_margin' => $this->boolValue($commercial['allow_stock_margin'] ?? false),
            'provider_target' => $providerTarget,
            'provider_target_label' => $providerLabels[$providerTarget],
        ];
    }

    private function boolValue(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
