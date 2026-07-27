<?php

$pageTitle       = "Applicant Management";
$pageCSS         = "applicants.css";
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
    "Submitted"  => ["label" => "Submitted",           "class" => "badge-gray"],
    "Review"     => ["label" => "Under Review",         "class" => "badge-blue"],
    "Interview"  => ["label" => "Interview Scheduled",  "class" => "badge-orange"],
    "Hired"      => ["label" => "Hired",                "class" => "badge-green"],
    "Rejected"   => ["label" => "Rejected",              "class" => "badge-red"],
];

/* Summary counts for the dashboard cards */
$summary = [
    "Total"     => count($applicants),
    "Submitted" => 0,
    "Review"    => 0,
    "Interview" => 0,
    "Hired"     => 0,
    "Rejected"  => 0,
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

        <div class="page-heading">
            <div>
                <h1>Applicant Management</h1>
                <p>Track every applicant from submission through AI screening, interviews, and hiring decisions.</p>
            </div>
        </div>

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

            <div class="summary-card" data-filter="Review">
                <div class="summary-icon icon-review"><i class="fa-solid fa-magnifying-glass"></i></div>
                <div class="summary-body">
                    <span class="summary-count"><?= (int)$summary['Review'] ?></span>
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
                            data-contact="<?= htmlspecialchars($applicant['contact_number'] ?? '') ?>"
                            data-applied-date="<?= htmlspecialchars($applicant['date_applied'] ?? '') ?>"
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

                            <td><?= htmlspecialchars($applicant['date_applied'] ?? '') ?></td>

                            <td class="col-actions">
                                <button type="button" class="btn-view" data-action="view">
                                    <i class="fa-solid fa-eye"></i> View
                                </button>
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

    <!-- ==========================================================
        APPLICANT DETAILS PANEL (slide-in)
    ========================================================== -->

    <div class="overlay" id="detailsOverlay"></div>

    <aside class="details-panel" id="detailsPanel" aria-hidden="true">

        <div class="details-header">
            <div class="details-header-left">
                <div class="avatar-circle avatar-large" id="dpAvatar">?</div>
                <div>
                    <h2 id="dpName">&nbsp;</h2>
                    <span class="badge" id="dpStatusBadge">&nbsp;</span>
                </div>
            </div>
            <button class="close-btn" id="closeDetails" type="button" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="details-body">

            <!-- Applicant Information -->
            <section class="detail-section">
                <h3><i class="fa-solid fa-id-card"></i> Applicant Information</h3>
                <div class="info-grid">
                    <div><span class="info-label">Applicant Name</span><span class="info-value" id="dpFullname">-</span></div>
                    <div><span class="info-label">Email Address</span><span class="info-value" id="dpEmail">-</span></div>
                    <div><span class="info-label">Contact Number</span><span class="info-value" id="dpContact">-</span></div>
                    <div><span class="info-label">Applied Position</span><span class="info-value" id="dpPosition">-</span></div>
                    <div><span class="info-label">Date Applied</span><span class="info-value" id="dpDate">-</span></div>
                    <div><span class="info-label">Application Status</span><span class="info-value" id="dpStatusText">-</span></div>
                </div>
            </section>

            <!-- Resume -->
            <section class="detail-section">
                <h3><i class="fa-solid fa-file-lines"></i> Resume</h3>
                <div class="resume-row">
                    <div class="resume-file">
                        <i class="fa-solid fa-file-pdf"></i>
                        <span id="dpResumeName">resume.pdf</span>
                    </div>
                    <div class="resume-actions">
                        <a href="#" id="dpViewResume" target="_blank" class="btn-outline">
                            <i class="fa-solid fa-eye"></i> View Resume
                        </a>
                        <a href="#" id="dpDownloadResume" download class="btn-outline">
                            <i class="fa-solid fa-download"></i> Download
                        </a>
                    </div>
                </div>
            </section>

            <!-- AI Screening -->
            <section class="detail-section">
                <h3><i class="fa-solid fa-robot"></i> AI Screening Results</h3>

                <div class="ai-score-row">
                    <div class="ai-score-circle" id="dpAiScoreCircle">
                        <span id="dpAiScoreText">0%</span>
                    </div>
                    <div class="ai-recommendation">
                        <span class="info-label">Recommendation</span>
                        <span class="rec-badge" id="dpRecommendation">-</span>
                    </div>
                </div>

                <div class="match-bars">
                    <div class="match-row">
                        <span class="match-label">Skills Match</span>
                        <div class="progress-track"><div class="progress-fill" id="dpSkillsBar"></div></div>
                        <span class="match-value" id="dpSkillsValue">0%</span>
                    </div>
                    <div class="match-row">
                        <span class="match-label">Experience Match</span>
                        <div class="progress-track"><div class="progress-fill" id="dpExperienceBar"></div></div>
                        <span class="match-value" id="dpExperienceValue">0%</span>
                    </div>
                    <div class="match-row">
                        <span class="match-label">Education Match</span>
                        <div class="progress-track"><div class="progress-fill" id="dpEducationBar"></div></div>
                        <span class="match-value" id="dpEducationValue">0%</span>
                    </div>
                </div>
            </section>

            <!-- Interview -->
            <section class="detail-section">
                <h3><i class="fa-solid fa-calendar-days"></i> Interview</h3>

                <div id="dpInterviewNotScheduled" class="interview-empty">
                    <span class="badge badge-gray">Not Scheduled</span>
                    <button type="button" class="btn-primary" id="openScheduleModal">
                        <i class="fa-solid fa-calendar-plus"></i> Schedule Interview
                    </button>
                </div>

                <div id="dpInterviewScheduled" class="interview-scheduled" style="display:none;">
                    <div class="info-grid">
                        <div><span class="info-label">Interview Date</span><span class="info-value" id="dpInterviewDate">-</span></div>
                        <div><span class="info-label">Interview Time</span><span class="info-value" id="dpInterviewTime">-</span></div>
                        <div><span class="info-label">Manager</span><span class="info-value" id="dpInterviewManager">-</span></div>
                        <div><span class="info-label">Location</span><span class="info-value" id="dpInterviewLocation">-</span></div>
                        <div><span class="info-label">Interview Status</span><span class="badge badge-orange" id="dpInterviewStatus">Scheduled</span></div>
                    </div>
                    <button type="button" class="btn-outline" id="editScheduleBtn">
                        <i class="fa-solid fa-pen"></i> Edit Schedule
                    </button>
                </div>
            </section>

            <!-- Manager Decision -->
            <section class="detail-section">
                <h3><i class="fa-solid fa-user-tie"></i> Manager Decision</h3>
                <div class="info-grid">
                    <div>
                        <span class="info-label">Recommendation</span>
                        <span class="rec-badge" id="dpMgrRecommendation">Pending</span>
                    </div>
                    <div class="full-width">
                        <span class="info-label">Interview Remarks</span>
                        <p class="remarks-text" id="dpMgrRemarks">No remarks yet.</p>
                    </div>
                </div>
            </section>

            <!-- Decision -->
            <section class="detail-section decision-section">
                <h3><i class="fa-solid fa-gavel"></i> Decision</h3>
                <div class="decision-buttons">
                    <button type="button" class="btn-success btn-block" id="hireBtn">
                        <i class="fa-solid fa-user-check"></i> Hire Applicant
                    </button>
                    <button type="button" class="btn-danger btn-block" id="rejectBtn">
                        <i class="fa-solid fa-user-xmark"></i> Reject Applicant
                    </button>
                </div>
            </section>

        </div>

    </aside>

    <!-- ==========================================================
        SCHEDULE INTERVIEW MODAL
    ========================================================== -->

    <div class="modal-overlay" id="scheduleModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fa-solid fa-calendar-days"></i> Schedule Interview</h3>
                <button type="button" class="close-btn" data-close="scheduleModal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="scheduleForm" class="modal-body">
                <label>Manager
                    <select name="manager" id="scheduleManager" required>
                        <option value="">Select manager</option>
                    </select>
                </label>
                <div class="form-row">
                    <label>Interview Date
                        <input type="date" name="interview_date" id="scheduleDate" required>
                    </label>
                    <label>Interview Time
                        <input type="time" name="interview_time" id="scheduleTime" required>
                    </label>
                </div>
                <label>Location
                    <input type="text" name="location" id="scheduleLocation" placeholder="e.g. HR Conference Room, Google Meet link" required>
                </label>
                <label>Notes
                    <textarea name="notes" id="scheduleNotes" rows="3" placeholder="Additional notes for the interview"></textarea>
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
                    You are about to hire <strong id="hireApplicantName">this applicant</strong>. This will:
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

        const rows          = Array.from(document.querySelectorAll('#applicantTable tbody tr.applicant-row'));
        const searchInput    = document.getElementById('applicantSearch');
        const positionFilter = document.getElementById('positionFilter');
        const statusFilter   = document.getElementById('statusFilter');
        const resultsCount   = document.getElementById('resultsCount');
        const pageNumbers    = document.getElementById('pageNumbers');
        const prevPageBtn    = document.getElementById('prevPage');
        const nextPageBtn    = document.getElementById('nextPage');

        const PAGE_SIZE = 8;
        let currentPage = 1;
        let activeStatusFilter = 'All';

        /* -------------------- Table filtering + pagination -------------------- */

        function applyFilters() {
            const search   = (searchInput.value || '').toLowerCase().trim();
            const position = positionFilter.value;
            const status   = statusFilter.value !== 'All' ? statusFilter.value : activeStatusFilter;

            const visible = rows.filter(row => {
                const matchesSearch   = !search || row.dataset.name.includes(search);
                const matchesPosition = position === 'All' || row.dataset.position === position;
                const matchesStatus   = status === 'All' || row.dataset.status === status;
                return matchesSearch && matchesPosition && matchesStatus;
            });

            rows.forEach(row => row.style.display = 'none');

            const totalPages = Math.max(1, Math.ceil(visible.length / PAGE_SIZE));
            if (currentPage > totalPages) currentPage = totalPages;

            const start = (currentPage - 1) * PAGE_SIZE;
            const pageRows = visible.slice(start, start + PAGE_SIZE);
            pageRows.forEach(row => row.style.display = '');

            resultsCount.textContent = visible.length
                ? `Showing ${start + 1}-${Math.min(start + PAGE_SIZE, visible.length)} of ${visible.length} applicants`
                : 'No applicants match your filters';

            renderPagination(totalPages);
        }

        function renderPagination(totalPages) {
            pageNumbers.innerHTML = '';
            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'page-num' + (i === currentPage ? ' active' : '');
                btn.textContent = i;
                btn.addEventListener('click', () => { currentPage = i; applyFilters(); });
                pageNumbers.appendChild(btn);
            }
            prevPageBtn.disabled = currentPage <= 1;
            nextPageBtn.disabled = currentPage >= totalPages;
        }

        prevPageBtn.addEventListener('click', () => { if (currentPage > 1) { currentPage--; applyFilters(); } });
        nextPageBtn.addEventListener('click', () => { currentPage++; applyFilters(); });

        searchInput.addEventListener('input', () => { currentPage = 1; applyFilters(); });
        positionFilter.addEventListener('change', () => { currentPage = 1; applyFilters(); });
        statusFilter.addEventListener('change', () => { currentPage = 1; activeStatusFilter = 'All'; applyFilters(); });

        document.querySelectorAll('.summary-card').forEach(card => {
            card.addEventListener('click', () => {
                activeStatusFilter = card.dataset.filter;
                statusFilter.value = 'All';
                currentPage = 1;
                document.querySelectorAll('.summary-card').forEach(c => c.classList.remove('active'));
                card.classList.add('active');
                applyFilters();
            });
        });

        applyFilters();

        /* -------------------- Details panel -------------------- */

        const overlay       = document.getElementById('detailsOverlay');
        const panel          = document.getElementById('detailsPanel');
        const closeDetails   = document.getElementById('closeDetails');

        const recClassMap = {
            'Highly Recommended': 'rec-high',
            'Recommended': 'rec-mid',
            'Needs Review': 'rec-review',
            'Not Recommended': 'rec-low'
        };

        const statusBadgeClass = {
            'Submitted': 'badge-gray',
            'Review': 'badge-blue',
            'Interview': 'badge-orange',
            'Hired': 'badge-green',
            'Rejected': 'badge-red'
        };

        const statusLabel = {
            'Submitted': 'Submitted',
            'Review': 'Under Review',
            'Interview': 'Interview Scheduled',
            'Hired': 'Hired',
            'Rejected': 'Rejected'
        };

        let currentRow = null;

        function openDetails(row) {
            currentRow = row;
            const d = row.dataset;

            document.getElementById('dpAvatar').textContent = (d.fullname || '?').charAt(0).toUpperCase();
            document.getElementById('dpName').textContent = d.fullname || '-';

            const statusBadge = document.getElementById('dpStatusBadge');
            statusBadge.textContent = statusLabel[d.status] || d.status;
            statusBadge.className = 'badge ' + (statusBadgeClass[d.status] || 'badge-gray');

            document.getElementById('dpFullname').textContent = d.fullname || '-';
            document.getElementById('dpEmail').textContent = d.email || '-';
            document.getElementById('dpContact').textContent = d.contact || '-';
            document.getElementById('dpPosition').textContent = d.position || '-';
            document.getElementById('dpDate').textContent = d.appliedDate || '-';
            document.getElementById('dpStatusText').textContent = statusLabel[d.status] || d.status;

            document.getElementById('dpResumeName').textContent = d.resumeName || 'resume.pdf';
            document.getElementById('dpViewResume').href = d.resume || '#';
            document.getElementById('dpDownloadResume').href = d.resume || '#';

            const aiScore = parseInt(d.aiScore || '0', 10);
            document.getElementById('dpAiScoreText').textContent = aiScore + '%';
            document.getElementById('dpAiScoreCircle').style.setProperty('--score', aiScore);

            const recBadge = document.getElementById('dpRecommendation');
            recBadge.textContent = d.recommendation || 'Needs Review';
            recBadge.className = 'rec-badge ' + (recClassMap[d.recommendation] || 'rec-review');

            setBar('dpSkillsBar', 'dpSkillsValue', d.skills);
            setBar('dpExperienceBar', 'dpExperienceValue', d.experience);
            setBar('dpEducationBar', 'dpEducationValue', d.education);

            if (d.interviewStatus === 'Scheduled') {
                document.getElementById('dpInterviewNotScheduled').style.display = 'none';
                document.getElementById('dpInterviewScheduled').style.display = 'block';
                document.getElementById('dpInterviewDate').textContent = d.interviewDate || '-';
                document.getElementById('dpInterviewTime').textContent = d.interviewTime || '-';
                document.getElementById('dpInterviewManager').textContent = d.interviewManager || '-';
                document.getElementById('dpInterviewLocation').textContent = d.interviewLocation || '-';
            } else {
                document.getElementById('dpInterviewNotScheduled').style.display = 'flex';
                document.getElementById('dpInterviewScheduled').style.display = 'none';
            }

            const mgrRec = document.getElementById('dpMgrRecommendation');
            mgrRec.textContent = d.mgrRecommendation || 'Pending';
            mgrRec.className = 'rec-badge ' + (recClassMap[d.mgrRecommendation] || 'rec-review');
            document.getElementById('dpMgrRemarks').textContent = d.mgrRemarks || 'No remarks yet.';

            document.getElementById('hireApplicantName').textContent = d.fullname || 'this applicant';

            overlay.classList.add('active');
            panel.classList.add('active');
            panel.setAttribute('aria-hidden', 'false');
        }

        function setBar(barId, valueId, raw) {
            const value = parseInt(raw || '0', 10);
            document.getElementById(barId).style.width = value + '%';
            document.getElementById(valueId).textContent = value + '%';
        }

        document.querySelectorAll('[data-action="view"]').forEach(btn => {
            btn.addEventListener('click', () => openDetails(btn.closest('tr')));
        });

        function closeDetailsPanel() {
            overlay.classList.remove('active');
            panel.classList.remove('active');
            panel.setAttribute('aria-hidden', 'true');
        }

        closeDetails.addEventListener('click', closeDetailsPanel);
        overlay.addEventListener('click', () => {
            closeDetailsPanel();
            closeAllModals();
        });

        /* -------------------- Modals -------------------- */

        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }
        function closeAllModals() {
            ['scheduleModal', 'hireModal', 'rejectModal'].forEach(closeModal);
        }

        document.querySelectorAll('[data-close]').forEach(btn => {
            btn.addEventListener('click', () => closeModal(btn.dataset.close));
        });

        /* Schedule interview */
        document.getElementById('openScheduleModal').addEventListener('click', () => openModal('scheduleModal'));
        document.getElementById('editScheduleBtn').addEventListener('click', () => {
            if (!currentRow) return;
            const d = currentRow.dataset;
            document.getElementById('scheduleDate').value = d.interviewDate || '';
            document.getElementById('scheduleTime').value = d.interviewTime || '';
            document.getElementById('scheduleLocation').value = d.interviewLocation || '';
            document.getElementById('scheduleNotes').value = d.interviewNotes || '';
            openModal('scheduleModal');
        });

        document.getElementById('scheduleForm').addEventListener('submit', function (e) {
            e.preventDefault();
            if (!currentRow) return;

            const d = currentRow.dataset;
            d.interviewStatus   = 'Scheduled';
            d.interviewDate     = document.getElementById('scheduleDate').value;
            d.interviewTime     = document.getElementById('scheduleTime').value;
            d.interviewManager  = document.getElementById('scheduleManager').selectedOptions[0]?.text || '';
            d.interviewLocation = document.getElementById('scheduleLocation').value;
            d.interviewNotes    = document.getElementById('scheduleNotes').value;

            d.status = 'Interview';
            const statusCell = currentRow.querySelector('td:nth-child(4) .badge');
            statusCell.textContent = statusLabel['Interview'];
            statusCell.className = 'badge ' + statusBadgeClass['Interview'];

            closeModal('scheduleModal');
            openDetails(currentRow);

            // TODO: replace with AJAX call to ApplicantController::scheduleInterview
        });

        /* Hire */
        document.getElementById('hireBtn').addEventListener('click', () => openModal('hireModal'));
        document.getElementById('confirmHireBtn').addEventListener('click', () => {
            if (!currentRow) return;

            currentRow.dataset.status = 'Hired';
            const statusCell = currentRow.querySelector('td:nth-child(4) .badge');
            statusCell.textContent = statusLabel['Hired'];
            statusCell.className = 'badge ' + statusBadgeClass['Hired'];

            closeModal('hireModal');
            closeDetailsPanel();
            applyFilters();

            // TODO: replace with AJAX call to ApplicantController::hireApplicant
            // which should: set application_status = Hired, insert into employees,
            // create the user account, send the hiring email, and start onboarding.
        });

        /* Reject */
        document.getElementById('rejectBtn').addEventListener('click', () => openModal('rejectModal'));
        document.getElementById('rejectForm').addEventListener('submit', function (e) {
            e.preventDefault();
            if (!currentRow) return;

            currentRow.dataset.status = 'Rejected';
            const statusCell = currentRow.querySelector('td:nth-child(4) .badge');
            statusCell.textContent = statusLabel['Rejected'];
            statusCell.className = 'badge ' + statusBadgeClass['Rejected'];

            closeModal('rejectModal');
            closeDetailsPanel();
            applyFilters();

            // TODO: replace with AJAX call to ApplicantController::rejectApplicant
            // passing document.getElementById('rejectionRemarks').value
        });

    })();
    </script>

<?php require '../resources/views/includes/scripts.php'?>