<?php

// FILE: app/Policies/ProductPolicy.php | V5

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Support\Auth\Security;

class ProductPolicy
{
    protected function security(): Security
    {
        return app(Security::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->security()->allows($user, 'products.viewAny');
    }

    public function view(User $user, Product $product): bool
    {
        return $this->security()->allows($user, 'products.view', $product);
    }

    public function create(User $user): bool
    {
        return $this->security()->allows($user, 'products.create', Product::class);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->security()->allows($user, 'products.update', $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->security()->allows($user, 'products.delete', $product);
    }
}
