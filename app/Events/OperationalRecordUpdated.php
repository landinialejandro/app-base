<?php

// FILE: app/Events/OperationalRecordUpdated.php | V1

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OperationalRecordUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $record,
        public array $beforeAttributes,
        public ?int $actorUserId = null,
    ) {
    }
}