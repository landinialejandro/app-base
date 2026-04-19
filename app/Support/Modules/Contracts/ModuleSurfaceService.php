<?php

// FILE: app/Support/Modules/Contracts/ModuleSurfaceService.php | V4

namespace App\Support\Modules\Contracts;

interface ModuleSurfaceService
{
    /**
     * Publica la oferta reusable del módulo.
     *
     * Cada offer debería respetar esta forma base:
     *
     *   'type'     => string              // ej: 'embedded', 'linked'
     *   'key'      => string              // identificador estable de la surface
     *   'label'    => string|null         // nombre visible
     *   'targets'  => array<int, string>  // hosts consumidores, ej: ['orders.show']
     *   'slot'     => string|null         // zona del host donde debe montarse
     *   'priority' => int|null            // orden relativo
     *   'view'     => string              // blade oficial publicada por el módulo
     *   'needs'    => array<int, string>  // claves requeridas del hostPack
     *   'resolver' => callable|null       // resolver reusable del módulo oferente
     *   'visible'  => bool|null           // visibilidad final si aplica
     *
     * Slots actualmente reconocidos por los hosts show:
     *
     *   - header_actions
     *   - summary_items
     *   - detail_items
     *   - tab_nav
     *   - tab_panels
     *
     * Regla:
     * el módulo oferente publica la surface con su slot explícito;
     * el host consumidor decide únicamente cómo iterar cada slot,
     * sin reinterpretar la intención de montaje.
     *
     * @return array<int, array<string, mixed>>
     */
    public function offers(): array;

    /**
     * Devuelve el paquete base del módulo cuando actúa como host.
     *
     * El array devuelto debe respetar esta forma mínima:
     *
     *   'host'       => string   // identificador del contexto, ej: 'orders.show'
     *   'record'     => mixed    // entidad principal del host
     *   'recordType' => string   // tipo del record, ej: 'order', 'document'
     *   'trailQuery' => array    // contexto de navegación
     *
     * Si el módulo no actúa como host para ese contexto, devuelve [].
     *
     * @return array<string, mixed>
     */
    public function hostPack(string $host, mixed $record = null, array $context = []): array;
}
