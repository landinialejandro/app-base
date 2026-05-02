// FILE: tools/project-lab/assets/js/app.js | V4

const CONFIG = {
    csrfToken: document.querySelector('input[name="csrf_token"]')?.value || "",
    baseUrl: window.location.href.split("?")[0],
};

const PROJECT_CONSOLE_STORAGE_KEY = "projectLabConsoleOutput";
const PROJECT_CONSOLE_STORAGE_AT_KEY = "projectLabConsoleAt";

// ==================== TABS ====================

function showTab(tabName) {
    document.querySelectorAll(".tab-content").forEach((tab) => {
        tab.style.display = "none";
        tab.classList.remove("active");
    });

    const targetTab = document.getElementById("tab-" + tabName);

    if (targetTab) {
        targetTab.style.display = "block";
        targetTab.classList.add("active");
    }

    document.querySelectorAll(".tab-btn").forEach((btn) => {
        btn.classList.remove("active");
    });

    const activeBtn = document.querySelector(
        `.tab-btn[onclick*="showTab('${tabName}')"]`,
    );

    if (activeBtn) {
        activeBtn.classList.add("active");
    }

    localStorage.setItem("projectLabActiveTab", tabName);
}

// ==================== CONSOLA GLOBAL ====================

function ensureProjectConsoleOutput() {
    let output = document.getElementById("projectConsoleOutput");

    if (output) {
        return output;
    }

    const main = document.querySelector("main");

    const card = document.createElement("div");
    card.className = "card output-card";
    card.id = "projectConsoleCard";
    card.innerHTML = `
        <div class="output-header">
            <span>📤 Consola Project Lab</span>
            <div style="display:flex; gap:6px;">
                <button onclick="copyProjectConsoleOutput()" class="secondary small">Copiar</button>
                <button onclick="clearProjectConsoleOutput()" class="danger small">Borrar</button>
            </div>
        </div>
        <pre id="projectConsoleOutput"></pre>
    `;

    main.appendChild(card);

    return document.getElementById("projectConsoleOutput");
}

function setProjectConsoleOutput(text) {
    const output = ensureProjectConsoleOutput();

    output.innerHTML = colorizeProjectOutput(text);

    localStorage.setItem(PROJECT_CONSOLE_STORAGE_KEY, text);
    localStorage.setItem(
        PROJECT_CONSOLE_STORAGE_AT_KEY,
        new Date().toISOString(),
    );

    return output;
}

function appendProjectConsoleOutput(text) {
    const previous = localStorage.getItem(PROJECT_CONSOLE_STORAGE_KEY) || "";
    const separator = previous.trim() === "" ? "" : "\n\n";

    const next = previous + separator + text;

    const output = ensureProjectConsoleOutput();
    output.innerHTML = colorizeProjectOutput(next);

    requestAnimationFrame(() => {
        output.scrollTop = output.scrollHeight;
    });

    localStorage.setItem(PROJECT_CONSOLE_STORAGE_KEY, next);
    localStorage.setItem(
        PROJECT_CONSOLE_STORAGE_AT_KEY,
        new Date().toISOString(),
    );

    return output;
}

function copyProjectConsoleOutput() {
    const output = document.getElementById("projectConsoleOutput");

    if (!output || output.innerText.trim() === "") {
        showNotification("No hay salida para copiar", "warning");
        return;
    }

    copyToClipboard(output.innerText, "Salida copiada al portapapeles");
}

function clearProjectConsoleOutput() {
    const output = document.getElementById("projectConsoleOutput");

    if (output) {
        output.innerHTML = "";
    }

    localStorage.removeItem(PROJECT_CONSOLE_STORAGE_KEY);
    localStorage.removeItem(PROJECT_CONSOLE_STORAGE_AT_KEY);

    showNotification("Consola borrada", "success");
}

// Compatibilidad con botones viejos
function ensureTinkerOutput() {
    return ensureProjectConsoleOutput();
}

function ensureLabOutput() {
    return ensureProjectConsoleOutput();
}

function copyOutput() {
    copyProjectConsoleOutput();
}

function copyLabOutput() {
    copyProjectConsoleOutput();
}

// ==================== AJAX RUNNER GENERAL ====================

function runProjectAction(config) {
    const formData = new FormData();

    formData.append("csrf_token", CONFIG.csrfToken);
    formData.append(config.action, "1");

    Object.entries(config.data || {}).forEach(([key, value]) => {
        formData.append(key, value);
    });

    const output = ensureProjectConsoleOutput();
    output.textContent = config.loading || "⏳ Ejecutando...";

    fetch(CONFIG.baseUrl, {
        method: "POST",
        body: formData,
        headers: {
            "X-Requested-With": "XMLHttpRequest",
        },
    })
        .then(async (response) => {
            const raw = await response.text();

            let data = null;

            try {
                data = raw ? JSON.parse(raw) : null;
            } catch (error) {
                appendProjectConsoleOutput(
                    "[ERROR] La respuesta no es JSON válido.\n\n" + raw,
                );
                return;
            }

            if (!response.ok) {
                appendProjectConsoleOutput(
                    "[ERROR] HTTP " +
                        response.status +
                        "\n\n" +
                        (data?.output || raw || ""),
                );
                return;
            }

            const hasOutput =
                data && Object.prototype.hasOwnProperty.call(data, "output");
            const finalOutput =
                hasOutput && typeof data.output === "string"
                    ? data.output.trim()
                    : "";

            if (finalOutput !== "") {
                appendProjectConsoleOutput(finalOutput);
                return;
            }

            const diagnostic = [
                "[WARN] Acción ejecutada sin salida visible.",
                "[INFO] Acción: " + config.action,
                data && data.ok === false
                    ? "[INFO] Estado: ok=false"
                    : "[INFO] Estado: ok=" + String(data?.ok ?? "sin dato"),
                data && data.command ? "[INFO] Comando: " + data.command : null,
                data && data.code
                    ? "[INFO] Código recibido: " + data.code.substring(0, 200)
                    : null,
                "[INFO] Respuesta cruda:",
                raw || "(vacía)",
            ]
                .filter(Boolean)
                .join("\n");

            appendProjectConsoleOutput(diagnostic);
        })
        .catch((error) => {
            const errorOutput = "[ERROR] Falló la comunicación AJAX.\n" + error;
            appendProjectConsoleOutput(errorOutput);
        });
}

// ==================== TINKER / ARTISAN ====================

function runTinkerAjax() {
    const textarea = document.getElementById("code");

    runProjectAction({
        action: "ajax_tinker",
        payload: {
            code: textarea ? textarea.value : "",
        },
        loading: "⏳ Ejecutando Tinker...",
        success: "Tinker ejecutado",
        error: "Error al ejecutar Tinker",
        onSuccess(data) {
            const finalCode =
                data.code !== undefined
                    ? data.code
                    : textarea
                      ? textarea.value
                      : "";

            if (textarea) {
                textarea.value = finalCode;
            }

            localStorage.setItem("projectLabTinkerCode", finalCode);
        },
    });
}

function runTinkerFromClipboard() {
    runProjectAction({
        action: "ajax_tinker_from_clipboard",
        payload: {},
        loading: "⏳ Ejecutando Tinker desde clipboard...",
        success: "Tinker ejecutado desde clipboard",
        error: "Error al ejecutar Tinker desde clipboard",
        onSuccess(data) {
            const textarea = document.getElementById("code");

            if (textarea && data.code !== undefined) {
                textarea.value = data.code;
                localStorage.setItem("projectLabTinkerCode", data.code);
            }
        },
    });
}

function runArtisanAjax(command) {
    runProjectAction({
        action: "ajax_artisan",
        payload: {
            artisan: command,
        },
        loading: "⏳ Ejecutando artisan " + command + "...",
        success: "Artisan ejecutado: " + command,
        error: "Error al ejecutar Artisan",
        onSuccess() {
            localStorage.setItem(
                "projectLabTinkerCode",
                "// artisan " + command,
            );
        },
    });
}

function clearTinker() {
    const textarea = document.getElementById("code");

    if (textarea) {
        textarea.value = "";
    }

    localStorage.removeItem("projectLabTinkerCode");

    showNotification("Editor Tinker borrado", "success");
}

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

    insertIntoTextarea(textarea, snippet);
}

function bindArtisanAjaxButtons() {
    document
        .querySelectorAll('.artisan-form button[name="artisan"]')
        .forEach((btn) => {
            btn.addEventListener("click", function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                const command = this.value || "";

                if (!command) {
                    showNotification("Comando Artisan vacío", "warning");
                    return;
                }

                if (this.dataset.danger === "true") {
                    const message = this.dataset.confirm || "¿Estás seguro?";

                    showConfirmDialog(message, () => {
                        runArtisanAjax(command);
                    });

                    return;
                }

                runArtisanAjax(command);
            });
        });
}

function exportOutput() {
    const output = document.getElementById("projectConsoleOutput")?.innerText;

    if (!output) {
        showNotification("No hay salida para exportar", "warning");
        return;
    }

    const blob = new Blob([output], { type: "text/plain" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, "-");

    a.href = url;
    a.download = `project-lab-output-${timestamp}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    showNotification("Archivo exportado correctamente", "success");
}

// ==================== HERRAMIENTAS LAB ====================

function runLabTool(tool, fromClipboard = false) {
    const allowedTools = ["code", "docs", "audit"];

    if (!allowedTools.includes(tool)) {
        appendProjectConsoleOutput(
            "[ERROR] Herramienta Lab inválida en frontend: " + String(tool),
        );
        return;
    }

    const input = document.querySelector('[name="lab_input"]');
    const labInput = input ? input.value : "";

    runProjectAction({
        action: "ajax_lab_tool",
        loading: "⏳ Ejecutando herramienta Lab: " + tool + "...",
        data: {
            lab_tool: tool,
            from_clipboard: fromClipboard ? "1" : "0",
            lab_input: labInput,
        },
    });
}

function insertLabSnippet(snippet) {
    const textarea = document.getElementById("labInput");

    if (!textarea) return;

    insertIntoTextarea(textarea, snippet);
}

function clearLabTools() {
    const textarea = document.getElementById("labInput");

    if (textarea) {
        textarea.value = "";
    }

    localStorage.removeItem("projectLabLabInput");
    localStorage.removeItem("projectLabLabActive");

    showNotification("Herramientas Lab borradas", "success");
}

// ==================== BASE DE DATOS ====================

function loadTableDetails(tableName, element) {
    const existingDetails = element.querySelector(".table-details");

    if (existingDetails) {
        existingDetails.style.display =
            existingDetails.style.display === "none" ? "block" : "none";
        return;
    }

    const detailsDiv = document.createElement("div");
    detailsDiv.className = "table-details";
    detailsDiv.style.cssText =
        "margin-top:8px; padding:8px; background:var(--bg); border-radius:6px; font-size:11px;";
    detailsDiv.innerHTML = "⏳ Cargando estructura...";
    element.appendChild(detailsDiv);

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

// ==================== SCRIPTS ====================

function runScript(scriptName) {
    const status = document.getElementById("scriptStatus");

    if (status) {
        status.innerHTML = "⏳ Ejecutando " + scriptName + "...";
    }

    const output = ensureProjectConsoleOutput();
    output.textContent = "⏳ Ejecutando " + scriptName + "...";

    const formData = new FormData();

    formData.append("run_script", scriptName);
    formData.append("csrf_token", CONFIG.csrfToken);

    fetch(CONFIG.baseUrl, {
        method: "POST",
        body: formData,
    })
        .then((response) => response.text())
        .then((data) => {
            appendProjectConsoleOutput(data);

            localStorage.setItem(
                "projectLabTinkerCode",
                "// script " + scriptName,
            );

            showTab("tinker");

            if (status) {
                status.innerHTML = "✅ " + scriptName + " finalizado.";
            }

            ensureProjectConsoleOutput().scrollIntoView({ behavior: "smooth" });
        })
        .catch((err) => {
            const errorOutput = "❌ Error al ejecutar " + scriptName;

            appendProjectConsoleOutput(errorOutput);

            if (status) {
                status.innerHTML = errorOutput;
            }

            showNotification("Error al ejecutar el script", "error");
            console.error("Error:", err);
        });
}

// ==================== GENERADOR DE MODELOS ====================

function generateModel() {
    const input = document.getElementById("modelName");
    const btn = document.querySelector(".generator-btn");

    if (!input || !btn) return;

    const name = input.value.trim();

    if (!name) {
        input.focus();
        input.style.borderColor = "var(--danger)";
        showStatus("❌ Por favor ingresa un nombre de modelo", "error");

        setTimeout(() => {
            input.style.borderColor = "var(--border)";
        }, 2000);

        return;
    }

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
            btn.innerHTML = "✅ ¡Creado!";
            btn.style.background = "var(--success)";
            btn.style.color = "white";

            appendProjectConsoleOutput(data);
            showStatus("✅ Modelo " + name + " creado exitosamente", "success");
        })
        .catch((err) => {
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

    if (type === "success" || type === "error") {
        setTimeout(() => {
            status.textContent = "";
            status.className = "generator-status";
        }, 5000);
    }
}

// ==================== UTILIDADES ====================

function insertIntoTextarea(textarea, snippet) {
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;

    textarea.value = text.substring(0, start) + snippet + text.substring(end);
    textarea.focus();
    textarea.setSelectionRange(start + snippet.length, start + snippet.length);
}

function copyToClipboard(text, successMessage) {
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

function colorizeProjectOutput(text) {
    return escapeHtml(text).replace(
        /^(\[(OK|ERROR|WARN|INFO)\])/gm,
        (match, full, type) => {
            const className = "lab-status-" + type.toLowerCase();

            return `<span class="${className}">${full}</span>`;
        },
    );
}

function showNotification(message, type = "info") {
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

    setTimeout(() => {
        notification.style.animation = "slideOut 0.3s ease-in";
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

function showConfirmDialog(message, callback) {
    const oldDialog = document.querySelector(".confirm-overlay");

    if (oldDialog) {
        oldDialog.remove();
    }

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

    overlay.addEventListener("click", function (e) {
        if (e.target === overlay) {
            overlay.remove();
        }
    });

    input.focus();
}

// <<SECTION: SECTION_TEMPLATE_COPY>>

function copySectionTemplate(template) {
    copyToClipboard(template, "Plantilla de sección copiada al portapapeles");
}

// <<END SECTION>>

// ==================== INICIALIZACIÓN ====================

document.addEventListener("DOMContentLoaded", function () {
    bindArtisanAjaxButtons();

    document.querySelectorAll('[data-danger="true"]').forEach((btn) => {
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();

            const message = this.dataset.confirm || "¿Estás seguro?";

            showConfirmDialog(message, () => {
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

    const textarea = document.getElementById("code");

    if (textarea) {
        textarea.addEventListener("input", function () {
            this.style.height = "auto";
            this.style.height =
                Math.min(Math.max(this.scrollHeight, 200), 600) + "px";
        });
    }

    const savedTinkerCode = localStorage.getItem("projectLabTinkerCode");

    if (savedTinkerCode !== null && textarea) {
        textarea.value = savedTinkerCode;
    }

    const labInput = document.getElementById("labInput");
    const savedLabInput = localStorage.getItem("projectLabLabInput");

    if (savedLabInput !== null && labInput) {
        labInput.value = savedLabInput;
    }

    const savedConsoleOutput = localStorage.getItem(
        PROJECT_CONSOLE_STORAGE_KEY,
    );

    if (savedConsoleOutput !== null && savedConsoleOutput !== "") {
        setProjectConsoleOutput(savedConsoleOutput);
    }

    const lastTab = localStorage.getItem("projectLabActiveTab");

    if (lastTab) {
        showTab(lastTab);
    }

    document.querySelectorAll(".snippet-chip").forEach((chip) => {
        chip.title = "Click para insertar: " + chip.textContent;
    });

    const modelInput = document.getElementById("modelName");

    if (modelInput) {
        modelInput.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                generateModel();
            }
        });

        modelInput.addEventListener("input", function () {
            if (this.value.length === 1) {
                this.value = this.value.toUpperCase();
            }

            this.value = this.value.replace(/[^a-zA-Z]/g, "");
        });

        modelInput.title =
            "Nombre del modelo en singular (ej: Product, User, Category)";
    }
});

// ==================== ATAJOS ====================

document.addEventListener("keydown", function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key === "Enter") {
        e.preventDefault();
        runTinkerAjax();
    }

    if ((e.ctrlKey || e.metaKey) && e.key === "l") {
        e.preventDefault();
        clearTinker();
    }

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

// ==================== ANIMACIONES ====================

function resetProjectRateLimit() {
    runProjectAction({
        action: "ajax_rate_limit_reset",
        loading: "⏳ Reiniciando rate limit...",
    });
}

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

console.log("🧪 Project Lab - JS inicializado");
