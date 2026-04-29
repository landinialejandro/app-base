<?php

// FILE: tools/project-lab/views/components.php | V1?>

<div id="tab-components" class="tab-content" style="display:none;">
    <div class="card">
        <div class="card help-section" style="margin-bottom:18px;">
            <h4>Esquema de utilización por profundidad</h4>

            <div class="component-depth-map">
                <div>
                    <strong>Nivel 1 — Estructura base</strong>
                    <code>x-page · x-page-header · x-breadcrumb · x-card · x-modal</code>
                    <p>Abstracción sana: estructura visual sin dominio.</p>
                </div>

                <div>
                    <strong>Nivel 2 — Lectura reusable</strong>
                    <code>x-show-summary · x-host-tabs · x-tabs-embedded</code>
                    <p>Sana si solo organiza lectura y no decide lógica del módulo.</p>
                </div>

                <div>
                    <strong>Nivel 3 — Acciones UI</strong>
                    <code>x-button-* · x-button-tool*</code>
                    <p>Sana si representa acciones ya autorizadas.</p>
                </div>

                <div>
                    <strong>Nivel 4 — Gramática compartida</strong>
                    <code>x-line-item-form · x-line-item-table · x-line-operation-modal</code>
                    <p>Vigilar: no debe absorber semántica de Orders, Documents o Inventory.</p>
                </div>

                <div>
                    <strong>Nivel 5 — Componentes modulares publicados</strong>
                    <code>linked-party · linked-order · linked-asset · linked-project</code>
                    <p>Sano si el módulo oferente arma el payload y el host solo monta.</p>
                </div>
            </div>
        </div>

        <div class="editor-header">
            <h4>Catálogo de componentes</h4>
            <span class="shortcuts">resources/views/components</span>
        </div>

        <?php
            $componentsBase = $projectRoot.'/resources/views/components';
$componentFiles = is_dir($componentsBase)
    ? glob($componentsBase.'/*.blade.php')
    : [];

sort($componentFiles);
?>

        <?php if (empty($componentFiles)) { ?>
            <div class="empty-state">No se encontraron componentes Blade globales.</div>
        <?php } else { ?>
            <div class="catalog-grid catalog-grid--wide">
                <?php foreach ($componentFiles as $file) {
                    $name = basename($file, '.blade.php');
                    $relative = str_replace($projectRoot.'/', '', $file);
                    $snippet = '<x-'.$name.'>';
                    ?>
                    <div class="catalog-card">
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