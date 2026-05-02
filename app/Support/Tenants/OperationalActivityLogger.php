<?php

// FILE: app/Support/Tenants/OperationalActivityLogger.php | V2

namespace App\Support\Tenants;

use App\Events\OperationalRecordCreated;
use App\Events\OperationalRecordUpdated;
use App\Models\OperationalActivity;
use Illuminate\Database\Eloquent\Model;

class OperationalActivityLogger
{
    public function recordCreated(OperationalRecordCreated $event): ?OperationalActivity
    {
        $record = $event->record;
        $module = $this->moduleFor($record);

        if ($module === null) {
            return null;
        }

        return $this->createActivity(
            record: $record,
            module: $module,
            activityType: OperationalActivityCatalog::TYPE_CREATED,
            actorUserId: $event->actorUserId,
            subjectUserId: $this->subjectUserIdForCreated($record, $event->actorUserId),
            metadata: array_merge([
                'changed_fields' => [],
            ], $event->metadata),
        );
    }

public function recordUpdated(OperationalRecordUpdated $event): ?OperationalActivity
{
    $record = $event->record;
    $module = $this->moduleFor($record);

    if ($module === null) {
        return null;
    }

    $changes = array_merge(
        $this->relevantChanges($record, $event->beforeAttributes, $module),
        $this->metadataChanges($event->metadata)
    );

    if ($changes === []) {
        return null;
    }

    $activityType = $this->activityTypeForChanges($changes);
    $subjectUserId = $this->subjectUserIdForChanges($record, $changes, $event->actorUserId);

    $eventMetadata = $event->metadata;
    unset($eventMetadata['extra_changes']);

    return $this->createActivity(
        record: $record,
        module: $module,
        activityType: $activityType,
        actorUserId: $event->actorUserId,
        subjectUserId: $subjectUserId,
        metadata: array_merge([
            'changed_fields' => array_keys($changes),
            'changes' => $changes,
        ], $eventMetadata),
    );
}

    protected function createActivity(
        Model $record,
        string $module,
        string $activityType,
        ?int $actorUserId,
        ?int $subjectUserId,
        array $metadata = [],
    ): OperationalActivity {
        return OperationalActivity::create([
            'tenant_id' => $this->tenantIdFor($record),
            'actor_user_id' => $actorUserId,
            'subject_user_id' => $subjectUserId,
            'module' => $module,
            'record_type' => $record::class,
            'record_id' => (int) $record->getKey(),
            'activity_type' => $activityType,
            'occurred_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    protected function moduleFor(Model $record): ?string
    {
        return OperationalActivityCatalog::moduleForRecordClass($record::class);
    }

    protected function tenantIdFor(Model $record): string
    {
        $tenantId = $record->getAttribute('tenant_id');

        if ($tenantId) {
            return (string) $tenantId;
        }

        return (string) app('tenant')->id;
    }

    protected function subjectUserIdForCreated(Model $record, ?int $actorUserId): ?int
    {
        $assignedUserId = $record->getAttribute('assigned_user_id');

        if ($assignedUserId) {
            return (int) $assignedUserId;
        }

        return $actorUserId;
    }

    protected function subjectUserIdForChanges(Model $record, array $changes, ?int $actorUserId): ?int
    {
        if (array_key_exists('assigned_user_id', $changes)) {
            $to = $changes['assigned_user_id']['to'] ?? null;
            $from = $changes['assigned_user_id']['from'] ?? null;

            return $to !== null
                ? (int) $to
                : ($from !== null ? (int) $from : $actorUserId);
        }

        $assignedUserId = $record->getAttribute('assigned_user_id');

        if ($assignedUserId) {
            return (int) $assignedUserId;
        }

        return $actorUserId;
    }

    protected function relevantChanges(Model $record, array $beforeAttributes, string $module): array
    {
        $currentAttributes = $record->getAttributes();
        $fields = $this->observableFields($record, $module);
        $changes = [];

        foreach ($fields as $field) {
            $before = $beforeAttributes[$field] ?? null;
            $after = $currentAttributes[$field] ?? null;

            if ($this->valuesAreEqual($before, $after)) {
                continue;
            }

            $changes[$field] = [
                'from' => $before,
                'to' => $after,
            ];
        }

        return $changes;
    }

    protected function metadataChanges(array $metadata): array
    {
        $changes = $metadata['extra_changes'] ?? [];

        return is_array($changes) ? $changes : [];
    }

    protected function observableFields(Model $record, string $module): array
    {
        $catalogClass = OperationalActivityCatalog::catalogForModule($module);

        if (
            $catalogClass
            && method_exists($catalogClass, 'activityTrackedFields')
        ) {
            $fields = $catalogClass::activityTrackedFields();

            if (is_array($fields) && $fields !== []) {
                return array_values(array_diff($fields, OperationalActivityCatalog::ignoredFields()));
            }
        }

        return array_values(array_diff(
            array_keys($record->getAttributes()),
            OperationalActivityCatalog::ignoredFields()
        ));
    }

    protected function valuesAreEqual(mixed $before, mixed $after): bool
    {
        if ($before === $after) {
            return true;
        }

        if ($before === null || $after === null) {
            return $before === $after;
        }

        return (string) $before === (string) $after;
    }

    protected function activityTypeForChanges(array $changes): string
    {
        if (array_key_exists('assigned_user_id', $changes)) {
            $from = $changes['assigned_user_id']['from'] ?? null;
            $to = $changes['assigned_user_id']['to'] ?? null;

            if ($from === null && $to !== null) {
                return OperationalActivityCatalog::TYPE_ASSIGNED;
            }

            if ($from !== null && $to === null) {
                return OperationalActivityCatalog::TYPE_UNASSIGNED;
            }

            return OperationalActivityCatalog::TYPE_REASSIGNED;
        }

        if (array_key_exists('status', $changes)) {
            return OperationalActivityCatalog::TYPE_STATUS_CHANGED;
        }

        return OperationalActivityCatalog::TYPE_UPDATED;
    }
}