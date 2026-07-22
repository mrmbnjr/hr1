<nav class="navbar">

    <!-- Left -->
    <div class="navbar-left">

        <button type="button"
                class="navbar-toggle"
                id="sidebarToggle"
                aria-label="Open menu"
                aria-expanded="false"
                aria-controls="ramyumSidebar">

            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="navbar-title">
            <h2>
                <?= htmlspecialchars($pageTitle); ?> for
                <strong><?= $_SESSION['username'] ?? 'User'; ?></strong>
            </h2>

            <p class="page-subtitle">
                <?= htmlspecialchars($pageDescription); ?>
            </p>
        </div>
    </div>

    <!-- Right -->
    <div class="navbar-right">
        <form class="navbar-search" action="#" method="GET">
            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="search"
                name="search"
                placeholder="Search employees, applicants..."
                autocomplete="off">
        </form>

        <!-- Notification -->
        <button class="navbar-icon-btn notification-btn">
            <i class="fa-solid fa-bell"></i>
            <span class="notification-badge">
                3
            </span>
        </button>

        <!-- Theme Toggle -->
        <button type="button"
                class="navbar-icon-btn"
                id="themeToggle"
                aria-label="Toggle Theme"
                aria-pressed="false">

            <!-- Sun -->
            <svg class="icon icon-sun"
                 viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2"
                 stroke-linecap="round"
                 stroke-linejoin="round">

                <circle cx="12" cy="12" r="4"></circle>

                <path d="M12 2v2"></path>
                <path d="M12 20v2"></path>

                <path d="M2 12h2"></path>
                <path d="M20 12h2"></path>

                <path d="M4.9 4.9l1.4 1.4"></path>
                <path d="M17.7 17.7l1.4 1.4"></path>

                <path d="M4.9 19.1l1.4-1.4"></path>
                <path d="M17.7 6.3l1.4-1.4"></path>

            </svg>

            <!-- Moon -->
            <svg class="icon icon-moon"
                 viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2"
                 stroke-linecap="round"
                 stroke-linejoin="round">

                <path d="M21 12.8A9 9 0 1111.2 3 7 7 0 0021 12.8z"/>
            </svg>
        </button>
    </div>
</nav>