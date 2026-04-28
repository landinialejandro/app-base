// FILE: tools/project-lab/assets/js/app.js | V2

const CONFIG = {
    csrfToken: document.querySelector('input[name="csrf_token"]')?.value || "",
    baseUrl: window.location.href.split("?")[0],
};

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

// ==================== AJAX RUNNER GENERAL ====================

function runProjectAction(config) {
    const formData = new FormData();

    formData.append("csrf_token", CONFIG.csrfToken);
    formData.append(config.action, "1");

    Object.entries(config.payload || {}).forEach(([key, value]) => {
        formData.append(key, value);
    });

    const output = config.ensureOutput();
    output.textContent = config.loading || "⏳ Ejecutando...";

    fetch(CONFIG.baseUrl, {
        method: "POST",
        body: formData,
    })
        .then((response) => response.json())
        .then((data) => {
            const finalOutput = data.output || "Sin salida.";

            output.innerHTML = colorizeProjectOutput(finalOutput);

            if (config.persistKey) {
                localStorage.setItem(config.persistKey + "Output", finalOutput);
                localStorage.setItem(
                    config.persistKey + "At",
                    new Date().toISOString(),
                );
            }

            if (config.onSuccess) {
                config.onSuccess(data, finalOutput);
            }

            showNotification(
                config.success || "Acción ejecutada",
                data.ok ? "success" : "warning",
            );
        })
        .catch((error) => {
            const errorOutput = "❌ Error AJAX: " + error.message;

            output.innerHTML = colorizeProjectOutput(errorOutput);

            if (config.persistKey) {
                localStorage.setItem(config.persistKey + "Output", errorOutput);
                localStorage.setItem(
                    config.persistKey + "At",
                    new Date().toISOString(),
                );
            }

            showNotification(
                config.error || "Error al ejecutar acción",
                "error",
            );
        });
}

// ==================== TINKER ====================

function runTinkerAjax() {
    const textarea = document.getElementById("code");

    runProjectAction({
        action: "ajax_tinker",
        payload: {
            code: textarea ? textarea.value : "",
        },
        ensureOutput: ensureTinkerOutput,
        loading: "⏳ Ejecutando Tinker...",
        success: "Tinker ejecutado",
        error: "Error al ejecutar Tinker",
        persistKey: "projectLabTinker",
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

function runArtisanAjax(command) {
    runProjectAction({
        action: "ajax_artisan",
        payload: {
            artisan: command,
        },
        ensureOutput: ensureTinkerOutput,
        loading: "⏳ Ejecutando artisan " + command + "...",
        success: "Artisan ejecutado: " + command,
        error: "Error al ejecutar Artisan",
        persistKey: "projectLabTinker",
        onSuccess() {
            localStorage.setItem(
                "projectLabTinkerCode",
                "// artisan " + command,
            );
        },
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

    const output = document.querySelector("#tab-tinker .output-card");

    if (output) {
        output.remove();
    }

    localStorage.removeItem("projectLabTinkerCode");
    localStorage.removeItem("projectLabTinkerOutput");
    localStorage.removeItem("projectLabTinkerAt");
}

function copyOutput() {
    const output = document.getElementById("tinkerOutput");

    if (!output) {
        showNotification("No hay salida para copiar", "warning");
        return;
    }

    copyToClipboard(output.innerText, "Salida copiada al portapapeles");
}

function exportOutput() {
    const output = document.getElementById("tinkerOutput")?.innerText;

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

// ==================== HERRAMIENTAS LAB ====================

function runLabTool(tool, fromClipboard = false) {
    const input = document.getElementById("labInput");

    const payload = {
        lab_tool: tool,
        lab_input: input ? input.value : "",
    };

    if (fromClipboard) {
        payload.from_clipboard = "1";
    }

    runProjectAction({
        action: "ajax_lab_tool",
        payload,
        ensureOutput: ensureLabOutput,
        loading: "⏳ Ejecutando herramienta Lab...",
        success: "Herramienta Lab ejecutada",
        error: "Error al ejecutar herramienta Lab",
        persistKey: "projectLabLab",
        onSuccess(data) {
            const finalInput =
                data.input !== undefined
                    ? data.input
                    : input
                      ? input.value
                      : "";

            if (input) {
                input.value = finalInput;
            }

            localStorage.setItem("projectLabLabInput", finalInput);
            localStorage.setItem("projectLabLabActive", tool);
        },
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
    localStorage.removeItem("projectLabLabAt");
}

function copyLabOutput() {
    const output = document.getElementById("labOutput");

    if (!output) {
        showNotification("No hay salida Lab para copiar", "warning");
        return;
    }

    copyToClipboard(output.innerText, "Salida Lab copiada al portapapeles");
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

    const formData = new FormData();
    formData.append("run_script", scriptName);
    formData.append("csrf_token", CONFIG.csrfToken);

    fetch(CONFIG.baseUrl, {
        method: "POST",
        body: formData,
    })
        .then((response) => response.text())
        .then((data) => {
            const output = ensureTinkerOutput();

            output.textContent = data;

            localStorage.setItem("projectLabTinkerOutput", data);
            localStorage.setItem(
                "projectLabTinkerCode",
                "// script " + scriptName,
            );
            localStorage.setItem(
                "projectLabTinkerAt",
                new Date().toISOString(),
            );

            showTab("tinker");

            if (status) {
                status.innerHTML = "✅ " + scriptName + " finalizado.";
            }

            output.scrollIntoView({ behavior: "smooth" });
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

            showStatus("✅ Modelo " + name + " creado exitosamente", "success");

            setTimeout(() => {
                alert("Resultado:\n\n" + data);
                location.reload();
            }, 1000);
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

// ==================== INICIALIZACIÓN ====================

document.addEventListener("DOMContentLoaded", function () {
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
    const savedTinkerOutput = localStorage.getItem("projectLabTinkerOutput");

    if (savedTinkerCode !== null && textarea) {
        textarea.value = savedTinkerCode;
    }

    if (savedTinkerOutput !== null && savedTinkerOutput !== "") {
        ensureTinkerOutput().innerHTML =
            colorizeProjectOutput(savedTinkerOutput);
    }

    const savedLabInput = localStorage.getItem("projectLabLabInput");
    const savedLabOutput = localStorage.getItem("projectLabLabOutput");

    const labInput = document.getElementById("labInput");

    if (savedLabInput !== null && labInput) {
        labInput.value = savedLabInput;
    }

    if (savedLabOutput !== null && savedLabOutput !== "") {
        ensureLabOutput().innerHTML = colorizeProjectOutput(savedLabOutput);
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
