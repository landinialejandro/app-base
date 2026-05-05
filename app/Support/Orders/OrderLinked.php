<?php

// FILE: app/Support/Orders/OrderLinked.php | V4

namespace App\Support\Orders;

use App\Models\Order;

class OrderLinked
{
    public static function forOrder(?Order $order, array $trailQuery = [], string $label = 'Orden asociada'): array
    {
        if (! $order) {
            return [
                'supported' => true,
                'exists' => false,
                'hidden' => false,
                'readonly' => false,
                'state' => 'missing',
                'show_url' => null,
                'create_url' => null,
                'label' => $label,
                'text' => '—',
            ];
        }

        $user = auth()->user();
        $canView = (bool) ($user && $user->can('view', $order));

        return [
            'supported' => true,
            'exists' => true,
            'hidden' => false,
            'readonly' => ! $canView,
            'state' => $canView ? 'linked_viewable' : 'linked_readonly',
            'show_url' => $canView
                ? route('orders.show', ['order' => $order] + $trailQuery)
                : null,
            'create_url' => null,
            'label' => $label,
            'text' => $order->number ?: 'Orden #'.$order->id,
        ];
    }
}