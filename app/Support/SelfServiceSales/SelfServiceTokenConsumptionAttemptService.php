<?php

// FILE: app/Support/SelfServiceSales/SelfServiceTokenConsumptionAttemptService.php | V2

namespace App\Support\SelfServiceSales;

use App\Models\SelfServiceTokenConsumptionAttempt;
use App\Models\SelfServiceTokenPocket;
use App\Models\SelfServiceTokenPocketMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SelfServiceTokenConsumptionAttemptService
{
    public const MESSAGE_INVALID_QUANTITY = 'La cantidad solicitada no es válida.';
    public const MESSAGE_NOT_AVAILABLE = 'No hay fichas disponibles para la cantidad solicitada.';
    public const MESSAGE_ATTEMPT_NOT_CONFIRMABLE = 'El intento de consumo no puede confirmarse.';

    public function createPendingAttempt(
        string $tenantId,
        int $accountId,
        int $storeCustomerId,
        int $pocketId,
        int|float $quantity,
    ): SelfServiceTokenConsumptionAttempt {
        return DB::transaction(function () use ($tenantId, $accountId, $storeCustomerId, $pocketId, $quantity): SelfServiceTokenConsumptionAttempt {
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

            $unitSeconds = $pocket->unit_seconds_snapshot !== null
                ? (int) $pocket->unit_seconds_snapshot
                : null;
            $totalSeconds = $unitSeconds !== null
                ? (int) round($quantity * $unitSeconds)
                : null;

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
                'source_type' => 'self_service_sales.token_consumption',
                'source_id' => $pocket->id,
                'idempotency_key' => (string) Str::uuid(),
                'request_payload' => [
                    'quantity' => $quantity,
                ],
                'response_payload' => null,
                'meta' => [
                    'stage' => 'pending_attempt',
                    'consumes_balance' => false,
                    'calls_external_controller' => false,
                ],
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
                ],
            ]);

            $pocket->update([
                'quantity_available' => $balanceAfter,
            ]);

            $this->markAttemptConfirmed($attempt);

            return $attempt->fresh();
        });
    }

    private function markAttemptConfirmed(SelfServiceTokenConsumptionAttempt $attempt): void
    {
        $meta = is_array($attempt->meta) ? $attempt->meta : [];
        $meta['stage'] = 'simulated_confirmed';

        $attempt->update([
            'status' => SelfServiceTokenConsumptionAttempt::STATUS_CONFIRMED,
            'confirmed_at' => $attempt->confirmed_at ?: now(),
            'response_payload' => [
                'provider' => 'simulated',
                'controller' => 'simulated',
                'status' => 'confirmed',
                'confirmed_at' => now()->toIso8601String(),
            ],
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
}
