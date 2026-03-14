<?php

// FILE: app/Http/Controllers/SuperadminDashboardController.php

namespace App\Http\Controllers;

class SuperadminDashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard');
    }
}
