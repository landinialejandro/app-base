<?php

// FILE: tools/project-lab/views/monitor.php | V3

$laravelInfo = $systemInfo ?? [];
$dbInfo = $dbInfo ?? [];
$cacheDrivers = $cacheDrivers ?? [];
$availableDrivers = $availableDrivers ?? [];
$folderSizes = $folderSizes ?? [];
$installedFeatured = $installedFeatured ?? [];
$topVendors = $topVendors ?? [];

$totalPackages = $systemInfo['packages_count'] ?? 0;
$totalVendors = $systemInfo['vendors_count'] ?? 0;
$memoryUsage = $systemInfo['memory_usage'] ?? 0;
$peakMemory = $systemInfo['memory_peak'] ?? 0;
$diskFree = $systemInfo['disk_free'] ?? 0;
$diskTotal = $systemInfo['disk_total'] ?? 0;

$diskUsedPercent = $diskTotal > 0
    ? max(0, min(100, round((1 - ($diskFree / $diskTotal)) * 100)))
    : 0;

?>

<div id="tab-monitor" class="tab-content">
    <div class="card monitor-shell">
        <div class="editor-header">
            <h4>📊 Monitor del sistema</h4>
            <span class="shortcuts">Lectura rápida del entorno local</span>
        </div>

        <div class="monitor-metrics-grid">
            <div class="monitor-metric monitor-metric--accent">
                <span>PHP</span>
                <strong><?= htmlspecialchars((string) ($systemInfo['php_version'] ?? phpversion())) ?></strong>
            </div>

            <div class="monitor-metric monitor-metric--success">
                <span>Laravel</span>
                <strong><?= htmlspecialchars((string) ($systemInfo['laravel_version'] ?? app()->version())) ?></strong>
            </div>

            <div class="monitor-metric monitor-metric--warning">
                <span>Paquetes</span>
                <strong><?= htmlspecialchars((string) $totalPackages) ?></strong>
            </div>

            <div class="monitor-metric monitor-metric--purple">
                <span>Vendors</span>
                <strong><?= htmlspecialchars((string) $totalVendors) ?></strong>
            </div>
        </div>

        <div class="monitor-layout-grid">
            <div class="monitor-column">
                <div class="monitor-panel monitor-panel--accent">
                    <h4>🚀 Laravel</h4>
                    <div class="table-item"><span>Entorno</span><span><?= htmlspecialchars((string) ($systemInfo['environment'] ?? '-')) ?></span></div>
                    <div class="table-item"><span>Debug</span><span><?= ! empty($systemInfo['debug']) ? 'Activado' : 'Desactivado' ?></span></div>
                    <div class="table-item"><span>URL</span><span><?= htmlspecialchars((string) ($systemInfo['url'] ?? '-')) ?></span></div>
                    <div class="table-item"><span>Timezone</span><span><?= htmlspecialchars((string) ($systemInfo['timezone'] ?? '-')) ?></span></div>
                    <div class="table-item"><span>Locale</span><span><?= htmlspecialchars((string) ($systemInfo['locale'] ?? '-')) ?></span></div>
                </div>

                <div class="monitor-panel monitor-panel--success">
                    <h4>🗄️ Base de datos</h4>
                    <div class="table-item"><span>Driver</span><span><?= htmlspecialchars((string) ($dbInfo['driver'] ?? '-')) ?></span></div>
                    <div class="table-item"><span>Host</span><span><?= htmlspecialchars((string) ($dbInfo['host'] ?? '-')) ?></span></div>
                    <div class="table-item"><span>Database</span><span><?= htmlspecialchars((string) ($dbInfo['database'] ?? '-')) ?></span></div>
                    <div class="table-item"><span>Charset</span><span><?= htmlspecialchars((string) ($dbInfo['charset'] ?? '-')) ?></span></div>
                </div>

                <div class="monitor-panel monitor-panel--warning">
                    <h4>⚡ Drivers activos</h4>

                    <?php if (empty($cacheDrivers)) { ?>
                        <p class="muted">Sin datos de drivers.</p>
                    <?php } else { ?>
                        <?php foreach ($cacheDrivers as $name => $driver) { ?>
                            <div class="table-item">
                                <span><?= htmlspecialchars((string) $name) ?></span>
                                <span><?= htmlspecialchars((string) $driver) ?></span>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>

            <div class="monitor-column">
                <div class="monitor-panel monitor-panel--pink">
                    <h4>💻 Recursos</h4>
                    <div class="table-item"><span>Memoria actual</span><span><?= htmlspecialchars((string) $memoryUsage) ?> MB</span></div>
                    <div class="table-item"><span>Memoria pico</span><span><?= htmlspecialchars((string) $peakMemory) ?> MB</span></div>
                    <div class="table-item"><span>Límite PHP</span><span><?= htmlspecialchars((string) ini_get('memory_limit')) ?></span></div>
                    <div class="table-item"><span>Disco libre</span><span><?= htmlspecialchars((string) $diskFree) ?> GB / <?= htmlspecialchars((string) $diskTotal) ?> GB</span></div>

                    <div class="monitor-progress-block">
                        <div class="monitor-progress-label">
                            <span>Disco usado</span>
                            <span><?= $diskUsedPercent ?>%</span>
                        </div>
                        <div class="monitor-progress-track">
                            <div class="monitor-progress-bar" style="width: <?= $diskUsedPercent ?>%;"></div>
                        </div>
                    </div>
                </div>

                <?php if (! empty($availableDrivers)) { ?>
                    <div class="monitor-panel monitor-panel--purple">
                        <h4>🔌 Extensiones / capacidades</h4>

                        <?php foreach ($availableDrivers as $name => $status) { ?>
                            <div class="table-item">
                                <span><?= htmlspecialchars((string) $name) ?></span>
                                <span><?= htmlspecialchars((string) $status) ?></span>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>

                <?php if (! empty($folderSizes)) { ?>
                    <div class="monitor-panel monitor-panel--accent">
                        <h4>📦 Tamaños de directorios</h4>

                        <?php foreach ($folderSizes as $folder => $size) { ?>
                            <div class="table-item">
                                <span><?= htmlspecialchars((string) $folder) ?></span>
                                <span><?= htmlspecialchars((string) ($size > 1024 ? round($size / 1024, 2).' GB' : $size.' MB')) ?></span>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>

        <?php if (! empty($installedFeatured)) { ?>
            <div class="monitor-panel monitor-panel--success monitor-panel--full">
                <h4>⭐ Paquetes destacados</h4>

                <div class="monitor-chip-grid">
                    <?php foreach ($installedFeatured as $package => $info) { ?>
                        <?php
                            $packageName = is_array($info)
                                ? ($info['name'] ?? $package)
                                : $package;

                            $packageVersion = is_array($info)
                                ? ($info['version'] ?? '-')
                                : (string) $info;
                        ?>

                        <div class="monitor-chip-card">
                            <strong><?= htmlspecialchars((string) $packageName) ?></strong>
                            <code>v<?= htmlspecialchars((string) $packageVersion) ?></code>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>

        <?php if (! empty($topVendors)) { ?>
            <div class="monitor-panel monitor-panel--warning monitor-panel--full">
                <h4>🏢 Top vendors</h4>

                <div class="monitor-chip-grid">
                    <?php foreach ($topVendors as $vendor => $count) { ?>
                        <div class="monitor-chip-card monitor-chip-card--row">
                            <strong><?= htmlspecialchars((string) $vendor) ?></strong>
                            <code><?= htmlspecialchars((string) $count) ?> paquete/s</code>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>

        <div class="editor-actions monitor-actions">
            <button type="button" onclick="location.reload()" class="secondary small">
                🔄 Actualizar métricas
            </button>
        </div>
    </div>
</div>