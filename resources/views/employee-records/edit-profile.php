<?php
/**
 * Admin editor for an employee profile launched from User Management.
 * Admin: edit a user's personal information.
 *
 * Presentation only — no schema, query or field-contract changes. The
 * editable inputs map 1:1 to what UserManagementController::updateProfile()
 * accepts: first_name, last_name, email, phone, address. Everything else on
 * this screen is read-only context rendered from the profile lookup.
 */

$pageTitle = 'Edit Profile';
$pageCSS   = 'user-edit.css';
$pageJS    = 'user-edit.js';
$pageDescription = 'Update this employee’s personal and contact information.';

if (!function_exists('e')) {
    function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
}

$user = is_array($user ?? null) ? $user : [];

/* ---------- Identity ---------- */
$firstName   = trim((string) ($user['first_name'] ?? ''));
$middleName  = trim((string) ($user['middle_name'] ?? ''));
$lastName    = trim((string) ($user['last_name'] ?? ''));
$fullName    = trim(preg_replace('/\s+/', ' ', $firstName . ' ' . $middleName . ' ' . $lastName));
$displayName = $fullName !== '' ? $fullName : 'Employee';
$initial     = mb_strtoupper(mb_substr($firstName !== '' ? $firstName : $displayName, 0, 1));

/* ---------- Editable values ---------- */
$badgeId = trim((string) ($user['badge_id'] ?? ''));
$email   = trim((string) ($user['email'] ?? ''));
$phone   = trim((string) ($user['phone'] ?? ''));
$address = trim((string) ($user['address'] ?? ''));

/* ---------- Editable account values ---------- */
$username      = trim((string) ($user['username'] ?? ''));
$accountEmail  = trim((string) ($user['account_email'] ?? ''));
$rawStatus     = in_array(($user['account_status'] ?? 'Active'), ['Active', 'Inactive'], true)
    ? (string) $user['account_status']
    : 'Active';
$currentRoleId = (int) ($user['role_id'] ?? 0);
$roles         = is_array($roles ?? null) ? $roles : [];

/* ---------- Read-only context ---------- */
$roleName     = trim((string) ($user['role_name'] ?? ''));
$department   = trim((string) ($user['department_name'] ?? ''));
$position     = trim((string) ($user['position_name'] ?? ''));
$empStatus    = trim((string) ($user['employment_status'] ?? ''));
$hireDate     = trim((string) ($user['hire_date'] ?? ''));
$createdAt    = trim((string) ($user['created_at'] ?? ''));
$verifiedAt   = trim((string) ($user['email_verified_at'] ?? ''));
$mustChange   = (int) ($user['must_change_password'] ?? 0);

/* Derived status pill mirrors the roster in User Management. */
if ($rawStatus === 'Inactive') {
    $acctStatus = 'Inactive';
} elseif ($mustChange === 1) {
    $acctStatus = 'Pending Setup';
} else {
    $acctStatus = 'Active';
}
$statusClass = [
    'Active'        => 'is-active',
    'Pending Setup' => 'is-pending',
    'Inactive'      => 'is-inactive',
][$acctStatus];

/* ---------- Completeness (editable fields only) ---------- */
$checks = [
    'Name'    => $firstName !== '' && $lastName !== '',
    'Email'   => $email !== '',
    'Phone'   => $phone !== '',
    'Address' => $address !== '',
];
$completeness = (int) round(count(array_filter($checks)) / max(1, count($checks)) * 100);

/* ---------- Format helpers ---------- */
$fmtDate = static function (string $value): string {
    if ($value === '' || str_starts_with($value, '0000-00-00')) {
        return '—';
    }
    $ts = strtotime($value);
    return $ts ? date('M j, Y', $ts) : '—';
};
$dash = static fn (string $value): string => $value !== '' ? $value : '—';
?>

<?php require BASE_PATH . '/resources/views/includes/header.php'; ?>
<?php require BASE_PATH . '/resources/views/includes/sidebar.php'; ?>

<div class="main-content">
    <?php require BASE_PATH . '/resources/views/includes/navbar.php'; ?>
<main class="ue-page">

    <!-- Identity hero: who you are editing, at a glance. -->
    <header class="ue-hero">
        <span class="ue-avatar" aria-hidden="true"><?= e($initial) ?></span>

        <div class="ue-identity">
            <div class="ue-identity-top">
                <h1 class="ue-title"><?= e($displayName) ?></h1>
                <span class="status-pill <?= e($statusClass) ?>">
                    <i class="status-dot" aria-hidden="true"></i><?= e($acctStatus) ?>
                </span>
            </div>

            <ul class="ue-chips">
                <?php if ($roleName !== ''): ?>
                    <li class="ue-chip"><i class="fa-solid fa-user-shield" aria-hidden="true"></i><?= e($roleName) ?></li>
                <?php endif; ?>
                <?php if ($department !== ''): ?>
                    <li class="ue-chip"><i class="fa-solid fa-building" aria-hidden="true"></i><?= e($department) ?></li>
                <?php endif; ?>
                <?php if ($position !== ''): ?>
                    <li class="ue-chip"><i class="fa-solid fa-briefcase" aria-hidden="true"></i><?= e($position) ?></li>
                <?php endif; ?>
                <?php if ($badgeId !== ''): ?>
                    <li class="ue-chip"><i class="fa-solid fa-id-badge" aria-hidden="true"></i><?= e($badgeId) ?></li>
                <?php endif; ?>
            </ul>

            <p class="ue-sub">Update this employee’s personal details and account access. Changes are written to their employee record.</p>
        </div>

        <div class="ue-head-actions">
            <a href="?page=user-management" class="btn-secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Cancel
            </a>
            <button type="submit" form="editProfileForm" class="btn-primary js-save">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Changes
            </button>
        </div>
    </header>

    <div class="ue-layout">

        <form id="editProfileForm" class="ue-main" action="?page=user-management-profile-update" method="post" novalidate>
            <input type="hidden" name="id" value="<?= e($user['id'] ?? '') ?>">

            <!-- Personal information -->
            <section class="ue-card" aria-labelledby="ue-personal-heading">
                <header class="ue-card-head">
                    <span class="ue-card-icon"><i class="fa-solid fa-id-card" aria-hidden="true"></i></span>
                    <div class="ue-card-titles">
                        <h2 id="ue-personal-heading">Personal information</h2>
                        <p class="ue-card-sub">Name and contact details shown on the employee record.</p>
                    </div>
                </header>

                <div class="ue-card-body ue-grid ue-grid-2">
                    <div class="ue-field ue-span-all">
                        <label for="badge_id">Employee / Badge ID</label>
                        <div class="ue-input is-readonly">
                            <i class="fa-solid fa-hashtag" aria-hidden="true"></i>
                            <input type="text" id="badge_id" name="badge_id" value="<?= e($badgeId) ?>" readonly tabindex="-1">
                        </div>
                        <small class="ue-hint">Assigned by the system — this value cannot be edited.</small>
                    </div>

                    <div class="ue-field">
                        <label for="first_name">First name <span class="req" aria-hidden="true">*</span></label>
                        <div class="ue-input">
                            <i class="fa-solid fa-user" aria-hidden="true"></i>
                            <input type="text" id="first_name" name="first_name" value="<?= e($firstName) ?>"
                                   maxlength="100" autocomplete="off" required aria-required="true">
                        </div>
                        <small class="ue-error" role="alert"></small>
                    </div>

                    <div class="ue-field">
                        <label for="last_name">Last name <span class="req" aria-hidden="true">*</span></label>
                        <div class="ue-input">
                            <i class="fa-solid fa-user" aria-hidden="true"></i>
                            <input type="text" id="last_name" name="last_name" value="<?= e($lastName) ?>"
                                   maxlength="100" autocomplete="off" required aria-required="true">
                        </div>
                        <small class="ue-error" role="alert"></small>
                    </div>

                    <div class="ue-field">
                        <label for="email">Contact email <span class="req" aria-hidden="true">*</span></label>
                        <div class="ue-input">
                            <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                            <input type="email" id="email" name="email" value="<?= e($email) ?>"
                                   maxlength="150" autocomplete="off" required aria-required="true">
                        </div>
                        <small class="ue-error" role="alert"></small>
                    </div>

                    <div class="ue-field">
                        <label for="phone">Phone <span class="req" aria-hidden="true">*</span></label>
                        <div class="ue-input">
                            <i class="fa-solid fa-phone" aria-hidden="true"></i>
                            <input type="tel" id="phone" name="phone" value="<?= e($phone) ?>"
                                   maxlength="30" inputmode="tel" autocomplete="off" required aria-required="true">
                        </div>
                        <small class="ue-error" role="alert"></small>
                    </div>
                </div>
            </section>

            <!-- Address -->
            <section class="ue-card" aria-labelledby="ue-address-heading">
                <header class="ue-card-head">
                    <span class="ue-card-icon"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></span>
                    <div class="ue-card-titles">
                        <h2 id="ue-address-heading">Address</h2>
                        <p class="ue-card-sub">Residential or mailing address on file.</p>
                    </div>
                </header>

                <div class="ue-card-body">
                    <div class="ue-field">
                        <div class="ue-label-row">
                            <label for="address">Full address</label>
                            <span class="ue-counter" id="addressCount" aria-live="polite">0 / 500</span>
                        </div>
                        <div class="ue-input is-area">
                            <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
                            <textarea id="address" name="address" rows="3" maxlength="500"
                                      placeholder="Street, barangay, city, province, postal code"><?= e($address) ?></textarea>
                        </div>
                        <small class="ue-error" role="alert"></small>
                    </div>
                </div>
            </section>

            <!-- Account & access (username, sign-in email, role, status) -->
            <section class="ue-card" aria-labelledby="ue-access-heading">
                <header class="ue-card-head">
                    <span class="ue-card-icon"><i class="fa-solid fa-user-shield" aria-hidden="true"></i></span>
                    <div class="ue-card-titles">
                        <h2 id="ue-access-heading">Account &amp; access</h2>
                        <p class="ue-card-sub">Sign-in identity, system role and account status.</p>
                    </div>
                </header>

                <div class="ue-card-body ue-grid ue-grid-2">
                    <div class="ue-field">
                        <label for="username">Username <span class="req" aria-hidden="true">*</span></label>
                        <div class="ue-input">
                            <i class="fa-solid fa-at" aria-hidden="true"></i>
                            <input type="text" id="username" name="username" value="<?= e($username) ?>"
                                   maxlength="50" autocomplete="off" required aria-required="true">
                        </div>
                        <small class="ue-hint">3–50 characters: letters, numbers, dots, dashes or underscores.</small>
                        <small class="ue-error" role="alert"></small>
                    </div>

                    <div class="ue-field">
                        <label for="account_email">Sign-in email <span class="req" aria-hidden="true">*</span></label>
                        <div class="ue-input">
                            <i class="fa-solid fa-envelope-circle-check" aria-hidden="true"></i>
                            <input type="email" id="account_email" name="account_email" value="<?= e($accountEmail) ?>"
                                   maxlength="150" autocomplete="off" required aria-required="true">
                        </div>
                        <small class="ue-hint">Used to sign in and receive verification codes.</small>
                        <small class="ue-error" role="alert"></small>
                    </div>

                    <div class="ue-field">
                        <label for="role_id">Role <span class="req" aria-hidden="true">*</span></label>
                        <div class="ue-input is-select">
                            <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
                            <select id="role_id" name="role_id" required aria-required="true">
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= (int) $role['role_id'] ?>"
                                        <?= (int) $role['role_id'] === $currentRoleId ? 'selected' : '' ?>>
                                        <?= e($role['role_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fa-solid fa-chevron-down ue-caret" aria-hidden="true"></i>
                        </div>
                        <small class="ue-error" role="alert"></small>
                    </div>

                    <div class="ue-field">
                        <label for="status">Account status <span class="req" aria-hidden="true">*</span></label>
                        <div class="ue-input is-select">
                            <i class="fa-solid fa-power-off" aria-hidden="true"></i>
                            <select id="status" name="status" required aria-required="true">
                                <option value="Active" <?= $rawStatus === 'Active' ? 'selected' : '' ?>>Active</option>
                                <option value="Inactive" <?= $rawStatus === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                            <i class="fa-solid fa-chevron-down ue-caret" aria-hidden="true"></i>
                        </div>
                        <small class="ue-hint">Inactive accounts cannot sign in.</small>
                        <small class="ue-error" role="alert"></small>
                    </div>

                    <p class="ue-note ue-span-all">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        Changing the sign-in email clears its verified status — the user must verify the new address before the next sign-in code is trusted.
                    </p>
                </div>
            </section>
        </form>

        <!-- Read-only context: everything else the admin may need to confirm. -->
        <aside class="ue-aside" aria-label="Profile details">

            <section class="ue-card ue-card--soft" aria-labelledby="ue-complete-heading">
                <header class="ue-card-head ue-card-head--plain">
                    <span class="ue-card-icon"><i class="fa-solid fa-list-check" aria-hidden="true"></i></span>
                    <div class="ue-card-titles"><h2 id="ue-complete-heading">Profile completeness</h2></div>
                    <span class="ue-meter-value"><?= $completeness ?>%</span>
                </header>
                <div class="ue-card-body">
                    <div class="ue-meter" role="img" aria-label="Profile completeness <?= $completeness ?> percent">
                        <span style="width: <?= $completeness ?>%"></span>
                    </div>
                    <ul class="ue-checklist">
                        <?php foreach ($checks as $label => $done): ?>
                            <li class="<?= $done ? 'is-done' : '' ?>">
                                <i class="fa-solid <?= $done ? 'fa-circle-check' : 'fa-circle' ?>" aria-hidden="true"></i>
                                <?= e($label) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>

            <section class="ue-card" aria-labelledby="ue-account-heading">
                <header class="ue-card-head ue-card-head--plain">
                    <span class="ue-card-icon"><i class="fa-solid fa-user-lock" aria-hidden="true"></i></span>
                    <div class="ue-card-titles"><h2 id="ue-account-heading">Verification &amp; history</h2></div>
                </header>
                <div class="ue-card-body">
                    <dl class="ue-rows">
                        <div class="ue-row">
                            <dt>Email verified</dt>
                            <dd>
                                <?php if ($verifiedAt !== ''): ?>
                                    <span class="ue-flag is-ok"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= e($fmtDate($verifiedAt)) ?></span>
                                <?php else: ?>
                                    <span class="ue-flag is-warn"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>Not verified</span>
                                <?php endif; ?>
                            </dd>
                        </div>
                        <div class="ue-row">
                            <dt>Member since</dt>
                            <dd><?= e($fmtDate($createdAt)) ?></dd>
                        </div>
                    </dl>
                    <p class="ue-note"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> These values are read-only.</p>
                </div>
            </section>

            <section class="ue-card" aria-labelledby="ue-employment-heading">
                <header class="ue-card-head ue-card-head--plain">
                    <span class="ue-card-icon"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></span>
                    <div class="ue-card-titles"><h2 id="ue-employment-heading">Employment</h2></div>
                </header>
                <div class="ue-card-body">
                    <dl class="ue-rows">
                        <div class="ue-row">
                            <dt>Employee ID</dt>
                            <dd><?= e($dash($badgeId)) ?></dd>
                        </div>
                        <div class="ue-row">
                            <dt>Position</dt>
                            <dd><?= e($dash($position)) ?></dd>
                        </div>
                        <div class="ue-row">
                            <dt>Department</dt>
                            <dd><?= e($dash($department)) ?></dd>
                        </div>
                        <div class="ue-row">
                            <dt>Employment status</dt>
                            <dd><?= $empStatus !== '' ? '<span class="role-tag">' . e($empStatus) . '</span>' : '—' ?></dd>
                        </div>
                        <div class="ue-row">
                            <dt>Hire date</dt>
                            <dd><?= e($fmtDate($hireDate)) ?></dd>
                        </div>
                    </dl>
                </div>
            </section>
        </aside>
    </div>

    <div class="ue-toast-wrap" id="toastWrap" aria-live="polite"></div>
</main>

    <?php require BASE_PATH . '/resources/views/includes/footer.php'; ?>
</div>
<?php require BASE_PATH . '/resources/views/includes/scripts.php'; ?>
