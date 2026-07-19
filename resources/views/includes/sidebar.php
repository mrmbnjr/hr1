<button type="button"
        class="sidebar-toggle"
        id="sidebarToggle"
        aria-label="Open menu"
        aria-expanded="false"
        aria-controls="ramyumSidebar">
    <i class="fa-solid fa-bars"></i>
</button>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="ramyumSidebar">

    <div class="sidebar-header">

        <button type="button"
                class="sidebar-close"
                id="sidebarClose"
                aria-label="Close menu">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <h2>RAM-YUM</h2>
        <p>Human Resource Management</p>

    </div>

    <nav class="sidebar-menu">

        <a href="?page=dashboard" class="active">
            <i class="fa-solid fa-house"></i>
            <span>Dashboard</span>
        </a>

        <p class="sidebar-title">
            Recruitment &amp; Onboarding
        </p>

        <a href="?page=applicants">
            <i class="fa-solid fa-file-lines"></i>
            <span>Applicant Management</span>
        </a>

        <a href="?page=recruitment">
            <i class="fa-solid fa-briefcase"></i>
            <span>Recruitment Management</span>
        </a>

        <a href="?page=onboarding">
            <i class="fa-solid fa-user-check"></i>
            <span>New Hire Onboarding</span>
        </a>

        <p class="sidebar-title">
            Core HR
        </p>

        <a href="?page=human-capital-management">
            <i class="fa-solid fa-id-card"></i>
            <span>Human Capital Management</span>
        </a>

        <a href="?page=employee-records">
            <i class="fa-solid fa-address-book"></i>
            <span>Employee Records</span>
        </a>

        <a href="?page=employee-self-service">
            <i class="fa-solid fa-user"></i>
            <span>Employee Self-Service</span>
        </a>

        <p class="sidebar-title">
            Administration
        </p>

        <a href="?page=user-management">
            <i class="fa-solid fa-users"></i>
            <span>User Management</span>
        </a>

        <a href="?page=system-settings">
            <i class="fa-solid fa-gear"></i>
            <span>System Settings</span>
        </a>

        <a href="?page=reports">
            <i class="fa-solid fa-chart-column"></i>
            <span>Reports</span>
        </a>

        <div class="sidebar-spacer"></div>

        <a href="?page=profile">
            <i class="fa-solid fa-circle-user"></i>
            <span>Profile</span>
        </a>

        <a href="?page=logout" class="sidebar-logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>

    </nav>

</aside>

<script>
(function () {

    const toggle = document.getElementById("sidebarToggle");
    const closeBtn = document.getElementById("sidebarClose");
    const overlay = document.getElementById("sidebarOverlay");
    const sidebar = document.getElementById("ramyumSidebar");

    if (!toggle || !sidebar) return;

    function openSidebar() {
        sidebar.classList.add("is-open");
        overlay.classList.add("is-visible");
        toggle.setAttribute("aria-expanded", "true");
    }

    function closeSidebar() {
        sidebar.classList.remove("is-open");
        overlay.classList.remove("is-visible");
        toggle.setAttribute("aria-expanded", "false");
    }

    toggle.addEventListener("click", openSidebar);

    closeBtn?.addEventListener("click", closeSidebar);

    overlay?.addEventListener("click", closeSidebar);

    document.addEventListener("keydown", function(e){
        if(e.key === "Escape"){
            closeSidebar();
        }
    });

})();
</script>