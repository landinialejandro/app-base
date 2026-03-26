<?php

// FILE: app/Support/Attachments/AttachmentAllowedParents.php | V3

namespace App\Support\Attachments;

class AttachmentAllowedParents
{
    public static function types(): array
    {
        return [
            'order',
            'document',
            'asset',
            'project',
            'task',
            'product',
        ];
    }

    public static function map(): array
    {
        return [
            'order' => \App\Models\Order::class,
            'document' => \App\Models\Document::class,
            'asset' => \App\Models\Asset::class,
            'project' => \App\Models\Project::class,
            'task' => \App\Models\Task::class,
            'product' => \App\Models\Product::class,
        ];
    }

    public static function resolve(string $type): string
    {
        return self::map()[$type] ?? throw new \InvalidArgumentException('Invalid attachable type');
    }
}
