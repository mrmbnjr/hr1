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
                <?= htmlspecialchars($pageTitle ?? ''); ?>
            </h2>

            <p class="page-subtitle">
                <?= htmlspecialchars($pageDescription ?? ''); ?>
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

        <?php
        $navbarNotifications = [];
        if (!empty($_SESSION['notifications']) && is_array($_SESSION['notifications'])) {
            foreach ($_SESSION['notifications'] as $notification) {
                if (!is_array($notification) || empty($notification['message'])) {
                    continue;
                }

                $navbarNotifications[] = [
                    'id' => (string) ($notification['id'] ?? sha1((string) $notification['message'])),
                    'title' => (string) ($notification['title'] ?? 'Notification'),
                    'message' => (string) $notification['message'],
                    'time' => (string) ($notification['time'] ?? 'Just now'),
                    'type' => in_array(($notification['type'] ?? 'info'), ['info', 'success', 'warning'], true)
                        ? $notification['type']
                        : 'info',
                    'url' => (string) ($notification['url'] ?? ''),
                ];
            }
        }
        ?>

        <!-- Notification center -->
        <div class="notification-menu">
            <button type="button"
                    class="navbar-icon-btn notification-btn"
                    aria-label="Open notifications"
                    aria-haspopup="true"
                    aria-expanded="false"
                    aria-controls="notificationPanel">
                <i class="fa-solid fa-bell" aria-hidden="true"></i>
                <span class="notification-badge" id="notificationBadge" hidden></span>
            </button>

            <section class="notification-panel"
                     id="notificationPanel"
                     aria-labelledby="notificationTitle"
                     hidden>
                <header class="notification-panel__header">
                    <div>
                        <h3 id="notificationTitle">Notifications</h3>
                        <p id="notificationSummary">You are all caught up</p>
                    </div>
                    <button type="button" class="notification-mark-read" id="markNotificationsRead">
                        Mark all read
                    </button>
                </header>

                <div class="notification-list" id="notificationList"
                     data-notifications="<?= htmlspecialchars(
                         json_encode($navbarNotifications, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
                         ENT_QUOTES,
                         'UTF-8'
                     ) ?>">
                </div>

                <div class="notification-empty" id="notificationEmpty" hidden>
                    <span class="notification-empty__icon"><i class="fa-solid fa-check" aria-hidden="true"></i></span>
                    <strong>You’re all caught up</strong>
                    <span>New updates will appear here.</span>
                </div>
            </section>
        </div>

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