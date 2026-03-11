<?php

// FILE: app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Party;
use App\Models\Product;
use App\Models\Project;
use App\Models\Task;

class DashboardController extends Controller
{
    public function index()
    {
        $tenant = app('tenant');

        return view('dashboard', [
            'tenant' => $tenant,
            'projectsCount' => Project::query()->count(),
            'tasksCount' => Task::query()->count(),
            'tasksDoneCount' => Task::query()
                ->where('status', 'done')
                ->count(),
            'partiesCount' => Party::query()->count(),
            'productsCount' => Product::query()->count(),
            'ordersCount' => Order::query()->count(),
        ]);
    }
}