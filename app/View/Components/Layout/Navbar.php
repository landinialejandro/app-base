<?php

// FILE: app/View/Components/Layout/Navbar.php

namespace App\View\Components\Layout;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Navbar extends Component
{
    public array $mainLinks;
    public array $managementLinks;

    public function __construct()
    {
        $this->mainLinks = [
            [
                'label' => 'Tareas',
                'route' => 'tasks.index',
                'active' => ['tasks.*'],
            ],
            [
                'label' => 'Contactos',
                'route' => 'parties.index',
                'active' => ['parties.*'],
            ],
            [
                'label' => 'Activos',
                'route' => 'assets.index',
                'active' => ['assets.*'],
            ],
        ];

        $this->managementLinks = [
            [
                'label' => 'Proyectos',
                'route' => 'projects.index',
                'active' => ['projects.*'],
            ],
            [
                'label' => 'Productos',
                'route' => 'products.index',
                'active' => ['products.*'],
            ],
            [
                'label' => 'Órdenes',
                'route' => 'orders.index',
                'active' => ['orders.*', 'orders.items.*'],
            ],
            [
                'label' => 'Documentos',
                'route' => 'documents.index',
                'active' => ['documents.*'],
            ],
        ];
    }

    public function render(): View|Closure|string
    {
        return view('components.layout.navbar');
    }
}