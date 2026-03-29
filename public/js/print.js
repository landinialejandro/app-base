// FILE: public/js/print.js | V1
(function () {
    const initPrintToolbar = () => {
        document
            .querySelectorAll('[data-action~="app-print-toolbar"]')
            .forEach((toolbar) => {
                if (toolbar.dataset.appPrintToolbarBound === "1") {
                    return;
                }

                toolbar.dataset.appPrintToolbarBound = "1";

                toolbar.addEventListener("click", (event) => {
                    const button = event.target.closest("[data-print-action]");

                    if (!button) {
                        return;
                    }

                    const action = button.dataset.printAction;

                    if (action === "print") {
                        window.print();
                    }

                    if (action === "close") {
                        window.close();
                    }
                });
            });
    };

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initPrintToolbar);
    } else {
        initPrintToolbar();
    }
})();
