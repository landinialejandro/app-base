<?php

// FILE: app/Support/SelfServiceSales/SelfServiceTokenConsumptionAttemptService.php | V5

namespace App\Support\SelfServiceSales;

use App\Models\SelfServiceTokenConsumptionAttempt;
use App\Models\SelfServiceTokenPocket;
use App\Models\SelfServiceTokenPocketMovement;
use App\Models\Shop;
use App\Models\ShopConsumptionPoint;
use App\Models\ShopItem;
use App\Support\SelfServiceSales\TokenConsumption\SelfServiceTokenConsumptionGateway;
use App\Support\SelfServiceSales\TokenConsumption\SelfServiceTokenConsumptionRequestFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SelfServiceTokenConsumptionAttemptService
{
    public const MESSAGE_INVALID_QUANTITY = 'La cantidad solicitada no es válida.';
    public const MESSAGE_NOT_AVAILABLE = 'No hay fichas disponibles para la cantidad solicitada.';
    public const MESSAGE_ATTEMPT_NOT_CONFIRMABLE = 'El intento de consumo no puede confirmarse.';
    public const MESSAGE_CONSUMPTION_POINT_NOT_AVAILABLE = 'El punto de consumo no está disponible.';

    public function __construct(
        protected SelfServiceTokenConsumptionRequestFactory $consumptionRequests,
        protected SelfServiceTokenConsumptionGateway $consumptionGateway
    ) {
    }

    public function createPendingAttempt(
        string $tenantId,
        int $accountId,
        int $storeCustomerId,
        int $pocketId,
        int|float $quantity,
        ?int $consumptionPointId = null,
    ): SelfServiceTokenConsumptionAttempt {
        return DB::transaction(function () use ($tenantId, $accountId, $storeCustomerId, $pocketId, $quantity, $consumptionPointId): SelfServiceTokenConsumptionAttempt {
            $quantity = (float) $quantity;

            if ($quantity <= 0) {
                throw new HttpException(422, self::MESSAGE_INVALID_QUANTITY);
            }

            $pocket = SelfServiceTokenPocket::query()
                ->whereKey($pocketId)
                ->where('tenant_id', $tenantId)
                ->where('self_service_customer_account_id', $accountId)
                ->where('self_service_store_customer_id', $storeCustomerId)
                ->where('status', SelfServiceTokenPocket::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if (! $pocket) {
                throw new HttpException(422, self::MESSAGE_NOT_AVAILABLE);
            }

            if ($quantity > (float) $pocket->quantity_available) {
                throw new HttpException(422, self::MESSAGE_NOT_AVAILABLE);
            }

            $consumptionPoint = $consumptionPointId !== null
                ? $this->availableConsumptionPointForPocket($tenantId, $consumptionPointId, $pocket)
                : null;

            $unitSeconds = $pocket->unit_seconds_snapshot !== null
                ? (int) $pocket->unit_seconds_snapshot
                : null;
            $totalSeconds = $unitSeconds !== null
                ? (int) round($quantity * $unitSeconds)
                : null;
            $requestPayload = [
                'quantity' => $quantity,
            ];
            $meta = [
                'stage' => 'pending_attempt',
                'consumes_balance' => false,
                'calls_external_controller' => false,
            ];

            if ($consumptionPoint instanceof ShopConsumptionPoint) {
                $requestPayload['consumption_point_id'] = $consumptionPoint->id;
                $requestPayload['consumption_point_label'] = $consumptionPoint->displayName();
                $meta['consumption_point_id'] = $consumptionPoint->id;
                $meta['consumption_point_label'] = $consumptionPoint->displayName();
            }

            return SelfServiceTokenConsumptionAttempt::query()->create([
                'tenant_id' => $tenantId,
                'self_service_customer_account_id' => $accountId,
                'self_service_store_customer_id' => $storeCustomerId,
                'self_service_token_pocket_id' => $pocket->id,
                'product_id' => $pocket->product_id,
                'quantity' => $quantity,
                'unit_label_snapshot' => $pocket->unit_label_snapshot,
                'unit_seconds_snapshot' => $unitSeconds,
                'total_seconds' => $totalSeconds,
                'status' => SelfServiceTokenConsumptionAttempt::STATUS_PENDING,
                'source_type' => $consumptionPoint instanceof ShopConsumptionPoint
                    ? ShopConsumptionPoint::class
                    : 'self_service_sales.token_consumption',
                'source_id' => $consumptionPoint instanceof ShopConsumptionPoint
                    ? $consumptionPoint->id
                    : $pocket->id,
                'idempotency_key' => (string) Str::uuid(),
                'request_payload' => $requestPayload,
                'response_payload' => null,
                'meta' => $meta,
            ]);
        });
    }

    public function confirmSimulated(
        string $tenantId,
        int $accountId,
        int $storeCustomerId,
        int $attemptId,
    ): SelfServiceTokenConsumptionAttempt {
        return DB::transaction(function () use ($tenantId, $accountId, $storeCustomerId, $attemptId): SelfServiceTokenConsumptionAttempt {
            $attempt = SelfServiceTokenConsumptionAttempt::query()
                ->whereKey($attemptId)
                ->where('tenant_id', $tenantId)
                ->where('self_service_customer_account_id', $accountId)
                ->where('self_service_store_customer_id', $storeCustomerId)
                ->lockForUpdate()
                ->first();

            if (! $attempt) {
                throw new HttpException(404, self::MESSAGE_ATTEMPT_NOT_CONFIRMABLE);
            }

            if ($attempt->status === SelfServiceTokenConsumptionAttempt::STATUS_CONFIRMED) {
                $this->markAttemptConfirmed($attempt);

                return $attempt->fresh();
            }

            if ($attempt->status !== SelfServiceTokenConsumptionAttempt::STATUS_PENDING) {
                throw new HttpException(422, self::MESSAGE_ATTEMPT_NOT_CONFIRMABLE);
            }

            $pocket = SelfServiceTokenPocket::query()
                ->whereKey($attempt->self_service_token_pocket_id)
                ->where('tenant_id', $tenantId)
                ->where('self_service_customer_account_id', $accountId)
                ->where('self_service_store_customer_id', $storeCustomerId)
                ->where('status', SelfServiceTokenPocket::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if (! $pocket) {
                throw new HttpException(422, self::MESSAGE_NOT_AVAILABLE);
            }

            $idempotencyKey = $this->consumptionIdempotencyKey($attempt);
            $existingMovement = SelfServiceTokenPocketMovement::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingMovement) {
                $this->markAttemptConfirmed($attempt);

                return $attempt->fresh();
            }

            $quantity = (float) $attempt->quantity;

            if ($quantity > (float) $pocket->quantity_available) {
                throw new HttpException(422, self::MESSAGE_NOT_AVAILABLE);
            }

            $consumptionPoint = $this->consumptionPointForAttempt($attempt);
            $consumptionRequest = $this->consumptionRequests->make($attempt, $pocket, $consumptionPoint);
            $gatewayResponse = $this->consumptionGateway->process($consumptionRequest);

            if (($gatewayResponse['status'] ?? null) !== 'approved') {
                $this->markAttemptFailed($attempt, $gatewayResponse);

                throw new HttpException(422, self::MESSAGE_ATTEMPT_NOT_CONFIRMABLE);
            }

            $balanceAfter = (float) $pocket->quantity_available - $quantity;

            SelfServiceTokenPocketMovement::query()->create([
                'tenant_id' => $tenantId,
                'self_service_token_pocket_id' => $pocket->id,
                'self_service_cart_id' => null,
                'self_service_cart_item_id' => null,
                'order_id' => null,
                'order_item_id' => null,
                'product_id' => $attempt->product_id,
                'movement_type' => SelfServiceTokenPocketMovement::TYPE_CONSUMPTION,
                'quantity' => $quantity,
                'balance_after' => $balanceAfter,
                'unit_label_snapshot' => $attempt->unit_label_snapshot,
                'unit_seconds_snapshot' => $attempt->unit_seconds_snapshot,
                'source_type' => SelfServiceTokenConsumptionAttempt::class,
                'source_id' => $attempt->id,
                'idempotency_key' => $idempotencyKey,
                'notes' => 'Consumo simulado confirmado de fichas.',
                'meta' => [
                    'origin' => 'self_service_sales.token_consumption',
                    'simulated' => true,
                    'attempt_id' => $attempt->id,
                    'total_seconds' => $attempt->total_seconds,
                    'consumes_balance' => true,
                    'calls_external_controller' => true,
                    'controller_response_status' => $gatewayResponse['status'] ?? null,
                    'external_consumption_id' => $gatewayResponse['external_consumption_id'] ?? null,
                    ...$this->consumptionPointPayloadForAttempt($attempt),
                ],
            ]);

            $pocket->update([
                'quantity_available' => $balanceAfter,
            ]);

            $this->markAttemptConfirmed($attempt, $gatewayResponse, $consumptionRequest);

            return $attempt->fresh();
        });
    }

    private function markAttemptConfirmed(
        SelfServiceTokenConsumptionAttempt $attempt,
        ?array $gatewayResponse = null,
        ?array $consumptionRequest = null
    ): void
    {
        $meta = is_array($attempt->meta) ? $attempt->meta : [];
        $responsePayload = $gatewayResponse ?? (is_array($attempt->response_payload) ? $attempt->response_payload : null);
        $hasControllerRequest = $consumptionRequest !== null || array_key_exists('controller_request', $meta);
        $hasApprovedControllerResponse = data_get($responsePayload, 'provider_target') === 'token_controller'
            && data_get($responsePayload, 'status') === 'approved';

        $meta['stage'] = 'simulated_confirmed';
        $meta['consumes_balance'] = true;
        $meta['calls_external_controller'] = $gatewayResponse !== null
            || ($meta['calls_external_controller'] ?? false) === true
            || $hasControllerRequest
            || $hasApprovedControllerResponse;

        if ($consumptionRequest !== null) {
            $meta['controller_request'] = $consumptionRequest;
        }

        $meta = array_merge($meta, $this->consumptionPointPayloadForAttempt($attempt));

        $attempt->update([
            'status' => SelfServiceTokenConsumptionAttempt::STATUS_CONFIRMED,
            'confirmed_at' => $attempt->confirmed_at ?: now(),
            'response_payload' => $responsePayload ?? [
                'provider' => 'simulated',
                'controller' => 'simulated',
                'status' => 'confirmed',
                'confirmed_at' => now()->toIso8601String(),
            ],
            'meta' => $meta,
        ]);
    }

    private function markAttemptFailed(SelfServiceTokenConsumptionAttempt $attempt, array $gatewayResponse): void
    {
        $meta = is_array($attempt->meta) ? $attempt->meta : [];
        $meta['stage'] = 'simulated_failed';
        $meta['consumes_balance'] = false;
        $meta['calls_external_controller'] = true;
        $meta = array_merge($meta, $this->consumptionPointPayloadForAttempt($attempt));

        $attempt->update([
            'status' => SelfServiceTokenConsumptionAttempt::STATUS_FAILED,
            'failed_at' => now(),
            'response_payload' => $gatewayResponse,
            'meta' => $meta,
        ]);
    }

    private function consumptionIdempotencyKey(SelfServiceTokenConsumptionAttempt $attempt): string
    {
        return sprintf(
            'self_service_token_pocket:consumption:attempt:%s:pocket:%s:product:%s',
            $attempt->id,
            $attempt->self_service_token_pocket_id,
            $attempt->product_id,
        );
    }

    private function consumptionPointPayloadForAttempt(SelfServiceTokenConsumptionAttempt $attempt): array
    {
        if ($attempt->source_type !== ShopConsumptionPoint::class || ! $attempt->source_id) {
            return [];
        }

        $payload = [
            'consumption_point_id' => (int) $attempt->source_id,
        ];

        $requestPayload = is_array($attempt->request_payload) ? $attempt->request_payload : [];
        $label = $requestPayload['consumption_point_label'] ?? null;

        if (! filled($label)) {
            $point = ShopConsumptionPoint::query()
                ->whereKey($attempt->source_id)
                ->where('tenant_id', $attempt->tenant_id)
                ->first();

            $label = $point?->displayName();
        }

        if (filled($label)) {
            $payload['consumption_point_label'] = (string) $label;
        }

        return $payload;
    }

    private function consumptionPointForAttempt(SelfServiceTokenConsumptionAttempt $attempt): ?ShopConsumptionPoint
    {
        if ($attempt->source_type !== ShopConsumptionPoint::class || ! $attempt->source_id) {
            return null;
        }

        return ShopConsumptionPoint::query()
            ->whereKey($attempt->source_id)
            ->where('tenant_id', $attempt->tenant_id)
            ->first();
    }

    private function availableConsumptionPointForPocket(
        string $tenantId,
        int $consumptionPointId,
        SelfServiceTokenPocket $pocket,
    ): ShopConsumptionPoint {
        $point = ShopConsumptionPoint::query()
            ->whereKey($consumptionPointId)
            ->where('tenant_id', $tenantId)
            ->with('shop')
            ->first();

        if (
            ! $point instanceof ShopConsumptionPoint
            || ! $point->isActive()
            || ! $point->shop instanceof Shop
            || (string) $point->shop->tenant_id !== $tenantId
            || ! $point->shop->isActive()
        ) {
            throw new HttpException(422, self::MESSAGE_CONSUMPTION_POINT_NOT_AVAILABLE);
        }

        $publishedForPointShop = ShopItem::query()
            ->where('tenant_id', $tenantId)
            ->where('self_service_shop_id', $point->self_service_shop_id)
            ->where('product_id', $pocket->product_id)
            ->where('status', ShopItem::STATUS_PUBLISHED)
            ->where('is_visible', true)
            ->exists();

        if (! $publishedForPointShop) {
            throw new HttpException(422, self::MESSAGE_CONSUMPTION_POINT_NOT_AVAILABLE);
        }

        return $point;
    }
}
