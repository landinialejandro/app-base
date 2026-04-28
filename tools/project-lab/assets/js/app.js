/**
 * PROJECT LAB v8 - Dashboard JavaScript
 * Funcionalidades interactivas del dashboard
 */

// ==================== CONFIGURACIÓN GLOBAL ====================
const CONFIG = {
    csrfToken: document.querySelector('input[name="csrf_token"]')?.value || "",
    baseUrl: window.location.href.split("?")[0],
};

// ==================== NAVEGACIÓN POR PESTAÑAS ====================
function showTab(tabName) {
    // Ocultar todas las pestañas
    document.querySelectorAll(".tab-content").forEach((tab) => {
        tab.style.display = "none";
        tab.classList.remove("active");
    });

    // Mostrar la pestaña seleccionada
    const targetTab = document.getElementById("tab-" + tabName);
    if (targetTab) {
        targetTab.style.display = "block";
        targetTab.classList.add("active");
    }

    // Actualizar botones activos
    document.querySelectorAll(".tab-btn").forEach((btn) => {
        btn.classList.remove("active");
    });

    const activeBtn = document.querySelector(
        `.tab-btn[onclick*="showTab('${tabName}')"]`,
    );
    if (activeBtn) {
        activeBtn.classList.add("active");
    }

    // Guardar pestaña activa en localStorage
    localStorage.setItem("projectLabActiveTab", tabName);
}

// ==================== EDITOR TINKER ====================
function insertCode(code) {
    const textarea = document.getElementById("code");
    if (textarea) {
        textarea.value = code;
        showTab("tinker");
        textarea.focus();
        textarea.setSelectionRange(code.length, code.length);
    }
}

function insertSnippet(snippet) {
    const textarea = document.getElementById("code");
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;

    textarea.value = text.substring(0, start) + snippet + text.substring(end);
    textarea.focus();
    textarea.setSelectionRange(start + snippet.length, start + snippet.length);
}

function clearTinker() {
    const textarea = document.getElementById("code");
    if (textarea) {
        textarea.value = "";
    }
    localStorage.removeItem("projectLabTinkerOutput");
    localStorage.removeItem("projectLabTinkerCode");
    // Eliminar outputs anteriores
    document.querySelectorAll(".output-card").forEach((card) => card.remove());
}

function copyOutput() {
    const output = document.querySelector(".output-card pre");
    if (!output) {
        showNotification("No hay salida para copiar", "warning");
        return;
    }

    copyToClipboard(output.innerText, "Salida copiada al portapapeles");
}

function exportOutput() {
    const output = document.querySelector(".output-card pre")?.innerText;
    if (!output) {
        showNotification("No hay salida para exportar", "warning");
        return;
    }

    const blob = new Blob([output], { type: "text/plain" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, "-");

    a.href = url;
    a.download = `tinker-output-${timestamp}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    showNotification("Archivo exportado correctamente", "success");
}

// ==================== BASE DE DATOS ====================
function loadTableDetails(tableName, element) {
    // Verificar si ya está cargada
    const existingDetails = element.querySelector(".table-details");
    if (existingDetails) {
        existingDetails.style.display =
            existingDetails.style.display === "none" ? "block" : "none";
        return;
    }

    // Crear contenedor de detalles
    const detailsDiv = document.createElement("div");
    detailsDiv.className = "table-details";
    detailsDiv.style.cssText =
        "margin-top:8px; padding:8px; background:var(--bg); border-radius:6px; font-size:11px;";
    detailsDiv.innerHTML = "⏳ Cargando estructura...";
    element.appendChild(detailsDiv);

    // Cargar datos
    const formData = new FormData();
    formData.append("describe_table", tableName);
    formData.append("csrf_token", CONFIG.csrfToken);

    fetch(CONFIG.baseUrl, {
        method: "POST",
        body: formData,
    })
        .then((response) => response.text())
        .then((data) => {
            detailsDiv.innerHTML = data;
        })
        .catch((err) => {
            detailsDiv.innerHTML = "❌ Error al cargar estructura";
            console.error("Error:", err);
        });
}

// ==================== RUTAS ====================
function filterRoutes() {
    const input = document.getElementById("routeSearch");
    const filter = input?.value.toLowerCase() || "";
    const rows = document.querySelectorAll("#routeTable tbody tr.route-row");
    let visibleCount = 0;

    rows.forEach((row) => {
        const text = row.textContent.toLowerCase();
        const isVisible = text.includes(filter);
        row.style.display = isVisible ? "" : "none";
        if (isVisible) visibleCount++;
    });

    // Actualizar contador en el botón de pestaña
    const tabBtn = document.querySelector('.tab-btn[onclick*="routes"]');
    if (tabBtn) {
        const total = rows.length;
        tabBtn.textContent = `🔗 Rutas (${visibleCount}/${total})`;
    }
}

function copyAllRoutes() {
    const rows = document.querySelectorAll("#routeTable tbody tr.route-row");
    let textToCopy = "";

    rows.forEach((row) => {
        if (row.style.display !== "none") {
            const cols = row.querySelectorAll("td");
            const method = cols[0]?.innerText.trim() || "";
            const uri = cols[1]?.innerText.trim() || "";
            const name = cols[2]?.innerText.trim() || "";
            textToCopy += `${method.padEnd(8)} ${uri.padEnd(50)} ${name}\n`;
        }
    });

    if (!textToCopy) {
        showNotification("No hay rutas visibles para copiar", "warning");
        return;
    }

    copyToClipboard(textToCopy, "Rutas copiadas al portapapeles");
}

// ==================== MONITOR ====================
function tailLogs(lines = 50) {
    const logViewer = document.getElementById("logViewer");
    if (!logViewer) return;

    logViewer.innerHTML = `
        <div class="card" style="text-align:center; padding:40px;">
            <div style="font-size:24px; margin-bottom:10px;">⏳</div>
            <div style="color:var(--muted);">Cargando logs...</div>
        </div>
    `;

    const formData = new FormData();
    formData.append("tail_logs", "1");
    formData.append("lines", lines);
    formData.append("csrf_token", CONFIG.csrfToken);

    fetch(CONFIG.baseUrl, {
        method: "POST",
        body: formData,
    })
        .then((response) => response.text())
        .then((data) => {
            logViewer.innerHTML = `
            <div class="card" style="margin-top:20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <h4 style="margin:0;">📝 Últimos ${lines} logs</h4>
                    <div style="display:flex; gap:5px;">
                        <button onclick="tailLogs(50)" class="secondary small">50</button>
                        <button onclick="tailLogs(100)" class="secondary small">100</button>
                        <button onclick="tailLogs(200)" class="secondary small">200</button>
                    </div>
                </div>
                ${data}
            </div>
        `;
            logViewer.scrollIntoView({ behavior: "smooth" });
        })
        .catch((err) => {
            logViewer.innerHTML = `
            <div class="card" style="text-align:center; padding:40px;">
                <div style="font-size:24px; margin-bottom:10px;">❌</div>
                <div style="color:var(--danger);">Error al cargar logs</div>
            </div>
        `;
            console.error("Error:", err);
        });
}

// ==================== SCRIPTS PERSONALIZADOS ====================
function runScript(scriptName) {
    const status = document.getElementById("scriptStatus");
    if (status) {
        status.innerHTML = "⏳ Ejecutando " + scriptName + "...";
    }

    const formData = new FormData();
    formData.append("run_script", scriptName);
    formData.append("csrf_token", CONFIG.csrfToken);

    fetch(CONFIG.baseUrl, {
        method: "POST",
        body: formData,
    })
        .then((response) => response.text())
        .then((data) => {
            // Mostrar resultado en pestaña Tinker
            const tinkerTab = document.getElementById("tab-tinker");

            // Eliminar salida anterior
            const oldOutput = tinkerTab.querySelector(".output-card");
            if (oldOutput) oldOutput.remove();

            // Crear nueva salida
            const outputCard = document.createElement("div");
            outputCard.className = "card output-card";
            outputCard.innerHTML = `
            <div class="output-header">
                <span>📤 Salida de ${scriptName}</span>
                <button onclick="copyOutput()" class="secondary small">Copiar</button>
            </div>
            <pre>${escapeHtml(data)}</pre>
        `;

            tinkerTab.appendChild(outputCard);
            showTab("tinker");

            if (status) {
                status.innerHTML = "✅ " + scriptName + " finalizado.";
            }

            outputCard.scrollIntoView({ behavior: "smooth" });
        })
        .catch((err) => {
            if (status) {
                status.innerHTML = "❌ Error al ejecutar " + scriptName;
            }
            showNotification("Error al ejecutar el script", "error");
            console.error("Error:", err);
        });
}

// ==================== GENERADOR DE MODELOS ====================
function generateModel() {
    const input = document.getElementById("modelName");
    const btn = document.querySelector(".generator-btn");
    const status = document.getElementById("genStatus");

    if (!input || !btn) return;

    const name = input.value.trim();

    // Validación del nombre
    if (!name) {
        input.focus();
        input.style.borderColor = "var(--danger)";
        showStatus("❌ Por favor ingresa un nombre de modelo", "error");
        setTimeout(() => {
            input.style.borderColor = "var(--border)";
        }, 2000);
        return;
    }

    // Validar formato (solo letras)
    if (!/^[a-zA-Z]+$/.test(name)) {
        showStatus(
            "❌ Solo letras permitidas (sin espacios ni números)",
            "error",
        );
        input.style.borderColor = "var(--danger)";
        setTimeout(() => {
            input.style.borderColor = "var(--border)";
        }, 2000);
        return;
    }

    // Estado de carga
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = "⏳ Generando...";
    btn.style.opacity = "0.7";
    showStatus("⏳ Creando modelo " + name + "...", "loading");

    const formData = new FormData();
    formData.append("generate_model", name);
    formData.append("csrf_token", CONFIG.csrfToken);

    fetch(CONFIG.baseUrl, {
        method: "POST",
        body: formData,
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error("Error del servidor: " + response.status);
            }
            return response.text();
        })
        .then((data) => {
            // Éxito
            btn.innerHTML = "✅ ¡Creado!";
            btn.style.background = "var(--success)";
            btn.style.color = "white";
            showStatus("✅ Modelo " + name + " creado exitosamente", "success");

            // Mostrar resultado y recargar
            setTimeout(() => {
                alert("Resultado:\n\n" + data);
                location.reload();
            }, 1000);
        })
        .catch((err) => {
            // Error
            console.error("Error al generar modelo:", err);
            btn.innerHTML = originalText;
            btn.disabled = false;
            btn.style.opacity = "1";
            showStatus("❌ Error al crear el modelo", "error");
        });
}

function showStatus(message, type = "") {
    const status = document.getElementById("genStatus");
    if (!status) return;

    status.textContent = message;
    status.className = "generator-status";

    if (type) {
        status.classList.add(type);
    }

    // Auto-limpiar después de 5 segundos (solo mensajes de éxito/error)
    if (type === "success" || type === "error") {
        setTimeout(() => {
            status.textContent = "";
            status.className = "generator-status";
        }, 5000);
    }
}

// También mejoramos la función para el input
document.addEventListener("DOMContentLoaded", function () {
    const modelInput = document.getElementById("modelName");
    const savedTinkerCode = localStorage.getItem("projectLabTinkerCode");
    const savedTinkerOutput = localStorage.getItem("projectLabTinkerOutput");
    const savedLabInput = localStorage.getItem("projectLabLabInput");
    const savedLabOutput = localStorage.getItem("projectLabLabOutput");

    if (savedLabInput !== null) {
        const labInput = document.getElementById("labInput");
        if (labInput && labInput.value.trim() === "") {
            labInput.value = savedLabInput;
        }
    }

    if (savedLabOutput) {
        ensureLabOutput().textContent = savedLabOutput;
    }

    if (savedTinkerCode !== null) {
        const textarea = document.getElementById("code");
        if (textarea && textarea.value.trim() === "") {
            textarea.value = savedTinkerCode;
        }
    }

    if (savedTinkerOutput) {
        ensureTinkerOutput().textContent = savedTinkerOutput;
    }

    if (modelInput) {
        // Permitir ejecutar con Enter
        modelInput.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                generateModel();
            }
        });

        // Capitalizar primera letra automáticamente
        modelInput.addEventListener("input", function () {
            if (this.value.length === 1) {
                this.value = this.value.toUpperCase();
            }
            // Remover caracteres no permitidos en tiempo real
            this.value = this.value.replace(/[^a-zA-Z]/g, "");
        });

        // Tooltip informativo
        modelInput.title =
            "Nombre del modelo en singular (ej: Product, User, Category)";
    }
});

// ==================== UTILIDADES ====================
function copyToClipboard(text, successMessage) {
    // Método moderno
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard
            .writeText(text)
            .then(() => showNotification(successMessage, "success"))
            .catch(() => fallbackCopy(text, successMessage));
    } else {
        fallbackCopy(text, successMessage);
    }
}

function fallbackCopy(text, successMessage) {
    const textarea = document.createElement("textarea");
    textarea.value = text;
    textarea.style.position = "fixed";
    textarea.style.opacity = "0";
    document.body.appendChild(textarea);
    textarea.select();

    try {
        document.execCommand("copy");
        showNotification(successMessage, "success");
    } catch (err) {
        showNotification("Error al copiar", "error");
    }

    document.body.removeChild(textarea);
}

function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
}

function showNotification(message, type = "info") {
    // Crear elemento de notificación
    const notification = document.createElement("div");

    const colors = {
        success: "var(--success)",
        error: "var(--danger)",
        warning: "var(--warning)",
        info: "var(--accent)",
    };

    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--card);
        color: var(--text);
        padding: 15px 20px;
        border-radius: var(--radius-md);
        border-left: 4px solid ${colors[type]};
        box-shadow: var(--shadow-lg);
        z-index: 9999;
        max-width: 400px;
        animation: slideIn 0.3s ease-out;
        font-size: 13px;
        white-space: pre-line;
    `;

    notification.textContent = message;
    document.body.appendChild(notification);

    // Auto-eliminar después de 4 segundos
    setTimeout(() => {
        notification.style.animation = "slideOut 0.3s ease-in";
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

// ==================== ATAJOS DE TECLADO ====================
document.addEventListener("keydown", function (e) {
    // Ctrl/Cmd + Enter: Ejecutar Tinker
    if ((e.ctrlKey || e.metaKey) && e.key === "Enter") {
        e.preventDefault();
        runTinkerAjax();
    }

    // Ctrl/Cmd + L: Limpiar
    if ((e.ctrlKey || e.metaKey) && e.key === "l") {
        e.preventDefault();
        clearTinker();
    }

    // Ctrl/Cmd + 1-4: Cambiar pestañas
    if (e.ctrlKey || e.metaKey) {
        const tabs = {
            1: "tinker",
            2: "tools",
            3: "database",
            4: "routes",
            5: "monitor",
        };

        if (tabs[e.key]) {
            e.preventDefault();
            showTab(tabs[e.key]);
        }
    }
});

// ==================== CONFIRMACIÓN DE COMANDOS PELIGROSOS ====================
document.addEventListener("DOMContentLoaded", function () {
    // Botones con confirmación
    document.querySelectorAll('[data-danger="true"]').forEach((btn) => {
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();

            const message = this.dataset.confirm || "¿Estás seguro?";
            showConfirmDialog(message, () => {
                // Crear y enviar el formulario
                const form = document.createElement("form");
                form.method = "POST";
                form.style.display = "none";

                const csrfInput = document.createElement("input");
                csrfInput.type = "hidden";
                csrfInput.name = "csrf_token";
                csrfInput.value = CONFIG.csrfToken;

                const actionInput = document.createElement("input");
                actionInput.type = "hidden";
                actionInput.name = "artisan";
                actionInput.value = this.value;

                form.appendChild(csrfInput);
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            });
        });
    });

    // Auto-resize del textarea
    const textarea = document.getElementById("code");
    if (textarea) {
        textarea.addEventListener("input", function () {
            this.style.height = "auto";
            this.style.height =
                Math.min(Math.max(this.scrollHeight, 200), 600) + "px";
        });
    }

    // Restaurar última pestaña activa
    const lastTab = localStorage.getItem("projectLabActiveTab");
    if (lastTab) {
        showTab(lastTab);
    }

    // Tooltips para snippets
    document.querySelectorAll(".snippet-chip").forEach((chip) => {
        chip.title = "Click para insertar: " + chip.textContent;
    });
});

// ==================== DIÁLOGO DE CONFIRMACIÓN ====================
function showConfirmDialog(message, callback) {
    // Eliminar diálogos anteriores
    const oldDialog = document.querySelector(".confirm-overlay");
    if (oldDialog) oldDialog.remove();

    const overlay = document.createElement("div");
    overlay.className = "confirm-overlay";

    overlay.innerHTML = `
        <div class="confirm-dialog">
            <h3>⚠️ Confirmación Requerida</h3>
            <p style="color: var(--muted); margin-bottom: 15px;">${message}</p>
            <input type="text" id="confirmInput" placeholder="Escribe BORRAR para confirmar" autofocus>
            <div class="confirm-dialog-actions">
                <button onclick="this.closest('.confirm-overlay').remove()" class="secondary" style="flex:1;">Cancelar</button>
                <button id="confirmBtn" class="danger" style="flex:1;" disabled>Confirmar</button>
            </div>
        </div>
    `;

    document.body.appendChild(overlay);

    const input = overlay.querySelector("#confirmInput");
    const btn = overlay.querySelector("#confirmBtn");

    input.addEventListener("input", function () {
        btn.disabled = this.value !== "BORRAR";
    });

    btn.addEventListener("click", function () {
        if (input.value === "BORRAR") {
            overlay.remove();
            callback();
        }
    });

    input.addEventListener("keydown", function (e) {
        if (e.key === "Enter" && this.value === "BORRAR") {
            overlay.remove();
            callback();
        }
    });

    // Cerrar al hacer clic fuera
    overlay.addEventListener("click", function (e) {
        if (e.target === overlay) {
            overlay.remove();
        }
    });

    input.focus();
}

// ==================== ANIMACIONES DE SALIDA ====================
const style = document.createElement("style");
style.textContent = `
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(100px); }
        to { opacity: 1; transform: translateX(0); }
    }
    
    @keyframes slideOut {
        from { opacity: 1; transform: translateX(0); }
        to { opacity: 0; transform: translateX(100px); }
    }
`;
document.head.appendChild(style);

// ==================== INICIALIZACIÓN ====================
console.log("🧪 Project Lab v8 - Dashboard inicializado");
console.log(
    "📍 Raíz del proyecto:",
    document.querySelector(".header-info")?.textContent || "No detectada",
);
console.log(
    "⌨️  Atajos: Ctrl+Enter ejecutar, Ctrl+L limpiar, Ctrl+1-4 pestañas",
);

// AGREGAR EN: tools/project-lab/assets/js/app.js

function insertLabSnippet(snippet) {
    const textarea = document.getElementById("labInput");
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;

    textarea.value = text.substring(0, start) + snippet + text.substring(end);
    textarea.focus();
    textarea.setSelectionRange(start + snippet.length, start + snippet.length);
}

function clearLabTools() {
    const textarea = document.getElementById("labInput");
    if (textarea) {
        textarea.value = "";
    }

    const output = document.querySelector("#tab-tools .output-card");
    if (output) {
        output.remove();
    }
    localStorage.removeItem("projectLabLabInput");
    localStorage.removeItem("projectLabLabOutput");
    localStorage.removeItem("projectLabLabActive");
}

function copyLabOutput() {
    const output = document.getElementById("labOutput");

    if (!output) {
        showNotification("No hay salida Lab para copiar", "warning");
        return;
    }

    copyToClipboard(output.innerText, "Salida Lab copiada al portapapeles");
}
function runLabTool(tool, fromClipboard = false) {
    const input = document.getElementById("labInput");
    const formData = new FormData();

    formData.append("csrf_token", CONFIG.csrfToken);
    formData.append("ajax_lab_tool", "1");
    formData.append("lab_tool", tool);
    formData.append("lab_input", input ? input.value : "");

    if (fromClipboard) {
        formData.append("from_clipboard", "1");
    }

    ensureLabOutput().textContent = "⏳ Ejecutando...";

    fetch(CONFIG.baseUrl, {
        method: "POST",
        body: formData,
    })
        .then((response) => response.json())
        .then((data) => {
            const finalInput =
                data.input !== undefined
                    ? data.input
                    : input
                      ? input.value
                      : "";

            const finalOutput = data.output || "Sin salida.";

            if (input) {
                input.value = finalInput;
            }

            ensureLabOutput().textContent = finalOutput;

            localStorage.setItem("projectLabLabInput", finalInput);
            localStorage.setItem("projectLabLabOutput", finalOutput);
            localStorage.setItem("projectLabLabActive", tool);

            showNotification("Herramienta Lab ejecutada", "success");
        })
        .catch((error) => {
            const errorOutput = "❌ Error AJAX: " + error.message;

            ensureLabOutput().textContent = errorOutput;
            localStorage.setItem("projectLabLabOutput", errorOutput);

            showNotification("Error al ejecutar herramienta Lab", "error");
        });
}

function ensureLabOutput() {
    let output = document.getElementById("labOutput");

    if (output) {
        return output;
    }

    const tab = document.getElementById("tab-tools");

    const card = document.createElement("div");
    card.className = "card output-card";
    card.innerHTML = `
        <div class="output-header">
            <span>📤 Salida Herramientas Lab</span>
            <button onclick="copyLabOutput()" class="secondary small">Copiar</button>
        </div>
        <pre id="labOutput"></pre>
    `;

    tab.appendChild(card);

    return document.getElementById("labOutput");
}
function runTinkerAjax() {
    const textarea = document.getElementById("code");
    const formData = new FormData();

    formData.append("csrf_token", CONFIG.csrfToken);
    formData.append("ajax_tinker", "1");
    formData.append("code", textarea ? textarea.value : "");

    ensureTinkerOutput().textContent = "⏳ Ejecutando Tinker...";

    fetch(CONFIG.baseUrl, {
        method: "POST",
        body: formData,
    })
        .then((response) => response.json())
        .then((data) => {
            const finalCode =
                data.code !== undefined
                    ? data.code
                    : textarea
                      ? textarea.value
                      : "";

            const finalOutput = data.output || "Sin salida.";

            if (textarea) {
                textarea.value = finalCode;
            }

            ensureTinkerOutput().textContent = finalOutput;

            localStorage.setItem("projectLabTinkerCode", finalCode);
            localStorage.setItem("projectLabTinkerOutput", finalOutput);
            localStorage.setItem(
                "projectLabTinkerAt",
                new Date().toISOString(),
            );

            showNotification(
                "Tinker ejecutado",
                data.ok ? "success" : "warning",
            );
        })
        .catch((error) => {
            const errorOutput = "❌ Error AJAX: " + error.message;

            ensureTinkerOutput().textContent = errorOutput;
            localStorage.setItem("projectLabTinkerOutput", errorOutput);
            localStorage.setItem(
                "projectLabTinkerAt",
                new Date().toISOString(),
            );

            showNotification("Error al ejecutar Tinker", "error");
        });
}

function ensureTinkerOutput() {
    let output = document.getElementById("tinkerOutput");

    if (output) {
        return output;
    }

    const tab = document.getElementById("tab-tinker");

    const card = document.createElement("div");
    card.className = "card output-card";
    card.innerHTML = `
        <div class="output-header">
            <span>📤 Resultado</span>
            <button onclick="copyOutput()" class="secondary small">Copiar</button>
        </div>
        <pre id="tinkerOutput"></pre>
    `;

    tab.appendChild(card);

    return document.getElementById("tinkerOutput");
}
