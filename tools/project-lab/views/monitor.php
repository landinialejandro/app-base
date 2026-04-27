<?php
/**
 * PROJECT LAB - Vista del Monitor
 * Diseño con métricas, paquetes y vendors
 */

// Estos datos deben venir de ProjectLab.php
$laravelInfo = $systemInfo ?? [];
$dbInfo = $dbInfo ?? [];
$cacheDrivers = $cacheDrivers ?? [];
$availableDrivers = $availableDrivers ?? [];
$folderSizes = $folderSizes ?? [];
$installedFeatured = $installedFeatured ?? [];
$vendorCounts = $vendorCounts ?? [];
$topVendors = $topVendors ?? [];
$totalPackages = $systemInfo['packages_count'] ?? 0;
$totalVendors = $systemInfo['vendors_count'] ?? 0;
$memoryUsage = $systemInfo['memory_usage'] ?? 0;
$peakMemory = $systemInfo['memory_peak'] ?? 0;
$diskFree = $systemInfo['disk_free'] ?? 0;
$diskTotal = $systemInfo['disk_total'] ?? 0;
?>

<div id="tab-monitor" class="tab-content" style="display:none;">
    <div class="card" style="margin-bottom: 20px;">
        <h3 style="margin:0 0 20px 0;">📊 Monitor del Sistema</h3>
        
        <!-- Métricas rápidas -->
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:15px; margin-bottom: 20px;">
            <div class="card" style="background: linear-gradient(135deg, #1e293b, #0f172a); border-left: 3px solid var(--accent);">
                <div style="font-size: 11px; color: var(--muted);">PHP</div>
                <div style="font-size: 20px; font-weight: bold;"><?= $systemInfo['php_version'] ?? phpversion() ?></div>
            </div>
            <div class="card" style="background: linear-gradient(135deg, #1e293b, #0f172a); border-left: 3px solid var(--success);">
                <div style="font-size: 11px; color: var(--muted);">Laravel</div>
                <div style="font-size: 20px; font-weight: bold;"><?= $systemInfo['laravel_version'] ?? app()->version() ?></div>
            </div>
            <div class="card" style="background: linear-gradient(135deg, #1e293b, #0f172a); border-left: 3px solid var(--warning);">
                <div style="font-size: 11px; color: var(--muted);">Paquetes</div>
                <div style="font-size: 20px; font-weight: bold;"><?= $totalPackages ?></div>
            </div>
            <div class="card" style="background: linear-gradient(135deg, #1e293b, #0f172a); border-left: 3px solid #8b5cf6;">
                <div style="font-size: 11px; color: var(--muted);">Vendors</div>
                <div style="font-size: 20px; font-weight: bold;"><?= $totalVendors ?></div>
            </div>
        </div>
        
        <!-- Grid principal -->
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap:20px;">
            
            <!-- Columna 1: Laravel & Entorno -->
            <div>
                <!-- Información de Laravel -->
                <div class="card" style="background:#1e293b; margin-bottom: 20px;">
                    <h4 style="margin:0 0 15px 0; color:var(--accent); display:flex; align-items:center; gap:8px;">
                        <span>🚀 Laravel</span>
                        <span class="badge badge-success"><?= $systemInfo['laravel_version'] ?? '' ?></span>
                    </h4>
                    <div class="table-item"><span>Entorno</span><span><?= $systemInfo['environment'] ?? '' ?></span></div>
                    <div class="table-item">
                        <span>Debug Mode</span>
                        <span class="badge <?= ($systemInfo['debug'] ?? false) ? 'badge-danger' : 'badge-success' ?>">
                            <?= ($systemInfo['debug'] ?? false) ? 'Activado' : 'Desactivado' ?>
                        </span>
                    </div>
                    <div class="table-item"><span>URL</span><span><?= $systemInfo['url'] ?? '' ?></span></div>
                    <div class="table-item"><span>Timezone</span><span><?= $systemInfo['timezone'] ?? '' ?></span></div>
                    <div class="table-item"><span>Locale</span><span><?= $systemInfo['locale'] ?? '' ?></span></div>
                </div>
                
                <!-- Configuración DB -->
                <div class="card" style="background:#1e293b; margin-bottom: 20px;">
                    <h4 style="margin:0 0 15px 0; color:var(--success);">🗄️ Base de Datos</h4>
                    <div class="table-item"><span>Driver</span><span><?= $dbInfo['driver'] ?? '' ?></span></div>
                    <div class="table-item"><span>Host</span><span><?= $dbInfo['host'] ?? '' ?></span></div>
                    <div class="table-item"><span>Database</span><span><?= $dbInfo['database'] ?? '' ?></span></div>
                    <div class="table-item"><span>Charset</span><span><?= $dbInfo['charset'] ?? '' ?></span></div>
                </div>
                
                <!-- Cache & Session -->
                <div class="card" style="background:#1e293b;">
                    <h4 style="margin:0 0 15px 0; color:var(--warning);">⚡ Drivers Activos</h4>
                    <?php foreach ($cacheDrivers as $name => $driver) { ?>
                    <div class="table-item">
                        <span><?= $name ?></span>
                        <span style="color:var(--accent);"><?= $driver ?></span>
                    </div>
                    <?php } ?>
                </div>
            </div>
            
            <!-- Columna 2: Sistema & Extensiones -->
            <div>
                <!-- Recursos del Sistema -->
                <div class="card" style="background:#1e293b; margin-bottom: 20px;">
                    <h4 style="margin:0 0 15px 0; color:var(--warning);">💻 Recursos</h4>
                    <div class="table-item">
                        <span>Memoria Actual</span>
                        <span><?= $memoryUsage ?> MB</span>
                    </div>
                    <div class="table-item">
                        <span>Memoria Pico</span>
                        <span><?= $peakMemory ?> MB</span>
                    </div>
                    <div class="table-item">
                        <span>Límite PHP</span>
                        <span><?= ini_get('memory_limit') ?></span>
                    </div>
                    <div class="table-item">
                        <span>Disco Libre</span>
                        <span><?= $diskFree ?> GB / <?= $diskTotal ?> GB</span>
                    </div>
                    
                    <!-- Barras de uso -->
                    <div style="margin-top: 15px;">
                        <div style="display:flex; justify-content:space-between; font-size:11px; margin-bottom:5px;">
                            <span>Disco Usado</span>
                            <span><?= $diskTotal > 0 ? round((1 - $diskFree / $diskTotal) * 100) : 0 ?>%</span>
                        </div>
                        <div style="background:#020617; height: 8px; border-radius: 4px; overflow:hidden;">
                            <div style="background: linear-gradient(90deg, var(--success), var(--warning), var(--danger)); 
                                        width: <?= $diskTotal > 0 ? round((1 - $diskFree / $diskTotal) * 100) : 0 ?>%; 
                                        height: 100%; transition: width 0.5s;"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Extensiones PHP -->
                <div class="card" style="background:#1e293b; margin-bottom: 20px;">
                    <h4 style="margin:0 0 15px 0; color:#8b5cf6;">🔌 Extensiones</h4>
                    <?php foreach ($availableDrivers as $name => $status) { ?>
                    <div class="table-item">
                        <span><?= $name ?></span>
                        <span style="font-size:11px;">
                            <?php if (strpos($status, '✅') !== false) { ?>
                                <span style="color:var(--success);"><?= $status ?></span>
                            <?php } else { ?>
                                <span style="color:var(--danger);"><?= $status ?></span>
                            <?php } ?>
                        </span>
                    </div>
                    <?php } ?>
                </div>
                
                <!-- Tamaño de Carpetas -->
                <?php if (! empty($folderSizes)) { ?>
                <div class="card" style="background:#1e293b;">
                    <h4 style="margin:0 0 15px 0; color:#ec4899;">📦 Tamaños de Directorios</h4>
                    <?php foreach ($folderSizes as $folder => $size) { ?>
                    <div class="table-item">
                        <span><?= $folder ?></span>
                        <span><?= $size > 1024 ? round($size / 1024, 2).' GB' : $size.' MB' ?></span>
                    </div>
                    <?php } ?>
                </div>
                <?php } ?>
            </div>
        </div>
        
        <!-- Paquetes Destacados -->
        <?php if (! empty($installedFeatured)) { ?>
        <div class="card" style="background:#1e293b; margin-top: 20px;">
            <h4 style="margin:0 0 15px 0; color:var(--accent);">⭐ Paquetes Destacados Instalados</h4>
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap:10px;">
                <?php foreach ($installedFeatured as $package => $info) { ?>
                <div class="card" style="background:#020617; padding:12px;">
                    <div style="font-weight:600; color:var(--success); margin-bottom:4px;"><?= $package ?></div>
                    <div style="font-size:11px; color:var(--muted); margin-bottom:4px;"><?= $info['description'] ?></div>
                    <span class="badge badge-success" style="font-size:10px;">v<?= $info['version'] ?></span>
                </div>
                <?php } ?>
            </div>
        </div>
        <?php } ?>
        
        <!-- Top Vendors -->
        <?php if (! empty($vendorCounts)) { ?>
        <div class="card" style="background:#1e293b; margin-top: 20px;">
            <h4 style="margin:0 0 15px 0; color:var(--warning);">
                🏢 Top Vendors 
                <small style="color:var(--muted); font-weight:normal;">(<?= $totalVendors ?> vendors, <?= $totalPackages ?> paquetes)</small>
            </h4>
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:8px;">
                <?php foreach ($topVendors as $vendor => $count) { ?>
                <div style="display:flex; justify-content:space-between; align-items:center; 
                            background:#020617; padding:8px 12px; border-radius:6px;">
                    <span style="font-weight:500;"><?= $vendor ?></span>
                    <span class="badge" style="background:rgba(59,130,246,0.2); color:var(--accent);">
                        <?= $count ?> <?= $count === 1 ? 'pkg' : 'pkgs' ?>
                    </span>
                </div>
                <?php } ?>
            </div>
        </div>
        <?php } ?>
        
        <!-- Botones de acción -->
        <div style="display:flex; gap:10px; margin-top: 20px;">
            <button onclick="tailLogs(100)" class="secondary" style="flex:1;">
                📋 Ver Últimos Logs
            </button>
            <button onclick="location.reload()" class="warning" style="flex:1;">
                🔄 Actualizar Métricas
            </button>
        </div>
        
        <!-- Visor de Logs -->
        <div id="logViewer" style="margin-top:20px;"></div>
    </div>
</div>