<?php

// FILE: app/Support/Parties/PartyEmployeeActivityContext.php | V1

namespace App\Support\Parties;

use App\Models\Party;
use App\Support\Catalogs\PartyCatalog;
use App\Support\Modules\Contracts\ActivityContextProvider;
use App\Support\Tenants\OperationalActivityContextReader;
use Illuminate\Database\Eloquent\Model;

class PartyEmployeeActivityContext implements ActivityContextProvider
{
    public function forRecord(Model $record, array $trail = [], int $limit = 20): ?array
    {
        if (! $record instanceof Party) {
            return null;
        }

        if (! $this->isEmployeeParty($record)) {
            return null;
        }

        $membership = $record->activeMemberships()
            ->whereNotNull('user_id')
            ->with('user')
            ->first();

        if (! $membership || ! $membership->user_id) {
            return null;
        }

        $rows = app(OperationalActivityContextReader::class)
            ->forRecordAndUsers(
                record: $record,
                userIds: [(int) $membership->user_id],
                trail: $trail,
                limit: $limit,
            );

        return [
            'count' => $rows->count(),
            'data' => [
                'operationalActivityRows' => $rows,
                'title' => 'Actividad del colaborador',
                'description' => 'Registro reciente de actividad asociada a esta ficha y al usuario interno vinculado como actor o sujeto operativo.',
                'emptyLabel' => 'Sin actividad registrada',
                'emptyMessage' => 'Todavía no hay actividad operativa registrada para esta ficha ni para el colaborador vinculado.',
            ],
        ];
    }

    protected function isEmployeeParty(Party $party): bool
    {
        return $party->roles()
            ->where('role', PartyCatalog::ROLE_EMPLOYEE)
            ->exists();
    }
}