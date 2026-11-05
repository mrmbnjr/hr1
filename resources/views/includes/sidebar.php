<?php

use App\Services\Auth;

$currentPage =
    $_GET['page'] ?? 'dashboard';

$userRole =
    Auth::role();

$isAdmin =
    $userRole === 'ADMIN';

$isHR =
    $userRole === 'HR';

$isMgr =
    $userRole === 'MGR';

$isEmployee =
    $userRole === 'EMP';

?>

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>


<aside
    class="sidebar"
    id="ramyumSidebar"
>

    <div class="sidebar-header">

        <button
            type="button"
            class="sidebar-close"
            id="sidebarClose"
            aria-label="Close menu"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>


        <img
            src="<?= asset('images/logo.png') ?>"
            alt="RAM-YUM Logo"
            class="sidebar-logo"
        >


        <div class="sidebar-header-text">

            <h2
                class="brand"
                id="brandTitle"
            >
                RAM-YUM <span>Store</span>
            </h2>


            <p>
                Korean & Japanese Store
            </p>

        </div>

    </div>


    <nav class="sidebar-menu">


        <!-- ======================================================
             DASHBOARD
        ======================================================= -->

        <?php if (
            $isAdmin ||
            $isHR ||
            $isMgr
        ): ?>

            <a
                href="?page=dashboard"
                class="<?= $currentPage === 'dashboard'
                    ? 'active'
                    : '' ?>"
            >

                <i class="fa-solid fa-house"></i>

                <span>
                    Dashboard
                </span>

            </a>

        <?php endif; ?>


        <!-- ======================================================
             EMPLOYEE
        ======================================================= -->

        <?php if ($isEmployee): ?>


            <a
                href="?page=dashboard"
                class="<?= $currentPage === 'dashboard'
                    ? 'active'
                    : '' ?>"
            >

                <i class="fa-solid fa-house"></i>

                <span>
                    Dashboard
                </span>

            </a>

            <p class="sidebar-title">
                Self-Service
            </p>

            <a
                href="?page=my-requests"
                class="<?= $currentPage === 'my-requests'
                    ? 'active'
                    : '' ?>"
            >

                <i class="fa-solid fa-file-circle-plus"></i>

                <span>
                    My Requests
                </span>

            </a>


            <a
                href="?page=assigned-requests"
                class="<?= $currentPage === 'assigned-requests'
                    ? 'active'
                    : '' ?>"
            >

                <i class="fa-solid fa-clipboard-check"></i>

                <span>
                    Task Approvals
                </span>

            </a>


            <a
                href="?page=profile"
                class="<?= $currentPage === 'profile'
                    ? 'active'
                    : '' ?>"
            >

                <i class="fa-solid fa-circle-user"></i>

                <span>
                    Profile
                </span>

            </a>

        <?php endif; ?>


        <!-- ======================================================
             RECRUITMENT & ONBOARDING
        ======================================================= -->

        <?php if (
            $isAdmin ||
            $isHR ||
            $isMgr
        ): ?>

            <p class="sidebar-title">
                Recruitment &amp; Onboarding
            </p>

        <?php endif; ?>


        <?php if (
            $isAdmin ||
            $isHR
        ): ?>

            <a
                href="?page=recruitment"
                class="<?= $currentPage === 'recruitment'
                    ? 'active'
                    : '' ?>"
            >

                <i class="fa-solid fa-briefcase"></i>

                <span>
                    Job Postings
                </span>

            </a>
        <?php endif; ?>


        <?php if (
            $isAdmin ||
            $isHR ||
            $isMgr
        ): ?>

            <a
                href="?page=applicants"
                class="<?= $currentPage === 'applicants'
                    ? 'active'
                    : '' ?>"
            >

                <i class="fa-solid fa-file-lines"></i>

                <span>
                    Applicants
                </span>

            </a>


            <a
                href="?page=onboarding"
                class="<?= $currentPage === 'onboarding'
                    ? 'active'
                    : '' ?>"
            >

                <i class="fa-solid fa-user-check"></i>

                <span>
                    Onboarding
                </span>

            </a>

        <?php endif; ?>


        <!-- ======================================================
             CORE HR
        ======================================================= -->

        <?php if (
            $isAdmin ||
            $isHR ||
            $isMgr
        ): ?>

            <p class="sidebar-title">
                Employee Management
            </p>

        <?php endif; ?>


        <?php if (
            $isAdmin ||
            $isHR
        ): ?>

            <a
                href="?page=human-capital"
                class="<?= $currentPage === 'human-capital'
                    ? 'active'
                    : '' ?>"
            >

                <i class="fa-solid fa-id-card"></i>

                <span>
                    Organization
                </span>

            </a>

        <?php endif; ?>


        <?php if (
            $isAdmin ||
            $isHR ||
            $isMgr
        ): ?>

            <a
                href="?page=employee-records"
                class="<?= $currentPage === 'employee-records'
                    ? 'active'
                    : '' ?>"
            >

                <i class="fa-solid fa-address-book"></i>

                <span>
                    Employee Records
                </span>

            </a>


            <a
                href="?page=employee-requests"
                class="<?= $currentPage === 'employee-requests'
                    ? 'active'
                    : '' ?>"
            >

                <i class="fa-solid fa-user"></i>

                <span>
                    Request Management
                </span>

            </a>

        <?php endif; ?>


        <!-- ======================================================
             ADMINISTRATION
        ======================================================= -->

        <?php if ($isAdmin): ?>

            <p class="sidebar-title">
                Administration
            </p>

            <a href="?page=user-management" class="<?= $currentPage == 'user-management' ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i>
                <span>User Accounts</span>
            </a>

            <a href="?page=system-settings" class="<?= $currentPage == 'system-settings' ? 'active' : '' ?>">
                <i class="fa-solid fa-gear"></i>
                <span>System Settings</span>
            </a>

            <a href="?page=reports" class="<?= $currentPage == 'reports' ? 'active' : '' ?>">
                <i class="fa-solid fa-chart-column"></i>
                <span>Reports &amp; Analytics</span>
            </a>

            <a href="?page=profile" class="<?= $currentPage == 'profile' ? 'active' : '' ?>">
                <i class="fa-solid fa-circle-user"></i>
                <span>Profile</span>
            </a>

        <?php endif; ?>


        <!-- ======================================================
             MY ACCOUNT — HR / MANAGER
        ======================================================= -->

        <?php if (
            $isHR ||
            $isMgr
        ): ?>

            <p class="sidebar-title">
                Account
            </p>

            <a
                href="?page=profile"
                class="<?= $currentPage === 'profile'
                    ? 'active'
                    : '' ?>"
            >

                <i class="fa-solid fa-circle-user"></i>

                <span>
                    Profile
                </span>

            </a>

        <?php endif; ?>


        <!-- ======================================================
             LOGOUT
        ======================================================= -->

        <a
            href="?page=logout"
            class="<?= $currentPage === 'logout'
                ? 'active sidebar-logout'
                : 'sidebar-logout' ?>"
        >

            <i
                class="fa-solid fa-right-from-bracket"
            ></i>

            <span>
                Logout
            </span>

        </a>

    </nav>

</aside>
