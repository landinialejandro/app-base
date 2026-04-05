<?php

// FILE: app/Policies/TaskPolicy.php | V6

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Support\Auth\Security;

class TaskPolicy
{
    protected function security(): Security
    {
        return app(Security::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->security()->allows($user, 'tasks.viewAny');
    }

    public function view(User $user, Task $task): bool
    {
        return $this->security()->allows($user, 'tasks.view', $task);
    }

    public function create(User $user): bool
    {
        return $this->security()->allows($user, 'tasks.create', Task::class);
    }

    public function update(User $user, Task $task): bool
    {
        return $this->security()->allows($user, 'tasks.update', $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->security()->allows($user, 'tasks.delete', $task);
    }
}
