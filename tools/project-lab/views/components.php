<?php

// FILE: tools/project-lab/views/components.php | V2

$componentsBase = $projectRoot.'/resources/views/components';
$componentFiles = is_dir($componentsBase)
    ? glob($componentsBase.'/*.blade.php')
    : [];

sort($componentFiles);

?>

<div id="tab-components" class="tab-content">
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
                    <p>Organiza lectura sin decidir lógica del módulo.</p>
                </div>

                <div>
                    <strong>Nivel 3 — Componentes modulares publicados</strong>
                    <code>linked-party · linked-order · linked-asset · linked-project</code>
                    <p>El módulo oferente arma el payload y el host solo monta.</p>
                </div>
            </div>
        </div>

        <div class="editor-header">
            <h4>Catálogo de componentes</h4>
            <span class="shortcuts">resources/views/components</span>
        </div>

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