<?php

// FILE: app/Policies/AssetPolicy.php | V5

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;
use App\Support\Auth\Security;

class AssetPolicy
{
    protected function security(): Security
    {
        return app(Security::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->security()->allows($user, 'assets.viewAny');
    }

    public function view(User $user, Asset $asset): bool
    {
        return $this->security()->allows($user, 'assets.view', $asset);
    }

    public function create(User $user): bool
    {
        return $this->security()->allows($user, 'assets.create', Asset::class);
    }

    public function update(User $user, Asset $asset): bool
    {
        return $this->security()->allows($user, 'assets.update', $asset);
    }

    public function delete(User $user, Asset $asset): bool
    {
        return $this->security()->allows($user, 'assets.delete', $asset);
    }
}
