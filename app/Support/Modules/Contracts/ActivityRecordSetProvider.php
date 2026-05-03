<?php

// FILE: app/Support/Modules/Contracts/ActivityRecordSetProvider.php | V1

namespace App\Support\Modules\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface ActivityRecordSetProvider
{
    /**
     * Define qué records componen la actividad contextual agregada de un host.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    public function forRecord(Model $record): Collection;
}