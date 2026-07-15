<header class="topbar">

    <div class="topbar-left">

        <button class="menu-toggle" type="button" aria-label="Toggle Sidebar">
            <i class="fa-solid fa-bars"></i>
        </button>

        <h2><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h2>

    </div>

    <div class="topbar-right">

        <span><?= htmlspecialchars($_SESSION['username']) ?></span>

    </div>

</header>

<div class="sidebar-overlay"></div>