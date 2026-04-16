<?php

// FILE: app/Support/Inventory/README-ORDER-LINE-NOTES.php | V1

/*
|--------------------------------------------------------------------------
| Inventory Nivel 3 — Notas operativas de implementación
|--------------------------------------------------------------------------
|
| Este archivo no participa del runtime.
| Sirve como referencia interna durante la reconversión del frente
| orders ↔ inventory ↔ order_items.
|
| Objetivo:
| - no perder reglas pactadas
| - no mezclar estados de cabecera con estados de línea
| - no reabrir decisiones ya cerradas
|
| -------------------------------------------------------------------------
| 1. Estados de Order
| -------------------------------------------------------------------------
|
| Order.status:
| - draft
| - approved
| - closed
| - cancelled
|
| Reglas:
| - draft      => sin movimientos operativos
| - approved   => inventory activo
| - closed     => readonly operativo
| - cancelled  => terminal
|
| approved reemplaza semánticamente a confirmed.
|
| -------------------------------------------------------------------------
| 2. Estados de OrderItem
| -------------------------------------------------------------------------
|
| OrderItem.status:
| - pending
| - partial
| - completed
| - cancelled
|
| Regla clave:
| - este campo cambia SOLO por código
| - nunca debe venir de formularios del usuario
|
| -------------------------------------------------------------------------
| 3. Trazabilidad de inventory
| -------------------------------------------------------------------------
|
| inventory_movements ahora puede referenciar:
| - product_id
| - order_id
| - order_item_id
| - document_id
|
| order_item_id permite:
| - ejecución por línea
| - pendiente real por línea
| - bloqueo correcto de edición/borrado
| - reversión trazable
|
| -------------------------------------------------------------------------
| 4. Bloqueos de OrderItem
| -------------------------------------------------------------------------
|
| Si la línea tiene movimientos:
| - no editar
| - no borrar
|
| Si la orden está readonly:
| - no crear items
| - no editar items
| - no borrar items
|
| -------------------------------------------------------------------------
| 5. Cambio de estado como horizonte
| -------------------------------------------------------------------------
|
| Aunque hoy exista compatibilidad transitoria con select de status,
| la dirección aprobada es que los cambios de estado deben tender a
| un proceso paralelo controlado por backend y no a simple edición libre.
|
| Horizonte doctrinal:
| - aprobar
| - cerrar
| - cancelar
| - eventualmente revertir / reabrir si se define
|
| Esto no implica implementar ahora una cadena compleja de aprobación,
| pero sí dejar asentado que el estado debe evolucionar hacia flujo
| controlado y no quedar como campo libre permanente.
|
| -------------------------------------------------------------------------
| 6. Regla de cancelación
| -------------------------------------------------------------------------
|
| Una orden solo puede cancelarse si:
| - no está closed
| - no tiene movimientos activos
|
| Si tuvo movimientos:
| - primero deben revertirse mediante contramovimientos
|
| -------------------------------------------------------------------------
| 7. Regla de cabecera vs línea
| -------------------------------------------------------------------------
|
| No mezclar:
|
| Estado de Order:
| - macro proceso de la cabecera
|
| Estado de OrderItem:
| - avance operativo de la línea
|
| Estado readonly / operable:
| - condición heredada o arbitrada por backend
|
| -------------------------------------------------------------------------
| 8. Criterio de diseño
| -------------------------------------------------------------------------
|
| - backend manda
| - blade solo expresa
| - orders consume contexto publicado por inventory
| - order_items sigue siendo hijo delegado
| - inventory absorbe complejidad operativa
|
*/
