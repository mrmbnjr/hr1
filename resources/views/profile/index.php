<?php

$pageTitle       = 'My Profile';
$pageCSS         = 'profile.css';
$pageJS          = 'profile.js';
$pageDescription = 'Your account details, personal information, and recent activity.';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('?page=login'));
    exit;
}

$profile         = $profile ?? [];
$activity        = $activity ?? [];
$activitySummary = $activitySummary ?? ['total' => 0, 'last_week' => 0, 'pages' => 0, 'last_at' => null];

$flash = $_SESSION['profile_flash'] ?? null;
unset($_SESSION['profile_flash']);

/* ---------------------------------------------------------------
   View helpers (closures so re-including never redeclares)
---------------------------------------------------------------- */
$e = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

$fmt = static function ($value, string $format = 'M j, Y'): string {
    if ($value === null || $value === '') {
        return '—';
    }
    $timestamp = strtotime((string) $value);
    return $timestamp ? date($format, $timestamp) : '—';
};

$ago = static function ($value): string {
    if ($value === null || $value === '') {
        return 'No activity yet';
    }
    $timestamp = strtotime((string) $value);
    if (!$timestamp) {
        return '—';
    }
    $diff = time() - $timestamp;
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return floor($diff / 60) . ' min ago';
    if ($diff < 86400)  return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800) {
        $days = (int) floor($diff / 86400);
        return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
    }
    return date('M j, Y', $timestamp);
};

/* ---------------------------------------------------------------
   Identity
---------------------------------------------------------------- */
$username   = trim((string) ($profile['username'] ?? ''));
$firstName  = trim((string) ($profile['first_name'] ?? ''));
$middleName = trim((string) ($profile['middle_name'] ?? ''));
$lastName   = trim((string) ($profile['last_name'] ?? ''));

$fullName = trim(preg_replace('/\s+/', ' ', $firstName . ' ' . $middleName . ' ' . $lastName) ?? '');
$isLinked = (int) ($profile['applicant_id'] ?? 0) > 0;
$displayName = $isLinked && $fullName !== ''
    ? $fullName
    : ($username !== '' ? $username : 'Your account');

if ($isLinked) {
    $initials = mb_strtoupper(mb_substr($firstName, 0, 1) . mb_substr($lastName, 0, 1));
} else {
    $initials = mb_strtoupper(mb_substr($username !== '' ? $username : 'U', 0, 2));
}
$initials = trim($initials) !== '' ? $initials : '?';

$statusRaw     = (string) ($profile['status'] ?? 'Active');
$mustChange    = (int) ($profile['must_change_password'] ?? 0);
$accountStatus = $statusRaw === 'Inactive'
    ? 'Inactive'
    : ($mustChange === 1 ? 'Pending Setup' : 'Active');
$statusClass   = ['Active' => 'is-active', 'Pending Setup' => 'is-pending', 'Inactive' => 'is-inactive'][$accountStatus] ?? 'is-inactive';

/* ---------------------------------------------------------------
   Details
---------------------------------------------------------------- */
$accountEmail     = trim((string) ($profile['account_email'] ?? ''));
$personalEmail    = trim((string) ($profile['personal_email'] ?? ''));
$phone            = trim((string) ($profile['phone'] ?? ''));
$address          = trim((string) ($profile['address'] ?? ''));
$employeeNumber   = trim((string) ($profile['employee_number'] ?? ''));
$position         = trim((string) ($profile['position_name'] ?? ''));
$department       = trim((string) ($profile['department_name'] ?? ''));
$employmentStatus = trim((string) ($profile['employment_status'] ?? ''));
$hireDate         = $profile['hire_date'] ?? null;
$createdAt        = $profile['created_at'] ?? null;
$verifiedAt       = $profile['email_verified_at'] ?? null;
$roleName         = trim((string) ($profile['role_name'] ?? 'User'));

/* Header meta line — built as a list so no empty <ul> is rendered. */
$heroMeta = [];
if ($position !== '') {
    $heroMeta[] = ['icon' => 'fa-user-tie', 'text' => $position];
}
if ($department !== '') {
    $heroMeta[] = ['icon' => 'fa-building', 'text' => $department];
}
if ($employeeNumber !== '') {
    $heroMeta[] = ['icon' => 'fa-id-badge', 'text' => $employeeNumber];
}
if (!$isLinked) {
    $heroMeta[] = ['icon' => 'fa-link-slash', 'text' => 'No employee record', 'muted' => true];
}

/* ---------------------------------------------------------------
   Tabs
---------------------------------------------------------------- */
$tabs = [
    'personal'   => ['label' => 'Personal',           'icon' => 'fa-user'],
    'employment' => ['label' => 'Employment',         'icon' => 'fa-briefcase'],
    'account'    => ['label' => 'Account & Security', 'icon' => 'fa-shield-halved'],
    'activity'   => ['label' => 'Activity',           'icon' => 'fa-clock-rotate-left'],
];

$activeTab = (string) ($_GET['tab'] ?? ($flash['tab'] ?? ($forcedTab ?? 'personal')));
if (!isset($tabs[$activeTab])) {
    $activeTab = 'personal';
}

/* ---------------------------------------------------------------
   Role context — links must stay inside what this role can open.
---------------------------------------------------------------- */
$roleCode  = strtoupper(trim((string) ($profile['role_code'] ?? 'EMP')));
$canBrowseRecords = in_array($roleCode, ['ADMIN', 'HR', 'MGR'], true);

$quickLinks = match ($roleCode) {
    'ADMIN' => [
        ['page' => 'user-management',  'icon' => 'fa-users',        'label' => 'User Management'],
        ['page' => 'employee-records', 'icon' => 'fa-address-book', 'label' => 'Employee Records'],
        ['page' => 'human-capital',    'icon' => 'fa-id-card',      'label' => 'Company Structure'],
    ],
    'HR' => [
        ['page' => 'employee-records', 'icon' => 'fa-address-book', 'label' => 'Employee Records'],
        ['page' => 'human-capital',    'icon' => 'fa-id-card',      'label' => 'Company Structure'],
        ['page' => 'onboarding',       'icon' => 'fa-user-check',   'label' => 'Onboarding'],
    ],
    'MGR' => [
        ['page' => 'employee-records',  'icon' => 'fa-address-book', 'label' => 'Employee Records'],
        ['page' => 'employee-requests', 'icon' => 'fa-user',         'label' => 'Employee Requests'],
        ['page' => 'applicants',        'icon' => 'fa-file-lines',   'label' => 'Applicants'],
    ],
    default => [
        ['page' => 'my-requests', 'icon' => 'fa-file-circle-plus', 'label' => 'My Requests'],
    ],
};

/* ---------------------------------------------------------------
   Setup checklist
---------------------------------------------------------------- */
$checklist = [
    ['label' => 'Account email', 'done' => $accountEmail !== '', 'hint' => 'The address used for sign-in codes.'],
    ['label' => 'Password set',  'done' => $mustChange === 0,    'hint' => 'Create your own password.'],
];

if ($isLinked) {
    $checklist[] = ['label' => 'Contact details',       'done' => $phone !== '' && $address !== '',       'hint' => 'Add a phone number and address.'];
    $checklist[] = ['label' => 'Employee record',       'done' => $employeeNumber !== '',                 'hint' => 'Employee number assigned by HR.'];
    $checklist[] = ['label' => 'Department & position', 'done' => $department !== '' && $position !== '', 'hint' => 'Your placement in the org chart.'];
}

$doneCount  = count(array_filter($checklist, static fn(array $item): bool => $item['done']));
$totalCount = count($checklist);
$percent    = $totalCount > 0 ? (int) round($doneCount / $totalCount * 100) : 0;

/* ---------------------------------------------------------------
   Activity presentation
---------------------------------------------------------------- */
$activityTone = static function (string $action): array {
    $a = strtolower($action);

    return match (true) {
        str_contains($a, 'logout') || str_contains($a, 'log out')                      => ['fa-right-from-bracket', 'is-logout'],
        str_contains($a, 'log') || str_contains($a, 'sign in')                          => ['fa-right-to-bracket', 'is-login'],
        str_contains($a, 'password')                                                    => ['fa-key', 'is-security'],
        str_contains($a, 'download')                                                    => ['fa-download', 'is-download'],
        str_contains($a, 'evaluate') || str_contains($a, 'analy')                       => ['fa-wand-magic-sparkles', 'is-ai'],
        str_contains($a, 'submit')                                                      => ['fa-paper-plane', 'is-submit'],
        str_contains($a, 'delete') || str_contains($a, 'reject')                        => ['fa-trash', 'is-danger'],
        str_contains($a, 'update') || str_contains($a, 'edit') || str_contains($a, 'save') => ['fa-pen', 'is-update'],
        default                                                                         => ['fa-circle-dot', 'is-default'],
    };
};

$flashType = ($flash['type'] ?? '') === 'success' ? 'success' : 'error';

?>

<?php require BASE_PATH . '/resources/views/includes/header.php'; ?>
<?php require BASE_PATH . '/resources/views/includes/sidebar.php'; ?>

<div class="main-content">
    <?php require BASE_PATH . '/resources/views/includes/navbar.php'; ?>

    <main class="profile-page">

        <?php if ($flash): ?>
            <div class="profile-flash <?= $flashType ?>" role="<?= $flashType === 'success' ? 'status' : 'alert' ?>">
                <i class="fa-solid <?= $flashType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>" aria-hidden="true"></i>
                <span><?= $e($flash['message'] ?? '') ?></span>
                <button type="button" class="flash-close" aria-label="Dismiss message"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
            </div>
        <?php endif; ?>

        <!-- =====================================================
             HEADER
        ====================================================== -->
        <header class="profile-hero">
            <div class="hero-avatar" aria-hidden="true"><?= $e($initials) ?></div>

            <div class="hero-id">
                <div class="hero-name-row">
                    <h1><?= $e($displayName) ?></h1>
                    <span class="status-pill <?= $statusClass ?>"><i class="status-dot" aria-hidden="true"></i><?= $e($accountStatus) ?></span>
                </div>

                <p class="hero-handle">
                    <span class="role-tag"><?= $e($roleName) ?></span>
                    <span class="hero-username">@<?= $e($username) ?></span>
                    <button type="button" class="copy-btn" data-copy="<?= $e($username) ?>" aria-label="Copy username" title="Copy username">
                        <i class="fa-regular fa-copy" aria-hidden="true"></i>
                    </button>
                </p>

                <?php if ($heroMeta): ?>
                    <ul class="hero-meta">
                        <?php foreach ($heroMeta as $item): ?>
                            <li<?= !empty($item['muted']) ? ' class="is-muted"' : '' ?>>
                                <i class="fa-solid <?= $e($item['icon']) ?>" aria-hidden="true"></i>
                                <span><?= $e($item['text']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="hero-actions">
                <button type="button" class="btn-primary" data-jump="personal" data-start-edit>
                    <i class="fa-solid fa-pen" aria-hidden="true"></i> Edit profile
                </button>
                <button type="button" class="btn-secondary" data-jump="account">
                    <i class="fa-solid fa-key" aria-hidden="true"></i> Change password
                </button>
            </div>
        </header>

        <!-- =====================================================
             BODY
        ====================================================== -->
        <div class="profile-grid">

            <div class="profile-main">

                <div class="profile-tabs" role="tablist" aria-label="Profile sections">
                    <?php foreach ($tabs as $key => $tab): ?>
                        <?php $isActive = $key === $activeTab; ?>
                        <button type="button"
                                class="profile-tab<?= $isActive ? ' active' : '' ?>"
                                role="tab"
                                id="tab-<?= $e($key) ?>"
                                aria-controls="panel-<?= $e($key) ?>"
                                aria-selected="<?= $isActive ? 'true' : 'false' ?>"
                                tabindex="<?= $isActive ? '0' : '-1' ?>"
                                data-tab="<?= $e($key) ?>">
                            <i class="fa-solid <?= $e($tab['icon']) ?>" aria-hidden="true"></i>
                            <span><?= $e($tab['label']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- PERSONAL -->
                <section class="profile-panel<?= $activeTab === 'personal' ? ' active' : '' ?>"
                         id="panel-personal" role="tabpanel" aria-labelledby="tab-personal"
                         <?= $activeTab === 'personal' ? '' : 'hidden' ?>>

                    <div class="panel-head">
                        <div>
                            <h2>Personal information</h2>
                            <p>Your name and contact details as they appear on your employee record.</p>
                        </div>
                    </div>

                    <?php if ($isLinked): ?>
                        <form class="info-form" method="post" action="?page=profile-action" data-editable>
                            <input type="hidden" name="action" value="personal">

                            <div class="form-toolbar">
                                <span class="edit-hint" data-edit-hint>
                                    <i class="fa-solid fa-lock" aria-hidden="true"></i> Viewing mode — select Edit to make changes.
                                </span>
                                <div class="form-buttons">
                                    <button type="button" class="btn-secondary btn-sm" data-edit-toggle>
                                        <i class="fa-solid fa-pen" aria-hidden="true"></i> Edit
                                    </button>
                                    <button type="button" class="btn-secondary btn-sm" data-cancel hidden>Cancel</button>
                                    <button type="submit" class="btn-primary btn-sm" data-save hidden>
                                        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save changes
                                    </button>
                                </div>
                            </div>

                            <div class="field-grid">
                                <div class="form-group">
                                    <label for="firstName">First name <span aria-hidden="true">*</span></label>
                                    <input type="text" id="firstName" name="first_name" value="<?= $e($firstName) ?>" maxlength="100" autocomplete="given-name" disabled required>
                                </div>
                                <div class="form-group">
                                    <label for="middleName">Middle name</label>
                                    <input type="text" id="middleName" name="middle_name" value="<?= $e($middleName) ?>" maxlength="100" autocomplete="additional-name" disabled>
                                </div>
                                <div class="form-group">
                                    <label for="lastName">Last name <span aria-hidden="true">*</span></label>
                                    <input type="text" id="lastName" name="last_name" value="<?= $e($lastName) ?>" maxlength="100" autocomplete="family-name" disabled required>
                                </div>
                                <div class="form-group">
                                    <label for="personalEmail">Contact email <span aria-hidden="true">*</span></label>
                                    <input type="email" id="personalEmail" name="personal_email" value="<?= $e($personalEmail) ?>" maxlength="150" autocomplete="email" disabled required>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone number <span aria-hidden="true">*</span></label>
                                    <input type="tel" id="phone" name="phone" value="<?= $e($phone) ?>" maxlength="20" autocomplete="tel" disabled required>
                                </div>
                                <div class="form-group is-wide">
                                    <label for="address">Address</label>
                                    <textarea id="address" name="address" rows="2" maxlength="500" autocomplete="street-address" disabled><?= $e($address) ?></textarea>
                                </div>
                            </div>

                            <p class="form-note">
                                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                <span>These details live on your employee record. Your sign-in email is managed in
                                <button type="button" class="link-btn" data-jump="account">Account &amp; Security</button>.</span>
                            </p>
                        </form>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-link-slash" aria-hidden="true"></i>
                            <h3>No employee record linked</h3>
                            <p>
                                This account isn't connected to an employee record, so there are no personal
                                details to show here.
                                <?php if ($canBrowseRecords): ?>
                                    An administrator can link it from
                                    <a href="?page=employee-records">Employee Records</a>.
                                <?php else: ?>
                                    Ask your HR administrator to link it.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- EMPLOYMENT -->
                <section class="profile-panel<?= $activeTab === 'employment' ? ' active' : '' ?>"
                         id="panel-employment" role="tabpanel" aria-labelledby="tab-employment"
                         <?= $activeTab === 'employment' ? '' : 'hidden' ?>>

                    <div class="panel-head">
                        <div>
                            <h2>Employment details</h2>
                            <p>Managed by HR. Contact your administrator to request a change.</p>
                        </div>
                        <span class="panel-badge"><i class="fa-solid fa-lock" aria-hidden="true"></i> Read-only</span>
                    </div>

                    <?php if ($isLinked): ?>
                        <dl class="info-list">
                            <div>
                                <dt>Employee number</dt>
                                <dd><?= $e($employeeNumber !== '' ? $employeeNumber : '—') ?></dd>
                            </div>
                            <div>
                                <dt>Employment status</dt>
                                <dd>
                                    <?php if ($employmentStatus !== ''): ?>
                                        <span class="soft-chip"><?= $e($employmentStatus) ?></span>
                                    <?php else: ?>—<?php endif; ?>
                                </dd>
                            </div>
                            <div>
                                <dt>Position</dt>
                                <dd><?= $e($position !== '' ? $position : '—') ?></dd>
                            </div>
                            <div>
                                <dt>Department</dt>
                                <dd><?= $e($department !== '' ? $department : '—') ?></dd>
                            </div>
                            <div>
                                <dt>Hire date</dt>
                                <dd><?= $e($fmt($hireDate)) ?></dd>
                            </div>
                            <div>
                                <dt>Employee ID</dt>
                                <dd><?= $e((string) ($profile['employee_id'] ?? '—')) ?></dd>
                            </div>
                        </dl>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-briefcase" aria-hidden="true"></i>
                            <h3>No employment details</h3>
                            <p>This account isn't linked to an employee record, so there is nothing to display yet.</p>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- ACCOUNT & SECURITY -->
                <section class="profile-panel<?= $activeTab === 'account' ? ' active' : '' ?>"
                         id="panel-account" role="tabpanel" aria-labelledby="tab-account"
                         <?= $activeTab === 'account' ? '' : 'hidden' ?>>

                    <div class="panel-head">
                        <div>
                            <h2>Sign-in details</h2>
                            <p>The identity you use to sign in to the system.</p>
                        </div>
                    </div>

                    <form class="info-form" method="post" action="?page=profile-action" data-editable>
                        <input type="hidden" name="action" value="account">

                        <div class="form-toolbar">
                            <span class="edit-hint" data-edit-hint>
                                <i class="fa-solid fa-lock" aria-hidden="true"></i> Viewing mode — select Edit to make changes.
                            </span>
                            <div class="form-buttons">
                                <button type="button" class="btn-secondary btn-sm" data-edit-toggle>
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i> Edit
                                </button>
                                <button type="button" class="btn-secondary btn-sm" data-cancel hidden>Cancel</button>
                                <button type="submit" class="btn-primary btn-sm" data-save hidden>
                                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save changes
                                </button>
                            </div>
                        </div>

                        <div class="field-grid">
                            <div class="form-group">
                                <label for="username">Username <span aria-hidden="true">*</span></label>
                                <input type="text" id="username" name="username" value="<?= $e($username) ?>" minlength="3" maxlength="50"
                                       pattern="[A-Za-z0-9._-]+" autocomplete="username" disabled required>
                                <small class="field-help">3–50 characters: letters, numbers, dots, dashes, or underscores.</small>
                            </div>
                            <div class="form-group">
                                <label for="accountEmail">Sign-in email <span aria-hidden="true">*</span></label>
                                <input type="email" id="accountEmail" name="account_email" value="<?= $e($accountEmail) ?>" maxlength="150"
                                       autocomplete="email" disabled required>
                                <small class="field-help <?= $verifiedAt ? 'is-ok' : 'is-warn' ?>">
                                    <i class="fa-solid <?= $verifiedAt ? 'fa-circle-check' : 'fa-circle-exclamation' ?>" aria-hidden="true"></i>
                                    <?= $verifiedAt ? 'Verified. Sign-in codes are sent here.' : 'Not verified. Sign-in codes are sent here.' ?>
                                </small>
                            </div>
                            <div class="form-group">
                                <label for="roleDisplay">Role</label>
                                <input type="text" id="roleDisplay" value="<?= $e($roleName) ?>" readonly>
                                <small class="field-help">Roles are assigned in User Management.</small>
                            </div>
                            <div class="form-group">
                                <label for="statusDisplay">Account status</label>
                                <input type="text" id="statusDisplay" value="<?= $e($accountStatus) ?>" readonly>
                                <small class="field-help">Accounts are enabled or disabled by an administrator.</small>
                            </div>
                        </div>
                    </form>

                    <hr class="panel-divider">

                    <div class="panel-head is-compact">
                        <div>
                            <h2>Change password</h2>
                            <p>Use at least 8 characters with a mix of letters and numbers.</p>
                        </div>
                    </div>

                    <form class="info-form" method="post" action="?page=profile-action" id="passwordForm">
                        <input type="hidden" name="action" value="password">

                        <div class="field-grid">
                            <div class="form-group is-wide">
                                <label for="currentPassword">Current password <span aria-hidden="true">*</span></label>
                                <div class="password-field">
                                    <input type="password" id="currentPassword" name="current_password" autocomplete="current-password" required>
                                    <button type="button" class="reveal-btn" data-reveal aria-label="Show password" aria-pressed="false">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="newPassword">New password <span aria-hidden="true">*</span></label>
                                <div class="password-field">
                                    <input type="password" id="newPassword" name="new_password" minlength="8" autocomplete="new-password" required data-strength-input>
                                    <button type="button" class="reveal-btn" data-reveal aria-label="Show password" aria-pressed="false">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="strength" data-strength>
                                    <div class="strength-track"><span class="strength-fill" data-strength-fill></span></div>
                                    <span class="strength-label" data-strength-label>Use 8+ characters with letters and numbers.</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="confirmPassword">Confirm new password <span aria-hidden="true">*</span></label>
                                <div class="password-field">
                                    <input type="password" id="confirmPassword" name="confirm_password" minlength="8" autocomplete="new-password" required>
                                    <button type="button" class="reveal-btn" data-reveal aria-label="Show password" aria-pressed="false">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <small class="field-help" data-match-hint>Both entries must match.</small>
                            </div>
                        </div>

                        <div class="form-actions-end">
                            <button type="submit" class="btn-primary">
                                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Update password
                            </button>
                        </div>
                    </form>
                </section>

                <!-- ACTIVITY -->
                <section class="profile-panel<?= $activeTab === 'activity' ? ' active' : '' ?>"
                         id="panel-activity" role="tabpanel" aria-labelledby="tab-activity"
                         <?= $activeTab === 'activity' ? '' : 'hidden' ?>>

                    <div class="panel-head">
                        <div>
                            <h2>Recent activity</h2>
                            <p>
                                <?= (int) $activitySummary['total'] ?> actions logged,
                                <?= (int) $activitySummary['last_week'] ?> in the last 7 days.
                                Last active <?= $e(strtolower($ago($activitySummary['last_at']))) ?>.
                            </p>
                        </div>
                    </div>

                    <?php if ($activity): ?>
                        <ol class="activity-list">
                            <?php foreach ($activity as $entry): ?>
                                <?php [$icon, $tone] = $activityTone((string) ($entry['action'] ?? '')); ?>
                                <li class="activity-item">
                                    <span class="activity-icon <?= $e($tone) ?>" aria-hidden="true"><i class="fa-solid <?= $e($icon) ?>"></i></span>
                                    <div class="activity-body">
                                        <p class="activity-action"><?= $e($entry['action'] ?? '') ?></p>
                                        <p class="activity-meta">
                                            <?php if (!empty($entry['page'])): ?>
                                                <span><i class="fa-solid fa-tag" aria-hidden="true"></i><?= $e($entry['page']) ?></span>
                                            <?php endif; ?>
                                            <span><i class="fa-solid fa-code-branch" aria-hidden="true"></i><?= $e($entry['request_method'] ?? 'GET') ?></span>
                                            <?php if (!empty($entry['ip_address'])): ?>
                                                <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i><?= $e($entry['ip_address']) ?></span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <time class="activity-time" datetime="<?= $e($entry['created_at'] ?? '') ?>" title="<?= $e($fmt($entry['created_at'] ?? null, 'M j, Y g:i A')) ?>">
                                        <?= $e($ago($entry['created_at'] ?? null)) ?>
                                    </time>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                            <h3>No activity yet</h3>
                            <p>Actions you take across the system will appear here.</p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <!-- =============================================
                 SIDE COLUMN
            ============================================== -->
            <aside class="profile-side" aria-label="Account summary">

                <section class="side-card">
                    <h2 class="side-card-title">Profile setup</h2>

                    <div class="progress">
                        <div class="progress-track"><span class="progress-fill" style="width: <?= (int) $percent ?>%"></span></div>
                        <span class="progress-label"><?= (int) $doneCount ?> of <?= (int) $totalCount ?> complete</span>
                    </div>

                    <ul class="checklist">
                        <?php foreach ($checklist as $item): ?>
                            <li class="<?= $item['done'] ? 'is-done' : 'is-todo' ?>">
                                <i class="fa-solid <?= $item['done'] ? 'fa-circle-check' : 'fa-circle' ?>" aria-hidden="true"></i>
                                <span>
                                    <?= $e($item['label']) ?>
                                    <?php if (!$item['done']): ?><small><?= $e($item['hint']) ?></small><?php endif; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if (!$isLinked): ?>
                        <p class="side-note">
                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                            Personal details appear once this account is linked to an employee record.
                        </p>
                    <?php endif; ?>
                </section>

                <section class="side-card">
                    <h2 class="side-card-title">Quick links</h2>

                    <nav class="quick-links">
                        <?php foreach ($quickLinks as $link): ?>
                            <a href="?page=<?= $e($link['page']) ?>">
                                <i class="fa-solid <?= $e($link['icon']) ?>" aria-hidden="true"></i>
                                <?= $e($link['label']) ?>
                                <i class="fa-solid fa-chevron-right go" aria-hidden="true"></i>
                            </a>
                        <?php endforeach; ?>
                    </nav>

                    <p class="side-meta">Member since <?= $e($fmt($createdAt)) ?></p>
                </section>
            </aside>
        </div>
    </main>

    <?php require BASE_PATH . '/resources/views/includes/footer.php'; ?>
</div>

<div class="profile-toast" id="profileToast" role="status" aria-live="polite" hidden></div>

<?php require BASE_PATH . '/resources/views/includes/scripts.php'; ?>