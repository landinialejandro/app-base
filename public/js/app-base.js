(function () {
    const dropdownSelectors = ['.app-nav-dropdown', '.app-user-dropdown'];

    const getDropdowns = () =>
        dropdownSelectors.flatMap((selector) => Array.from(document.querySelectorAll(selector)));

    const closeDropdown = (dropdown) => {
        if (dropdown) {
            dropdown.removeAttribute('open');
        }
    };

    const closeAllDropdowns = (except = null) => {
        getDropdowns().forEach((dropdown) => {
            if (dropdown !== except) {
                closeDropdown(dropdown);
            }
        });
    };

    document.addEventListener('click', function (event) {
        const clickedDropdown = event.target.closest('.app-nav-dropdown, .app-user-dropdown');

        if (!clickedDropdown) {
            closeAllDropdowns();
            return;
        }

        closeAllDropdowns(clickedDropdown);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAllDropdowns();
        }
    });

    getDropdowns().forEach((dropdown) => {
        dropdown.addEventListener('toggle', function () {
            if (this.hasAttribute('open')) {
                closeAllDropdowns(this);
            }
        });
    });

    document.querySelectorAll('.app-nav-dropdown-menu a, .app-user-dropdown-menu a').forEach(function (link) {
        link.addEventListener('click', function () {
            closeAllDropdowns();
        });
    });

    document.querySelectorAll('.app-user-dropdown-menu form').forEach(function (form) {
        form.addEventListener('submit', function () {
            closeAllDropdowns();
        });
    });
})();
