<?php

// FILE: app/Http/Controllers/SelfServiceTokenConsumptionAttemptController.php | V1

namespace App\Http\Controllers;

use App\Models\SelfServiceStoreCustomer;
use App\Models\Tenant;
use App\Support\SelfServiceSales\SelfServiceTokenConsumptionAttemptService;
use App\Support\SelfServiceSales\SelfServiceTokenPocketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class SelfServiceTokenConsumptionAttemptController extends Controller
{
    public function __construct(
        protected SelfServiceTokenConsumptionAttemptService $attempts,
        protected SelfServiceTokenPocketService $tokenPockets
    ) {
    }

    public function store(Request $request, Tenant $tenant): JsonResponse
    {
        $payload = $request->attributes->get('self_service_external_customer');

        if (! $payload) {
            return response()->json([
                'ok' => false,
                'message' => 'Ingresá como customer externo para usar fichas.',
            ], 403);
        }

        $storeCustomer = $payload['store_customer'] ?? null;
        $account = $payload['account'] ?? null;

        if (! $storeCustomer instanceof SelfServiceStoreCustomer || ! $account || $payload['can_operate'] !== true) {
            return response()->json([
                'ok' => false,
                'message' => 'Tu customer externo no está habilitado para operar.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'pocket_id' => ['required', 'integer'],
            'quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => SelfServiceTokenConsumptionAttemptService::MESSAGE_INVALID_QUANTITY,
            ], 422);
        }

        $data = $validator->validated();

        try {
            $attempt = $this->attempts->createPendingAttempt(
                tenantId: (string) $tenant->id,
                accountId: (int) $account->id,
                storeCustomerId: (int) $storeCustomer->id,
                pocketId: (int) $data['pocket_id'],
                quantity: (float) $data['quantity'],
            );
        } catch (HttpExceptionInterface $exception) {
            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        }

        $pocket = collect($this->tokenPockets->summaryForExternalCustomer(
            tenantId: (string) $tenant->id,
            accountId: (int) $account->id,
            storeCustomerId: (int) $storeCustomer->id,
        ))->firstWhere('product_id', $attempt->product_id);

        return response()->json([
            'ok' => true,
            'message' => 'Intento de consumo registrado.',
            'attempt' => [
                'id' => $attempt->id,
                'status' => $attempt->status,
                'quantity' => $this->normalizeNumber((float) $attempt->quantity),
                'unit_seconds' => $attempt->unit_seconds_snapshot,
                'total_seconds' => $attempt->total_seconds,
                'total_minutes' => $attempt->total_seconds !== null
                    ? $this->normalizeNumber((float) $attempt->total_seconds / 60)
                    : null,
            ],
            'pocket' => $pocket ? [
                'quantity_available' => $pocket['quantity_available'],
                'summary_label' => $pocket['summary_label'],
            ] : null,
        ]);
    }

    private function normalizeNumber(float $value): int|float
    {
        return floor($value) === $value ? (int) $value : $value;
    }
}
