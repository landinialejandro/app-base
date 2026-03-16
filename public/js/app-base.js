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
        .querySelectorAll(".app-nav-dropdown-menu a, .app-user-dropdown-menu a")
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

    document.querySelectorAll("[data-tabs]").forEach(function (tabsRoot) {
        const links = Array.from(tabsRoot.querySelectorAll("[data-tab-link]"));
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
                link.setAttribute("aria-selected", isActive ? "true" : "false");
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

        const initialActiveLink =
            links.find((link) => link.classList.contains("is-active")) ||
            links[0];

        activateTab(initialActiveLink.dataset.tabLink, { updateUrl: false });
    });
})();
