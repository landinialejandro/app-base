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

    const bindToggleDetails = () => {
        document
            .querySelectorAll('[data-action~="app-toggle-details"]')
            .forEach((button) => {
                if (button.dataset.appToggleDetailsBound === "1") {
                    return;
                }

                button.dataset.appToggleDetailsBound = "1";

                button.addEventListener("click", function () {
                    const selector = this.dataset.toggleTarget;

                    if (!selector) {
                        return;
                    }

                    const panel = document.querySelector(selector);

                    if (!panel) {
                        return;
                    }

                    const isHidden = panel.hasAttribute("hidden");

                    if (isHidden) {
                        panel.removeAttribute("hidden");
                        if (this.dataset.toggleTextExpanded) {
                            this.textContent = this.dataset.toggleTextExpanded;
                        }
                    } else {
                        panel.setAttribute("hidden", "hidden");
                        if (this.dataset.toggleTextCollapsed) {
                            this.textContent = this.dataset.toggleTextCollapsed;
                        }
                    }
                });
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
            ).filter(function (link) {
                return link.closest("[data-tabs]") === tabsRoot;
            });

            const panels = Array.from(
                tabsRoot.querySelectorAll("[data-tab-panel]"),
            ).filter(function (panel) {
                return panel.parentElement.closest("[data-tabs]") === tabsRoot;
            });

            if (!links.length || !panels.length) {
                return;
            }

            const activateTab = function (tabName) {
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
            };

            links.forEach(function (link) {
                link.addEventListener("click", function () {
                    activateTab(link.dataset.tabLink);
                });
            });

            const initialActiveLink =
                links.find((link) => link.classList.contains("is-active")) ||
                links[0];

            activateTab(initialActiveLink.dataset.tabLink);
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

    const bindAppointmentPartyAssetSync = () => {
        document
            .querySelectorAll(
                '[data-action~="app-appointment-party-asset-sync"]',
            )
            .forEach((root) => {
                if (root.dataset.appAppointmentPartyAssetSyncBound === "1") {
                    return;
                }

                root.dataset.appAppointmentPartyAssetSyncBound = "1";

                const partySelect = root.querySelector("#party_id");
                const assetSelect = root.querySelector("#asset_id");
                const startField = root.querySelector("#starts_at");
                const endField = root.querySelector("#ends_at");

                if (!partySelect || !assetSelect) {
                    return;
                }

                const assetOptions = Array.from(assetSelect.options).map(
                    (option) => ({
                        value: option.value,
                        label: option.textContent,
                        partyId: option.dataset.partyId || "",
                    }),
                );

                const rebuildAssets = (partyId, selectedValue = "") => {
                    assetSelect.innerHTML = "";

                    const emptyOption = document.createElement("option");
                    emptyOption.value = "";
                    emptyOption.textContent = "Sin activo asociado";
                    assetSelect.appendChild(emptyOption);

                    const matchingAssets = assetOptions.filter((option) => {
                        if (!option.value) {
                            return false;
                        }

                        if (!partyId) {
                            return true;
                        }

                        return option.partyId === partyId;
                    });

                    matchingAssets.forEach((optionData) => {
                        const option = document.createElement("option");
                        option.value = optionData.value;
                        option.textContent = optionData.label;
                        option.dataset.partyId = optionData.partyId;

                        if (
                            selectedValue &&
                            selectedValue === optionData.value
                        ) {
                            option.selected = true;
                        }

                        assetSelect.appendChild(option);
                    });

                    return matchingAssets;
                };

                const syncAssetsFromParty = () => {
                    const partyId = partySelect.value;
                    const currentAssetValue = assetSelect.value;

                    const matchingAssets = rebuildAssets(
                        partyId,
                        currentAssetValue,
                    );

                    if (!partyId) {
                        return;
                    }

                    if (matchingAssets.length === 1) {
                        assetSelect.value = matchingAssets[0].value;
                    }
                };

                const syncPartyFromAsset = () => {
                    const selectedOption =
                        assetSelect.options[assetSelect.selectedIndex];

                    if (!selectedOption || !selectedOption.value) {
                        return;
                    }

                    const assetPartyId = selectedOption.dataset.partyId || "";

                    if (!assetPartyId) {
                        return;
                    }

                    partySelect.value = assetPartyId;
                    rebuildAssets(assetPartyId, selectedOption.value);
                    assetSelect.value = selectedOption.value;
                };

                const fillEndAtFromStartAt = () => {
                    if (!startField || !endField) {
                        return;
                    }

                    if (!startField.value || endField.value) {
                        return;
                    }

                    const startDate = new Date(startField.value);

                    if (Number.isNaN(startDate.getTime())) {
                        return;
                    }

                    const endDate = new Date(
                        startDate.getTime() + 2 * 60 * 60 * 1000,
                    );

                    const pad = (value) => String(value).padStart(2, "0");

                    const formatted =
                        [
                            endDate.getFullYear(),
                            pad(endDate.getMonth() + 1),
                            pad(endDate.getDate()),
                        ].join("-") +
                        "T" +
                        [
                            pad(endDate.getHours()),
                            pad(endDate.getMinutes()),
                        ].join(":");

                    endField.value = formatted;
                };

                partySelect.addEventListener("change", syncAssetsFromParty);
                assetSelect.addEventListener("change", syncPartyFromAsset);

                if (startField && endField) {
                    startField.addEventListener("change", fillEndAtFromStartAt);
                }

                if (assetSelect.value) {
                    syncPartyFromAsset();
                } else {
                    syncAssetsFromParty();
                }
            });
    };

    const bindAppointmentKindSync = () => {
        document
            .querySelectorAll('[data-action~="app-appointment-kind-sync"]')
            .forEach((root) => {
                if (root.dataset.appAppointmentKindSyncBound === "1") {
                    return;
                }

                root.dataset.appAppointmentKindSyncBound = "1";

                const kindField = root.querySelector("#kind");
                const workModeField = root.querySelector("#work_mode");
                const referenceLabel = root.querySelector(
                    "#workstation_name_label",
                );

                if (!kindField || !workModeField || !referenceLabel) {
                    return;
                }

                const applyKindRules = () => {
                    const kind = kindField.value;

                    if (kind === "service") {
                        workModeField.value = "in_shop";
                        referenceLabel.textContent = "Ubicación en taller";
                    } else if (kind === "visit") {
                        workModeField.value = "on_site";
                        referenceLabel.textContent = "Dirección";
                    } else if (kind === "block") {
                        referenceLabel.textContent = "Referencia";
                    } else {
                        referenceLabel.textContent = "Referencia";
                    }
                };

                kindField.addEventListener("change", applyKindRules);
                applyKindRules();
            });
    };

    const bindAppointmentCalendarAutoScroll = () => {
        document
            .querySelectorAll("[data-appointment-calendar-scroll]")
            .forEach((scrollRoot) => {
                if (
                    scrollRoot.dataset.appAppointmentCalendarAutoScrollBound ===
                    "1"
                ) {
                    return;
                }

                scrollRoot.dataset.appAppointmentCalendarAutoScrollBound = "1";

                const todayCell = scrollRoot.querySelector(
                    "[data-calendar-today-cell]",
                );

                if (!todayCell) {
                    return;
                }

                const targetLeft = Math.max(todayCell.offsetLeft - 12, 0);
                const targetTop = Math.max(todayCell.offsetTop - 12, 0);

                scrollRoot.scrollLeft = targetLeft;
                scrollRoot.scrollTop = targetTop;
            });
    };

    const bindHorizontalScroll = () => {
        document
            .querySelectorAll("[data-horizontal-scroll]")
            .forEach((root) => {
                if (root.dataset.appHorizontalScrollBound === "1") {
                    return;
                }

                root.dataset.appHorizontalScrollBound = "1";

                const viewport = root.querySelector(
                    "[data-horizontal-scroll-viewport]",
                );
                const prevButton = root.querySelector(
                    "[data-horizontal-scroll-prev]",
                );
                const nextButton = root.querySelector(
                    "[data-horizontal-scroll-next]",
                );

                if (!viewport || !prevButton || !nextButton) {
                    return;
                }

                const updateButtons = () => {
                    const maxScrollLeft = Math.max(
                        0,
                        viewport.scrollWidth - viewport.clientWidth,
                    );

                    const canScroll = maxScrollLeft > 4;

                    if (!canScroll) {
                        prevButton.hidden = true;
                        nextButton.hidden = true;
                        return;
                    }

                    prevButton.hidden = viewport.scrollLeft <= 4;
                    nextButton.hidden =
                        viewport.scrollLeft >= maxScrollLeft - 4;
                };

                const scrollByStep = (direction) => {
                    const amount = Math.max(viewport.clientWidth * 0.6, 160);

                    viewport.scrollBy({
                        left: direction * amount,
                        behavior: "smooth",
                    });
                };

                const scheduleUpdate = () => {
                    requestAnimationFrame(() => {
                        updateButtons();

                        requestAnimationFrame(() => {
                            updateButtons();
                        });
                    });

                    setTimeout(updateButtons, 80);
                };

                prevButton.addEventListener("click", () => {
                    scrollByStep(-1);
                });

                nextButton.addEventListener("click", () => {
                    scrollByStep(1);
                });

                viewport.addEventListener("scroll", updateButtons);
                window.addEventListener("resize", scheduleUpdate);

                if (typeof ResizeObserver !== "undefined") {
                    const observer = new ResizeObserver(() => {
                        updateButtons();
                    });

                    observer.observe(viewport);
                    root._appHorizontalScrollObserver = observer;
                }

                scheduleUpdate();
            });
    };

    const openModal = (modal) => {
        if (!modal) {
            return;
        }

        modal.hidden = false;
        modal.setAttribute("aria-hidden", "false");
        document.body.classList.add("app-modal-open");
    };

    const closeModal = (modal) => {
        if (!modal) {
            return;
        }

        modal.hidden = true;
        modal.setAttribute("aria-hidden", "true");

        const anyOpen = Array.from(
            document.querySelectorAll(".app-modal"),
        ).some((item) => !item.hidden);

        if (!anyOpen) {
            document.body.classList.remove("app-modal-open");
        }
    };

    const bindModals = () => {
        document
            .querySelectorAll('[data-action~="app-modal-open"]')
            .forEach((button) => {
                if (button.dataset.appModalOpenBound === "1") {
                    return;
                }

                button.dataset.appModalOpenBound = "1";

                button.addEventListener("click", function (event) {
                    event.preventDefault();

                    const selector = this.dataset.modalTarget;
                    if (!selector) {
                        return;
                    }

                    openModal(document.querySelector(selector));
                });
            });

        document
            .querySelectorAll('[data-action~="app-modal-close"]')
            .forEach((button) => {
                if (button.dataset.appModalCloseBound === "1") {
                    return;
                }

                button.dataset.appModalCloseBound = "1";

                button.addEventListener("click", function (event) {
                    event.preventDefault();

                    const selector = this.dataset.modalTarget;
                    if (!selector) {
                        return;
                    }

                    closeModal(document.querySelector(selector));
                });
            });

        document.addEventListener("keydown", function (event) {
            if (event.key !== "Escape") {
                return;
            }

            const modals = Array.from(document.querySelectorAll(".app-modal"));
            const opened = modals.filter((modal) => !modal.hidden);
            const lastOpen = opened[opened.length - 1];

            if (lastOpen) {
                closeModal(lastOpen);
            }
        });
    };

    const bindAttachmentViewer = () => {
        const viewersState = new Map();

        const renderViewer = (modal, state) => {
            if (!modal || !state || !state.ids.length) {
                return;
            }

            const viewerRoot = modal.querySelector("[data-attachment-viewer]");
            if (!viewerRoot) {
                return;
            }

            const currentId = state.ids[state.index];
            const item = viewerRoot.querySelector(
                `[data-attachment-viewer-item][data-attachment-id="${currentId}"]`,
            );

            if (!item) {
                return;
            }

            const titleTarget = viewerRoot.querySelector(
                "[data-attachment-viewer-title]",
            );
            const metaTarget = viewerRoot.querySelector(
                "[data-attachment-viewer-meta]",
            );
            const imageWrap = viewerRoot.querySelector(
                "[data-attachment-viewer-image-wrap]",
            );
            const imageTarget = viewerRoot.querySelector(
                "[data-attachment-viewer-image]",
            );
            const fallback = viewerRoot.querySelector(
                "[data-attachment-viewer-fallback]",
            );
            const previewLink = viewerRoot.querySelector(
                "[data-attachment-viewer-preview-link]",
            );
            const downloadLink = viewerRoot.querySelector(
                "[data-attachment-viewer-download-link]",
            );

            const isImage = item.dataset.isImage === "1";
            const name = item.dataset.name || "Adjunto";
            const meta = item.dataset.meta || "—";
            const previewUrl = item.dataset.previewUrl || "#";
            const downloadUrl = item.dataset.downloadUrl || "#";

            if (titleTarget) {
                titleTarget.textContent = name;
            }

            if (metaTarget) {
                metaTarget.textContent = meta;
            }

            if (previewLink) {
                previewLink.href = previewUrl;
            }

            if (downloadLink) {
                downloadLink.href = downloadUrl;
            }

            if (isImage) {
                if (imageTarget) {
                    imageTarget.src = previewUrl;
                    imageTarget.alt = name;
                }

                if (imageWrap) {
                    imageWrap.hidden = false;
                }

                if (fallback) {
                    fallback.hidden = true;
                }
            } else {
                if (imageTarget) {
                    imageTarget.src = "";
                    imageTarget.alt = "";
                }

                if (imageWrap) {
                    imageWrap.hidden = true;
                }

                if (fallback) {
                    fallback.hidden = false;
                }
            }

            const prevButton = viewerRoot.querySelector(
                '[data-action~="app-attachment-viewer-prev"]',
            );
            const nextButton = viewerRoot.querySelector(
                '[data-action~="app-attachment-viewer-next"]',
            );

            if (prevButton) {
                prevButton.disabled = state.index <= 0;
            }

            if (nextButton) {
                nextButton.disabled = state.index >= state.ids.length - 1;
            }
        };

        document
            .querySelectorAll('[data-action~="app-attachment-viewer-open"]')
            .forEach((link) => {
                if (link.dataset.appAttachmentViewerOpenBound === "1") {
                    return;
                }

                link.dataset.appAttachmentViewerOpenBound = "1";

                link.addEventListener("click", function (event) {
                    event.preventDefault();

                    const selector = this.dataset.modalTarget;
                    const modal = selector
                        ? document.querySelector(selector)
                        : null;

                    if (!modal) {
                        return;
                    }

                    const rawIds = (this.dataset.viewerIds || "")
                        .split(",")
                        .map((value) => value.trim())
                        .filter(Boolean);

                    const currentId = (this.dataset.attachmentId || "").trim();
                    const initialIndex = Math.max(
                        0,
                        rawIds.findIndex((value) => value === currentId),
                    );

                    const state = {
                        ids: rawIds,
                        index: initialIndex,
                    };

                    viewersState.set(modal.id, state);
                    renderViewer(modal, state);
                    openModal(modal);
                });
            });

        document
            .querySelectorAll('[data-action~="app-attachment-viewer-prev"]')
            .forEach((button) => {
                if (button.dataset.appAttachmentViewerPrevBound === "1") {
                    return;
                }

                button.dataset.appAttachmentViewerPrevBound = "1";

                button.addEventListener("click", function () {
                    const selector = this.dataset.modalTarget;
                    const modal = selector
                        ? document.querySelector(selector)
                        : null;

                    if (!modal) {
                        return;
                    }

                    const state = viewersState.get(modal.id);
                    if (!state || state.index <= 0) {
                        return;
                    }

                    state.index -= 1;
                    renderViewer(modal, state);
                });
            });

        document
            .querySelectorAll('[data-action~="app-attachment-viewer-next"]')
            .forEach((button) => {
                if (button.dataset.appAttachmentViewerNextBound === "1") {
                    return;
                }

                button.dataset.appAttachmentViewerNextBound = "1";

                button.addEventListener("click", function () {
                    const selector = this.dataset.modalTarget;
                    const modal = selector
                        ? document.querySelector(selector)
                        : null;

                    if (!modal) {
                        return;
                    }

                    const state = viewersState.get(modal.id);
                    if (!state || state.index >= state.ids.length - 1) {
                        return;
                    }

                    state.index += 1;
                    renderViewer(modal, state);
                });
            });
    };

    const initAppBase = () => {
        bindConfirmSubmit();
        bindSelectOnClick();
        bindCopyTarget();
        bindCopyValue();
        bindPartyAssetSync();
        bindAppointmentPartyAssetSync();
        bindProductAutofill();
        bindAlerts();
        bindTabs();
        bindToggleDetails();
        bindAppointmentKindSync();
        bindAppointmentCalendarAutoScroll();
        bindHorizontalScroll();
        bindModals();
        bindAttachmentViewer();
    };

    bindDropdowns();

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initAppBase);
    } else {
        initAppBase();
    }
})();
