<?php

// FILE: tools/project-lab/views/documents.php | V1

$technicalDocuments = is_array($technicalDocuments ?? null) ? $technicalDocuments : [];
$technicalDocumentsJson = json_encode(
    $technicalDocuments,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);

if (! is_string($technicalDocumentsJson)) {
    $technicalDocumentsJson = '[]';
}

?>

<div id="tab-documents" class="tab-content" style="display:none;">
    <script>
        window.projectLabTechnicalDocuments = <?= $technicalDocumentsJson ?>;
    </script>

    <div class="card">
        <div class="editor-header">
            <h4>Documentos</h4>
            <span class="shortcuts">Base activa: tools/project-lab/documentos/</span>
        </div>

        <?php if (empty($technicalDocuments)) { ?>
            <div class="empty-state">
                <p>No se detectaron documentos técnicos activos.</p>
                <p class="muted">Solo se listan archivos .txt con DOC_SLUG válido.</p>
            </div>
        <?php } else { ?>
            <div style="display:grid; grid-template-columns:minmax(220px, 0.9fr) minmax(220px, 0.8fr) minmax(320px, 1.3fr); gap:14px; align-items:start;">
                <section>
                    <div class="output-header" style="margin-bottom:10px;">
                        <span>Documentos técnicos</span>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:8px; max-height:420px; overflow:auto; padding-right:4px;">
                        <?php foreach ($technicalDocuments as $document) { ?>
                            <?php
                            $structure = is_array($document['structure'] ?? null) ? $document['structure'] : [];
                            $isBalanced = (bool) ($structure['balanced'] ?? true);
                            $sectionCount = (int) ($structure['section_count'] ?? ($document['section_count'] ?? 0));
                            $endSectionCount = (int) ($structure['end_section_count'] ?? ($document['end_section_count'] ?? 0));
                            $isRecent = (bool) ($document['recently_updated'] ?? false);
                            $documentTitle = (string) ($document['title'] ?? 'Documento sin título');
                            $documentSlug = (string) ($document['slug'] ?? '');
                            ?>
                            <button
                                type="button"
                                class="tab-btn project-doc-item"
                                data-doc-slug="<?= htmlspecialchars($documentSlug, ENT_QUOTES, 'UTF-8') ?>"
                                onclick="selectProjectDocument(<?= htmlspecialchars(json_encode($documentSlug), ENT_QUOTES, 'UTF-8') ?>)"
                                style="margin:0;"
                            >
                                <strong><?= htmlspecialchars($documentTitle) ?></strong><br>
                                <code><?= htmlspecialchars($documentSlug !== '' ? $documentSlug : 'sin_doc_slug') ?></code><br>
                                <?php if ($isBalanced) { ?>
                                    <span class="table-meta doc-balance-ok">Secciones: <?= $sectionCount ?></span>
                                <?php } else { ?>
                                    <span class="table-meta doc-balance-bad">Secciones: <?= $sectionCount ?> / END: <?= $endSectionCount ?> <span class="doc-balance-badge">DESBALANCEADO</span></span>
                                <?php } ?>
                                <?php if ($isRecent) { ?>
                                    <span class="doc-recent-badge doc-recent-glow">RECIENTE</span>
                                <?php } ?>
                            </button>
                        <?php } ?>
                    </div>
                </section>

                <section>
                    <div class="output-header" style="margin-bottom:10px;">
                        <span>Secciones</span>
                    </div>

                    <div id="projectDocumentSections" style="display:flex; flex-direction:column; gap:8px; max-height:420px; overflow:auto; padding-right:4px;">
                        <div class="empty-state" style="padding:14px;">
                            <p>Seleccioná un documento.</p>
                        </div>
                    </div>
                </section>

                <section>
                    <div class="output-header" style="margin-bottom:10px;">
                        <span>Contenido de sección</span>
                    </div>

                    <pre id="projectDocumentSectionViewer" class="project-console-empty" style="min-height:260px; max-height:420px; overflow:auto; margin:0; padding:14px; background:#000; border:1px solid var(--border); border-radius:var(--radius-sm); color:var(--success); font-family:'Fira Code', 'Courier New', monospace; font-size:12px; white-space:pre-wrap;">Seleccioná una sección.</pre>
                </section>
            </div>

            <div class="card" style="margin-top:14px;">
                <div class="editor-header">
                    <h4 style="margin:0;">Notas o fragmentos nuevos</h4>
                    <span id="projectDocumentSelectionLabel" class="shortcuts">Sin selección</span>
                </div>

                <textarea
                    id="projectDocumentAuditFragments"
                    placeholder="Pegá acá fragmentos, notas de cambio o intención documental. Esto no aplica cambios: solo arma una auditoría IA compatible con REEMPLAZAR EN / AGREGAR EN."
                    style="min-height:150px;"
                ></textarea>

                <label style="display:flex; align-items:center; gap:8px; margin-top:12px; color:var(--muted); font-size:12px;">
                    <input
                        type="checkbox"
                        id="projectDocumentIncludeConsole"
                        style="width:auto;"
                    >
                    Incluir consola actual como evidencia
                </label>

                <div class="editor-actions">
                    <button type="button" class="secondary" onclick="clearProjectDocumentAuditFragments()">Limpiar</button>
                    <button type="button" class="success" onclick="auditProjectDocumentWithAi()">Auditar con IA activa</button>
                </div>
            </div>
        <?php } ?>
    </div>
</div>
