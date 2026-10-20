<?php

$pageTitle = "Applicant Management";
$pageCSS = "applicants.css";
$pageJS = "applicants.js";
$pageDescription = "Review applicants, monitor AI screening, and manage the hiring pipeline.";

if (!isset($_SESSION['user_id'])) {
    header("Location: " . url("?page=login"));
    exit;
}

/* ==========================================================
   DATA
   $applicants / $positions are expected to be passed in by
   ApplicantController. Each $applicant row is expected to
   carry the joined applicant + application + ai_screening +
   interview + manager-decision fields described below.
   ========================================================== */

$applicants = $applicants ?? [];
$positions  = $positions  ?? [];

$formatRelativeTime = static function (?string $appliedAt): string {
    if (empty($appliedAt)) {
        return 'Time unavailable';
    }

    try {
        $submittedAt = new DateTimeImmutable($appliedAt);
    } catch (Exception $exception) {
        return 'Time unavailable';
    }

    $elapsedSeconds = max(0, time() - $submittedAt->getTimestamp());

    if ($elapsedSeconds < 60) {
        return 'Just now';
    }

    $units = [
        86400 => 'day',
        3600  => 'hour',
        60    => 'minute',
    ];

    foreach ($units as $secondsPerUnit => $unit) {
        if ($elapsedSeconds >= $secondsPerUnit) {
            $count = intdiv($elapsedSeconds, $secondsPerUnit);
            return $count . ' ' . $unit . ($count === 1 ? '' : 's') . ' ago';
        }
    }

    return 'Just now';
};

/**
 * Canonical status set for RAM-YUM Applicant Management.
 * Keys are the values stored in applications.application_status.
 */
$statusMeta = [
    "Submitted"    => ["label" => "Submitted",    "class" => "badge-gray"],
    "Under Review" => ["label" => "Under Review", "class" => "badge-blue"],
    "Interview"    => ["label" => "Interview",    "class" => "badge-purple"],
    "Selection"    => ["label" => "Selection",    "class" => "badge-orange"],
    "Offer"        => ["label" => "Offer",        "class" => "badge-teal"],
    "Hired"        => ["label" => "Hired",        "class" => "badge-green"],
    "Rejected"     => ["label" => "Rejected",     "class" => "badge-red"],
];

/* Summary counts for the dashboard cards */
$summary = [
    "Total"        => count($applicants),
    "Submitted"    => 0,
    "Under Review" => 0,
    "Interview"    => 0,
    "Selection"    => 0,
    "Offer"        => 0,
    "Hired"        => 0,
    "Rejected"     => 0,
];

foreach ($applicants as $a) {
    $status = $a['application_status'] ?? 'Submitted';
    if (isset($summary[$status])) {
        $summary[$status]++;
    }
}

?>

<?php require BASE_PATH . '/resources/views/includes/header.php'; ?>
<?php require BASE_PATH . '/resources/views/includes/sidebar.php'; ?>

<div class="main-content">

    <?php require BASE_PATH . '/resources/views/includes/navbar.php'; ?>

    <div class="applicants-page">

        <!-- ==========================================================
            SUMMARY STRIP
            One card: the total on the left, one neutral tile per status.
            Every element with .summary-card doubles as a status filter;
            data-filter is read by applicants.js.
        ========================================================== -->

        <?php
        $summaryTiles = [
            ['filter' => 'Submitted',    'tone' => 'gray',   'value' => (int) $summary['Submitted']],
            ['filter' => 'Under Review', 'tone' => 'blue',   'value' => (int) $summary['Under Review']],
            ['filter' => 'Interview',    'tone' => 'purple', 'value' => (int) $summary['Interview']],
            ['filter' => 'Selection',    'tone' => 'orange', 'value' => (int) $summary['Selection']],
            ['filter' => 'Offer',        'tone' => 'teal',   'value' => (int) $summary['Offer']],
            ['filter' => 'Hired',        'tone' => 'green',  'value' => (int) $summary['Hired']],
            ['filter' => 'Rejected',     'tone' => 'red',    'value' => (int) $summary['Rejected']],
        ];
        ?>

        <section class="summary-strip" aria-label="Applicant summary">

            <div class="summary-total summary-card" data-filter="All" role="button" tabindex="0" aria-pressed="false">
                <div class="summary-total-icon">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="summary-total-body">
                    <span>Total Applicants</span>
                    <strong><?= (int) $summary['Total'] ?></strong>
                    <small>Across all postings</small>
                </div>
            </div>

            <div class="summary-grid">
                <?php foreach ($summaryTiles as $tile): ?>
                    <div class="summary-tile tone-<?= htmlspecialchars($tile['tone']) ?> summary-card"
                         data-filter="<?= htmlspecialchars($tile['filter']) ?>"
                         data-empty="<?= $tile['value'] === 0 ? 'true' : 'false' ?>"
                         role="button"
                         tabindex="0"
                         aria-pressed="false">
                        <span class="tile-label">
                            <i class="tile-dot"></i>
                            <?= htmlspecialchars($tile['filter']) ?>
                        </span>
                        <strong><?= (int) $tile['value'] ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>

        </section>

        <!-- ==========================================================
            APPLICANT PANEL  (filters + table + footer)
        ========================================================== -->

        <section class="applicant-panel">

            <div class="filter-bar">

                <div class="filter-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="applicantSearch" placeholder="Search by name or email...">
                </div>

                <div class="select-field">
                    <i class="fa-solid fa-briefcase select-icon"></i>
                    <select id="positionFilter" aria-label="Filter by position">
                        <option value="All">All Positions</option>
                        <?php foreach ($positions as $position): ?>
                            <option value="<?= htmlspecialchars($position['title']) ?>">
                                <?= htmlspecialchars($position['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down select-caret"></i>
                </div>

                <div class="select-field">
                    <i class="fa-solid fa-bullseye select-icon"></i>
                    <select id="statusFilter" aria-label="Filter by status">
                        <option value="All">All Statuses</option>
                        <?php foreach ($statusMeta as $statusKey => $statusInfo): ?>
                            <option value="<?= htmlspecialchars($statusKey) ?>">
                                <?= htmlspecialchars($statusInfo['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down select-caret"></i>
                </div>

                <div class="select-field">
                    <i class="fa-solid fa-arrow-down-wide-short select-icon"></i>
                    <select id="sortFilter" aria-label="Sort applicants">
                        <option value="priority">Sort by Priority</option>
                        <option value="highest-score">Highest AI Score</option>
                        <option value="lowest-score">Lowest AI Score</option>
                        <option value="newest">Newest Applications</option>
                        <option value="oldest">Oldest Applications</option>
                        <option value="name-az">Applicant Name (A-Z)</option>
                        <option value="name-za">Applicant Name (Z-A)</option>
                    </select>
                    <i class="fa-solid fa-chevron-down select-caret"></i>
                </div>

                <button type="button" class="btn-secondary clear-filters" id="clearFilters">
                    <i class="fa-solid fa-arrows-rotate"></i> Clear Filters
                </button>

            </div>

            <p class="results-count" id="resultsCount"></p>

            <div class="table-card">

                <div class="table-scroll">
                    <table class="applicant-table" id="applicantTable">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Applied Position</th>
                                <th>AI Score</th>
                                <th>Status</th>
                                <th>Applied</th>
                                <th class="col-chevron"></th>
                            </tr>
                        </thead>
                        <tbody>

                        <?php if (empty($applicants)): ?>

                            <tr class="empty-row">
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-user-slash"></i>
                                        <p>No applicants found.</p>
                                    </div>
                                </td>
                            </tr>

                        <?php else: ?>

                            <tr class="no-match-row" id="noMatchRow" hidden>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-filter-circle-xmark"></i>
                                        <p>No applicants match your filters.</p>
                                        <button type="button" class="btn-secondary" id="clearFiltersInline">
                                            <i class="fa-solid fa-arrows-rotate"></i> Reset filters
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <?php foreach ($applicants as $applicant):

                                $status     = $applicant['application_status'] ?? 'Submitted';
                                $meta       = $statusMeta[$status] ?? $statusMeta['Submitted'];
                                $aiScore    = (int)($applicant['ai_score'] ?? 0);

                                $skillsMatch     = (int)($applicant['skills_match'] ?? 0);
                                $experienceMatch = (int)($applicant['experience_match'] ?? 0);
                                $educationMatch  = (int)($applicant['education_match'] ?? 0);
                                $recommendation  = $applicant['recommendation'] ?? 'Needs Review';

                                $interviewStatus = $applicant['interview_status'] ?? 'Not Scheduled';
                                $interviewDate   = $applicant['interview_date'] ?? '';
                                $interviewTime   = $applicant['interview_time'] ?? '';
                                $interviewMgr    = $applicant['interview_manager'] ?? '';
                                $interviewLoc    = $applicant['interview_location'] ?? '';
                                $interviewNotes  = $applicant['interview_notes'] ?? '';

                                $mgrRecommendation = $applicant['manager_recommendation'] ?? '';
                                $mgrRemarks         = $applicant['manager_remarks'] ?? '';

                                /* Optional: shown under the position title when the controller supplies it */
                                $department = $applicant['department'] ?? '';

                                /* Presentation only: score tier + "not screened" state + exact date */
                                $scoreRaw   = $applicant['ai_score'] ?? null;
                                $isScreened = $scoreRaw !== null && $scoreRaw !== '';
                                $scoreTier  = $aiScore >= 75 ? 'high' : ($aiScore >= 50 ? 'mid' : 'low');

                                $appliedLabel = '';
                                if (!empty($applicant['applied_at'])) {
                                    try {
                                        $appliedLabel = (new DateTimeImmutable($applicant['applied_at']))->format('M j, Y');
                                    } catch (Exception $e) {
                                        $appliedLabel = '';
                                    }
                                }

                            ?>

                            <tr class="applicant-row"
                                data-row-href="?page=review&id=<?= (int)$applicant['applicant_id'] ?>"
                                tabindex="0"
                                role="link"
                                data-application-id="<?= (int) $applicant['application_id'] ?>"
                                data-status="<?= htmlspecialchars($status) ?>"
                                data-position="<?= htmlspecialchars($applicant['position'] ?? '') ?>"
                                data-name="<?= htmlspecialchars(strtolower($applicant['fullname'] ?? '')) ?>"
                                data-id="<?= htmlspecialchars($applicant['applicant_id'] ?? '') ?>"
                                data-app-id="<?= htmlspecialchars($applicant['application_id'] ?? '') ?>"
                                data-fullname="<?= htmlspecialchars($applicant['fullname'] ?? '') ?>"
                                data-email="<?= htmlspecialchars($applicant['email'] ?? '') ?>"
                                data-phone="<?= htmlspecialchars($applicant['phone'] ?? '') ?>"
                                data-applied-date="<?= htmlspecialchars($applicant['applied_at'] ?? '') ?>"
                                data-resume="<?= htmlspecialchars($applicant['resume'] ?? '') ?>"
                                data-resume-name="<?= htmlspecialchars($applicant['resume_filename'] ?? 'resume.pdf') ?>"
                                data-ai-score="<?= $aiScore ?>"
                                data-recommendation="<?= htmlspecialchars($recommendation) ?>"
                                data-skills="<?= $skillsMatch ?>"
                                data-experience="<?= $experienceMatch ?>"
                                data-education="<?= $educationMatch ?>"
                                data-interview-status="<?= htmlspecialchars($interviewStatus) ?>"
                                data-interview-date="<?= htmlspecialchars($interviewDate) ?>"
                                data-interview-time="<?= htmlspecialchars($interviewTime) ?>"
                                data-interview-manager="<?= htmlspecialchars($interviewMgr) ?>"
                                data-interview-location="<?= htmlspecialchars($interviewLoc) ?>"
                                data-interview-notes="<?= htmlspecialchars($interviewNotes) ?>"
                                data-mgr-recommendation="<?= htmlspecialchars($mgrRecommendation) ?>"
                                data-mgr-remarks="<?= htmlspecialchars($mgrRemarks) ?>">

                                <td>
                                    <a href="?page=review&id=<?= (int)$applicant['applicant_id'] ?>" class="applicant-link">
                                        <div class="applicant-cell">
                                            <div class="avatar-circle">
                                                <?= strtoupper(substr($applicant['fullname'] ?? '?', 0, 1)) ?>
                                            </div>

                                            <div>
                                                <strong><?= htmlspecialchars($applicant['fullname'] ?? '') ?></strong>
                                                <span class="sub-text">
                                                    <?= htmlspecialchars($applicant['email'] ?? '') ?>
                                                </span>
                                            </div>
                                        </div>
                                    </a>
                                </td>

                                <td>
                                    <div class="position-cell">
                                        <span class="position-title"><?= htmlspecialchars($applicant['position'] ?? '') ?></span>
                                        <?php if ($department !== ''): ?>
                                            <span class="sub-text"><?= htmlspecialchars($department) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td>
                                    <?php if ($isScreened): ?>
                                        <div class="score-pill tier-<?= $scoreTier ?>">
                                            <span class="score-dot" style="--score:<?= $aiScore ?>"></span>
                                            <span class="score-value"><?= $aiScore ?>%</span>
                                        </div>
                                    <?php else: ?>
                                        <span class="score-empty">Not screened</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="badge <?= $meta['class'] ?>"><?= $meta['label'] ?></span>
                                </td>

                                <td>
                                    <span class="application-time" title="<?= htmlspecialchars($applicant['applied_at'] ?? '') ?>">
                                        <i class="fa-regular fa-calendar"></i>
                                        <span class="application-time-text">
                                            <span><?= htmlspecialchars($formatRelativeTime($applicant['applied_at'] ?? null)) ?></span>
                                            <?php if ($appliedLabel !== ''): ?>
                                                <small><?= htmlspecialchars($appliedLabel) ?></small>
                                            <?php endif; ?>
                                        </span>
                                    </span>
                                </td>

                                <td class="col-chevron" aria-hidden="true">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </td>

                            </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        </tbody>
                    </table>
                </div>

            </div>

            <div class="table-footer">
                <label class="rows-per-page" for="rowsPerPage">
                    <span>Rows per page:</span>
                    <span class="select-field">
                        <select id="rowsPerPage">
                            <option value="8" selected>8</option>
                            <option value="16">16</option>
                            <option value="24">24</option>
                            <option value="48">48</option>
                        </select>
                        <i class="fa-solid fa-chevron-down select-caret"></i>
                    </span>
                </label>

                <div class="pagination" id="pagination">
                    <button class="page-btn" id="prevPage" type="button" aria-label="Previous page"><i class="fa-solid fa-chevron-left"></i></button>
                    <div class="page-numbers" id="pageNumbers"></div>
                    <button class="page-btn" id="nextPage" type="button" aria-label="Next page"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>

        </section>

    </div>
    <?php require BASE_PATH . '/resources/views/includes/footer.php'; ?>
</div>

<?php require BASE_PATH . '/resources/views/includes/scripts.php'?>