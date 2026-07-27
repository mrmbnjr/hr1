<?php

/**
 * resources/views/applicant/view.php
 *
 * Standalone Applicant Details page.
 * Reached via ?page=view-applicant&id={applicant_id}
 *
 * Expects the controller (ApplicantController::view) to pass:
 *   $applicant   -> single associative array, same shape as one row
 *                   of the $applicants list used on the Applicant
 *                   Management table (applicants + applications +
 *                   ai_screening + interviews + manager decision, joined)
 *   $managers    -> array of managers for the Schedule Interview modal
 *                   (each with at least ['id' => ..., 'name' => ...])
 */

$pageTitle       = "Applicant Details";
$pageCSS         = "applicants.css";
$pageDescription = "Full profile, AI screening, interview, and hiring decision for this applicant.";

if (!isset($_SESSION['user_id'])) {
    header("Location: /hr1/public/?page=login");
    exit;
}

$applicant = $applicant ?? [];
$managers  = $managers  ?? [];

if (empty($applicant)) {
    header("Location: /hr1/public/?page=applicants");
    exit;
}

$statusMeta = [
    "Submitted"  => ["label" => "Submitted",           "class" => "badge-gray"],
    "Review"     => ["label" => "Under Review",         "class" => "badge-blue"],
    "Interview"  => ["label" => "Interview Scheduled",  "class" => "badge-orange"],
    "Hired"      => ["label" => "Hired",                "class" => "badge-green"],
    "Rejected"   => ["label" => "Rejected",              "class" => "badge-red"],
];

$recClassMap = [
    "Highly Recommended" => "rec-high",
    "Recommended"        => "rec-mid",
    "Needs Review"        => "rec-review",
    "Not Recommended"    => "rec-low",
];

$status      = $applicant['application_status'] ?? 'Submitted';
$statusInfo  = $statusMeta[$status] ?? $statusMeta['Submitted'];

$aiScore         = (int)($applicant['ai_score'] ?? 0);
$skillsMatch     = (int)($applicant['skills_match'] ?? 0);
$experienceMatch = (int)($applicant['experience_match'] ?? 0);
$educationMatch  = (int)($applicant['education_match'] ?? 0);
$recommendation  = $applicant['recommendation'] ?? 'Needs Review';
$recClass        = $recClassMap[$recommendation] ?? 'rec-review';

$interviewStatus = $applicant['interview_status'] ?? 'Not Scheduled';
$interviewDate   = $applicant['interview_date'] ?? '';
$interviewTime   = $applicant['interview_time'] ?? '';
$interviewMgr    = $applicant['interview_manager'] ?? '';
$interviewLoc    = $applicant['interview_location'] ?? '';
$interviewNotes  = $applicant['interview_notes'] ?? '';

$mgrRecommendation = $applicant['manager_recommendation'] ?? '';
$mgrRecClass        = $recClassMap[$mgrRecommendation] ?? 'rec-review';
$mgrRemarks         = $applicant['manager_remarks'] ?? '';

$resumePath = $applicant['resume'] ?? '';
$resumeName = $applicant['resume_filename'] ?? 'resume.pdf';

?>

<?php require '../resources/views/includes/header.php'; ?>
<?php require '../resources/views/includes/sidebar.php'; ?>

<div class="main-content">

<?php require '../resources/views/includes/navbar.php'; ?>

<div class="applicants-page view-applicant-page">

    <a href="?page=applicants" class="back-link">
        <i class="fa-solid fa-arrow-left"></i> Back to Applicants
    </a>

    <!-- ==========================================================
        PROFILE HEADER
    ========================================================== -->

    <section class="profile-header">
        <div class="profile-header-left">
            <div class="avatar-circle avatar-large">
                <?= strtoupper(substr($applicant['fullname'] ?? '?', 0, 1)) ?>
            </div>
            <div>
                <h1><?= htmlspecialchars($applicant['fullname'] ?? '') ?></h1>
                <span class="profile-position"><?= htmlspecialchars($applicant['position'] ?? '') ?></span>
            </div>
        </div>
        <div class="profile-header-right">
            <span class="badge <?= $statusInfo['class'] ?> badge-lg"><?= $statusInfo['label'] ?></span>
        </div>
    </section>

    <!-- ==========================================================
        TWO COLUMN LAYOUT
    ========================================================== -->

    <div class="view-grid">

        <div class="view-col-main">

            <!-- Applicant Information -->
            <section class="detail-section">
                <h3><i class="fa-solid fa-id-card"></i> Applicant Information</h3>
                <div class="info-grid">
                    <div><span class="info-label">Applicant Name</span><span class="info-value"><?= htmlspecialchars($applicant['fullname'] ?? '-') ?></span></div>
                    <div><span class="info-label">Email Address</span><span class="info-value"><?= htmlspecialchars($applicant['email'] ?? '-') ?></span></div>
                    <div><span class="info-label">Contact Number</span><span class="info-value"><?= htmlspecialchars($applicant['contact_number'] ?? '-') ?></span></div>
                    <div><span class="info-label">Applied Position</span><span class="info-value"><?= htmlspecialchars($applicant['position'] ?? '-') ?></span></div>
                    <div><span class="info-label">Date Applied</span><span class="info-value"><?= htmlspecialchars($applicant['date_applied'] ?? '-') ?></span></div>
                    <div><span class="info-label">Application Status</span><span class="info-value"><?= $statusInfo['label'] ?></span></div>
                </div>
            </section>

            <!-- Resume -->
            <section class="detail-section">
                <h3><i class="fa-solid fa-file-lines"></i> Resume</h3>
                <div class="resume-row">
                    <div class="resume-file">
                        <i class="fa-solid fa-file-pdf"></i>
                        <span><?= htmlspecialchars($resumeName) ?></span>
                    </div>
                    <div class="resume-actions">
                        <a href="<?= htmlspecialchars($resumePath ?: '#') ?>" target="_blank" class="btn-outline">
                            <i class="fa-solid fa-eye"></i> View Resume
                        </a>
                        <a href="<?= htmlspecialchars($resumePath ?: '#') ?>" download class="btn-outline">
                            <i class="fa-solid fa-download"></i> Download
                        </a>
                    </div>
                </div>
            </section>

            <!-- AI Screening -->
            <section class="detail-section">
                <h3><i class="fa-solid fa-robot"></i> AI Screening Results</h3>

                <div class="ai-score-row">
                    <div class="ai-score-circle" style="--score: <?= $aiScore ?>;">
                        <span><?= $aiScore ?>%</span>
                    </div>
                    <div class="ai-recommendation">
                        <span class="info-label">Recommendation</span>
                        <span class="rec-badge <?= $recClass ?>"><?= htmlspecialchars($recommendation) ?></span>
                    </div>
                </div>

                <div class="match-bars">
                    <div class="match-row">
                        <span class="match-label">Skills Match</span>
                        <div class="progress-track"><div class="progress-fill" style="width: <?= $skillsMatch ?>%;"></div></div>
                        <span class="match-value"><?= $skillsMatch ?>%</span>
                    </div>
                    <div class="match-row">
                        <span class="match-label">Experience Match</span>
                        <div class="progress-track"><div class="progress-fill" style="width: <?= $experienceMatch ?>%;"></div></div>
                        <span class="match-value"><?= $experienceMatch ?>%</span>
                    </div>
                    <div class="match-row">
                        <span class="match-label">Education Match</span>
                        <div class="progress-track"><div class="progress-fill" style="width: <?= $educationMatch ?>%;"></div></div>
                        <span class="match-value"><?= $educationMatch ?>%</span>
                    </div>
                </div>
            </section>

        </div>

        <div class="view-col-side">

            <!-- Interview -->
            <section class="detail-section">
                <h3><i class="fa-solid fa-calendar-days"></i> Interview</h3>

                <?php if ($interviewStatus !== 'Scheduled'): ?>

                    <div class="interview-empty">
                        <span class="badge badge-gray">Not Scheduled</span>
                        <button type="button" class="btn-primary" id="openScheduleModal">
                            <i class="fa-solid fa-calendar-plus"></i> Schedule Interview
                        </button>
                    </div>

                <?php else: ?>

                    <div class="interview-scheduled">
                        <div class="info-grid">
                            <div><span class="info-label">Interview Date</span><span class="info-value"><?= htmlspecialchars($interviewDate) ?></span></div>
                            <div><span class="info-label">Interview Time</span><span class="info-value"><?= htmlspecialchars($interviewTime) ?></span></div>
                            <div><span class="info-label">Manager</span><span class="info-value"><?= htmlspecialchars($interviewMgr) ?></span></div>
                            <div><span class="info-label">Location</span><span class="info-value"><?= htmlspecialchars($interviewLoc) ?></span></div>
                            <div class="full-width"><span class="badge badge-orange">Scheduled</span></div>
                        </div>
                        <button type="button" class="btn-outline" id="openScheduleModal">
                            <i class="fa-solid fa-pen"></i> Edit Schedule
                        </button>
                    </div>

                <?php endif; ?>
            </section>

            <!-- Manager Decision -->
            <section class="detail-section">
                <h3><i class="fa-solid fa-user-tie"></i> Manager Decision</h3>
                <span class="info-label">Recommendation</span>
                <span class="rec-badge <?= $mgrRecClass ?>"><?= htmlspecialchars($mgrRecommendation ?: 'Pending') ?></span>

                <span class="info-label" style="margin-top:14px; display:block;">Interview Remarks</span>
                <p class="remarks-text"><?= htmlspecialchars($mgrRemarks ?: 'No remarks yet.') ?></p>
            </section>

            <!-- Decision -->
            <section class="detail-section decision-section">
                <h3><i class="fa-solid fa-gavel"></i> Decision</h3>
                <div class="decision-buttons decision-buttons-stacked">
                    <button type="button" class="btn-success btn-block" id="hireBtn"
                        <?= in_array($status, ['Hired', 'Rejected']) ? 'disabled' : '' ?>>
                        <i class="fa-solid fa-user-check"></i> Hire Applicant
                    </button>
                    <button type="button" class="btn-danger btn-block" id="rejectBtn"
                        <?= in_array($status, ['Hired', 'Rejected']) ? 'disabled' : '' ?>>
                        <i class="fa-solid fa-user-xmark"></i> Reject Applicant
                    </button>
                </div>
                <?php if (in_array($status, ['Hired', 'Rejected'])): ?>
                    <p class="decision-locked-note">
                        <i class="fa-solid fa-circle-info"></i>
                        This application has already been marked as <strong><?= $statusInfo['label'] ?></strong>.
                    </p>
                <?php endif; ?>
            </section>

        </div>

    </div>

</div>

</div>

<!-- ==========================================================
    SCHEDULE INTERVIEW MODAL
========================================================== -->

<div class="modal-overlay" id="scheduleModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-calendar-days"></i> Schedule Interview</h3>
            <button type="button" class="close-btn" data-close="scheduleModal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="scheduleForm" class="modal-body" method="post" action="?page=schedule-interview&id=<?= htmlspecialchars($applicant['application_id'] ?? '') ?>">
            <label>Manager
                <select name="manager" id="scheduleManager" required>
                    <option value="">Select manager</option>
                    <?php foreach ($managers as $manager): ?>
                        <option value="<?= htmlspecialchars($manager['id']) ?>"
                            <?= (isset($applicant['interview_manager_id']) && $applicant['interview_manager_id'] == $manager['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($manager['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="form-row">
                <label>Interview Date
                    <input type="date" name="interview_date" id="scheduleDate" value="<?= htmlspecialchars($interviewDate) ?>" required>
                </label>
                <label>Interview Time
                    <input type="time" name="interview_time" id="scheduleTime" value="<?= htmlspecialchars($interviewTime) ?>" required>
                </label>
            </div>
            <label>Location
                <input type="text" name="location" id="scheduleLocation" value="<?= htmlspecialchars($interviewLoc) ?>" placeholder="e.g. HR Conference Room, Google Meet link" required>
            </label>
            <label>Notes
                <textarea name="notes" id="scheduleNotes" rows="3" placeholder="Additional notes for the interview"><?= htmlspecialchars($interviewNotes) ?></textarea>
            </label>
            <div class="modal-actions">
                <button type="button" class="btn-outline" data-close="scheduleModal">Cancel</button>
                <button type="submit" class="btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================================
    HIRE CONFIRMATION MODAL
========================================================== -->

<div class="modal-overlay" id="hireModal">
    <div class="modal-box modal-small">
        <div class="modal-header">
            <h3><i class="fa-solid fa-user-check"></i> Confirm Hiring</h3>
            <button type="button" class="close-btn" data-close="hireModal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p class="confirm-text">
                You are about to hire <strong><?= htmlspecialchars($applicant['fullname'] ?? 'this applicant') ?></strong>. This will:
            </p>
            <ul class="confirm-list">
                <li><i class="fa-solid fa-check"></i> Change application status to <strong>Hired</strong></li>
                <li><i class="fa-solid fa-check"></i> Create an employee record</li>
                <li><i class="fa-solid fa-check"></i> Create an employee system account</li>
                <li><i class="fa-solid fa-check"></i> Send a hiring email with login credentials</li>
                <li><i class="fa-solid fa-check"></i> Automatically start New Hire Onboarding</li>
            </ul>
            <form method="post" action="?page=hire-applicant&id=<?= htmlspecialchars($applicant['application_id'] ?? '') ?>" class="modal-actions">
                <button type="button" class="btn-outline" data-close="hireModal">Cancel</button>
                <button type="submit" class="btn-success">Confirm Hire</button>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================================
    REJECT MODAL
========================================================== -->

<div class="modal-overlay" id="rejectModal">
    <div class="modal-box modal-small">
        <div class="modal-header">
            <h3><i class="fa-solid fa-user-xmark"></i> Reject Applicant</h3>
            <button type="button" class="close-btn" data-close="rejectModal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="rejectForm" class="modal-body" method="post" action="?page=reject-applicant&id=<?= htmlspecialchars($applicant['application_id'] ?? '') ?>">
            <label>Rejection Remarks
                <textarea name="rejection_remarks" id="rejectionRemarks" rows="4" placeholder="Explain the reason for rejection" required></textarea>
            </label>
            <div class="modal-actions">
                <button type="button" class="btn-outline" data-close="rejectModal">Cancel</button>
                <button type="submit" class="btn-danger">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {

    function openModal(id) { document.getElementById(id).classList.add('active'); }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); }

    document.querySelectorAll('[data-close]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.close));
    });

    document.querySelectorAll('#openScheduleModal').forEach(btn => {
        btn.addEventListener('click', () => openModal('scheduleModal'));
    });

    const hireBtn = document.getElementById('hireBtn');
    if (hireBtn) hireBtn.addEventListener('click', () => openModal('hireModal'));

    const rejectBtn = document.getElementById('rejectBtn');
    if (rejectBtn) rejectBtn.addEventListener('click', () => openModal('rejectModal'));

    // Close a modal when clicking its dark backdrop, not its content box
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) overlay.classList.remove('active');
        });
    });

})();
</script>

<?php require '../resources/views/includes/footer.php'; ?>