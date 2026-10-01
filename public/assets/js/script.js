document.addEventListener("DOMContentLoaded", () => {

    const sidebar = document.getElementById("ramyumSidebar");
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebarClose = document.getElementById("sidebarClose");
    const sidebarOverlay = document.getElementById("sidebarOverlay");

    if (!sidebar || !sidebarToggle) {
        console.log("Sidebar elements not found");
        return;
    }

    sidebarToggle.addEventListener("click", () => {

        if (window.innerWidth <= 960) {

            const isOpen = sidebar.classList.toggle("is-open");

            sidebarOverlay?.classList.toggle("is-visible", isOpen);

            sidebarToggle.setAttribute("aria-expanded", isOpen);

        } else {
            document.body.classList.toggle("sidebar-hidden");
        }

    });

    sidebarClose?.addEventListener("click", () => {
        sidebar.classList.remove("is-open");
        sidebarOverlay?.classList.remove("is-visible");
        sidebarToggle.setAttribute("aria-expanded", "false");
    });

    sidebarOverlay?.addEventListener("click", () => {
        sidebar.classList.remove("is-open");
        sidebarOverlay?.classList.remove("is-visible");
        sidebarToggle.setAttribute("aria-expanded", "false");
    });

});

// Job Postings
document.addEventListener("DOMContentLoaded", () => {

    // Highlight search input when typing
    const search = document.querySelector('.filter-group input');

    if(search){
        search.addEventListener("input", function(){
            this.classList.toggle("has-value", this.value.trim() !== "");
        });
    }

});

document.addEventListener("DOMContentLoaded", () => {

    // Highlight completed fields
    document.querySelectorAll("input, textarea, select").forEach(field => {

        const update = () => {
            field.classList.toggle(
                "has-value",
                field.value.trim() !== ""
            );
        };

        update();

        field.addEventListener("input", update);
        field.addEventListener("change", update);

    });

    // Prevent double submit
    const form = document.querySelector(".form-card");

    if(form){

        form.addEventListener("submit", () => {

            const btn = form.querySelector(".btn-primary");

            if(btn){

                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

            }

        });

    }

});

// Dark mode
(function () {
    "use strict";

    const THEME_KEY = "ramyum-theme";
    const root = document.documentElement;
    const themeToggle = document.getElementById("themeToggle");

    function applyTheme(theme) {
        if (theme === "dark") {
            root.setAttribute("data-theme", "dark");
            themeToggle?.setAttribute("aria-pressed", "true");
        } else {
            root.removeAttribute("data-theme");
            themeToggle?.setAttribute("aria-pressed", "false");
        }
    }

    function getStoredTheme() {
        try {
            return localStorage.getItem(THEME_KEY);
        } catch {
            return null;
        }
    }

    function storeTheme(theme) {
        try {
            localStorage.setItem(THEME_KEY, theme);
        } catch {}
    }

    const initialTheme =
        getStoredTheme() ||
        (window.matchMedia("(prefers-color-scheme: dark)").matches
            ? "dark"
            : "light");

    applyTheme(initialTheme);

    themeToggle?.addEventListener("click", () => {
        const next =
            root.getAttribute("data-theme") === "dark"
                ? "light"
                : "dark";

        applyTheme(next);
        storeTheme(next);
    });
})();
// Shared table pagination renderer.
//
// Draws the page-number buttons inside `pageNumbers`, highlights the current
// page, and keeps the previous/next buttons in sync. Each page owns its own
// `currentPage` state and re-renders through `onSelect`, so this helper only
// builds the controls — it never touches the table itself.
window.renderTablePagination = function ({
    pageNumbers,
    prevPageBtn,
    nextPageBtn,
    totalPages,
    currentPage,
    onSelect,
}) {
    if (!pageNumbers) {
        return;
    }

    pageNumbers.innerHTML = "";

    const addButton = (page) => {
        const button = document.createElement("button");

        button.type = "button";
        button.className = "page-num" + (page === currentPage ? " active" : "");
        button.textContent = page;

        button.addEventListener("click", () => onSelect(page));

        pageNumbers.appendChild(button);
    };

    const addDots = () => {
        const span = document.createElement("span");

        span.textContent = "...";

        pageNumbers.appendChild(span);
    };

    if (totalPages <= 7) {
        for (let page = 1; page <= totalPages; page++) {
            addButton(page);
        }
    } else {
        addButton(1);

        if (currentPage > 3) {
            addDots();
        }

        const start = Math.max(2, currentPage - 1);
        const end = Math.min(totalPages - 1, currentPage + 1);

        for (let page = start; page <= end; page++) {
            addButton(page);
        }

        if (currentPage < totalPages - 2) {
            addDots();
        }

        addButton(totalPages);
    }

    if (prevPageBtn) {
        prevPageBtn.disabled = currentPage === 1;
    }

    if (nextPageBtn) {
        nextPageBtn.disabled = currentPage === totalPages;
    }
};
// Make table rows click- and keyboard-navigable through their data-row-href.
// Clicks on interactive children (links, buttons, fields) are ignored so the
// row only navigates when the row itself is activated.
window.bindRowNavigation = function (rows) {
    rows.forEach((row) => {
        const openRow = (event) => {
            if (event.target.closest("a, button, input, select, textarea")) {
                return;
            }

            if (event.type === "keydown" && event.key !== "Enter" && event.key !== " ") {
                return;
            }

            if (event.type === "keydown") {
                event.preventDefault();
            }

            window.location.href = row.dataset.rowHref;
        };

        row.addEventListener("click", openRow);
        row.addEventListener("keydown", openRow);
    });
};
