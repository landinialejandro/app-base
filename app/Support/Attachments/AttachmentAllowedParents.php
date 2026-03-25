<?php

// FILE: app/Support/Attachments/AttachmentAllowedParents.php | V1

namespace App\Support\Attachments;

use App\Models\Asset;
use App\Models\Order;
use App\Models\Product;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;

class AttachmentAllowedParents
{
    public static function classes(): array
    {
        return [
            Asset::class,
            Product::class,
            Project::class,
            Task::class,
            Order::class,
        ];
    }

    public static function isAllowed(string $class): bool
    {
        return in_array($class, static::classes(), true);
    }

    public static function directoryNameFor(Model|string $attachable): string
    {
        $class = is_string($attachable) ? $attachable : $attachable::class;

        return match ($class) {
            Asset::class => 'assets',
            Product::class => 'products',
            Project::class => 'projects',
            Task::class => 'tasks',
            Order::class => 'orders',
            default => 'other',
        };
    }

    public static function showRouteNameFor(Model|string $attachable): ?string
    {
        $class = is_string($attachable) ? $attachable : $attachable::class;

        return match ($class) {
            Asset::class => 'assets.show',
            Product::class => 'products.show',
            Project::class => 'projects.show',
            Task::class => 'tasks.show',
            Order::class => 'orders.show',
            default => null,
        };
    }

    public static function labelFor(Model|string $attachable): string
    {
        $class = is_string($attachable) ? $attachable : $attachable::class;

        return match ($class) {
            Asset::class => 'Activo',
            Product::class => 'Producto',
            Project::class => 'Proyecto',
            Task::class => 'Tarea',
            Order::class => 'Orden',
            default => 'Registro',
        };
    }
}
