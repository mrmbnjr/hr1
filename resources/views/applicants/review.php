<?php
$pageTitle       = "Applicant Management";
$pageCSS         = "applicants.css";
$pageJS          = "applicants.js";
$pageDescription = "Review applicants, monitor AI screening, and manage the hiring pipeline.";

if (!isset($_SESSION['user_id'])) {
    header("Location: /hr1/public/?page=login");
    exit;
}

$applicant = $applicant ?? [];

if (!$applicant) {
    header("Location: ?page=applicants");
    exit;
}

$statusMeta = [
    "Submitted"    => ["label" => "Submitted", "class" => "badge-gray"],
    "Under Review" => ["label" => "Under Review", "class" => "badge-blue"],
    "Interview"    => ["label" => "Interview Scheduled", "class" => "badge-orange"],
    "Hired"        => ["label" => "Hired", "class" => "badge-green"],
    "Rejected"     => ["label" => "Rejected", "class" => "badge-red"],
];

?>

<?php require '../resources/views/includes/header.php'; ?>
<?php require '../resources/views/includes/sidebar.php'; ?>

<div class="main-content">

    <?php require '../resources/views/includes/navbar.php'; ?>

    <!-- ==========================================================
        APPLICANT DETAILS PANEL
    ========================================================== -->

    <div class="details-body">

        <!-- Applicant Information -->
        <section class="detail-section">
            <h3><i class="fa-solid fa-id-card"></i> Applicant Information</h3>
            <div class="info-grid">
                <div>
                    <span class="info-label">Applicant Name</span>
                    <span class="info-value"><?= htmlspecialchars($applicant['fullname']) ?></span>
                </div>
                <div>
                    <span class="info-label">Email Address</span>
                    <span class="info-value"><?= htmlspecialchars($applicant['email']) ?></span>
                </div>
                <div>
                    <span class="info-label">Phone Number</span>
                    <span class="info-value"><?= htmlspecialchars($applicant['phone']) ?></span>
                </div>
                <div>
                    <span class="info-label">Applied Position</span>
                    <span class="info-value"><?= htmlspecialchars($applicant['position']) ?></span>
                </div>
                <div>
                    <span class="info-label">Date Applied</span>
                    <span class="info-value"><?= date('F d, Y', strtotime($applicant['applied_at'])) ?></span>
                </div>
                <div>
                    <span class="info-label">Application Status</span>
                        <span class="badge <?= htmlspecialchars($statusMeta[$applicant['application_status']]['class']) ?>">
                            <?= htmlspecialchars($statusMeta[$applicant['application_status']]['label']) ?>
                        </span>                        
                    </span>
                </div>
            </div>
        </section>

        <!-- Resume -->
        <section class="detail-section">
            <h3><i class="fa-solid fa-file-lines"></i> Resume</h3>
            <div class="resume-row">
                <div class="resume-file">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>
                        <?= basename($applicant['resume_file']) ?>
                    </span>
                </div>
                <div class="resume-actions">
                    <a href="?page=download-resume&id=<?= (int) $applicant['application_id'] ?>" target="_blank" class="btn-outline">
                        <i class="fa-solid fa-eye"></i> View Resume
                    </a>
                    <a href="?page=download-resume&id=<?= (int) $applicant['application_id'] ?>&download=1" class="btn-outline">
                        <i class="fa-solid fa-download"></i> Download
                    </a>
                </div>
            </div>
        </section>

        <!-- Academic Document Validation -->
        <section class="detail-section">
            <div class="ai-actions">
                <h3><i class="fa-solid fa-graduation-cap"></i> Academic Document Validation</h3>
                <?php if (!empty($applicant['academic_document_file'])): ?>
                    <button type="button" class="btn-primary" id="evaluateAcademicDocumentBtn" data-application-id="<?= (int) $applicant['application_id'] ?>">
                        <i class="fa-solid fa-file-circle-check"></i>
                        <?= isset($applicant['academic_confidence_score']) ? 'Re-run Validation' : 'Validate Document' ?>
                    </button>
                <?php endif; ?>
            </div>
            <div class="resume-row">
                <div class="resume-file">
                    <i class="fa-solid fa-file-lines"></i>
                    <span><?= htmlspecialchars(basename($applicant['academic_document_file'] ?? 'No document uploaded')) ?></span>
                </div>
                <?php if (!empty($applicant['academic_document_file'])): ?>
                    <div class="resume-actions">
                        <a href="?page=download-academic-document&id=<?= (int) $applicant['application_id'] ?>" target="_blank" class="btn-outline"><i class="fa-solid fa-eye"></i> View Document</a>
                        <a href="?page=download-academic-document&id=<?= (int) $applicant['application_id'] ?>&download=1" class="btn-outline"><i class="fa-solid fa-download"></i> Download</a>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (isset($applicant['academic_confidence_score'])): ?>
                <div class="ai-score-row">
                    <div class="ai-score-circle" style="--score: <?= number_format((float) $applicant['academic_confidence_score'], 2, '.', '') ?>;"><span><?= number_format((float) $applicant['academic_confidence_score'], 2) ?>%</span></div>
                    <div class="ai-recommendation"><span class="info-label">Document Status</span><span class="rec-badge"><?= !empty($applicant['academic_document_valid']) ? 'Appears Valid' : 'Needs Review' ?></span></div>
                </div>
                <div class="info-grid">
                    <div><span class="info-label">Document Type</span><span class="info-value"><?= htmlspecialchars($applicant['academic_document_type'] ?? '') ?></span></div>
                    <div><span class="info-label">Institution</span><span class="info-value"><?= htmlspecialchars($applicant['academic_institution'] ?? '') ?></span></div>
                    <div><span class="info-label">Degree</span><span class="info-value"><?= htmlspecialchars($applicant['academic_degree'] ?? '') ?></span></div>
                    <div><span class="info-label">Field of Study</span><span class="info-value"><?= htmlspecialchars($applicant['academic_field_of_study'] ?? '') ?></span></div>
                    <div><span class="info-label">Graduation Year</span><span class="info-value"><?= htmlspecialchars($applicant['academic_graduation_year'] ?? '') ?></span></div>
                    <div class="full-width"><span class="info-label">AI Summary</span><span class="info-value"><?= htmlspecialchars($applicant['academic_ai_summary'] ?? '') ?></span></div>
                </div>
            <?php else: ?>
                <p class="file-note">No academic validation result is available yet.</p>
            <?php endif; ?>
        </section>

        <!-- AI Screening -->
        <?php
            function getMatchClass(int $score): string
            {
                if ($score >= 85) {
                    return "high";
                }

                if ($score >= 70) {
                    return "medium";
                }
                return "low";
            }?>
        <section class="detail-section">
            <div class="ai-actions">

                <h3>
                    <i class="fa-solid fa-robot"></i>
                    AI Screening Results
                </h3>

                <button
                    type="button"
                    class="btn-primary"
                    id="evaluateResumeBtn"
                    data-application-id="<?= (int) $applicant['application_id'] ?>"
                >
                    <i class="fa-solid fa-robot"></i>
                    <?= !empty($applicant['ai_score']) ? 'Re-run AI Screening' : 'Evaluate Resume' ?>
                </button>
            </div>

            <?php

            $score = (float) ($applicant['ai_score'] ?? 0);

            $skills      = (float) ($applicant['skills_score'] ?? 0);
            $experience  = (float) ($applicant['experience_score'] ?? 0);
            $education   = (float) ($applicant['education_score'] ?? 0);
            $keywords    = (float) ($applicant['keyword_score'] ?? 0);

            $strengths = json_decode($applicant['strengths'] ?? '[]', true);
            $concerns  = json_decode($applicant['concerns'] ?? '[]', true);

            $summary = $applicant['ai_summary'] ?? 'No AI summary available.';

            if (!is_array($strengths)) {
                $strengths = [];
            }

            if (!is_array($concerns)) {
                $concerns = [];
            }

            ?>

            <div class="ai-score-row">
                <div class="ai-score-circle"
                    style="--score: <?= number_format($score, 2, '.', '') ?>;">
                    <span><?= number_format($score, 2) ?>%</span>
                </div>

                <div class="ai-recommendation">

                    <span class="info-label">
                        Recommendation
                    </span>

                    <span class="rec-badge">
                        <?= htmlspecialchars($applicant['recommendation']) ?>
                    </span>

                </div>

            </div>

            <div class="match-bars">

                <div class="match-row">

                    <span class="match-label">Skills Match</span>

                    <div class="progress-track">
                        <div class="progress-fill" style="width:<?= number_format($skills, 2) ?>%"></div>
                    </div>

                    <span class="match-value"><?= number_format($skills, 2) ?>%</span>

                </div>

                <div class="match-row">

                    <span class="match-label">Experience Match</span>

                    <div class="progress-track">
                        <div class="progress-fill" style="width:<?= number_format($experience, 2) ?>%"></div>
                    </div>

                    <span class="match-value"><?= number_format($experience, 2) ?>%</span>

                </div>

                <div class="match-row">

                    <span class="match-label">Education Match</span>

                    <div class="progress-track">
                        <div class="progress-fill" style="width:<?= number_format($education, 2) ?>%"></div>
                    </div>

                    <span class="match-value"><?= number_format($education, 2) ?>%</span>

                </div>

                <div class="match-row">

                    <span class="match-label">Keyword Match</span>

                    <div class="progress-track">
                        <div class="progress-fill" style="width:<?= number_format($keywords, 2) ?>%"></div>
                    </div>

                    <span class="match-value"><?= number_format($keywords, 2) ?>%</span>

                </div>

            </div>

            <div class="ai-analysis-grid">

                <div class="analysis-card">

                    <h4>
                        <i class="fa-solid fa-circle-check"></i>
                        Key Strengths
                    </h4>

                    <ul>

                        <?php foreach($strengths as $item): ?>

                            <li><?= htmlspecialchars($item) ?></li>

                        <?php endforeach; ?>

                    </ul>

                </div>

                <div class="analysis-card">

                    <h4>
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Areas for Review
                    </h4>

                    <ul>

                        <?php foreach($concerns as $item): ?>

                            <li><?= htmlspecialchars($item) ?></li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            </div>

            <div class="ai-summary">

                <span class="info-label">
                    AI Summary
                </span>

                <p>
                    <?= htmlspecialchars($summary) ?>
                </p>

            </div>

        </section>

        <!-- Interview -->
        <section class="detail-section">
            <h3><i class="fa-solid fa-calendar-days"></i> Interview</h3>

            <?php if (empty($applicant['interview_id'])): ?>

                <div class="interview-empty">
                    <span class="badge badge-gray">Not Scheduled</span>

                    <button type="button" class="btn-primary" id="openScheduleModal">
                        <i class="fa-solid fa-calendar-plus"></i>
                        Schedule Interview
                    </button>
                </div>

            <?php else: ?>

                <?php

                $resultClass = match ($applicant['result']) {
                    'Passed' => 'badge-green',
                    'Failed' => 'badge-red',
                    default  => 'badge-orange'
                };

                $isManagerInterview =
                    ($applicant['interviewer_role'] ?? '') === 'MANAGER';

                ?>

                <div class="interview-scheduled">

                    <div class="info-grid">

                        <div>
                            <span class="info-label">Interview Date</span>
                            <span class="info-value">
                                <?= date('F d, Y', strtotime($applicant['interview_date'])) ?>
                            </span>
                        </div>

                        <div>
                            <span class="info-label">Interview Time</span>
                            <span class="info-value">
                                <?= date('g:i A', strtotime($applicant['interview_date'])) ?>
                            </span>
                        </div>

                        <div>
                            <span class="info-label">Interviewer</span>
                            <span class="info-value">
                                <?= htmlspecialchars($applicant['interviewer_name']) ?>
                                (<?= htmlspecialchars($applicant['interviewer_role']) ?>)
                            </span>
                        </div>

                        <div>
                            <span class="info-label">Interview Type</span>
                            <span class="info-value">
                                <?= htmlspecialchars($applicant['interview_type']) ?>
                            </span>
                        </div>

                        <div>
                            <span class="info-label">Location</span>
                            <span class="info-value">
                                <?= htmlspecialchars($applicant['location']) ?>
                            </span>
                        </div>

                        <?php if ($isManagerInterview): ?>

                            <div>
                                <span class="info-label">Result</span>

                                <span class="badge <?= $resultClass ?>">
                                    <?= htmlspecialchars($applicant['result']) ?>
                                </span>
                            </div>

                        <?php endif; ?>

                    </div>

                    <?php if ($isManagerInterview && $applicant['result'] !== 'Pending'): ?>

                        <div class="remarks-box">

                            <span class="info-label">
                                Interview Remarks
                            </span>

                            <p class="remarks-text">
                                <?= nl2br(htmlspecialchars($applicant['remarks'])) ?>
                            </p>

                        </div>

                    <?php endif; ?>

                    <?php if ($applicant['result'] === 'Pending'): ?>

                        <button
                            type="button"
                            class="btn-outline"
                            id="editScheduleBtn">

                            <i class="fa-solid fa-pen"></i>
                            Edit Schedule

                        </button>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </section>

            <?php if (!in_array($applicant['application_status'], ['Hired', 'Rejected'], true)): ?>
                <!-- Decision -->
                <section class="detail-section decision-section">
                    <h3><i class="fa-solid fa-gavel"></i> Decision</h3>
                    <div class="decision-buttons">
                        <button
                            type="button"
                            class="btn-success btn-block"
                            id="hireBtn"
                            data-application-id="<?= (int) $applicant['application_id'] ?>"
                        >
                            <i class="fa-solid fa-user-check"></i>
                            Hire Applicant
                        </button>

                        <button
                            type="button"
                            class="btn-danger btn-block"
                            id="rejectBtn"
                            data-application-id="<?= (int) $applicant['application_id'] ?>"
                        >
                            <i class="fa-solid fa-user-xmark"></i>
                            Reject Applicant
                        </button>
                    </div>
                </section>
            <?php endif; ?>
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
            <form id="scheduleForm" class="modal-body" method="POST" action="?page=scheduleInterview">
                <input type="hidden" name="application_id" value="<?= $applicant['application_id']; ?>">
                <input type="hidden" name="applicant_id" value="<?= $applicant['applicant_id']; ?>">
                <label>Interviewer
                    <select name="interviewer_id" id="scheduleManager" required>
                        <option value="" hidden>Select Interviewer</option>
                        <?php if (empty($managers)): ?>
                            <option disabled>No Staff Available</option>
                        <?php else: ?>
                            <?php foreach ($managers as $manager): ?>
                                <option
                                    value="<?= $manager['user_id']; ?>"
                                    <?= ($applicant['interviewer_id'] ?? '') == $manager['user_id'] ? 'selected' : ''; ?>>

                                    <?= htmlspecialchars($manager['fullname']); ?>
                                    (<?= htmlspecialchars($manager['role_name']); ?>)
                                </option>                            
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </label>

                <label>Interview Type
                    <select name="interview_type" id="scheduleType" required>
                        <option value="" hidden>Select Type</option>

                        <option value="Phone"
                            <?= ($applicant['interview_type'] ?? '') === 'Phone' ? 'selected' : ''; ?>>
                            Phone
                        </option>

                        <option value="Online"
                            <?= ($applicant['interview_type'] ?? '') === 'Online' ? 'selected' : ''; ?>>
                            Online
                        </option>

                        <option value="Face-to-Face"
                            <?= ($applicant['interview_type'] ?? '') === 'Face-to-Face' ? 'selected' : ''; ?>>
                            Face-to-Face
                        </option>
                    </select>
                </label>

                <div class="form-row">                    
                    <label>Interview Date
                        <input type="date" name="interview_date" id="scheduleDate" min="<?= date('Y-m-d') ?>" value="<?= !empty($applicant['interview_date']) ? date('Y-m-d', strtotime($applicant['interview_date'])) : ''; ?>" required>
                    </label>
                    <label>Interview Time
                        <input type="time" name="interview_time" id="scheduleTime" value="<?= !empty($applicant['interview_date']) ? date('H:i', strtotime($applicant['interview_date'])) : ''; ?>" required>
                    </label>
                </div>
                <label>Location
                    <input type="text" name="location" minlength="3" maxlength="64" value="<?= htmlspecialchars($applicant['location'] ?? ''); ?>" id="scheduleLocation" placeholder="e.g. HR Conference Room, Google Meet link" required>
                </label>
                <label>Notes
                    <textarea name="notes" minlength="3" maxlength="500" id="scheduleNotes" rows="3" placeholder="Additional notes for the interview"><?= htmlspecialchars($applicant['remarks'] ?? ''); ?></textarea>
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
                    You are about to hire <strong><?= htmlspecialchars($applicant['fullname']) ?></strong>. This will:
                </p>
                <ul class="confirm-list">
                    <li><i class="fa-solid fa-check"></i> Change application status to <strong>Hired</strong></li>
                    <li><i class="fa-solid fa-check"></i> Create an employee record</li>
                    <li><i class="fa-solid fa-check"></i> Create an employee system account</li>
                    <li><i class="fa-solid fa-check"></i> Send a hiring email with login credentials</li>
                    <li><i class="fa-solid fa-check"></i> Automatically start New Hire Onboarding</li>
                </ul>
                <div class="modal-actions">
                    <button type="button" class="btn-outline" data-close="hireModal">Cancel</button>
                    <button type="button" class="btn-success" id="confirmHireBtn">Confirm Hire</button>
                </div>
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
            <form id="rejectForm" class="modal-body">
                <label>
                    <p>Are you sure you want to reject this applicant?</p>
                </label>
                <div class="modal-actions">
                    <button type="button" class="btn-outline" data-close="rejectModal">Cancel</button>
                    <button type="submit" class="btn-danger">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>

    <?php require '../resources/views/includes/footer.php'; ?>
</div>

<?php require '../resources/views/includes/scripts.php'?>