<?php

$pageTitle = 'User Management';
$pageCSS = 'user-management.css';
$pageJS = 'user-management.js';
$pageDescription = 'Manage employee accounts, system access, roles, and account status.';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('?page=login'));
    exit;
}

$users = $users ?? [];
$roles = $roles ?? [];
$departments = $departments ?? [];
$flash = $_SESSION['user_management_flash'] ?? null;
unset($_SESSION['user_management_flash']);

/* ---------------------------------------------------------------
   View helpers. Closures (not named functions) so re-including this
   view can never trigger "cannot redeclare". Move to a presenter
   or helper file when convenient.
---------------------------------------------------------------- */
$e = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

// Single source of truth for account status (was duplicated in the loop and in a function).
$accountStatus = static function (array $user): string {
    if (($user['status'] ?? '') === 'Inactive') {
        return 'Inactive';
    }
    return (int) ($user['must_change_password'] ?? 0) === 1 ? 'Pending Setup' : 'Active';
};

$statusClass = [
    'Active'        => 'is-active',
    'Pending Setup' => 'is-pending',
    'Inactive'      => 'is-inactive',
];

// An active account nobody has used for a while is worth a second look.
$dormantDays   = 90;
$dormantCutoff = strtotime("-{$dormantDays} days");

$checkUnlinked = static fn(array $user): bool => trim((string) ($user['fullname'] ?? '')) === '';
$checkDormant  = static function (array $user, string $status) use ($dormantCutoff): bool {
    if ($status !== 'Active') {
        return false;
    }
    // Measure from the last sign-in, or from creation if they've never signed in.
    $since = strtotime((string) (($user['last_login'] ?? '') ?: ($user['created_at'] ?? '')));
    return $since !== false && $since < $dormantCutoff;
};

$counts = ['Active' => 0, 'Pending Setup' => 0, 'Inactive' => 0];
$dormantCount = 0;
$unlinkedCount = 0;
$lastSignIn = 0;
foreach ($users as $user) {
    $status = $accountStatus($user);
    $counts[$status]++;
    $dormantCount  += $checkDormant($user, $status) ? 1 : 0;
    $unlinkedCount += $checkUnlinked($user) ? 1 : 0;
    if (!empty($user['last_login'])) {
        $lastSignIn = max($lastSignIn, (int) strtotime($user['last_login']));
    }
}
$totalUsers = count($users);
$canSignIn  = $counts['Active'] + $counts['Pending Setup'];   // pending accounts can sign in, they just must set a password

// The status tabs above the table double as its status filter.
$statusKeys = [
    'All'           => ['label' => 'All accounts',  'dot' => '',            'count' => $totalUsers],
    'Active'        => ['label' => 'Active',        'dot' => 'is-active',   'count' => $counts['Active']],
    'Pending Setup' => ['label' => 'Pending setup', 'dot' => 'is-pending',  'count' => $counts['Pending Setup']],
    'Inactive'      => ['label' => 'Inactive',      'dot' => 'is-inactive', 'count' => $counts['Inactive']],
];

$flashType = ($flash['type'] ?? '') === 'success' ? 'success' : 'error';
?>

<?php require BASE_PATH . '/resources/views/includes/header.php'; ?>
<?php require BASE_PATH . '/resources/views/includes/sidebar.php'; ?>

<div class="main-content">
    <?php require BASE_PATH . '/resources/views/includes/navbar.php'; ?>

    <main class="user-management-page">
        <?php if ($flash): ?>
            <div class="user-flash <?= $flashType ?>" role="<?= $flashType === 'success' ? 'status' : 'alert' ?>">
                <i class="fa-solid <?= $flashType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>" aria-hidden="true"></i>
                <?= $e($flash['message'] ?? '') ?>
            </div>
        <?php endif; ?>

        <section class="table-card user-accounts-card" aria-label="User accounts">
            <!-- Card header: a plain-language read on the roster. -->
            <div class="section-heading">
                <?php if ($totalUsers === 0): ?>
                    <h2 class="account-headline">No accounts yet</h2>
                    <p class="account-note">Accounts will show up here once they're created.</p>
                <?php else: ?>
                    <h2 class="account-headline"><?= $canSignIn ?> of <?= $totalUsers ?> account<?= $totalUsers === 1 ? '' : 's' ?> can sign in</h2>
                    <p class="account-note">
                        <?php if ($counts['Pending Setup'] > 0): ?>
                            <?= $counts['Pending Setup'] ?> <?= $counts['Pending Setup'] === 1 ? "hasn't" : "haven't" ?> set a first password yet.
                        <?php endif; ?>
                        <?= $lastSignIn ? 'Last sign-in was ' . $e(date('M j, Y g:ia', $lastSignIn)) . '.' : 'Nobody has signed in yet.' ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Reuses the shared .filter-bar so search and selects pick up the shared field styling. -->
            <div class="filter-bar user-toolbar">
                <div class="filter-search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input id="userSearch" type="search" placeholder="Search name, username, email, or ID" aria-label="Search users">
                </div>
                <select id="roleFilter" aria-label="Filter by role">
                    <option value="All">Any role</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $e($role['role_name']) ?>"><?= $e($role['role_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="departmentFilter" aria-label="Filter by department">
                    <option value="All">Any department</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= $e($department['department_name']) ?>"><?= $e($department['department_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="sortFilter" aria-label="Sort users">
                    <option value="newest">Newest first</option>
                    <option value="oldest">Oldest first</option>
                    <option value="name-az">Name, A-Z</option>
                    <option value="name-za">Name, Z-A</option>
                    <option value="status">By status</option>
                </select>
            </div>

            <!-- Status tabs. The strip under them is the same data as proportions and dims everything
                 except the selected status, so it reads as the underline of the table, not a separate chart. -->
            <div class="status-tabs" id="statusTabs" data-selected="All">
                <div class="status-bar">
                    <div class="tab-row" role="group" aria-label="Filter by account status">
                        <?php foreach ($statusKeys as $value => $key): ?>
                            <button type="button" class="status-tab<?= $value === 'All' ? ' active' : '' ?>"
                                    data-status-filter="<?= $e($value) ?>" aria-pressed="<?= $value === 'All' ? 'true' : 'false' ?>">
                                <?php if ($key['dot'] !== ''): ?><span class="tab-dot <?= $key['dot'] ?>" aria-hidden="true"></span><?php endif; ?>
                                <?= $e($key['label']) ?>
                                <span class="tab-count"><?= (int) $key['count'] ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($totalUsers > 0): ?>
                        <div class="attention" role="group" aria-label="Accounts that may need review">
                            <?php if ($dormantCount + $unlinkedCount === 0): ?>
                                <p class="attention-clear" title="Every account is linked to an employee and none have gone quiet.">
                                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                    Nothing needs attention
                                </p>
                            <?php else: ?>
                                <span class="attention-title">Needs attention</span>
                                <?php if ($dormantCount > 0): ?>
                                    <button type="button" class="attention-item" data-attention="dormant" aria-pressed="false"
                                            title="Active accounts with no sign-in for <?= $dormantDays ?> days. Disable any that are no longer needed.">
                                        <span class="attention-count"><?= $dormantCount ?></span>No sign-in for <?= $dormantDays ?> days
                                    </button>
                                <?php endif; ?>
                                <?php if ($unlinkedCount > 0): ?>
                                    <button type="button" class="attention-item" data-attention="unlinked" aria-pressed="false"
                                            title="No employee record attached, so name and email are missing.">
                                        <span class="attention-count"><?= $unlinkedCount ?></span>Not linked to an employee
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="table-scroll">
                <table class="data-table user-table" id="userTable">
                    <caption class="sr-only">User accounts</caption>
                    <thead>
                        <tr>
                            <th scope="col">User</th>
                            <th scope="col">Login</th>
                            <th scope="col">Role</th>
                            <th scope="col">Department</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="last-login">Last login</th>
                            <th scope="col" class="actions-heading"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <?php
                        $status         = $accountStatus($user);
                        $rawName        = trim((string) ($user['fullname'] ?? ''));
                        $isUnlinked     = $rawName === '';
                        $fullname       = $isUnlinked ? 'Unlinked account' : $rawName;
                        $initial        = mb_strtoupper(mb_substr($fullname, 0, 1)); // multibyte-safe (ñ, etc.)
                        $employeeNo     = trim((string) ($user['employee_number'] ?? ''));
                        $roleId         = (int) ($user['role_id'] ?? 0);
                        $dormant        = $checkDormant($user, $status);
                        $email          = trim((string) ($user['email'] ?? ''));
                        $lastLogin      = !empty($user['last_login']) ? date('M j, Y g:ia', strtotime($user['last_login'])) : 'Never logged in';
                        $createdDisplay = !empty($user['created_at']) ? date('M j, Y', strtotime($user['created_at'])) : '—';
                        ?>
                        <tr class="user-row"
                            data-name="<?= $e(mb_strtolower($fullname)) ?>"
                            data-employee-id="<?= $e(mb_strtolower($employeeNo)) ?>"
                            data-username="<?= $e(mb_strtolower((string) ($user['username'] ?? ''))) ?>"
                            data-email="<?= $e(mb_strtolower($email)) ?>"
                            data-role="<?= $e($user['role_name'] ?? '') ?>"
                            data-department="<?= $e($user['department_name'] ?? '') ?>"
                            data-status="<?= $e($status) ?>"
                            data-created="<?= $e($user['created_at'] ?? '') ?>"
                            data-created-display="<?= $e($createdDisplay) ?>"
                            data-last-login="<?= $e($lastLogin) ?>"
                            data-user-id="<?= (int) $user['user_id'] ?>"
                            data-fullname="<?= $e($fullname) ?>"
                            data-login="<?= $e($user['username'] ?? '') ?>"
                            data-email-address="<?= $e($email) ?>"
                            data-employee-number="<?= $e($employeeNo) ?>"
                            data-role-id="<?= $roleId ?: '' ?>"
                            data-dormant="<?= $dormant ? '1' : '0' ?>"
                            data-unlinked="<?= $isUnlinked ? '1' : '0' ?>">
                            <td data-label="User">
                                <button type="button" class="user-identity" data-action="details">
                                    <span class="avatar-circle"><?= $e($initial) ?></span>
                                    <span class="cell-text">
                                        <strong>
                                            <?= $e($fullname) ?>
                                            <?php if ($isUnlinked): ?>
                                                <i class="fa-solid fa-triangle-exclamation unlinked-flag" role="img" aria-label="Not linked to an employee record" title="Not linked to an employee record"></i>
                                            <?php endif; ?>
                                        </strong>
                                        <small class="sub-text"><?= $e($employeeNo !== '' ? $employeeNo : 'No employee ID') ?></small>
                                    </span>
                                </button>
                            </td>
                            <td data-label="Login">
                                <span class="cell-text">
                                    <strong><?= $e($user['username'] ?? '') ?></strong>
                                    <small class="sub-text"><?= $e($email !== '' ? $email : 'No email on record') ?></small>
                                </span>
                            </td>
                            <td data-label="Role"><span class="role-tag"><?= $e($user['role_name'] ?? '') ?></span></td>
                            <td data-label="Department"><?= $e($user['department_name'] ?? 'Unassigned') ?></td>
                            <td data-label="Status" class="status-cell">
                                <span class="status-pill <?= $statusClass[$status] ?>"><i class="status-dot" aria-hidden="true"></i><?= $e($status) ?></span>
                            </td>
                            <td data-label="Last login" class="last-login"><?= $e($lastLogin) ?></td>
                            <td class="actions-cell">
                                <button type="button" class="icon-action" data-action="menu" aria-label="Open actions for <?= $e($fullname) ?>" aria-haspopup="true" aria-expanded="false"><i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i></button>
                                <div class="row-menu" hidden>
                                    <button type="button" data-action="details"><i class="fa-regular fa-eye" aria-hidden="true"></i> View details</button>
                                    <button type="button" data-action="edit"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edit user</button>
                                    <button type="button" data-action="toggle"<?= $status === 'Inactive' ? '' : ' class="is-danger"' ?>><i class="fa-solid fa-power-off" aria-hidden="true"></i> <?= $status === 'Inactive' ? 'Enable account' : 'Disable account' ?></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="empty-state" id="userEmptyState" hidden>
                <i class="fa-solid fa-user-slash" aria-hidden="true"></i>
                <h3>No users found</h3>
                <p>Try adjusting your search or filters.</p>
                <button type="button" class="btn-outline" id="resetUserFilters">Reset filters</button>
            </div>

            <div class="table-footer">
                <span id="resultsCount" aria-live="polite"></span>
                <div class="pagination" aria-label="User account pagination">
                    <button type="button" class="page-btn" id="prevPage" aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
                    <span class="page-numbers" id="pageNumbers"></span>
                    <button type="button" class="page-btn" id="nextPage" aria-label="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
                </div>
            </div>
        </section>
    </main>
    <?php require BASE_PATH . '/resources/views/includes/footer.php'; ?>
</div>

<div class="modal-backdrop" id="userModal" hidden>
    <section class="user-modal" role="dialog" aria-modal="true" aria-labelledby="userModalTitle">
        <div class="modal-header">
            <div>
                <h2 id="userModalTitle">Edit user</h2>
                <p>Update this account's username, role, or access status.</p>
            </div>
            <button type="button" class="modal-close" data-close-modal aria-label="Close dialog"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
        </div>
        <form method="post" action="?page=user-management-action" id="userForm">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="user_id" id="formUserId">
            <div class="modal-body">
                <div class="form-group">
                    <label for="username">Username <span aria-hidden="true">*</span></label>
                    <input type="text" name="username" id="username" minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]+" aria-describedby="usernameHelp" required>
                    <small class="field-help" id="usernameHelp">3–50 characters: letters, numbers, dots, dashes, or underscores.</small>
                </div>
                <div class="form-group">
                    <label for="accountEmail">Email</label>
                    <input type="email" id="accountEmail" readonly aria-describedby="emailHelp">
                    <small class="field-help" id="emailHelp">Read from the employee record. Accounts don't store a separate email.</small>
                </div>
                <div class="form-group">
                    <label for="roleId">Role <span aria-hidden="true">*</span></label>
                    <select name="role_id" id="roleId" required>
                        <option value="">Select a role</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= (int) $role['role_id'] ?>"><?= $e($role['role_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="accountStatus">Account status</label>
                    <select name="status" id="accountStatus" aria-describedby="statusHelp">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                    <small class="field-help" id="statusHelp">Inactive accounts can't sign in.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" data-close-modal>Cancel</button>
                <button type="submit" class="btn-primary">Save changes</button>
            </div>
        </form>
    </section>
</div>

<div class="modal-backdrop" id="detailsModal" hidden>
    <section class="user-modal details-modal" role="dialog" aria-modal="true" aria-labelledby="detailsTitle">
        <div class="modal-header">
            <div>
                <h2 id="detailsTitle">User details</h2>
                <p>System access information for this account.</p>
            </div>
            <button type="button" class="modal-close" data-close-modal aria-label="Close dialog"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
        </div>
        <div class="details-content">
            <div class="details-identity">
                <span class="avatar-large" id="detailsAvatar">?</span>
                <div>
                    <h3 id="detailsName">-</h3>
                    <p id="detailsEmployee">-</p>
                    <span id="detailsStatus" class="status-pill">-</span>
                </div>
            </div>
            <dl class="details-grid">
                <div><dt>Username</dt><dd id="detailsUsername">-</dd></div>
                <div><dt>Role</dt><dd id="detailsRole">-</dd></div>
                <div><dt>Email</dt><dd id="detailsEmail">-</dd></div>
                <div><dt>Department</dt><dd id="detailsDepartment">-</dd></div>
                <div><dt>Created</dt><dd id="detailsCreated">-</dd></div>
                <div><dt>Last login</dt><dd id="detailsLastLogin">Never logged in</dd></div>
            </dl>
        </div>
    </section>
</div>

<?php require BASE_PATH . '/resources/views/includes/scripts.php'; ?>