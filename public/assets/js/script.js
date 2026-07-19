
/* ==========================
   Sidebar
========================== */

const sidebar = document.querySelector(".sidebar");
const toggle = document.querySelector(".menu-toggle");
const overlay = document.querySelector(".sidebar-overlay");

toggle.addEventListener("click", () => {

    if (window.innerWidth <= 768) {
        sidebar.classList.toggle("active");
        overlay.classList.toggle("active");
    } else {
        sidebar.classList.toggle("collapsed");
    }

});

overlay.addEventListener("click", () => {
    sidebar.classList.remove("active");
    overlay.classList.remove("active");
});

/* ==========================
    Navbar
========================== */

(function () {
    "use strict";

    /* ==========================================================
       SIDEBAR
    ========================================================== */

    const sidebar = document.getElementById("ramyumSidebar");
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebarClose = document.getElementById("sidebarClose");
    const sidebarOverlay = document.getElementById("sidebarOverlay");

    function openSidebar() {

        if (!sidebar) return;

        sidebar.classList.add("is-open");

        sidebarOverlay?.classList.add("is-visible");

        sidebarToggle?.setAttribute("aria-expanded", "true");

    }

    function closeSidebar() {

        if (!sidebar) return;

        sidebar.classList.remove("is-open");

        sidebarOverlay?.classList.remove("is-visible");

        sidebarToggle?.setAttribute("aria-expanded", "false");

    }

    sidebarToggle?.addEventListener("click", openSidebar);

    sidebarClose?.addEventListener("click", closeSidebar);

    sidebarOverlay?.addEventListener("click", closeSidebar);

    document.addEventListener("keydown", function (e) {

        if (e.key === "Escape") {

            closeSidebar();

            closeProfileMenu();

        }

    });

    /* ==========================================================
       THEME TOGGLE
    ========================================================== */

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
        (
            window.matchMedia &&
            window.matchMedia("(prefers-color-scheme: dark)").matches
        )
            ? "dark"
            : "light";

    applyTheme(initialTheme);

    themeToggle?.addEventListener("click", function () {

        const next =
            root.getAttribute("data-theme") === "dark"
                ? "light"
                : "dark";

        applyTheme(next);

        storeTheme(next);

    });

    /* ==========================================================
       PROFILE DROPDOWN
    ========================================================== */

    const profile = document.getElementById("profileDropdown");

    const profileButton =
        profile?.querySelector(".profile-btn");

    const profileMenu =
        profile?.querySelector(".profile-menu");

    function closeProfileMenu() {

        profileMenu?.classList.remove("show");

    }

    profileButton?.addEventListener("click", function (e) {

        e.stopPropagation();

        profileMenu.classList.toggle("show");

    });

    document.addEventListener("click", function () {

        closeProfileMenu();

    });

    profileMenu?.addEventListener("click", function (e) {

        e.stopPropagation();

    });

    /* ==========================================================
       SEARCH
    ========================================================== */

    const searchInput = document.querySelector(
        ".navbar-search input"
    );

    searchInput?.addEventListener("keydown", function (e) {

        if (e.key !== "Enter") return;

        e.preventDefault();

        console.log("Search:", this.value);

        /*
            Example:

            window.location.href =
                "?page=search&q=" +
                encodeURIComponent(this.value);
        */

    });

    /* ==========================================================
       NOTIFICATION
    ========================================================== */

    const notificationButton =
        document.querySelector(".notification-btn");

    notificationButton?.addEventListener("click", function () {

        console.log("Open notifications");

        /*
            Replace later with:

            notificationDropdown.classList.toggle(...)
        */

    });

})();