<?php

// FILE: tools/project-lab/views/icons.php | V2

use Illuminate\Support\Facades\Blade;

?>

<div id="tab-icons" class="tab-content" style="display:none;">
    <div class="card">
        <div class="editor-header">
            <h4>Catálogo de íconos</h4>
            <span class="shortcuts">resources/views/components/icons</span>
        </div>

        <?php
            $iconsBase = $projectRoot.'/resources/views/components/icons';
$iconFiles = is_dir($iconsBase) ? glob($iconsBase.'/*.blade.php') : [];
sort($iconFiles);
?>

        <?php if (empty($iconFiles)) { ?>
            <div class="empty-state">No se encontraron íconos Blade.</div>
        <?php } else { ?>
            <div class="catalog-grid">
                <?php foreach ($iconFiles as $file) {
                    $name = basename($file, '.blade.php');
                    $relative = str_replace($projectRoot.'/', '', $file);
                    $snippet = '<x-icons.'.$name.' class="w-4 h-4" />';

                    try {
                        $preview = Blade::render('<x-icons.'.$name.' class="w-6 h-6" />');
                    } catch (Throwable $e) {
                        $preview = '<span class="catalog-preview-missing">?</span>';
                    }
                    ?>
                    <div class="catalog-card">
                        <div class="catalog-preview">
                            <?= $preview ?>
                        </div>

                        <div class="catalog-title"><?= htmlspecialchars($name) ?></div>
                        <code><?= htmlspecialchars($relative) ?></code>

                        <pre><?= htmlspecialchars($snippet) ?></pre>

                        <button
                            type="button"
                            class="secondary small"
                            onclick="copyToClipboard('<?= htmlspecialchars($snippet, ENT_QUOTES) ?>', 'Snippet copiado')"
                        >
                            Copiar
                        </button>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>