<?php

// FILE: app/Policies/ProjectPolicy.php | V5

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Support\Auth\Security;

class ProjectPolicy
{
    protected function security(): Security
    {
        return app(Security::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->security()->allows($user, 'projects.viewAny');
    }

    public function view(User $user, Project $project): bool
    {
        return $this->security()->allows($user, 'projects.view', $project);
    }

    public function create(User $user): bool
    {
        return $this->security()->allows($user, 'projects.create', Project::class);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->security()->allows($user, 'projects.update', $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->security()->allows($user, 'projects.delete', $project);
    }
}
