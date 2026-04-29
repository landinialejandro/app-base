<?php

// FILE: tools/project-lab/views/sections.php | V2

?>

<div id="tab-sections" class="tab-content" style="display:none;">
    <div class="card">
        <div class="editor-header">
            <h4>🧱 Catálogo de secciones CSS/JS</h4>
            <span class="shortcuts">Secciones detectadas en archivos CSS y JS</span>
        </div>

        <?php if (empty($assetSectionsCatalog)): ?>
            <div class="empty-state">
                <p>No se detectaron secciones CSS/JS.</p>
                <p class="muted">El catálogo busca bloques con formato &lt;&lt;SECTION: ...&gt;&gt;.</p>
            </div>
        <?php else: ?>
            <div class="help-grid">
                <?php foreach ($assetSectionsCatalog as $assetFile): ?>
                    <div class="help-card">
                        <h4><?= strtoupper(htmlspecialchars($assetFile['extension'])) ?></h4>
                        <div class="section-file-path"><?= htmlspecialchars($assetFile['path']) ?></div>
                        <p><?= (int) $assetFile['count'] ?> sección/es detectada/s.</p>

                        <div class="section-chip-list">
                            <?php foreach ($assetFile['sections'] as $sectionName): ?>
                                <?php
                                    $path = $assetFile['path'];

                                    $template = "REEMPLAZAR EN: [{$path}]\n"
                                        ."<<SECTION: {$sectionName}>>\n\n"
                                        ."/* contenido */\n\n"
                                        ."<<END SECTION>>";
                                ?>

                                <button
                                    type="button"
                                    class="snippet-chip"
                                    onclick="copySectionTemplate(<?= htmlspecialchars(json_encode($template), ENT_QUOTES, 'UTF-8') ?>)"
                                    title="Copiar plantilla para <?= htmlspecialchars($sectionName) ?>"
                                >
                                    <?= htmlspecialchars($sectionName) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>