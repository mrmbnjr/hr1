<?php

$pageTitle = "Applicant Management";
$pageCSS = "applicants.css";
$pageJS = "applicants.js";
$pageDescription = "Review applicants, monitor AI screening, and manage the hiring pipeline.";

if (!isset($_SESSION['user_id'])) {
    header("Location: /hr1/public/?page=login");
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

<?php require '../resources/views/includes/header.php'; ?>
<?php require '../resources/views/includes/sidebar.php'; ?>

<div class="main-content">

    <?php require '../resources/views/includes/navbar.php'; ?>

    <div class="applicants-page">
        <!-- ==========================================================
            SUMMARY CARDS
        ========================================================== -->

        <section class="summary-grid">

            <div class="summary-card" data-filter="All">
                <div class="summary-icon icon-total"><i class="fa-solid fa-users"></i></div>
                <div class="summary-body">
                    <span class="summary-count"><?= (int)$summary['Total'] ?></span>
                    <span class="summary-label">Total Applicants</span>
                </div>
            </div>

            <div class="summary-card" data-filter="Submitted">
                <div class="summary-icon icon-submitted"><i class="fa-solid fa-inbox"></i></div>
                <div class="summary-body">
                    <span class="summary-count"><?= (int)$summary['Submitted'] ?></span>
                    <span class="summary-label">Submitted</span>
                </div>
            </div>

            <div class="summary-card" data-filter="Under Review">
                <div class="summary-icon icon-review"><i class="fa-solid fa-magnifying-glass"></i></div>
                <div class="summary-body">
                    <span class="summary-count"><?= (int)$summary['Under Review'] ?></span>
                    <span class="summary-label">Under Review</span>
                </div>
            </div>

            <div class="summary-card" data-filter="Interview">
                <div class="summary-icon icon-interview"><i class="fa-solid fa-calendar-check"></i></div>
                <div class="summary-body">
                    <span class="summary-count"><?= (int)$summary['Interview'] ?></span>
                    <span class="summary-label">Interview Scheduled</span>
                </div>
            </div>

            <div class="summary-card" data-filter="Hired">
                <div class="summary-icon icon-hired"><i class="fa-solid fa-user-check"></i></div>
                <div class="summary-body">
                    <span class="summary-count"><?= (int)$summary['Hired'] ?></span>
                    <span class="summary-label">Hired</span>
                </div>
            </div>

            <div class="summary-card" data-filter="Rejected">
                <div class="summary-icon icon-rejected"><i class="fa-solid fa-user-xmark"></i></div>
                <div class="summary-body">
                    <span class="summary-count"><?= (int)$summary['Rejected'] ?></span>
                    <span class="summary-label">Rejected</span>
                </div>
            </div>

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

            <select id="statusFilter">
                <option value="All">All Statuses</option>
                <?php foreach ($statusMeta as $key => $meta): ?>
                    <option value="<?= $key ?>"><?= $meta['label'] ?></option>
                <?php endforeach; ?>
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
                            <th>Date Applied</th>
                            <th class="col-actions">Actions</th>
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
                                <div class="applicant-cell">
                                    <div class="avatar-circle"><?= strtoupper(substr($applicant['fullname'] ?? '?', 0, 1)) ?></div>
                                    <div>
                                        <strong><?= htmlspecialchars($applicant['fullname'] ?? '') ?></strong>
                                        <span class="sub-text"><?= htmlspecialchars($applicant['email'] ?? '') ?></span>
                                    </div>
                                </div>
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

                            <td><?= htmlspecialchars($applicant['applied_at'] ?? '') ?></td>

                            <td class="col-actions">
                                <a href="?page=review&id=<?= $applicant['applicant_id'] ?>" class="btn-review">
                                    <i class="fa-solid fa-magnifying-glass"></i>Review
                                </a>
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
    <?php require '../resources/views/includes/footer.php'; ?>
</div>

<?php require '../resources/views/includes/scripts.php'?>