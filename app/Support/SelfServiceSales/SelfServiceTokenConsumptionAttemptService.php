<?php

// FILE: app/Support/SelfServiceSales/SelfServiceTokenConsumptionAttemptService.php | V1

namespace App\Support\SelfServiceSales;

use App\Models\SelfServiceTokenConsumptionAttempt;
use App\Models\SelfServiceTokenPocket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SelfServiceTokenConsumptionAttemptService
{
    public const MESSAGE_INVALID_QUANTITY = 'La cantidad solicitada no es válida.';
    public const MESSAGE_NOT_AVAILABLE = 'No hay fichas disponibles para la cantidad solicitada.';

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
}
