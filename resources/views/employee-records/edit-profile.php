<?php
/**
 * Admin editor for an employee profile launched from User Management.
 * Admin: edit a user's personal information.
 *
 */

$pageTitle = 'Edit Profile';
$pageCSS   = 'user-edit.css';
$pageJS    = 'user-edit.js';
$pageDescription = 'Update this employee’s personal and contact information.';

if (!function_exists('e')) {
    function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
}

require BASE_PATH . '/resources/views/includes/header.php';
require BASE_PATH . '/resources/views/includes/sidebar.php';
?>

<div class="main-content">
    <?php require BASE_PATH . '/resources/views/includes/navbar.php'; ?>
<main class="ue-page">

    <div class="ue-head">
        <div>
            <nav class="ue-crumbs" aria-label="Breadcrumb">
                <a href="?page=user-management">User Management</a>
                <i class="fa-solid fa-chevron-right"></i>
                <span>Edit Profile</span>
            </nav>
            <h1 class="ue-title">Edit Profile</h1>
            <p class="ue-sub">Update personal information for this user.</p>
        </div>
        <div class="ue-head-actions">
            <a href="?page=user-management" class="ue-btn ue-btn-ghost">Cancel</a>
            <button type="submit" form="editProfileForm" class="ue-btn ue-btn-primary js-save">
                <i class="fa-solid fa-floppy-disk"></i> Save Changes
            </button>
        </div>
    </div>

    <form id="editProfileForm" class="ue-layout" action="?page=user-management-profile-update" method="post" novalidate>
        <input type="hidden" name="id" value="<?= e($user['id']) ?>">

        <div class="ue-main">

            <!-- Basic Identity -->
            <section class="ue-card">
                <header class="ue-card-head"><h2>Basic Identity</h2></header>
                <div class="ue-card-body ue-grid ue-grid-3">
                    <div class="ue-field">
                        <label for="badge_id">Badge ID</label>
                        <input type="text" id="badge_id" name="badge_id" value="<?= e($user['badge_id']) ?>" readonly>
                        <small class="ue-hint">Assigned by the system.</small>
                    </div>
                    <div class="ue-field">
                        <label for="first_name">First Name <span class="req">*</span></label>
                        <input type="text" id="first_name" name="first_name" value="<?= e($user['first_name']) ?>"
                               maxlength="100" autocomplete="off" required>
                        <small class="ue-error"></small>
                    </div>
                    <div class="ue-field">
                        <label for="last_name">Last Name <span class="req">*</span></label>
                        <input type="text" id="last_name" name="last_name" value="<?= e($user['last_name']) ?>"
                               maxlength="100" autocomplete="off" required>
                        <small class="ue-error"></small>
                    </div>

                    <div class="ue-field">
                        <label for="email">Email <span class="req">*</span></label>
                        <input type="email" id="email" name="email" value="<?= e($user['email']) ?>"
                               maxlength="150" autocomplete="off" required>
                        <small class="ue-error"></small>
                    </div>
                    <div class="ue-field">
                        <label for="phone">Phone <span class="req">*</span></label>
                        <input type="tel" id="phone" name="phone" value="<?= e($user['phone']) ?>"
                               maxlength="30" inputmode="tel" autocomplete="off" required>
                        <small class="ue-error"></small>
                    </div>
                </div>
            </section>

            <!-- Contact & Address -->
            <section class="ue-card">
                <header class="ue-card-head"><h2>Contact &amp; Address</h2></header>
                <div class="ue-card-body ue-grid ue-grid-3">
                    <div class="ue-field ue-span-all">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="2" maxlength="500"
                                  placeholder="Address"><?= e($user['address']) ?></textarea>
                    </div>
                </div>
            </section>

            <div class="ue-foot-actions">
                <a href="?page=user-management" class="ue-btn ue-btn-ghost">Cancel</a>
                <button type="submit" class="ue-btn ue-btn-primary js-save">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
            </div>
        </div>

    </form>

    <div class="ue-toast-wrap" id="toastWrap" aria-live="polite"></div>
</main>

    <?php require BASE_PATH . '/resources/views/includes/footer.php'; ?>
</div>
<?php require BASE_PATH . '/resources/views/includes/scripts.php'; ?>