<?php

// FILE: app/Support/Modules/Contracts/ActivityContextProvider.php | V1

namespace App\Support\Modules\Contracts;

use Illuminate\Database\Eloquent\Model;

interface ActivityContextProvider
{
    /**
     * Resuelve una lectura contextual especial de actividad operativa.
     *
     * Devuelve null cuando el módulo no necesita ampliar la lectura directa
     * del record host.
     *
     * @return array<string, mixed>|null
     */
    public function forRecord(Model $record, array $trail = [], int $limit = 20): ?array;
}