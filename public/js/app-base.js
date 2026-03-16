(function () {
    const dropdownSelectors = [".app-nav-dropdown", ".app-user-dropdown"];

    const getDropdowns = () =>
        dropdownSelectors.flatMap((selector) =>
            Array.from(document.querySelectorAll(selector)),
        );

    const closeDropdown = (dropdown) => {
        if (dropdown) {
            dropdown.removeAttribute("open");
        }
    };

    const closeAllDropdowns = (except = null) => {
        getDropdowns().forEach((dropdown) => {
            if (dropdown !== except) {
                closeDropdown(dropdown);
            }
        });
    };

    const bindConfirmSubmit = () => {
        document
            .querySelectorAll('[data-action~="app-confirm-submit"]')
            .forEach((form) => {
                if (form.dataset.appConfirmSubmitBound === "1") {
                    return;
                }

                form.dataset.appConfirmSubmitBound = "1";

                form.addEventListener("submit", function (event) {
                    const message =
                        this.dataset.confirmMessage || "¿Deseas continuar?";

                    if (!window.confirm(message)) {
                        event.preventDefault();
                    }
                });
            });
    };

    const bindSelectOnClick = () => {
        document
            .querySelectorAll('[data-action~="app-select-on-click"]')
            .forEach((element) => {
                if (element.dataset.appSelectOnClickBound === "1") {
                    return;
                }

                element.dataset.appSelectOnClickBound = "1";

                element.addEventListener("click", function () {
                    if (typeof this.select === "function") {
                        this.select();
                    }

                    if (typeof this.setSelectionRange === "function") {
                        this.setSelectionRange(0, 99999);
                    }
                });
            });
    };

    const copyText = async (text) => {
        if (!text) {
            return false;
        }

        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (_) {
                // fallback abajo
            }
        }

        const temp = document.createElement("input");
        temp.value = text;
        temp.setAttribute("readonly", "readonly");
        temp.style.position = "absolute";
        temp.style.left = "-9999px";

        document.body.appendChild(temp);
        temp.select();
        temp.setSelectionRange(0, 99999);

        let success = false;

        try {
            success = document.execCommand("copy");
        } catch (_) {
            success = false;
        }

        document.body.removeChild(temp);

        return success;
    };

    const applyTemporaryFeedback = (element, successText, resetText) => {
        if (!element) {
            return;
        }

        const originalText =
            element.dataset.originalText || element.textContent;
        element.dataset.originalText = originalText;

        element.textContent = successText || originalText;

        window.setTimeout(() => {
            element.textContent = resetText || originalText;
        }, 1500);
    };

    const bindCopyTarget = () => {
        document
            .querySelectorAll('[data-action~="app-copy-target"]')
            .forEach((button) => {
                if (button.dataset.appCopyTargetBound === "1") {
                    return;
                }

                button.dataset.appCopyTargetBound = "1";

                button.addEventListener("click", async function () {
                    const selector = this.dataset.copyTarget;

                    if (!selector) {
                        return;
                    }

                    const target = document.querySelector(selector);

                    if (!target) {
                        return;
                    }

                    const value = target.value || target.textContent || "";
                    const copied = await copyText(value);

                    if (copied) {
                        applyTemporaryFeedback(
                            this,
                            this.dataset.copyFeedback,
                            this.dataset.copyFeedbackReset,
                        );
                    }
                });
            });
    };

    const bindCopyValue = () => {
        document
            .querySelectorAll('[data-action~="app-copy-value"]')
            .forEach((button) => {
                if (button.dataset.appCopyValueBound === "1") {
                    return;
                }

                button.dataset.appCopyValueBound = "1";

                button.addEventListener("click", async function () {
                    const value = this.dataset.copyValue || "";
                    const copied = await copyText(value);

                    if (copied) {
                        applyTemporaryFeedback(
                            this,
                            this.dataset.copyFeedback,
                            this.dataset.copyFeedbackReset,
                        );
                    }
                });
            });
    };

    const bindPartyAssetSync = () => {
        document
            .querySelectorAll('[data-action~="app-party-asset-sync"]')
            .forEach((root) => {
                if (root.dataset.appPartyAssetSyncBound === "1") {
                    return;
                }

                root.dataset.appPartyAssetSyncBound = "1";

                const partySelector = root.dataset.partySelect;
                const assetSelector = root.dataset.assetSelect;

                if (!partySelector || !assetSelector) {
                    return;
                }

                const partySelect =
                    root.querySelector(partySelector) ||
                    document.querySelector(partySelector);
                const assetSelect =
                    root.querySelector(assetSelector) ||
                    document.querySelector(assetSelector);

                if (!partySelect || !assetSelect) {
                    return;
                }

                const filterAssetsByParty = () => {
                    const selectedPartyId = partySelect.value;

                    Array.from(assetSelect.options).forEach((option, index) => {
                        if (index === 0) {
                            option.hidden = false;
                            return;
                        }

                        const assetPartyId = option.dataset.partyId || "";
                        const shouldShow =
                            !selectedPartyId ||
                            assetPartyId === selectedPartyId;

                        option.hidden = !shouldShow;
                    });

                    const selectedOption =
                        assetSelect.options[assetSelect.selectedIndex];

                    if (
                        selectedOption &&
                        selectedOption.value &&
                        selectedOption.hidden
                    ) {
                        assetSelect.value = "";
                    }
                };

                assetSelect.addEventListener("change", () => {
                    const selected =
                        assetSelect.options[assetSelect.selectedIndex];

                    if (!selected || !selected.value) {
                        return;
                    }

                    const assetPartyId = selected.dataset.partyId || "";

                    if (assetPartyId) {
                        partySelect.value = assetPartyId;
                        filterAssetsByParty();
                    }
                });

                partySelect.addEventListener("change", () => {
                    filterAssetsByParty();
                });

                filterAssetsByParty();
            });
    };

    const bindProductAutofill = () => {
        document
            .querySelectorAll('[data-action~="app-product-autofill"]')
            .forEach((root) => {
                if (root.dataset.appProductAutofillBound === "1") {
                    return;
                }

                root.dataset.appProductAutofillBound = "1";

                const productSelector = root.dataset.productSelect;
                const kindSelector = root.dataset.kindField;
                const descriptionSelector = root.dataset.descriptionField;
                const priceSelector = root.dataset.priceField;

                if (
                    !productSelector ||
                    !kindSelector ||
                    !descriptionSelector ||
                    !priceSelector
                ) {
                    return;
                }

                const productSelect =
                    root.querySelector(productSelector) ||
                    document.querySelector(productSelector);
                const kindField =
                    root.querySelector(kindSelector) ||
                    document.querySelector(kindSelector);
                const descriptionField =
                    root.querySelector(descriptionSelector) ||
                    document.querySelector(descriptionSelector);
                const priceField =
                    root.querySelector(priceSelector) ||
                    document.querySelector(priceSelector);

                if (
                    !productSelect ||
                    !kindField ||
                    !descriptionField ||
                    !priceField
                ) {
                    return;
                }

                const applySelectedProductData = (force = false) => {
                    const selected =
                        productSelect.options[productSelect.selectedIndex];

                    if (!selected || !selected.value) {
                        return;
                    }

                    const kind = selected.dataset.kind || "";
                    const description = selected.dataset.description || "";
                    const price = selected.dataset.price || "";

                    if (kind && (force || !kindField.value)) {
                        kindField.value = kind;
                    }

                    if (description && (force || !descriptionField.value)) {
                        descriptionField.value = description;
                    }

                    if (
                        price !== "" &&
                        (force ||
                            !priceField.value ||
                            Number(priceField.value) === 0)
                    ) {
                        priceField.value = price;
                    }
                };

                productSelect.addEventListener("change", () => {
                    applySelectedProductData(true);
                });

                applySelectedProductData(false);
            });
    };

    const closeAlert = (alert) => {
        if (!alert) {
            return;
        }

        alert.classList.add("is-closing");

        window.setTimeout(() => {
            alert.remove();
        }, 200);
    };

    const bindAlerts = () => {
        document
            .querySelectorAll('[data-action~="app-alert"]')
            .forEach((alert) => {
                if (alert.dataset.appAlertBound === "1") {
                    return;
                }

                alert.dataset.appAlertBound = "1";

                const dismissButton = alert.querySelector(
                    "[data-alert-dismiss]",
                );
                const dismissible = alert.dataset.alertDismissible !== "false";
                const autohide = alert.dataset.alertAutohide === "true";
                const timeout = Number(alert.dataset.alertTimeout || 5000);

                if (dismissButton && dismissible) {
                    dismissButton.addEventListener("click", function () {
                        closeAlert(alert);
                    });
                }

                if (dismissButton && !dismissible) {
                    dismissButton.hidden = true;
                }

                if (autohide && timeout > 0) {
                    window.setTimeout(() => {
                        if (document.body.contains(alert)) {
                            closeAlert(alert);
                        }
                    }, timeout);
                }
            });
    };

    const bindTabs = () => {
        document.querySelectorAll("[data-tabs]").forEach(function (tabsRoot) {
            if (tabsRoot.dataset.appTabsBound === "1") {
                return;
            }

            tabsRoot.dataset.appTabsBound = "1";

            const links = Array.from(
                tabsRoot.querySelectorAll("[data-tab-link]"),
            );
            const panels = Array.from(
                tabsRoot.querySelectorAll("[data-tab-panel]"),
            );

            if (!links.length || !panels.length) {
                return;
            }

            const updateUrlTabParam = function (tabName) {
                const url = new URL(window.location.href);
                url.searchParams.set("tab", tabName);
                window.history.replaceState({}, "", url);
            };

            const activateTab = function (tabName, options = {}) {
                const shouldUpdateUrl = options.updateUrl ?? true;

                links.forEach(function (link) {
                    const isActive = link.dataset.tabLink === tabName;
                    link.classList.toggle("is-active", isActive);
                    link.setAttribute(
                        "aria-selected",
                        isActive ? "true" : "false",
                    );
                });

                panels.forEach(function (panel) {
                    const isActive = panel.dataset.tabPanel === tabName;
                    panel.classList.toggle("is-active", isActive);
                    panel.hidden = !isActive;
                });

                if (shouldUpdateUrl) {
                    updateUrlTabParam(tabName);
                }
            };

            links.forEach(function (link) {
                link.addEventListener("click", function () {
                    activateTab(link.dataset.tabLink, { updateUrl: true });
                });
            });

            const initialTabFromUrl = new URL(
                window.location.href,
            ).searchParams.get("tab");
            const initialActiveLink =
                links.find(
                    (link) => link.dataset.tabLink === initialTabFromUrl,
                ) ||
                links.find((link) => link.classList.contains("is-active")) ||
                links[0];

            activateTab(initialActiveLink.dataset.tabLink, {
                updateUrl: false,
            });
        });
    };

    const bindDropdowns = () => {
        document.addEventListener("click", function (event) {
            const clickedDropdown = event.target.closest(
                ".app-nav-dropdown, .app-user-dropdown",
            );

            if (!clickedDropdown) {
                closeAllDropdowns();
                return;
            }

            closeAllDropdowns(clickedDropdown);
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeAllDropdowns();
            }
        });

        getDropdowns().forEach((dropdown) => {
            dropdown.addEventListener("toggle", function () {
                if (this.hasAttribute("open")) {
                    closeAllDropdowns(this);
                }
            });
        });

        document
            .querySelectorAll(
                ".app-nav-dropdown-menu a, .app-user-dropdown-menu a",
            )
            .forEach(function (link) {
                link.addEventListener("click", function () {
                    closeAllDropdowns();
                });
            });

        document
            .querySelectorAll(".app-user-dropdown-menu form")
            .forEach(function (form) {
                form.addEventListener("submit", function () {
                    closeAllDropdowns();
                });
            });
    };

    const initAppBase = () => {
        bindConfirmSubmit();
        bindSelectOnClick();
        bindCopyTarget();
        bindCopyValue();
        bindPartyAssetSync();
        bindProductAutofill();
        bindAlerts();
        bindTabs();
    };

    bindDropdowns();

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initAppBase);
    } else {
        initAppBase();
    }
})();
