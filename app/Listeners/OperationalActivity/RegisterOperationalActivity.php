<?php

// FILE: app/Listeners/OperationalActivity/RegisterOperationalActivity.php | V1

namespace App\Listeners\OperationalActivity;

use App\Events\OperationalRecordCreated;
use App\Events\OperationalRecordUpdated;
use App\Support\Tenants\OperationalActivityLogger;
use Illuminate\Contracts\Queue\ShouldQueue;

class RegisterOperationalActivity implements ShouldQueue
{
    public bool $afterCommit = true;

    public int $tries = 1;

    public string $queue = 'default';

    public function handle(OperationalRecordCreated|OperationalRecordUpdated $event): void
    {
        try {
            $logger = app(OperationalActivityLogger::class);

            if ($event instanceof OperationalRecordCreated) {
                $logger->recordCreated($event);

                return;
            }

            $logger->recordUpdated($event);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}