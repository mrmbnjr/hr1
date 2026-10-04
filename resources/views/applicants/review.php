<?php
$pageTitle       = "Applicant Management";
$pageCSS         = "applicants.css";
$pageJS          = "applicants.js";
$pageDescription = "Review applicants, monitor AI screening, and manage the hiring pipeline.";

if (!isset($_SESSION['user_id'])) {
    header("Location: " . url("?page=login"));
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

/** Renders a "label" + "value" pair for the info grids below. */
function infoField(string $label, string $value, bool $fullWidth = false): void
{
    $class = $fullWidth ? 'full-width' : '';
    echo "<div class=\"{$class}\">";
    echo "<span class=\"info-label\">" . htmlspecialchars($label) . "</span>";
    echo "<span class=\"info-value\">" . htmlspecialchars($value) . "</span>";
    echo "</div>";
}

function getMatchClass(int $score): string
{
    if ($score >= 85) return "high";
    if ($score >= 70) return "medium";
    return "low";
}

/**
 * Builds an ordered "File N" reference for every credential document so
 * combined values can point back at the file they came from.
 */
function credentialFileRefs(array $documents): array
{
    $refs = [];

    foreach ($documents as $index => $document) {
        $refs[(int) ($document['evaluation_id'] ?? 0)] = [
            'number' => $index + 1,
            'name'   => (string) (
                $document['original_file_name']
                ?? basename((string) ($document['file_name'] ?? 'Document'))
            ),
        ];
    }

    return $refs;
}

/** Only the documents the AI has actually assessed. */
function credentialAssessedDocuments(array $documents): array
{
    return array_values(
        array_filter(
            $documents,
            static fn (array $document): bool =>
                strtolower(trim((string) ($document['evaluation_status'] ?? ''))) === 'evaluated'
        )
    );
}

/**
 * Merges the credential fields across every assessed document.
 *
 * Returns [label => list of ['value' => ..., 'number' => ..., 'source' => ...]]
 * so a single field can show the value contributed by each file.
 */
function credentialCombinedFields(array $documents, array $fileRefs): array
{
    $columns = [
        'Document Type'             => 'document_type',
        'Issuing Organization'      => 'institution',
        'Credential or Achievement' => 'degree',
        'Skills or Competencies'    => 'field_of_study',
        'Year Earned'               => 'graduation_year',
        'Assessment Summary'        => 'ai_summary',
    ];

    $fields = [];

    foreach ($columns as $label => $column) {

        $entries = [];

        foreach ($documents as $document) {

            $value = trim((string) ($document[$column] ?? ''));

            if ($value === '') {
                continue;
            }

            $ref = $fileRefs[(int) ($document['evaluation_id'] ?? 0)] ?? [];

            $entries[] = [
                'value'  => $value,
                'number' => $ref['number'] ?? null,
                'source' => $ref['name'] ?? '',
            ];
        }

        $fields[$label] = $entries;
    }

    return $fields;
}

/**
 * Renders one combined credential field: every value stacked with the
 * file it was extracted from.
 */
function combinedField(string $label, array $entries, bool $fullWidth = false): void
{
    $class = $fullWidth ? 'full-width' : '';

    echo "<div class=\"{$class}\">";
    echo '<span class="info-label">' . htmlspecialchars($label) . '</span>';

    if ($entries === []) {
        echo '<span class="info-value">—</span>';
        echo '</div>';
        return;
    }

    echo '<ul class="credential-value-list">';

    foreach ($entries as $entry) {

        echo '<li>';
        echo '<span class="credential-value">'
            . htmlspecialchars($entry['value'])
            . '</span>';

        $source = (string) ($entry['source'] ?? '');

        if ($entry['number'] !== null) {
            $source = 'File ' . $entry['number'] . ' · ' . $source;
        }

        if ($source !== '') {
            echo '<span class="credential-source">'
                . htmlspecialchars($source)
                . '</span>';
        }

        echo '</li>';
    }

    echo '</ul>';
    echo '</div>';
}
?>

<?php require BASE_PATH . '/resources/views/includes/header.php'; ?>
<?php require BASE_PATH . '/resources/views/includes/sidebar.php'; ?>

<div class="main-content">

    <?php require BASE_PATH . '/resources/views/includes/navbar.php'; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success" role="status">
            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-error" role="alert">
            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="details-body">

        <!-- Applicant Information -->
        <section class="detail-section">
            <h3><i class="fa-solid fa-id-card"></i> Applicant Information</h3>
            <div class="info-grid">
                <?php
                infoField('Applicant Name', $applicant['fullname']);
                infoField('Email Address', $applicant['email']);
                infoField('Phone Number', $applicant['phone']);
                infoField('Applied Position', $applicant['position']);
                infoField('Date Applied', date('F d, Y', strtotime($applicant['applied_at'])));
                ?>
                <div>
                    <span class="info-label">Application Status</span>
                    <span class="badge <?= htmlspecialchars($statusMeta[$applicant['application_status']]['class']) ?>">
                        <?= htmlspecialchars($statusMeta[$applicant['application_status']]['label']) ?>
                    </span>
                </div>
            </div>
        </section>

        <!-- Applicant Achievements & Credentials -->
        <section class="detail-section credential-section">
            <div class="ai-actions">
                <div class="section-heading-copy">
                    <h3>
                        <i class="fa-solid fa-award"></i>
                        Achievements &amp; Credentials
                    </h3>
                    <p>
                        Every certificate, diploma, and transcript the applicant submitted is
                        assessed together as one set. The values below are combined across all
                        documents, with the file each one came from.
                    </p>
                </div>

                <?php if (!empty($academicDocuments)): ?>
                    <button
                        type="button"
                        class="btn-primary"
                        id="evaluateAcademicDocumentBtn"
                        data-application-id="<?= (int) $applicant['application_id'] ?>"
                    >
                        <i class="fa-solid fa-shield-check"></i>
                        Assess All Credentials
                    </button>
                <?php endif; ?>
            </div>

            <?php if (!empty($academicDocuments)): ?>

                <?php
                /*
                 * Every credential document is assessed together, so the
                 * summary below merges the values from all of them instead
                 * of surfacing a single document.
                 */
                $credentialFileRefs = credentialFileRefs($academicDocuments);
                $assessedDocuments  = credentialAssessedDocuments($academicDocuments);

                $documentCount = count($academicDocuments);
                $assessedCount = count($assessedDocuments);

                $validCount    = 0;
                $confidenceSum = 0.0;

                foreach ($assessedDocuments as $document) {
                    if (!empty($document['document_valid'])) {
                        $validCount++;
                    }

                    $confidenceSum += (float) ($document['confidence_score'] ?? 0);
                }

                $averageConfidence = $assessedCount > 0
                    ? $confidenceSum / $assessedCount
                    : 0.0;

                if ($assessedCount === 0) {
                    $overallClass = 'rec-review';
                    $overallLabel = 'Not Assessed';
                } elseif ($validCount === $assessedCount) {
                    $overallClass = 'rec-high';
                    $overallLabel = 'All Documents Valid';
                } elseif ($validCount > 0) {
                    $overallClass = 'rec-review';
                    $overallLabel = 'Partially Verified';
                } else {
                    $overallClass = 'rec-low';
                    $overallLabel = 'Needs Review';
                }
                ?>

                <div class="credential-list">

                    <?php foreach ($academicDocuments as $document): ?>
                        <?php
                        $reference  = $credentialFileRefs[(int) $document['evaluation_id']] ?? null;
                        $isAssessed = strtolower(trim((string) ($document['evaluation_status'] ?? ''))) === 'evaluated';
                        ?>

                        <div class="resume-row document-file-row">

                            <div class="resume-file">
                                <i class="fa-solid fa-certificate"></i>

                                <div>
                                    <span class="document-file-name">
                                        <?= htmlspecialchars(
                                            $document['original_file_name']
                                            ?? basename($document['file_name'] ?? 'Document')
                                        ) ?>
                                    </span>

                                    <?php if ($reference): ?>
                                        <span class="credential-file-index">File <?= (int) $reference['number'] ?></span>
                                    <?php endif; ?>

                                    <?php if (!empty($document['is_primary'])): ?>
                                        <span class="rec-badge">Primary</span>
                                    <?php endif; ?>

                                    <span class="rec-badge <?= $isAssessed ? 'rec-high' : 'rec-review' ?>">
                                        <?= $isAssessed ? 'Assessed' : 'Not Assessed' ?>
                                    </span>
                                </div>
                            </div>

                            <div class="resume-actions">
                                <a
                                    href="?page=download-academic-document&id=<?= (int) $applicant['application_id'] ?>&document_id=<?= (int) $document['evaluation_id'] ?>"
                                    target="_blank"
                                    class="btn-outline"
                                >
                                    <i class="fa-solid fa-eye"></i>
                                    View Document
                                </a>

                                <a
                                    href="?page=download-academic-document&id=<?= (int) $applicant['application_id'] ?>&document_id=<?= (int) $document['evaluation_id'] ?>&download=1"
                                    class="btn-outline"
                                >
                                    <i class="fa-solid fa-download"></i>
                                    Download
                                </a>
                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

                <?php if ($assessedCount > 0): ?>

                    <div class="credential-combined">
                        <div class="ai-score-row">
                            <div
                                class="ai-score-circle"
                                style="--score: <?= number_format($averageConfidence, 2, '.', '') ?>;"
                            >
                                <span><?= number_format($averageConfidence, 2) ?>%</span>
                            </div>

                            <div class="ai-recommendation">
                                <span class="info-label">
                                    Combined Credential Status
                                </span>

                                <span class="rec-badge <?= $overallClass ?>">
                                    <?= htmlspecialchars($overallLabel) ?>
                                </span>

                                <span class="credential-meta-note">
                                    Combined from <?= $assessedCount ?>
                                    of <?= $documentCount ?> document(s)
                                </span>
                            </div>
                        </div>

                        <div class="info-grid">
                            <?php
                            $combinedFields = credentialCombinedFields(
                                $assessedDocuments,
                                $credentialFileRefs
                            );

                            foreach ($combinedFields as $label => $entries) {
                                combinedField(
                                    $label,
                                    $entries,
                                    $label === 'Assessment Summary'
                                );
                            }
                            ?>
                        </div>
                    </div>

                <?php else: ?>

                    <p class="file-note">
                        None of the submitted documents have been assessed yet.
                        Use <strong>Assess All Credentials</strong> to evaluate them together.
                    </p>

                <?php endif; ?>

                <?php if ($assessedCount > 0 && $assessedCount < $documentCount): ?>

                    <p class="file-note">
                        <?= $documentCount - $assessedCount ?> document(s) have not been assessed yet
                        and are not included in the combined values above.
                    </p>

                <?php endif; ?>

            <?php else: ?>

                <p class="file-note">
                    No credential documents were submitted with this application.
                </p>

            <?php endif; ?>
        </section>

        <!-- AI Screening -->
        <section class="detail-section">
            <div class="ai-actions">
                <h3><i class="fa-solid fa-robot"></i> AI Screening Results</h3>
                <button type="button" class="btn-primary" id="evaluateResumeBtn" data-application-id="<?= (int) $applicant['application_id'] ?>">
                    <i class="fa-solid fa-robot"></i>
                    <?= !empty($applicant['ai_score']) ? 'Re-run AI Screening' : 'Evaluate Resume' ?>
                </button>
            </div>

            <div class="resume-row document-file-row">
                <div class="resume-file">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span class="document-file-name"><?= htmlspecialchars(basename($applicant['resume_file'])) ?></span>
                </div>
                <div class="resume-actions resume-actions-right">
                    <a href="?page=download-resume&id=<?= (int) $applicant['application_id'] ?>" target="_blank" class="btn-outline">
                        <i class="fa-solid fa-eye"></i> View Resume
                    </a>
                    <a href="?page=download-resume&id=<?= (int) $applicant['application_id'] ?>&download=1" class="btn-outline">
                        <i class="fa-solid fa-download"></i> Download
                    </a>
                </div>
            </div>

            <?php
            $score      = (float) ($applicant['ai_score'] ?? 0);
            $skills     = (float) ($applicant['skills_score'] ?? 0);
            $experience = (float) ($applicant['experience_score'] ?? 0);
            $education  = (float) ($applicant['education_score'] ?? 0);
            $keywords   = (float) ($applicant['keyword_score'] ?? 0);

            $strengths = json_decode($applicant['strengths'] ?? '[]', true);
            $concerns  = json_decode($applicant['concerns'] ?? '[]', true);
            $summary   = $applicant['ai_summary'] ?? 'No AI summary available.';

            if (!is_array($strengths)) $strengths = [];
            if (!is_array($concerns))  $concerns  = [];

            $matchRows = [
                'Skills Match'     => $skills,
                'Experience Match' => $experience,
                'Education Match'  => $education,
                'Keyword Match'    => $keywords,
            ];
            ?>

            <div class="ai-score-row">
                <div class="ai-score-circle" style="--score: <?= number_format($score, 2, '.', '') ?>;">
                    <span><?= number_format($score, 2) ?>%</span>
                </div>
                <div class="ai-recommendation">
                    <span class="info-label">Recommendation</span>
                    <span class="rec-badge"><?= htmlspecialchars($applicant['recommendation']) ?></span>
                </div>
            </div>

            <div class="match-bars">
                <?php foreach ($matchRows as $label => $value): ?>
                    <div class="match-row">
                        <span class="match-label"><?= htmlspecialchars($label) ?></span>
                        <div class="progress-track">
                            <div class="progress-fill" style="width:<?= number_format($value, 2) ?>%"></div>
                        </div>
                        <span class="match-value"><?= number_format($value, 2) ?>%</span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="ai-analysis-grid">
                <div class="analysis-card">
                    <h4><i class="fa-solid fa-circle-check"></i> Key Strengths</h4>
                    <ul>
                        <?php foreach ($strengths as $item): ?>
                            <li><?= htmlspecialchars($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="analysis-card">
                    <h4><i class="fa-solid fa-triangle-exclamation"></i> Areas for Review</h4>
                    <ul>
                        <?php foreach ($concerns as $item): ?>
                            <li><?= htmlspecialchars($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="ai-summary">
                <span class="info-label">AI Summary</span>
                <p><?= htmlspecialchars($summary) ?></p>
            </div>
        </section>

        <!-- Interview -->
        <section class="detail-section">
            <h3><i class="fa-solid fa-calendar-days"></i> Interview</h3>

            <?php if (empty($applicant['interview_id'])): ?>
                <div class="interview-empty">
                    <span class="badge badge-gray">Not Scheduled</span>
                    <button type="button" class="btn-primary" id="openScheduleModal">
                        <i class="fa-solid fa-calendar-plus"></i> Schedule Interview
                    </button>
                </div>
            <?php else: ?>
                <?php
                $resultClass = match ($applicant['result']) {
                    'Passed' => 'badge-green',
                    'Failed' => 'badge-red',
                    default  => 'badge-orange'
                };
                $isManagerInterview = ($applicant['interviewer_role'] ?? '') === 'MANAGER';
                ?>
                <div class="interview-scheduled">
                    <div class="info-grid">
                        <?php
                        infoField('Interview Date', date('F d, Y', strtotime($applicant['interview_date'])));
                        infoField('Interview Time', date('g:i A', strtotime($applicant['interview_date'])));
                        infoField('Interviewer', $applicant['interviewer_name'] . ' (' . $applicant['interviewer_role'] . ')');
                        infoField('Interview Type', $applicant['interview_type']);
                        infoField('Location', $applicant['location']);
                        ?>
                        <?php if ($isManagerInterview): ?>
                            <div>
                                <span class="info-label">Result</span>
                                <span class="badge <?= $resultClass ?>"><?= htmlspecialchars($applicant['result']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($isManagerInterview && $applicant['result'] !== 'Pending'): ?>
                        <div class="remarks-box">
                            <span class="info-label">Interview Remarks</span>
                            <p class="remarks-text"><?= nl2br(htmlspecialchars($applicant['remarks'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($applicant['result'] === 'Pending'): ?>
                        <button type="button" class="btn-outline" id="editScheduleBtn">
                            <i class="fa-solid fa-pen"></i> Edit Schedule
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Decision -->
        <?php if (!in_array($applicant['application_status'], ['Hired', 'Rejected'], true)): ?>
            <section class="detail-section decision-section">
                <h3><i class="fa-solid fa-gavel"></i> Decision</h3>
                <div class="decision-buttons">
                    <button type="button" class="btn-success btn-block" id="hireBtn" data-application-id="<?= (int) $applicant['application_id'] ?>">
                        <i class="fa-solid fa-user-check"></i> Hire Applicant
                    </button>
                    <button type="button" class="btn-danger btn-block" id="rejectBtn" data-application-id="<?= (int) $applicant['application_id'] ?>">
                        <i class="fa-solid fa-user-xmark"></i> Reject Applicant
                    </button>
                </div>
            </section>
        <?php endif; ?>

    </div>

    <!-- Schedule Interview Modal -->
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
                                <option value="<?= (int) $manager['user_id']; ?>" <?= (int) ($applicant['interviewer_id'] ?? 0) === (int) $manager['user_id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($manager['fullname'] ?? ''); ?> (<?= htmlspecialchars($manager['role_name'] ?? ''); ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </label>

                <label>Interview Type
                    <select name="interview_type" id="scheduleType" required>
                        <option value="" hidden>Select Type</option>
                        <?php foreach (['Phone', 'Online', 'Face-to-Face'] as $type): ?>
                            <option value="<?= $type ?>" <?= ($applicant['interview_type'] ?? '') === $type ? 'selected' : ''; ?>>
                                <?= $type ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div class="form-row">
                    <label>Interview Date
                        <input type="date" name="interview_date" id="scheduleDate" min="<?= date('Y-m-d') ?>"
                               value="<?= !empty($applicant['interview_date']) ? date('Y-m-d', strtotime($applicant['interview_date'])) : ''; ?>" required>
                    </label>
                    <label>Interview Time
                        <input type="time" name="interview_time" id="scheduleTime"
                               value="<?= !empty($applicant['interview_date']) ? date('H:i', strtotime($applicant['interview_date'])) : ''; ?>" required>
                    </label>
                </div>

                <label>Location
                    <input type="text" name="location" minlength="3" maxlength="64" id="scheduleLocation"
                           value="<?= htmlspecialchars($applicant['location'] ?? ''); ?>"
                           placeholder="e.g. HR Conference Room, Google Meet link" required>
                </label>

                <label>Notes
                    <textarea name="notes" minlength="3" maxlength="500" id="scheduleNotes" rows="3"
                              placeholder="Additional notes for the interview"><?= htmlspecialchars($applicant['remarks'] ?? ''); ?></textarea>
                </label>

                <div class="modal-actions">
                    <button type="button" class="btn-outline" data-close="scheduleModal">Cancel</button>
                    <button type="submit" class="btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Hire Confirmation Modal -->
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

    <!-- Reject Modal -->
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

    <?php require BASE_PATH . '/resources/views/includes/footer.php'; ?>
</div>

<?php require BASE_PATH . '/resources/views/includes/scripts.php'; ?>