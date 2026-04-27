<div id="tab-database" class="tab-content" style="display:none;">
    <div class="card">
        <div class="section-header">
            <h3>🗄️ Tablas</h3>
            <span><?= count($tablesInfo) ?> tablas</span>
        </div>
        
        <?php if (empty($tablesInfo)) { ?>
            <div class="empty-state">No se encontraron tablas</div>
        <?php } else { ?>
            <?php foreach ($tablesInfo as $t) { ?>
            <div class="table-item" onclick="loadTableDetails('<?= $t['name'] ?>', this)">
                <div>
                    <strong><code><?= $t['name'] ?></code></strong>
                    <div class="table-meta">
                        <?= number_format($t['count']) ?> registros
                    </div>
                </div>
                <div class="table-actions">
                    <button onclick="event.stopPropagation(); insertCode('DB::table(\'<?= $t['name'] ?>\')->limit(10)->get();')" 
                            class="secondary small">Query</button>
                    <button onclick="event.stopPropagation(); insertCode('DB::table(\'<?= $t['name'] ?>\')->count();')" 
                            class="secondary small">Count</button>
                </div>
            </div>
            <?php } ?>
        <?php } ?>
    </div>
</div>