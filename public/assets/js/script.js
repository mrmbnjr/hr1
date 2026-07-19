
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

document.addEventListener("DOMContentLoaded", () => {

    const searchInput = document.getElementById("searchApplicant");
    const statusFilter = document.getElementById("filterStatus");
    const scoreFilter = document.getElementById("filterScore");

    const applicantCards = document.querySelectorAll(".applicant-card");

    function filterApplicants() {

        const searchValue = searchInput.value.toLowerCase().trim();
        const statusValue = statusFilter.value.toLowerCase();
        const scoreValue = scoreFilter.value;

        applicantCards.forEach(card => {

            const name =
                card.querySelector(".profile-header h2")
                ?.textContent
                .toLowerCase() || "";

            const position =
                card.querySelector(".profile-header p")
                ?.textContent
                .toLowerCase() || "";

            const status =
                card.querySelector(".status")
                ?.textContent
                .trim()
                .toLowerCase() || "";

            const scoreText =
                card.querySelector(".score")
                ?.textContent
                .replace("%", "") || "0";

            const score = parseInt(scoreText);

            let visible = true;

            /* --------------------------
               Search
            --------------------------- */

            if (
                searchValue !== "" &&
                !name.includes(searchValue) &&
                !position.includes(searchValue)
            ) {
                visible = false;
            }

            /* --------------------------
               Status
            --------------------------- */

            if (
                statusValue !== "" &&
                status !== statusValue
            ) {
                visible = false;
            }

            /* --------------------------
               AI Score
            --------------------------- */

            if (
                scoreValue !== "" &&
                score < parseInt(scoreValue)
            ) {
                visible = false;
            }

            card.style.display = visible ? "block" : "none";

        });

    }

    searchInput.addEventListener("keyup", filterApplicants);

    statusFilter.addEventListener("change", filterApplicants);

    scoreFilter.addEventListener("change", filterApplicants);

});