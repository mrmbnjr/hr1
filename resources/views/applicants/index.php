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
    "Submitted"    => ["label" => "Submitted",           "class" => "badge-gray"],
    "Under Review" => ["label" => "Under Review",         "class" => "badge-blue"],
    "Interview"    => ["label" => "Interview Scheduled",  "class" => "badge-orange"],
    "Hired"        => ["label" => "Hired",                "class" => "badge-green"],
    "Rejected"     => ["label" => "Rejected",              "class" => "badge-red"],
];

/* Summary counts for the dashboard cards */
$summary = [
    "Total"        => count($applicants),
    "Submitted"    => 0,
    "Under Review" => 0,
    "Interview"    => 0,
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
            SUMMARY CARDS
        ========================================================== -->

        <?php
        /* Summary tiles. Each tile doubles as a status filter:
           data-filter is read by applicants.js, and the extra
           .summary-card class is the JS hook it binds to. The visual
           contract (.stat-card / .stat-icon / .stat-content) is the
           same one used on the recruitment, statistics and onboarding
           screens. */
        $summaryTiles = [
            ['filter' => 'All',          'icon' => 'fa-users',            'label' => 'Total Applicants',    'value' => (int) $summary['Total'],        'note' => 'Across all postings'],
            ['filter' => 'Submitted',    'icon' => 'fa-inbox',            'label' => 'Submitted',           'value' => (int) $summary['Submitted'],    'note' => 'Awaiting screening'],
            ['filter' => 'Under Review', 'icon' => 'fa-magnifying-glass', 'label' => 'Under Review',        'value' => (int) $summary['Under Review'], 'note' => 'In AI screening'],
            ['filter' => 'Interview',    'icon' => 'fa-calendar-check',   'label' => 'Interview Scheduled', 'value' => (int) $summary['Interview'],    'note' => 'Interviews arranged'],
            ['filter' => 'Hired',        'icon' => 'fa-user-check',       'label' => 'Hired',               'value' => (int) $summary['Hired'],        'note' => 'Successfully hired'],
            ['filter' => 'Rejected',     'icon' => 'fa-user-xmark',       'label' => 'Rejected',            'value' => (int) $summary['Rejected'],     'note' => 'Not moving forward'],
        ];
        ?>

        <section class="summary-grid" aria-label="Applicant summary">

            <?php foreach ($summaryTiles as $tile): ?>
                <div class="stat-card summary-card" data-filter="<?= htmlspecialchars($tile['filter']) ?>">
                    <div class="stat-icon">
                        <i class="fa-solid <?= htmlspecialchars($tile['icon']) ?>"></i>
                    </div>
                    <div class="stat-content">
                        <span><?= htmlspecialchars($tile['label']) ?></span>
                        <strong><?= (int) $tile['value'] ?></strong>
                        <small><?= htmlspecialchars($tile['note']) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>

        </section>

        <!-- ==========================================================
            FILTER BAR
        ========================================================== -->

        <section class="filter-bar">

            <div class="filter-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="applicantSearch" placeholder="Search applicant by name...">
            </div>

            <select id="positionFilter">
                <option value="All">All Positions</option>
                <?php foreach ($positions as $position): ?>
                    <option value="<?= htmlspecialchars($position['title']) ?>">
                        <?= htmlspecialchars($position['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select id="sortFilter">
                <option value="priority">Priority Queue</option>
                <option value="highest-score">Highest AI Score</option>
                <option value="newest">Newest Applications</option>
                <option value="oldest">Oldest Applications</option>
                <option value="name-az">Applicant Name (A-Z)</option>
            </select>
        </section>

        <!-- ==========================================================
            APPLICANT TABLE
        ========================================================== -->

        <section class="table-card">

            <div class="table-scroll">
                <table class="applicant-table" id="applicantTable">
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Applied Position</th>
                            <th>AI Score</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>

                    <?php if (empty($applicants)): ?>

                        <tr class="empty-row">
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fa-solid fa-user-slash"></i>
                                    <p>No applicants found.</p>
                                </div>
                            </td>
                        </tr>

                    <?php else: ?>

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

                            <td><?= htmlspecialchars($applicant['position'] ?? '') ?></td>

                            <td>
                                <div class="score-pill">
                                    <span class="score-dot" style="--score:<?= $aiScore ?>"></span>
                                    <?= $aiScore ?>%
                                </div>
                            </td>

                            <td>
                                <span class="badge <?= $meta['class'] ?>"><?= $meta['label'] ?></span>
                            </td>

                            <td>
                                <span class="sub-text application-time">
                                    <?= htmlspecialchars($formatRelativeTime($applicant['applied_at'] ?? null)) ?>
                                </span>
                            </td>

                        </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <span class="results-count" id="resultsCount"></span>
                <div class="pagination" id="pagination">
                    <button class="page-btn" id="prevPage" type="button"><i class="fa-solid fa-chevron-left"></i></button>
                    <div class="page-numbers" id="pageNumbers"></div>
                    <button class="page-btn" id="nextPage" type="button"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>

        </section>

    </div>
    <?php require BASE_PATH . '/resources/views/includes/footer.php'; ?>
</div>

<?php require BASE_PATH . '/resources/views/includes/scripts.php'?>