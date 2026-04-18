<?php

// FILE: app/Support/Modules/Contracts/ModuleSurfaceService.php | V3

namespace App\Support\Modules\Contracts;

interface ModuleSurfaceService
{
    /**
     * Publica la oferta reusable del módulo.
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
     */
    public function hostPack(string $host, mixed $record = null, array $context = []): array;
}
