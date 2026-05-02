<?php

// FILE: app/Events/OperationalRecordCreated.php | V2

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OperationalRecordCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $record,
        public ?int $actorUserId = null,
        public array $metadata = [],
    ) {
    }
}