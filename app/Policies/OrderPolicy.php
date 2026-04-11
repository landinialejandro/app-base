<?php

// FILE: app/Policies/OrderPolicy.php | V8

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
        /**
         * orders.create es contextual.
         * Requiere kind y no debe resolverse mediante policy abstracta.
         * El consumo correcto es Security::authorize/allows con contexto explícito.
         */
        return false;
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
