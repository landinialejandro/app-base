<?php

// FILE: tools/project-lab/views/help.php | V1?> 

<div id="tab-help" class="tab-content" style="display:none;">
    <div class="card">
        <div class="editor-header">
            <h4>Ayuda Project Lab</h4>
            <span class="shortcuts">Guía rápida de comandos y formatos aceptados</span>
        </div>

        <div class="help-grid">
            <div class="help-card help-card--green">
                <h4>💻 Actualizar código</h4>
                <p>Aplica cambios desde el textarea o desde el clipboard.</p>
                <ul>
                    <li>Archivos completos PHP.</li>
                    <li>Archivos completos Blade.</li>
                    <li>Archivos completos CSS.</li>
                    <li>Archivos completos JS.</li>
                    <li>Reemplazo o agregado de métodos PHP.</li>
                </ul>
            </div>

            <div class="help-card help-card--blue">
                <h4>📄 Actualizar docs</h4>
                <p>Reemplaza secciones completas en documentos técnicos por slug.</p>
                <pre>REEMPLAZAR EN: [contexto_fijo_proyecto_app_base]
&lt;&lt;SECTION: NOMBRE EXACTO&gt;&gt;
SECTION_VERSION: 00001

Contenido
&lt;&lt;END SECTION&gt;&gt;</pre>
            </div>

            <div class="help-card help-card--orange">
                <h4>🔍 Ejecutar auditoría</h4>
                <p>Ejecuta comandos bash desde textarea o clipboard.</p>
                <pre>cat app/Http/Controllers/DashboardController.php
echo
cat resources/views/dashboard.blade.php</pre>
            </div>

            <div class="help-card help-card--red">
                <h4>📋 Clipboard</h4>
                <p>Ejecuta directamente el contenido del portapapeles del sistema.</p>
                <p>Es útil para aplicar rápidamente bloques preparados desde el chat.</p>
            </div>
        </div>

        <div class="card help-section">
            <h4>Formatos de código aceptados</h4>

            <h5>PHP completo</h5>
            <pre>&lt;?php

// FILE: app/Support/Ejemplo.php | V1

class Ejemplo
{
}</pre>

            <h5>Blade completo</h5>
            <pre>&#123;&#123;-- FILE: resources/views/ejemplo.blade.php | V1 --&#125;&#125;

&lt;div&gt;Contenido&lt;/div&gt;</pre>

            <h5>CSS completo</h5>
            <pre>/* FILE: documentos/log/project-lab-test.css | V1 */

.project-lab-test {
    outline: none;
}</pre>

            <h5>JS completo</h5>
            <pre>// FILE: documentos/log/project-lab-test.js | V1

console.log('Project Lab JS full file OK');</pre>

            <h5>Reemplazar método PHP</h5>
            <pre>// TARGET: app/Support/Ejemplo.php :: metodoExistente

private function metodoExistente(): void
{
    // nuevo contenido
}</pre>

            <h5>Agregar método PHP</h5>
            <pre>// TARGET: app/Support/Ejemplo.php ++ metodoNuevo

private function metodoNuevo(): void
{
    // contenido
}</pre>
        </div>

        <div class="card help-section">
            <h4>Mensajes de salida</h4>
            <p><span class="lab-status-ok">[OK]</span> Acción ejecutada correctamente.</p>
            <p><span class="lab-status-info">[INFO]</span> Información contextual útil.</p>
            <p><span class="lab-status-warn">[WARN]</span> Advertencia operativa. No bloquea.</p>
            <p><span class="lab-status-error">[ERROR]</span> Error o inconsistencia. Bloquea la acción.</p>
        </div>

        <div class="card help-section">
            <h4>Reglas prácticas</h4>
            <ul>
                <li>Un bloque FILE aplica un solo archivo por ejecución.</li>
                <li>Un TARGET aplica un solo método por ejecución.</li>
                <li>Docs puede aplicar varias secciones si el formato es válido.</li>
                <li>Para pruebas seguras usar <code>documentos/log/</code> o <code>documentos/auditoria/</code>.</li>
                <li>Si se usa <code>++</code> y el método ya existe, Project Lab debe bloquear.</li>
            </ul>
        </div>
    </div>
</div>