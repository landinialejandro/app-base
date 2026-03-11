document.querySelectorAll('.app-nav-dropdown-menu a').forEach(function (link) {

    link.addEventListener('click', function () {

        const dropdown = this.closest('.app-nav-dropdown');

        if (dropdown) {
            dropdown.removeAttribute('open');
        }

    });

});