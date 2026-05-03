<?php

// FILE: app/Support/Projects/ProjectActivityRecordSet.php | V1

namespace App\Support\Projects;

use App\Models\Project;
use App\Support\Modules\Contracts\ActivityRecordSetProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ProjectActivityRecordSet implements ActivityRecordSetProvider
{
    public function forRecord(Model $record): Collection
    {
        if (! $record instanceof Project) {
            return collect([$record]);
        }

        $tasks = $record->relationLoaded('tasks')
            ? $record->tasks
            : $record->tasks()->get();

        return collect([$record])
            ->merge($tasks)
            ->filter(fn ($item) => $item instanceof Model)
            ->filter(fn (Model $item) => $item->getKey() !== null)
            ->unique(fn (Model $item) => $item->getMorphClass().':'.$item->getKey())
            ->values();
    }
}