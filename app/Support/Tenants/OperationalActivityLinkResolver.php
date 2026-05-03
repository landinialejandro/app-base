<?php

// FILE: app/Support/Tenants/OperationalActivityLinkResolver.php | V2

namespace App\Support\Tenants;

use App\Models\Appointment;
use App\Models\Asset;
use App\Models\Document;
use App\Models\OperationalActivity;
use App\Models\Order;
use App\Models\Party;
use App\Models\Product;
use App\Models\Project;
use App\Models\Task;
use App\Support\Navigation\NavigationTrail;
use Illuminate\Database\Eloquent\Model;

class OperationalActivityLinkResolver
{
    public function resolve(OperationalActivity $activity, array $trail = []): array
    {
        $record = $activity->record;

        return [
            'label' => $this->recordLabel($activity, $record),
            'url' => $this->recordUrl($record, $trail),
        ];
    }

    protected function recordLabel(OperationalActivity $activity, ?Model $record): string
    {
        if ($record instanceof Task && filled($record->title ?? null)) {
            return 'Tarea: '.$record->title;
        }

        if ($record instanceof Task && filled($record->name ?? null)) {
            return 'Tarea: '.$record->name;
        }

        if ($record instanceof Appointment && filled($record->title ?? null)) {
            return 'Turno: '.$record->title;
        }

        if ($record instanceof Project && filled($record->name ?? null)) {
            return 'Proyecto: '.$record->name;
        }

        if ($record instanceof Party && filled($record->name ?? null)) {
            return 'Contacto: '.$record->name;
        }

        if ($record instanceof Product && filled($record->name ?? null)) {
            return 'Producto: '.$record->name;
        }

        if ($record instanceof Asset && filled($record->name ?? null)) {
            return 'Activo: '.$record->name;
        }

        if ($record instanceof Order && filled($record->number ?? null)) {
            return 'Orden: '.$record->number;
        }

        if ($record instanceof Document && filled($record->number ?? null)) {
            return 'Documento: '.$record->number;
        }

        return class_basename((string) $activity->record_type).' #'.$activity->record_id;
    }

    protected function recordUrl(?Model $record, array $trail = []): ?string
    {
        $trailQuery = NavigationTrail::toQuery($trail);

        return match (true) {
            $record instanceof Appointment => route('appointments.show', ['appointment' => $record] + $trailQuery),
            $record instanceof Asset => route('assets.show', ['asset' => $record] + $trailQuery),
            $record instanceof Document => route('documents.show', ['document' => $record] + $trailQuery),
            $record instanceof Order => route('orders.show', ['order' => $record] + $trailQuery),
            $record instanceof Party => route('parties.show', ['party' => $record] + $trailQuery),
            $record instanceof Product => route('products.show', ['product' => $record] + $trailQuery),
            $record instanceof Project => route('projects.show', ['project' => $record] + $trailQuery),
            $record instanceof Task => route('tasks.show', ['task' => $record] + $trailQuery),
            default => null,
        };
    }
}