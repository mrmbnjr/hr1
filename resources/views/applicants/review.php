<?php

use App\Services\Auth;
use App\Services\PipelineService;

$pageTitle       = "Applicant Management";
$pageCSS         = "applicants.css";
$pageJS          = "applicants.js";
$pageDescription = "Review applicants, monitor AI screening, and manage the hiring pipeline.";

/*
 * Fixed rejection reason categories, shared with the server-side
 * validation so the form can never offer an invalid value.
 */
$rejectionReasons = PipelineService::REJECTION_REASONS;

/*
 * HR-only actions use $isHrOrAdmin; interview and final-selection
 * decisions are also available to managers assigned the MGR role.
 */
$isHrOrAdmin = in_array(Auth::role(), ['ADMIN', 'HR'], true);
$isHrOrManager = in_array(Auth::role(), ['ADMIN', 'HR', 'MGR'], true);

/*
 * Pipeline data loaded by ApplicantController::review(); defaults
 * keep this view safe if it is ever included without them.
 */
$evaluations   = $evaluations ?? [];
$requirements  = $requirements ?? [];
$offer         = $offer ?? null;

if (!isset($_SESSION['user_id'])) {
    header("Location: " . url("?page=login"));
    exit;
}

$applicant = $applicant ?? [];

if (!$applicant) {
    header("Location: ?page=applicants");
    exit;
}

$isAssignedInterviewer =
    (int) Auth::userId() === (int) ($applicant['interviewer_id'] ?? 0);

/*
 * Applicants still in the screening stages are qualified or rejected
 * here; the interview stage (and its schedule) only opens once they
 * are qualified.
 */
$isScreeningStage = in_array(
    $applicant['application_status'],
    ['Submitted', 'Under Review'],
    true
);

$statusMeta = [
    "Submitted"    => ["label" => "Submitted", "class" => "badge-gray"],
    "Under Review" => ["label" => "Under Review", "class" => "badge-blue"],
    "Interview"    => ["label" => "Interview", "class" => "badge-orange"],
    "Selection"    => ["label" => "Selection", "class" => "badge-purple"],
    "Offer"        => ["label" => "Offer", "class" => "badge-teal"],
    "Hired"        => ["label" => "Hired", "class" => "badge-green"],
    "Rejected"     => ["label" => "Rejected", "class" => "badge-red"],
];

/*
 * Resolved once so an unexpected status can never trigger a PHP
 * warning or render an empty badge.
 */
$currentStatusMeta = $statusMeta[$applicant['application_status']]
    ?? ["label" => (string) $applicant['application_status'], "class" => "badge-gray"];

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

/**
 * Renders a hiring-requirements checklist table for one stage
 * (Selection or Pre-employment). Mark buttons are HR/ADMIN-only and
 * feed the shared requirement modal.
 */
function requirementsTable(array $stageRequirements, bool $showActions): void
{
    if ($stageRequirements === []) {
        echo '<p class="file-note">No requirements recorded for this stage.</p>';
        return;
    }

    $statusClass = [
        'Pending' => 'badge-orange',
        'Cleared' => 'badge-green',
        'Failed'  => 'badge-red'
    ];

    echo '<section class="table-card"><div class="table-scroll">';
    echo '<table class="applicant-table requirements-table"><thead><tr>';
    echo '<th>Requirement</th><th>Status</th><th>Verified by</th><th>Notes</th>';
    if ($showActions) {
        echo '<th class="col-actions">Action</th>';
    }
    echo '</tr></thead><tbody>';

    foreach ($stageRequirements as $requirement) {

        $status = (string) $requirement['status'];

        $verifiedBy = trim((string) ($requirement['verified_by_name'] ?? ''));

        echo '<tr>';

        echo '<td><strong>'
            . htmlspecialchars((string) $requirement['requirement_type'])
            . '</strong></td>';

        echo '<td><span class="badge '
            . htmlspecialchars($statusClass[$status] ?? 'badge-gray')
            . '">'
            . htmlspecialchars($status)
            . '</span></td>';

        echo '<td>';
        if (!empty($requirement['verified_at'])) {
            echo htmlspecialchars($verifiedBy !== '' ? $verifiedBy : 'HR');
            echo '<small>'
                . date('M d, Y', strtotime((string) $requirement['verified_at']))
                . '</small>';
        } else {
            echo '&mdash;';
        }
        echo '</td>';

        echo '<td>';
        if (!empty($requirement['notes'])) {
            echo nl2br(htmlspecialchars((string) $requirement['notes']));
        } else {
            echo '&mdash;';
        }
        echo '</td>';

        if ($showActions) {
            echo '<td class="col-actions"><button type="button" '
                . 'class="btn-outline requirement-mark-btn" '
                . 'data-requirement-id="' . (int) $requirement['requirement_id'] . '" '
                . 'data-requirement-type="' . htmlspecialchars((string) $requirement['requirement_type']) . '" '
                . 'data-requirement-status="' . htmlspecialchars($status) . '" '
                . 'data-requirement-note="' . htmlspecialchars((string) ($requirement['notes'] ?? '')) . '">'
                . '<i class="fa-solid fa-pen"></i> '
                . ($requirement['stage'] === 'Pre-employment' ? 'Mark Cleared' : 'Update')
                . '</button></td>';
        }

        echo '</tr>';
    }

    echo '</tbody></table></div></section>';
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

    <?php
    /*
     * Pipeline tracker: the five hiring phases, with the applicant's
     * current phase highlighted and the earlier ones checked.
     */
    $pipelineSteps = [
        1 => ['label' => 'Application Review',  'icon' => 'fa-file-lines'],
        2 => ['label' => 'Interview',           'icon' => 'fa-calendar-days'],
        3 => ['label' => 'Selection',           'icon' => 'fa-clipboard-check'],
        4 => ['label' => 'Offer',               'icon' => 'fa-file-signature'],
        5 => ['label' => 'Hiring Confirmation', 'icon' => 'fa-user-check'],
    ];

    $statusStep = [
        'Submitted'    => 1,
        'Under Review' => 1,
        'Interview'    => 2,
        'Selection'    => 3,
        'Offer'        => 4,
        'Hired'        => 5,
        'Rejected'     => 1,
    ];

    $currentStep   = $statusStep[$applicant['application_status']] ?? 1;
    $isRejectedApp = $applicant['application_status'] === 'Rejected';
    ?>
    <section class="detail-section pipeline-tracker" aria-label="Hiring pipeline progress">
        <ol class="pipeline-steps">
            <?php foreach ($pipelineSteps as $stepNumber => $step): ?>
                <?php
                if ($isRejectedApp) {
                    $stepState = $stepNumber === $currentStep ? 'rejected' : 'dimmed';
                } elseif (
                    $stepNumber < $currentStep
                    || $applicant['application_status'] === 'Hired'
                ) {
                    $stepState = 'completed';
                } elseif ($stepNumber === $currentStep) {
                    $stepState = 'current';
                } else {
                    $stepState = 'upcoming';
                }
                ?>
                <li class="pipeline-step <?= $stepState ?>">
                    <span class="pipeline-step-marker">
                        <i class="fa-solid <?= $stepState === 'completed' ? 'fa-check' : $step['icon'] ?>"></i>
                    </span>
                    <span class="pipeline-step-label"><?= htmlspecialchars($step['label']) ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
        <?php if ($isRejectedApp): ?>
            <p class="pipeline-rejected-note">This application was rejected.</p>
        <?php endif; ?>
    </section>

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
                    <span class="badge <?= htmlspecialchars($currentStatusMeta['class']) ?>">
                        <?= htmlspecialchars($currentStatusMeta['label']) ?>
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

        <!-- Interview (hidden until the applicant qualifies) -->
        <?php if (!$isScreeningStage): ?>
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
        <?php endif; ?>

        <!-- Interview Evaluation (visible from the Interview stage onward) -->
        <?php if (!$isScreeningStage): ?>
            <section class="detail-section">
                <div class="ai-actions">
                    <div class="section-heading-copy">
                        <h3><i class="fa-solid fa-clipboard-list"></i> Interview Evaluation</h3>
                        <p>
                            Every interviewer evaluation is kept in full - the latest is marked
                            Current, but earlier scores stay visible before HR advances the
                            applicant.
                        </p>
                    </div>

                    <?php if (
                        $applicant['application_status'] === 'Interview'
                        && $isAssignedInterviewer
                    ): ?>
                        <button type="button" class="btn-primary" id="openEvaluationModal">
                            <i class="fa-solid fa-plus"></i> Submit Evaluation
                        </button>
                    <?php endif; ?>
                </div>

                <?php if ($evaluations === []): ?>
                    <p class="file-note">
                        No evaluations submitted yet.
                        <?php if (
                            $applicant['application_status'] === 'Interview'
                            && $isAssignedInterviewer
                        ): ?>
                            Use <strong>Submit Evaluation</strong> to record ratings, remarks, and a recommendation.
                        <?php elseif ($applicant['application_status'] === 'Interview'): ?>
                            The scheduled interviewer has not submitted an evaluation yet.
                        <?php endif; ?>
                    </p>
                <?php else: ?>
                    <div class="evaluation-list">
                        <?php foreach ($evaluations as $evaluationIndex => $evaluation): ?>
                            <?php
                            $isCurrentEvaluation = $evaluationIndex === 0;
                            $evaluatorName = trim((string) ($evaluation['evaluator_name'] ?? ''));

                            $recommendationClass = match ($evaluation['recommendation']) {
                                'Recommend'           => 'rec-high',
                                'Further Assessment'  => 'rec-review',
                                'Do Not Recommend'    => 'rec-low',
                                default               => 'rec-review'
                            };
                            ?>
                            <div class="evaluation-card<?= $isCurrentEvaluation ? ' current' : '' ?>">
                                <div class="evaluation-head">
                                    <div class="evaluation-head-left">
                                        <span class="evaluation-evaluator">
                                            <?= htmlspecialchars($evaluatorName !== '' ? $evaluatorName : 'System account') ?>
                                        </span>
                                        <?php if (!empty($evaluation['evaluator_role_code'])): ?>
                                            <span class="badge badge-gray"><?= htmlspecialchars($evaluation['evaluator_role_code']) ?></span>
                                        <?php endif; ?>
                                        <?php if ($isCurrentEvaluation): ?>
                                            <span class="badge badge-blue">Current</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="evaluation-date">
                                        <?= date('F d, Y g:i A', strtotime((string) $evaluation['created_at'])) ?>
                                    </span>
                                </div>

                                <div class="evaluation-ratings">
                                    <?php foreach (PipelineService::RATING_CRITERIA as $criterionKey => $criterionLabel): ?>
                                        <div class="evaluation-rating">
                                            <span class="match-label"><?= htmlspecialchars($criterionLabel) ?></span>
                                            <span class="evaluation-score"><?= (int) $evaluation[$criterionKey] ?> / 5</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="evaluation-rec">
                                    <span class="info-label">Recommendation</span>
                                    <span class="rec-badge <?= $recommendationClass ?>">
                                        <?= htmlspecialchars($evaluation['recommendation']) ?>
                                    </span>
                                </div>

                                <?php if (!empty($evaluation['strengths'])): ?>
                                    <div class="analysis-card">
                                        <h4><i class="fa-solid fa-circle-check"></i> Strengths</h4>
                                        <p><?= nl2br(htmlspecialchars((string) $evaluation['strengths'])) ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($evaluation['weaknesses'])): ?>
                                    <div class="analysis-card">
                                        <h4><i class="fa-solid fa-triangle-exclamation"></i> Weaknesses</h4>
                                        <p><?= nl2br(htmlspecialchars((string) $evaluation['weaknesses'])) ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($evaluation['remarks'])): ?>
                                    <div class="remarks-box">
                                        <span class="info-label">Remarks</span>
                                        <p class="remarks-text"><?= nl2br(htmlspecialchars((string) $evaluation['remarks'])) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <!-- Selection: credential, reference, and background checks -->
        <?php if ($applicant['application_status'] === 'Selection'): ?>
            <?php
            $selectionRequirements = array_values(
                array_filter(
                    $requirements,
                    static fn (array $requirement): bool =>
                        $requirement['stage'] === 'Selection'
                )
            );
            $repeatableSelectionRequirements = array_values(
                array_filter(
                    $selectionRequirements,
                    static fn (array $requirement): bool =>
                        $requirement['status'] !== 'Pending'
                )
            );

            $selectionOutstanding = array_filter(
                $selectionRequirements,
                static fn (array $requirement): bool =>
                    $requirement['status'] !== 'Cleared'
            );

            $allSelectionCleared =
                $selectionRequirements !== []
                && $selectionOutstanding === [];
            ?>
            <section class="detail-section">
                <div class="ai-actions">
                    <div class="section-heading-copy">
                        <h3><i class="fa-solid fa-clipboard-check"></i> Selection</h3>
                        <p>
                            Credential, reference, and background checks. The AI assessment
                            supports HR's review but does not clear a required check. Every
                            check must be cleared before approving for offer.
                        </p>
                    </div>
                </div>

                <?php if ($selectionRequirements === []): ?>
                    <p class="file-note">No selection requirements were recorded for this application.</p>
                <?php else: ?>
                    <?php requirementsTable($selectionRequirements, $isHrOrManager); ?>
                <?php endif; ?>
            </section>

            <?php if ($isHrOrManager): ?>
                <section class="detail-section decision-section">
                    <h3><i class="fa-solid fa-gavel"></i> Decision</h3>

                    <?php if (!$allSelectionCleared): ?>
                        <p class="file-note">
                            Every selection requirement must be cleared before this applicant
                            can be approved for offer.
                        </p>
                    <?php endif; ?>

                    <div class="decision-buttons">
                        <button type="button" class="btn-success btn-block" id="approveOfferBtn" data-application-id="<?= (int) $applicant['application_id'] ?>"<?= $allSelectionCleared ? '' : ' disabled' ?>>
                            <i class="fa-solid fa-file-signature"></i> Approve for Offer
                        </button>
                        <button type="button" class="btn-outline btn-block" id="requestVerificationBtn" data-application-id="<?= (int) $applicant['application_id'] ?>">
                            <i class="fa-solid fa-rotate-left"></i> Additional Verification
                        </button>
                        <button type="button" class="btn-danger btn-block" id="rejectBtn" data-application-id="<?= (int) $applicant['application_id'] ?>" data-applicant-id="<?= (int) $applicant['applicant_id'] ?>">
                            <i class="fa-solid fa-user-xmark"></i> Reject
                        </button>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Offer: job offer details and candidate response -->
        <?php if ($applicant['application_status'] === 'Offer'): ?>
            <?php
            $offerStatusClass = [
                'Draft'    => 'badge-gray',
                'Sent'     => 'badge-orange',
                'Accepted' => 'badge-green',
                'Declined' => 'badge-red'
            ];

            $offerIsExpired =
                $offer !== null
                && $offer['status'] === 'Sent'
                && !empty($offer['offer_expires_on'])
                && strtotime((string) $offer['offer_expires_on']) < strtotime('today');

            $preEmploymentRequirements = array_values(
                array_filter(
                    $requirements,
                    static fn (array $requirement): bool =>
                        $requirement['stage'] === 'Pre-employment'
                )
            );

            $preEmploymentOutstanding = array_filter(
                $preEmploymentRequirements,
                static fn (array $requirement): bool =>
                    $requirement['status'] !== 'Cleared'
            );

            $allPreEmploymentCleared =
                $preEmploymentRequirements !== []
                && $preEmploymentOutstanding === [];
            ?>
            <section class="detail-section">
                <div class="ai-actions">
                    <div class="section-heading-copy">
                        <h3><i class="fa-solid fa-file-signature"></i> Offer</h3>
                        <p>
                            Prepare and send the job offer, then record the candidate's
                            response. Pre-employment checks begin after acceptance and must
                            be cleared before the hire can be confirmed.
                        </p>
                    </div>
                    <?php if ($offer !== null): ?>
                        <span class="badge <?= $offerStatusClass[$offer['status']] ?? 'badge-gray' ?>">
                            Offer <?= htmlspecialchars((string) $offer['status']) ?>
                        </span>
                    <?php else: ?>
                        <span class="badge badge-gray">Offer not prepared</span>
                    <?php endif; ?>
                </div>

                <?php if ($offer === null): ?>
                    <p class="file-note">
                        No offer has been prepared yet.
                        <?php if ($isHrOrAdmin): ?>
                            Use <strong>Send Offer</strong> to prepare one.
                        <?php endif; ?>
                    </p>
                <?php else: ?>
                    <div class="offer-card">
                        <div class="info-grid">
                            <?php
                            infoField('Offered Position', (string) $offer['offered_position']);
                            infoField(
                                'Offered Salary',
                                'PHP ' . number_format((float) $offer['offered_salary'], 2)
                            );
                            infoField('Employment Type', (string) $offer['employment_type']);
                            infoField(
                                'Proposed Start Date',
                                date('F d, Y', strtotime((string) $offer['start_date']))
                            );
                            if (!empty($offer['offer_expires_on'])) {
                                infoField(
                                    'Offer Expires On',
                                    date('F d, Y', strtotime((string) $offer['offer_expires_on']))
                                    . ($offerIsExpired ? ' (expired)' : '')
                                );
                            }
                            infoField(
                                'Sent',
                                !empty($offer['sent_at'])
                                    ? date('F d, Y g:i A', strtotime((string) $offer['sent_at']))
                                    : 'Not sent yet'
                            );
                            if (!empty($offer['responded_at'])) {
                                infoField(
                                    'Responded',
                                    date('F d, Y g:i A', strtotime((string) $offer['responded_at']))
                                );
                            }
                            ?>
                        </div>

                        <?php if ($offerIsExpired): ?>
                            <p class="file-note offer-expired-note">
                                This offer has expired. Revise and resend it with a new expiry
                                date if it is still open.
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($offer['terms'])): ?>
                            <div class="remarks-box">
                                <span class="info-label">Terms</span>
                                <p class="remarks-text"><?= nl2br(htmlspecialchars((string) $offer['terms'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($offer['response_note']) && $offer['status'] === 'Declined'): ?>
                            <div class="remarks-box">
                                <span class="info-label">Candidate Response Note</span>
                                <p class="remarks-text"><?= nl2br(htmlspecialchars((string) $offer['response_note'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>

            <?php if ($offer !== null && $offer['status'] === 'Accepted'): ?>
                <!-- Pre-employment requirements -->
                <section class="detail-section">
                    <div class="ai-actions">
                        <div class="section-heading-copy">
                            <h3><i class="fa-solid fa-clipboard-list"></i> Pre-employment Requirements</h3>
                            <p>
                                Clear the outstanding pre-employment requirements before confirming the hire.
                            </p>
                        </div>
                    </div>
                    <?php requirementsTable($preEmploymentRequirements, $isHrOrAdmin); ?>
                </section>
            <?php else: ?>
                <section class="detail-section">
                    <h3><i class="fa-solid fa-clipboard-list"></i> Pre-employment Requirements</h3>
                    <p class="file-note">Pre-employment checks begin after the candidate accepts the offer.</p>
                </section>
            <?php endif; ?>

            <?php if ($isHrOrAdmin): ?>
                <section class="detail-section decision-section">
                    <h3><i class="fa-solid fa-gavel"></i> Decision</h3>

                    <?php if (!$allPreEmploymentCleared): ?>
                        <p class="file-note">
                            Every pre-employment requirement must be cleared before the hire
                            can be confirmed.
                        </p>
                    <?php endif; ?>

                    <div class="decision-buttons">
                        <?php if ($offer === null || $offer['status'] === 'Draft'): ?>
                            <button type="button" class="btn-success btn-block" id="sendOfferBtn" data-application-id="<?= (int) $applicant['application_id'] ?>">
                                <i class="fa-solid fa-paper-plane"></i> Send Offer
                            </button>
                        <?php endif; ?>

                        <?php if ($offer !== null && $offer['status'] === 'Sent'): ?>
                            <button type="button" class="btn-primary btn-block" id="reviseOfferBtn" data-application-id="<?= (int) $applicant['application_id'] ?>">
                                <i class="fa-solid fa-pen"></i> Revise &amp; Resend Offer
                            </button>
                            <button type="button" class="btn-success btn-block" id="recordAcceptBtn" data-application-id="<?= (int) $applicant['application_id'] ?>">
                                <i class="fa-solid fa-check"></i> Record Acceptance
                            </button>
                            <button type="button" class="btn-outline btn-block" id="recordDeclineBtn" data-application-id="<?= (int) $applicant['application_id'] ?>">
                                <i class="fa-solid fa-xmark"></i> Record Decline
                            </button>
                        <?php endif; ?>

                        <?php if ($offer !== null && $offer['status'] === 'Accepted'): ?>
                            <button type="button" class="btn-success btn-block" id="hireBtn" data-application-id="<?= (int) $applicant['application_id'] ?>" data-applicant-id="<?= (int) $applicant['applicant_id'] ?>"<?= $allPreEmploymentCleared ? '' : ' disabled' ?>>
                                <i class="fa-solid fa-user-check"></i> Confirm Hire
                            </button>
                        <?php endif; ?>

                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Decision -->
        <?php if ($isScreeningStage && $isHrOrAdmin): ?>
            <section class="detail-section decision-section">
                <h3><i class="fa-solid fa-gavel"></i> Decision</h3>
                <div class="decision-buttons">
                    <button type="button" class="btn-success btn-block" id="qualifyBtn" data-application-id="<?= (int) $applicant['application_id'] ?>" data-applicant-id="<?= (int) $applicant['applicant_id'] ?>">
                        <i class="fa-solid fa-user-check"></i> Qualify
                    </button>
                    <button type="button" class="btn-danger btn-block" id="rejectBtn" data-application-id="<?= (int) $applicant['application_id'] ?>" data-applicant-id="<?= (int) $applicant['applicant_id'] ?>">
                        <i class="fa-solid fa-user-xmark"></i> Reject
                    </button>
                </div>
            </section>
        <?php elseif ($applicant['application_status'] === 'Interview'): ?>
            <section class="detail-section decision-section">
                <h3><i class="fa-solid fa-gavel"></i> Decision</h3>

                <?php if ($isHrOrManager && $evaluations === []): ?>
                    <p class="file-note">
                        Waiting for the interviewer's evaluation before this applicant can advance.
                    </p>
                <?php endif; ?>

                <div class="decision-buttons">
                    <?php if ($isHrOrManager): ?>
                        <button type="button" class="btn-success btn-block" id="advanceBtn" data-application-id="<?= (int) $applicant['application_id'] ?>"<?= $evaluations === [] ? ' disabled' : '' ?>>
                            <i class="fa-solid fa-forward"></i> Advance to Selection
                        </button>
                        <button type="button" class="btn-danger btn-block" id="rejectBtn" data-application-id="<?= (int) $applicant['application_id'] ?>" data-applicant-id="<?= (int) $applicant['applicant_id'] ?>">
                            <i class="fa-solid fa-user-xmark"></i> Reject
                        </button>
                    <?php endif; ?>
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
                    <?php if ($offer !== null && !empty($offer['start_date'])): ?>
                        <li><i class="fa-solid fa-check"></i> Set the employee start date to <strong><?= date('F d, Y', strtotime((string) $offer['start_date'])) ?></strong> (employment begins then)</li>
                    <?php endif; ?>
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

    <!-- Qualify Confirmation Modal -->
    <div class="modal-overlay" id="qualifyModal">
        <div class="modal-box modal-small">
            <div class="modal-header">
                <h3><i class="fa-solid fa-user-check"></i> Qualify Applicant</h3>
                <button type="button" class="close-btn" data-close="qualifyModal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <p class="confirm-text">
                    You are about to qualify <strong><?= htmlspecialchars($applicant['fullname']) ?></strong> for the next stage. This will:
                </p>
                <ul class="confirm-list">
                    <li><i class="fa-solid fa-check"></i> Change application status to <strong>Interview</strong></li>
                    <li><i class="fa-solid fa-check"></i> Unlock interview scheduling for this applicant</li>
                </ul>
                <div class="modal-actions">
                    <button type="button" class="btn-outline" data-close="qualifyModal">Cancel</button>
                    <button type="button" class="btn-success" id="confirmQualifyBtn">Confirm Qualification</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Interview Evaluation Modal -->
    <div class="modal-overlay" id="evaluationModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fa-solid fa-clipboard-list"></i> Interview Evaluation</h3>
                <button type="button" class="close-btn" data-close="evaluationModal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="evaluationForm" class="modal-body">
                <input type="hidden" name="application_id" value="<?= (int) $applicant['application_id'] ?>">

                <p class="confirm-text">
                    One fixed rating form is used for every applicant of a position.
                    Rate all four criteria from 1 to 5.
                </p>

                <div class="form-row">
                    <?php foreach (PipelineService::RATING_CRITERIA as $criterionKey => $criterionLabel): ?>
                        <label><?= htmlspecialchars($criterionLabel) ?>
                            <select name="<?= $criterionKey ?>" required>
                                <option value="" hidden>Select</option>
                                <?php for ($rating = PipelineService::RATING_MIN; $rating <= PipelineService::RATING_MAX; $rating++): ?>
                                    <option value="<?= $rating ?>"><?= $rating ?></option>
                                <?php endfor; ?>
                            </select>
                        </label>
                    <?php endforeach; ?>
                </div>

                <label>Strengths
                    <textarea name="strengths" rows="2" maxlength="2000"
                              placeholder="Key strengths observed during the interview"></textarea>
                </label>

                <label>Weaknesses
                    <textarea name="weaknesses" rows="2" maxlength="2000"
                              placeholder="Areas for improvement"></textarea>
                </label>

                <label>Remarks
                    <textarea name="remarks" rows="2" maxlength="2000"
                              placeholder="Additional remarks for HR"></textarea>
                </label>

                <label>Recommendation
                    <select name="recommendation" required>
                        <option value="" hidden>Select</option>
                        <?php foreach (PipelineService::RECOMMENDATIONS as $recommendationOption): ?>
                            <option value="<?= htmlspecialchars($recommendationOption) ?>">
                                <?= htmlspecialchars($recommendationOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div class="modal-actions">
                    <button type="button" class="btn-outline" data-close="evaluationModal">Cancel</button>
                    <button type="submit" class="btn-primary">Submit Evaluation</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Advance to Selection Confirmation Modal -->
    <div class="modal-overlay" id="advanceModal">
        <div class="modal-box modal-small">
            <div class="modal-header">
                <h3><i class="fa-solid fa-forward"></i> Advance to Selection</h3>
                <button type="button" class="close-btn" data-close="advanceModal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <p class="confirm-text">
                    You are about to move <strong><?= htmlspecialchars($applicant['fullname']) ?></strong>
                    to the selection stage. Every evaluation on file is listed below.
                </p>

                <?php if ($evaluations !== []): ?>
                    <ul class="confirm-list">
                        <?php foreach ($evaluations as $evaluationIndex => $evaluation): ?>
                            <li>
                                <i class="fa-solid fa-clipboard-check"></i>
                                <?= htmlspecialchars(
                                    trim((string) ($evaluation['evaluator_name'] ?? '')) !== ''
                                        ? (string) $evaluation['evaluator_name']
                                        : 'System account'
                                ) ?>
                                &mdash; <strong><?= htmlspecialchars($evaluation['recommendation']) ?></strong>
                                <?= $evaluationIndex === 0 ? '(current)' : '' ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <p class="file-note">
                    Moving to Selection seeds the credential, reference, and background
                    checks for this applicant.
                </p>

                <div class="modal-actions">
                    <button type="button" class="btn-outline" data-close="advanceModal">Cancel</button>
                    <button type="button" class="btn-success" id="confirmAdvanceBtn" data-application-id="<?= (int) $applicant['application_id'] ?>">Confirm Advance</button>
                </div>
            </div>
        </div>
    </div>

    <?php /* The requirement modal serves both the Selection and Offer stage checklists. */ ?>
    <?php if (in_array($applicant['application_status'], ['Selection', 'Offer'], true)): ?>
        <!-- Mark Requirement Modal -->
        <div class="modal-overlay" id="requirementModal">
            <div class="modal-box modal-small">
                <div class="modal-header">
                    <h3><i class="fa-solid fa-clipboard-check"></i> <span id="requirementModalTitle">Mark Requirement</span></h3>
                    <button type="button" class="close-btn" data-close="requirementModal"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form id="requirementForm" class="modal-body">
                    <input type="hidden" name="application_id" value="<?= (int) $applicant['application_id'] ?>">
                    <input type="hidden" name="requirement_id" id="requirementIdField" value="">

                    <label>Requirement
                        <input type="text" id="requirementTypeField" value="" disabled>
                    </label>

                    <label>Status
                        <select name="status" id="requirementStatusField" required>
                            <option value="Cleared">Cleared</option>
                            <option value="Failed">Failed</option>
                        </select>
                    </label>

                    <label>Note (optional)
                        <textarea name="note" id="requirementNoteField" rows="2" maxlength="500"
                                  placeholder="Internal verification note for HR records"></textarea>
                    </label>

                    <div class="modal-actions">
                        <button type="button" class="btn-outline" data-close="requirementModal">Cancel</button>
                        <button type="submit" class="btn-primary">Save Requirement</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($applicant['application_status'] === 'Selection'): ?>
        <!-- Approve for Offer Confirmation Modal -->
        <div class="modal-overlay" id="approveModal">
            <div class="modal-box modal-small">
                <div class="modal-header">
                    <h3><i class="fa-solid fa-file-signature"></i> Approve for Offer</h3>
                    <button type="button" class="close-btn" data-close="approveModal"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <p class="confirm-text">
                        You are about to approve <strong><?= htmlspecialchars($applicant['fullname']) ?></strong>
                        for a job offer. This will:
                    </p>
                    <ul class="confirm-list">
                        <li><i class="fa-solid fa-check"></i> Move the application to the <strong>Offer</strong> stage</li>
                        <li><i class="fa-solid fa-check"></i> Start pre-employment checks after the candidate accepts</li>
                    </ul>
                    <div class="modal-actions">
                        <button type="button" class="btn-outline" data-close="approveModal">Cancel</button>
                        <button type="button" class="btn-success" id="confirmApproveBtn" data-application-id="<?= (int) $applicant['application_id'] ?>">Confirm Approval</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Request Additional Verification Confirmation Modal -->
        <div class="modal-overlay" id="verifyModal">
            <div class="modal-box modal-small">
                <div class="modal-header">
                    <h3><i class="fa-solid fa-rotate-left"></i> Request Additional Verification</h3>
                    <button type="button" class="close-btn" data-close="verifyModal"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <p class="confirm-text">
                        Select only the completed checks that need to be repeated for
                        <strong><?= htmlspecialchars($applicant['fullname']) ?></strong>.
                    </p>
                    <?php if ($repeatableSelectionRequirements === []): ?>
                        <p class="file-note">There are no completed selection checks to request again.</p>
                    <?php else: ?>
                        <div class="verification-options">
                            <?php foreach ($repeatableSelectionRequirements as $requirement): ?>
                                <label>
                                    <input type="checkbox" name="requirement_ids[]" value="<?= (int) $requirement['requirement_id'] ?>">
                                    <?= htmlspecialchars((string) $requirement['requirement_type']) ?>
                                    (<?= htmlspecialchars((string) $requirement['status']) ?>)
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="modal-actions">
                        <button type="button" class="btn-outline" data-close="verifyModal">Cancel</button>
                        <button type="button" class="btn-primary" id="confirmVerifyBtn" data-application-id="<?= (int) $applicant['application_id'] ?>"<?= $repeatableSelectionRequirements === [] ? ' disabled' : '' ?>>Request Verification</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($applicant['application_status'] === 'Offer'): ?>
        <!-- Offer Modal: send or revise & resend -->
        <div class="modal-overlay" id="offerModal">
            <div class="modal-box">
                <div class="modal-header">
                    <h3><i class="fa-solid fa-file-signature"></i> <span id="offerModalTitle">Send Offer</span></h3>
                    <button type="button" class="close-btn" data-close="offerModal"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form id="offerForm" class="modal-body">
                    <input type="hidden" name="application_id" value="<?= (int) $applicant['application_id'] ?>">

                    <div class="form-row">
                        <label>Offered Position
                            <input type="text" name="offered_position" id="offerPositionField"
                                   maxlength="150" required
                                   value="<?= htmlspecialchars((string) ($offer['offered_position'] ?? $applicant['position'])) ?>">
                        </label>
                        <label>Offered Salary (PHP)
                            <input type="number" name="offered_salary" id="offerSalaryField"
                                   min="0" step="0.01" required
                                   value="<?= $offer !== null ? htmlspecialchars((string) $offer['offered_salary']) : '' ?>">
                        </label>
                    </div>

                    <div class="form-row">
                        <label>Employment Type
                            <select name="employment_type" id="offerEmploymentTypeField" required>
                                <?php
                                $employmentTypes = ['Full-Time', 'Part-Time', 'Contract', 'Internship'];
                                $selectedType = $offer['employment_type'] ?? 'Full-Time';
                                ?>
                                <?php foreach ($employmentTypes as $employmentType): ?>
                                    <option value="<?= $employmentType ?>"<?= $selectedType === $employmentType ? ' selected' : '' ?>>
                                        <?= $employmentType ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Proposed Start Date
                            <input type="date" name="start_date" id="offerStartDateField" required
                                   value="<?= $offer !== null ? htmlspecialchars((string) $offer['start_date']) : '' ?>">
                        </label>
                    </div>

                    <label>Offer Expires On (optional)
                        <input type="date" name="offer_expires_on" id="offerExpiryField"
                               value="<?= htmlspecialchars((string) ($offer['offer_expires_on'] ?? '')) ?>">
                    </label>

                    <label>Terms
                        <textarea name="terms" id="offerTermsField" rows="4" maxlength="5000"
                                  placeholder="Offer terms shared with the candidate"><?= htmlspecialchars((string) ($offer['terms'] ?? '')) ?></textarea>
                    </label>

                    <div class="modal-actions">
                        <button type="button" class="btn-outline" data-close="offerModal">Cancel</button>
                        <button type="submit" class="btn-primary" id="offerSubmitBtn">Send Offer</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Record Acceptance Confirmation Modal -->
        <div class="modal-overlay" id="acceptModal">
            <div class="modal-box modal-small">
                <div class="modal-header">
                    <h3><i class="fa-solid fa-check"></i> Record Acceptance</h3>
                    <button type="button" class="close-btn" data-close="acceptModal"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <p class="confirm-text">
                        You are about to record that <strong><?= htmlspecialchars($applicant['fullname']) ?></strong>
                        has accepted the offer. The hire can be confirmed once every
                        pre-employment requirement is cleared.
                    </p>
                    <div class="modal-actions">
                        <button type="button" class="btn-outline" data-close="acceptModal">Cancel</button>
                        <button type="button" class="btn-success" id="confirmAcceptBtn" data-application-id="<?= (int) $applicant['application_id'] ?>">Confirm Acceptance</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Record Decline Confirmation Modal -->
        <div class="modal-overlay" id="declineModal">
            <div class="modal-box modal-small">
                <div class="modal-header">
                    <h3><i class="fa-solid fa-xmark"></i> Record Decline</h3>
                    <button type="button" class="close-btn" data-close="declineModal"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <p class="confirm-text">
                        You are about to record that <strong><?= htmlspecialchars($applicant['fullname']) ?></strong>
                        declined the offer. This will:
                    </p>
                    <ul class="confirm-list">
                        <li><i class="fa-solid fa-check"></i> Mark the offer as <strong>Declined</strong></li>
                        <li><i class="fa-solid fa-check"></i> Reject the application with the reason <strong>Offer Declined</strong></li>
                        <li><i class="fa-solid fa-check"></i> Send <strong>no email</strong> - the candidate ended the process</li>
                    </ul>
                    <label>Counter-request or note (optional)
                        <textarea name="note" id="declineNoteField" rows="2" maxlength="1000"
                                  placeholder="Any counter-request the candidate mentioned"></textarea>
                    </label>
                    <div class="modal-actions">
                        <button type="button" class="btn-outline" data-close="declineModal">Cancel</button>
                        <button type="button" class="btn-danger" id="confirmDeclineBtn" data-application-id="<?= (int) $applicant['application_id'] ?>">Confirm Decline</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

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
                <label>Reason (optional)
                    <select name="reason_category" id="rejectReason">
                        <option value="">Select a reason (optional)</option>
                        <?php foreach ($rejectionReasons as $reason): ?>
                            <option value="<?= htmlspecialchars($reason) ?>"><?= htmlspecialchars($reason) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Note (optional, HR-internal)
                    <textarea name="reason_note" id="rejectNote" rows="2" maxlength="500"
                              placeholder="Internal note kept out of any candidate email"></textarea>
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