<?php

// FILE: tools/project-lab/views/help.php | V2

?>

<div id="tab-help" class="tab-content">
    <div class="card">
        <div class="editor-header">
            <h4>Ayuda Project Lab</h4>
            <span class="shortcuts">Guía rápida de comandos y formatos aceptados</span>
        </div>

        <div class="help-grid">
            <div class="help-card help-card--green">
                <h4>Actualizar código</h4>
                <p>Aplica cambios desde el textarea o desde el clipboard.</p>
                <ul>
                    <li>Archivos completos PHP, Blade, CSS o JS.</li>
                    <li>Reemplazo o agregado de métodos PHP.</li>
                    <li>Reemplazo de funciones JS.</li>
                    <li>Reemplazo de secciones CSS o JS.</li>
                </ul>
            </div>

            <div class="help-card help-card--blue">
                <h4>Actualizar docs</h4>
                <p>Reemplaza secciones completas en documentos técnicos.</p>
                <pre>REEMPLAZAR EN: [contexto_fijo_proyecto_app_base]

&lt;&lt;SECTION: NOMBRE EXACTO&gt;&gt;
SECTION_VERSION: 00001

Contenido
&lt;&lt;END SECTION&gt;&gt;</pre>
            </div>

            <div class="help-card help-card--orange">
                <h4>Ejecutar auditoría</h4>
                <p>Ejecuta bash local desde el projectRoot actual.</p>
                <pre>grep -R "texto" -n app resources routes</pre>
            </div>

            <div class="help-card help-card--red">
                <h4>Tinker</h4>
                <p>Ejecuta código PHP en contexto Laravel con salida y SQL debug.</p>
                <pre>use App\Models\User;

return User::query()-&gt;first();</pre>
            </div>
        </div>

        <div class="card help-section">
            <h4>Mensajes de salida</h4>
            <p><span class="lab-status-ok">[OK]</span> Acción ejecutada correctamente.</p>
            <p><span class="lab-status-info">[INFO]</span> Información contextual útil.</p>
            <p><span class="lab-status-warn">[WARN]</span> Advertencia operativa.</p>
            <p><span class="lab-status-error">[ERROR]</span> Error o inconsistencia.</p>
        </div>
    </div>
</div>