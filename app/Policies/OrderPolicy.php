<?php

// FILE: app/Policies/OrderPolicy.php | V5

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Support\Auth\Security;

class OrderPolicy
{
    protected function security(): Security
    {
        return app(Security::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->security()->allows($user, 'orders.viewAny');
    }

    public function view(User $user, Order $order): bool
    {
        return $this->security()->allows($user, 'orders.view', $order);
    }

    public function create(User $user): bool
    {
        return $this->security()->allows($user, 'orders.create', Order::class);
    }

    public function update(User $user, Order $order): bool
    {
        return $this->security()->allows($user, 'orders.update', $order);
    }

    public function delete(User $user, Order $order): bool
    {
        return $this->security()->allows($user, 'orders.delete', $order);
    }
}
