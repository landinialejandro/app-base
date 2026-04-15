<?php

// FILE: app/Support/System/OwnerAlertTaskService.php | V2

namespace App\Support\System;

use App\Models\Membership;
use App\Models\Task;
use App\Models\Tenant;
use App\Support\Catalogs\TaskCatalog;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class OwnerAlertTaskService
{
    public function createOnceForTenant(
        Tenant $tenant,
        string $type,
        string $title,
        string $description,
        string $dedupeKey,
        array $metadata = [],
        ?string $priority = null,
        CarbonInterface|string|null $dueDate = null
    ): ?Task {
        $ownerMembership = Membership::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->where('is_owner', true)
            ->first();

        if (! $ownerMembership || ! $ownerMembership->user_id) {
            return null;
        }

        $existingTask = Task::query()
            ->where('tenant_id', $tenant->id)
            ->where('assigned_user_id', $ownerMembership->user_id)
            ->whereIn('status', [
                TaskCatalog::STATUS_PENDING,
                TaskCatalog::STATUS_IN_PROGRESS,
            ])
            ->where('metadata->system_alert->dedupe_key', $dedupeKey)
            ->latest('id')
            ->first();

        if ($existingTask) {
            return $existingTask;
        }

        $normalizedDueDate = null;

        if ($dueDate instanceof CarbonInterface) {
            $normalizedDueDate = $dueDate->toDateString();
        } elseif (is_string($dueDate) && trim($dueDate) !== '') {
            $normalizedDueDate = Carbon::parse($dueDate)->toDateString();
        }

        $systemAlertMetadata = array_merge([
            'type' => $type,
            'source' => 'system',
            'dedupe_key' => $dedupeKey,
            'summary' => $title,
            'occurred_at' => now()->toDateTimeString(),
        ], $metadata);

        $finalDescription = trim($description);

        if ($finalDescription !== '') {
            $finalDescription .= "\n\n";
        }

        // Se conserva como apoyo legible, pero la deduplicación real ya no depende de description.
        $finalDescription .= '[system_alert_dedupe_key] '.$dedupeKey;

        return Task::create([
            'tenant_id' => $tenant->id,
            'project_id' => null,
            'party_id' => null,
            'assigned_user_id' => $ownerMembership->user_id,
            'name' => $title,
            'description' => $finalDescription,
            'status' => TaskCatalog::STATUS_PENDING,
            'priority' => $priority ?: TaskCatalog::PRIORITY_HIGH,
            'due_date' => $normalizedDueDate,
            'metadata' => [
                'system_alert' => $systemAlertMetadata,
            ],
        ]);
    }
}
