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
});

// Search only pages currently available in the role-filtered sidebar.
document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector(".navbar-search");
    const input = document.getElementById("navbarPageSearch");
    const results = document.getElementById("navbarSearchResults");
    const sidebarLinks = [...document.querySelectorAll(".sidebar-menu a[href*='?page=']")];

    if (!form || !input || !results || sidebarLinks.length === 0) {
        return;
    }

    const pages = sidebarLinks.map((link) => ({
        label: link.querySelector("span")?.textContent.trim() || link.textContent.trim(),
        href: link.href,
        icon: link.querySelector("i")?.className || "fa-solid fa-file"
    }));
    let activeIndex = -1;
    const recentStorageKey = "hr1-recent-page-searches";
    let recentPages = [];

    try {
        recentPages = JSON.parse(localStorage.getItem(recentStorageKey) || "[]");
    } catch (error) {
        console.error("Unable to restore recent page searches.", error);
    }

    const saveRecentPage = (page) => {
        recentPages = [
            page.href,
            ...recentPages.filter((href) => href !== page.href)
        ].slice(0, 3);

        try {
            localStorage.setItem(recentStorageKey, JSON.stringify(recentPages));
        } catch (error) {
            console.error("Unable to save recent page searches.", error);
        }
    };

    const closeResults = () => {
        results.hidden = true;
        input.setAttribute("aria-expanded", "false");
        activeIndex = -1;
    };

    const goToPage = (page, remember = true) => {
        if (page) {
            if (remember) {
                saveRecentPage(page);
            }
            window.location.href = page.href;
        }
    };

    const createResult = (page, index) => {
        const result = document.createElement("button");
        result.type = "button";
        result.className = "navbar-search-result";
        result.setAttribute("role", "option");
        result.setAttribute("aria-selected", "false");
        result.innerHTML = `<i class="${page.icon}" aria-hidden="true"></i><span></span>`;
        result.querySelector("span").textContent = page.label;
        result.addEventListener("click", () => goToPage(page));
        result.dataset.index = String(index);
        return result;
    };

    const renderResults = (query, showRecent = false) => {
        const normalizedQuery = query.trim().toLowerCase();
        const matches = normalizedQuery
            ? pages.filter((page) => page.label.toLowerCase().includes(normalizedQuery)).slice(0, 7)
            : [];
        const recentMatches = recentPages
            .map((href) => pages.find((page) => page.href === href))
            .filter(Boolean)
            .slice(0, 3);

        results.innerHTML = "";
        activeIndex = -1;

        if (!normalizedQuery) {
            if (!showRecent || recentMatches.length === 0) {
                closeResults();
                return;
            }

            const title = document.createElement("div");
            title.className = "navbar-search-recent-title";
            title.textContent = "Recent searches";
            results.appendChild(title);
            recentMatches.forEach((page, index) => results.appendChild(createResult(page, index)));
            results.hidden = false;
            input.setAttribute("aria-expanded", "true");
            return;
        }

        if (matches.length === 0) {
            results.innerHTML = '<div class="navbar-search-no-results">No matching pages found</div>';
            results.hidden = false;
            input.setAttribute("aria-expanded", "true");
            return;
        }

        matches.forEach((page, index) => results.appendChild(createResult(page, index)));

        results.hidden = false;
        input.setAttribute("aria-expanded", "true");
    };

    const setActiveResult = (index) => {
        const resultButtons = [...results.querySelectorAll(".navbar-search-result")];
        if (resultButtons.length === 0) return;

        activeIndex = (index + resultButtons.length) % resultButtons.length;
        resultButtons.forEach((result, resultIndex) => {
            result.setAttribute("aria-selected", String(resultIndex === activeIndex));
        });
    };

    input.addEventListener("focus", () => renderResults(input.value, true));
    input.addEventListener("input", () => renderResults(input.value));
    input.addEventListener("keydown", (event) => {
        const resultButtons = [...results.querySelectorAll(".navbar-search-result")];

        if (event.key === "ArrowDown" && resultButtons.length) {
            event.preventDefault();
            setActiveResult(activeIndex + 1);
        } else if (event.key === "ArrowUp" && resultButtons.length) {
            event.preventDefault();
            setActiveResult(activeIndex - 1);
        } else if (event.key === "Enter" && activeIndex >= 0) {
            event.preventDefault();
            goToPage(pages.find((page) => page.label === resultButtons[activeIndex].textContent));
        } else if (event.key === "Escape") {
            closeResults();
        }
    });

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        const resultButtons = [...results.querySelectorAll(".navbar-search-result")];
        const selected = activeIndex >= 0 ? resultButtons[activeIndex].textContent : resultButtons[0]?.textContent;
        goToPage(pages.find((page) => page.label === selected));
    });

    document.addEventListener("click", (event) => {
        if (!form.contains(event.target)) closeResults();
    });
});

// Shared notification center. Notification records may be supplied by the
// server through the navbar; read state is kept locally per browser.
document.addEventListener("DOMContentLoaded", () => {
        const menu = document.querySelector(".notification-menu");
        const button = menu?.querySelector(".notification-btn");
        const panel = document.getElementById("notificationPanel");
        const list = document.getElementById("notificationList");
        const empty = document.getElementById("notificationEmpty");
        const badge = document.getElementById("notificationBadge");
        const summary = document.getElementById("notificationSummary");
        const markRead = document.getElementById("markNotificationsRead");

        if (!menu || !button || !panel || !list || !empty || !badge || !summary || !markRead) {
            return;
        }

        let notifications = [];
        try {
            notifications = JSON.parse(list.dataset.notifications || "[]");
        } catch (error) {
            console.error("Unable to load notifications.", error);
        }

        const storageKey = "hr1-read-notifications";
        let readIds = [];
        try {
            readIds = JSON.parse(localStorage.getItem(storageKey) || "[]");
        } catch (error) {
            console.error("Unable to restore notification state.", error);
        }

        const saveReadIds = () => {
            try {
                localStorage.setItem(storageKey, JSON.stringify(readIds));
            } catch (error) {
                console.error("Unable to save notification state.", error);
            }
        };

        const iconFor = (type) => ({
            success: "fa-circle-check",
            warning: "fa-triangle-exclamation",
            info: "fa-circle-info"
        }[type] || "fa-circle-info");

        const render = () => {
            list.innerHTML = "";
            let unreadCount = 0;

            notifications.forEach((notification) => {
                const isRead = readIds.includes(notification.id);
                if (!isRead) unreadCount++;

                const item = document.createElement("article");
                item.className = `notification-item${isRead ? "" : " unread"}`;
                item.dataset.id = notification.id;
                item.tabIndex = 0;
                item.setAttribute("role", "button");
                item.innerHTML = `
                    <span class="notification-item__icon"><i class="fa-solid ${iconFor(notification.type)}" aria-hidden="true"></i></span>
                    <span>
                        <strong class="notification-item__title"></strong>
                        <span class="notification-item__message"></span>
                        <small class="notification-item__time"></small>
                    </span>
                    <button type="button" class="notification-item__dismiss" aria-label="Dismiss notification">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                `;
                item.querySelector(".notification-item__title").textContent = notification.title;
                item.querySelector(".notification-item__message").textContent = notification.message;
                item.querySelector(".notification-item__time").textContent = notification.time;
                list.appendChild(item);
            });

            badge.textContent = unreadCount > 99 ? "99+" : String(unreadCount);
            badge.hidden = unreadCount === 0;
            summary.textContent = unreadCount ? `${unreadCount} unread notification${unreadCount === 1 ? "" : "s"}` : "You are all caught up";
            markRead.disabled = unreadCount === 0;
            empty.hidden = notifications.length !== 0;
        };

        const close = () => {
            panel.hidden = true;
            button.setAttribute("aria-expanded", "false");
        };

        button.addEventListener("click", () => {
            panel.hidden = !panel.hidden;
            button.setAttribute("aria-expanded", String(!panel.hidden));
        });

        markRead.addEventListener("click", () => {
            readIds = [...new Set([...readIds, ...notifications.map((item) => item.id)])];
            saveReadIds();
            render();
        });

        list.addEventListener("click", (event) => {
            const item = event.target.closest(".notification-item");
            if (!item) return;

            const notification = notifications.find((entry) => entry.id === item.dataset.id);
            if (event.target.closest(".notification-item__dismiss")) {
                notifications = notifications.filter((entry) => entry.id !== item.dataset.id);
                render();
                return;
            }

            if (!readIds.includes(item.dataset.id)) {
                readIds.push(item.dataset.id);
                saveReadIds();
                render();
            }

            if (notification?.url) {
                window.location.href = notification.url;
            }
        });

        list.addEventListener("keydown", (event) => {
            if ((event.key === "Enter" || event.key === " ") && event.target.classList.contains("notification-item")) {
                event.preventDefault();
                event.target.click();
            }
        });

        document.addEventListener("click", (event) => {
            if (!menu.contains(event.target)) close();
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape" && !panel.hidden) {
                close();
                button.focus();
            }
        });

        render();
    });

document.addEventListener("DOMContentLoaded", () => {
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
