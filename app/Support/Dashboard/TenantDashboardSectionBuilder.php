<?php

// FILE: app/Support/Dashboard/TenantDashboardSectionBuilder.php | V1

namespace App\Support\Dashboard;

use Illuminate\Support\Collection;

class TenantDashboardSectionBuilder
{
    public static function make(array $payload): Collection
    {
        return collect([
            self::cardsSection(
                key: 'daily',
                order: 10,
                title: 'Operación diaria',
                text: 'Accesos principales para el trabajo cotidiano.',
                infoCards: $payload['dailyInfoCards'] ?? collect(),
                actionCards: $payload['dailyCards'] ?? collect(),
            ),
            self::cardsSection(
                key: 'service',
                order: 20,
                title: 'Servicio y mantenimiento',
                text: 'Accesos automatizados para trabajos técnicos, servicios y mantenimiento. Las acciones disponibles dependen de los permisos configurados para órdenes de servicio.',
                infoCards: $payload['serviceMaintenanceInfoCards'] ?? collect(),
                actionCards: $payload['serviceMaintenanceCards'] ?? collect(),
            ),
            self::cardsSection(
                key: 'production',
                order: 30,
                title: 'Producción',
                text: 'Acceso operativo a órdenes de producción, recetas y contrato material. Las órdenes siguen siendo gestionadas por Orders, con materiales desde Inventory y composición desde Products.',
                infoCards: $payload['productionInfoCards'] ?? collect(),
                actionCards: $payload['productionCards'] ?? collect(),
            ),
            self::cardsSection(
                key: 'management',
                order: 40,
                title: 'Gestión complementaria',
                text: 'Módulos de seguimiento interno, planificación y soporte.',
                infoCards: $payload['managementInfoCards'] ?? collect(),
                actionCards: $payload['managementCards'] ?? collect(),
            ),
            self::projectOperationalAnalysisSection($payload),
        ])
            ->filter()
            ->sortBy('order')
            ->values();
    }

    private static function cardsSection(
        string $key,
        int $order,
        string $title,
        string $text,
        Collection $infoCards,
        Collection $actionCards,
    ): ?array {
        if ($infoCards->isEmpty() && $actionCards->isEmpty()) {
            return null;
        }

        return [
            'key' => $key,
            'order' => $order,
            'type' => 'cards',
            'title' => $title,
            'text' => $text,
            'variant' => 'premium',
            'info_cards' => $infoCards,
            'action_cards' => $actionCards,
        ];
    }

    private static function projectOperationalAnalysisSection(array $payload): ?array
    {
        if (($payload['canSeeAnalytics'] ?? false) !== true) {
            return null;
        }

        return [
            'key' => 'project_operational_analysis',
            'order' => 50,
            'type' => 'partial',
            'partial' => 'projects.partials.operational-analysis',
            'payload' => [
                'projectOverview' => $payload['projectOverview'] ?? [],
                'taskOverview' => $payload['taskOverview'] ?? [],
            ],
        ];
    }
}
