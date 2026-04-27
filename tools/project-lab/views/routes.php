<div id="tab-routes" class="tab-content" style="display:none;">
    <div class="card">
        <div class="section-header">
            <h3>🔗 Rutas</h3>
            <span><?= count($routes) ?> registradas</span>
        </div>
        
        <div class="route-controls">
            <input type="text" id="routeSearch" placeholder="🔍 Filtrar..." onkeyup="filterRoutes()">
            <button onclick="copyAllRoutes()" class="secondary">📋 Copiar</button>
            <button onclick="location.reload()" class="warning">🔄 Actualizar</button>
        </div>
        
        <table id="routeTable">
            <thead>
                <tr>
                    <th>Método</th>
                    <th>URI</th>
                    <th>Nombre</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($routes)) { ?>
                    <tr><td colspan="3" class="empty-state">No se encontraron rutas</td></tr>
                <?php } else { ?>
                    <?php foreach ($routes as $r) { ?>
                    <tr class="route-row">
                        <td><span class="badge"><?= $r['method'] ?></span></td>
                        <td><code><?= htmlspecialchars($r['uri']) ?></code></td>
                        <td><?= $r['name'] ?? '-' ?></td>
                    </tr>
                    <?php } ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>