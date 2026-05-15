<?php

// FILE: app/Policies/ShopPolicy.php | V1

namespace App\Policies;

use App\Models\Shop;
use App\Models\User;
use App\Support\Auth\Security;

class ShopPolicy
{
    protected function security(): Security
    {
        return app(Security::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->security()->allows($user, 'shops.viewAny');
    }

    public function view(User $user, Shop $shop): bool
    {
        return $this->security()->allows($user, 'shops.view', $shop);
    }

    public function create(User $user): bool
    {
        return $this->security()->allows($user, 'shops.create', Shop::class);
    }

    public function update(User $user, Shop $shop): bool
    {
        return $this->security()->allows($user, 'shops.update', $shop);
    }

    public function delete(User $user, Shop $shop): bool
    {
        return $this->security()->allows($user, 'shops.delete', $shop);
    }

    public function activate(User $user, Shop $shop): bool
    {
        return $this->update($user, $shop);
    }
}