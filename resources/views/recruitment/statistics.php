<?php

$pageTitle =
    "Job Statistics";

$pageCSS =
    "statistics.css";

$pageJS =
    "statistics.js";

$pageDescription =
    "View recruitment statistics for this job posting.";


if (!isset($_SESSION['user_id'])) {

    header(
        "Location: " . url("?page=login")
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Data supplied by the controller
|--------------------------------------------------------------------------
| $candidates = every applicant of this job (not only the latest few), one
| row each, with AI screening, interview and document data joined in.
|
| Existing keys:
|   applicant_id, fullname, email, applied_at, application_status,
|   ai_score, recommendation
|
| New, optional keys (a missing key simply hides that detail):
|   requirement_match, skills_score, experience_score, education_score
|   strengths, concerns          array, JSON string or one item per line
|   interview_date, interview_result
|   missing_documents            array, JSON string or one item per line
|
| If $candidates is not set, $recentApplicants is used so the page still
| renders before the controller is updated.
|--------------------------------------------------------------------------
*/

$applicationTrend = $applicationTrend ?? [];
$job = $job ?? [];
$summary = $summary ?? [];
$interviewCount = $interviewCount ?? 0;
$totalApplications = $totalApplications ?? 0;
$hired = $hired ?? 0;
$remainingVacancies = $remainingVacancies ?? 0;
$hiringRate = $hiringRate ?? 0;
$recentApplicants = $recentApplicants ?? [];
$candidates = $candidates ?? $recentApplicants;
$vacancies = $vacancies ?? 0;


/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

if (!function_exists('statistics_e')) {

    function statistics_e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('statistics_percent')) {

    function statistics_percent($value): ?float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        return max(0, min(100, (float) $value));
    }
}

if (!function_exists('statistics_list')) {

    function statistics_list($value): array
    {
        if (is_string($value) && trim($value) !== '') {

            $decoded = json_decode($value, true);

            $value = is_array($decoded)
                ? $decoded
                : preg_split('/\r\n|\r|\n/', $value);
        }

        if (!is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {

            if (!is_scalar($item)) {
                continue;
            }

            $item = trim((string) $item);

            if ($item !== '') {
                $items[] = $item;
            }
        }

        return $items;
    }
}

if (!function_exists('statistics_recommendation_class')) {

    function statistics_recommendation_class(?string $recommendation): string
    {
        return match ($recommendation) {
            'Highly Recommended' => 'rec-high',
            'Recommended' => 'rec-mid',
            'Consider' => 'rec-review',
            'Not Recommended' => 'rec-low',
            default => 'rec-none',
        };
    }
}

if (!function_exists('statistics_status_class')) {

    function statistics_status_class(?string $status): string
    {
        return 'status-' . strtolower(str_replace(' ', '-', (string) $status));
    }
}

/*
 * Maps the stored interview result to passed / failed / pending / none.
 * Adjust the keywords here if your result values are worded differently.
 */
if (!function_exists('statistics_interview_bucket')) {

    function statistics_interview_bucket(array $row): string
    {
        $result = strtolower(trim((string) ($row['interview_result'] ?? '')));

        if ($result !== '') {

            if (
                str_contains($result, 'not recommend')
                || str_contains($result, 'fail')
                || str_contains($result, 'reject')
            ) {
                return 'failed';
            }

            if (
                str_contains($result, 'pass')
                || str_contains($result, 'recommend')
                || str_contains($result, 'hire')
            ) {
                return 'passed';
            }

            return 'pending';
        }

        return !empty($row['interview_date']) ? 'pending' : 'none';
    }
}

if (!function_exists('statistics_date')) {

    function statistics_date($value, string $format = 'M d, Y'): string
    {
        $time = $value ? strtotime((string) $value) : false;

        return $time !== false ? date($format, $time) : '—';
    }
}


/*
|--------------------------------------------------------------------------
| Job information + trend
|--------------------------------------------------------------------------
*/

$trendLabels = [];

$trendValues = [];

foreach ($applicationTrend as $date => $count) {

    $trendLabels[] =
        date('M d', strtotime($date));

    $trendValues[] =
        (int) $count;
}


$deadline = !empty($job['application_deadline'])
    ? date(
        'M d, Y',
        strtotime($job['application_deadline'])
    )
    : 'No deadline';


$createdAt = !empty($job['created_at'])
    ? date(
        'M d, Y',
        strtotime($job['created_at'])
    )
    : '—';


$vacancyPercentage = 0;

if ($vacancies > 0) {

    $vacancyPercentage = min(
        100,
        round(
            ($hired / $vacancies) * 100,
            1
        )
    );
}


$funnelStages = [
    [
        'label' => 'Submitted',
        'count' => (int) ($summary['submitted'] ?? 0),
        'class' => 'submitted',
    ],
    [
        'label' => 'Under Review',
        'count' => (int) ($summary['under_review'] ?? 0),
        'class' => 'under-review',
    ],
    [
        'label' => 'Interview',
        'count' => (int) ($summary['interview'] ?? 0),
        'class' => 'interview',
    ],
    [
        'label' => 'Hired',
        'count' => (int) ($summary['hired'] ?? $hired),
        'class' => 'hired',
    ],
];


/*
|--------------------------------------------------------------------------
| Normalise candidates
|--------------------------------------------------------------------------
*/

$candidateRows = [];

foreach ($candidates as $row) {

    $status = (string) ($row['application_status'] ?? '');
    $recommendation = (string) ($row['recommendation'] ?? '');

    $row['_status'] = $status;
    $row['_recommendation'] = $recommendation;
    $row['_ai'] = statistics_percent($row['ai_score'] ?? null);
    $row['_match'] = statistics_percent($row['requirement_match'] ?? null);
    $row['_skills'] = statistics_percent($row['skills_score'] ?? null);
    $row['_experience'] = statistics_percent($row['experience_score'] ?? null);
    $row['_education'] = statistics_percent($row['education_score'] ?? null);
    $row['_strengths'] = statistics_list($row['strengths'] ?? []);
    $row['_concerns'] = statistics_list($row['concerns'] ?? []);
    $row['_missing'] = statistics_list($row['missing_documents'] ?? []);
    $row['_bucket'] = statistics_interview_bucket($row);
    $row['_active'] = !in_array($status, ['Hired', 'Rejected'], true);
    $row['_qualified'] = in_array(
        $recommendation,
        ['Highly Recommended', 'Recommended'],
        true
    );

    $candidateRows[] = $row;
}

$candidateTotal = count($candidateRows);

$interviewLabels = [
    'passed' => 'Passed',
    'failed' => 'Failed',
    'pending' => 'Pending',
    'none' => 'Not interviewed',
];


/*
|--------------------------------------------------------------------------
| Ranking (rejected candidates are left out)
|--------------------------------------------------------------------------
*/

$ranked = array_values(array_filter(
    $candidateRows,
    fn ($c) => $c['_status'] !== 'Rejected'
));

usort($ranked, function ($a, $b) {

    return [$b['_ai'] ?? -1, $b['_match'] ?? -1]
        <=> [$a['_ai'] ?? -1, $a['_match'] ?? -1];
});

$topCandidates = array_slice($ranked, 0, 10);

$aiDetailCandidates = array_slice(
    array_values(array_filter($ranked, fn ($c) => $c['_ai'] !== null)),
    0,
    5
);


/*
|--------------------------------------------------------------------------
| AI screening summary
|--------------------------------------------------------------------------
*/

$aiScreened = 0;
$aiScoreSum = 0.0;

$aiRecommendations = [
    'Highly Recommended' => 0,
    'Recommended' => 0,
    'Consider' => 0,
    'Not Recommended' => 0,
];

foreach ($candidateRows as $c) {

    if ($c['_ai'] === null) {
        continue;
    }

    $aiScreened++;
    $aiScoreSum += $c['_ai'];

    if (array_key_exists($c['_recommendation'], $aiRecommendations)) {
        $aiRecommendations[$c['_recommendation']]++;
    }
}

$aiAverage = $aiScreened > 0
    ? round($aiScoreSum / $aiScreened, 1)
    : 0;

$recommendedCount = count(array_filter(
    $candidateRows,
    fn ($c) => $c['_qualified'] && $c['_status'] !== 'Rejected'
));


/*
|--------------------------------------------------------------------------
| Interview summary
|--------------------------------------------------------------------------
*/

$interviewBuckets = [
    'passed' => 0,
    'failed' => 0,
    'pending' => 0,
    'none' => 0,
];

foreach ($candidateRows as $c) {
    $interviewBuckets[$c['_bucket']]++;
}

$interviewsWithResult = $interviewBuckets['passed'] + $interviewBuckets['failed'];

$interviewed = array_values(array_filter(
    $candidateRows,
    fn ($c) => $c['_bucket'] !== 'none'
));

usort($interviewed, function ($a, $b) {
    return strcmp(
        (string) ($b['interview_date'] ?? ''),
        (string) ($a['interview_date'] ?? '')
    );
});

$interviewList = array_slice($interviewed, 0, 6);


/*
|--------------------------------------------------------------------------
| Hiring readiness
|--------------------------------------------------------------------------
*/

$shortlist = array_values(array_filter(
    $candidateRows,
    fn ($c) => $c['_active'] && $c['_qualified']
));

$shortlistCount = count($shortlist);

$shortlistInterviewed = count(array_filter(
    $shortlist,
    fn ($c) => in_array($c['_bucket'], ['passed', 'failed'], true)
));

$shortlistMissingDocs = count(array_filter(
    $shortlist,
    fn ($c) => !empty($c['_missing'])
));

$unscreenedActive = count(array_filter(
    $candidateRows,
    fn ($c) => $c['_active'] && $c['_ai'] === null
));

$readyForDecision = count(array_filter(
    $shortlist,
    fn ($c) => $c['_bucket'] === 'passed' && empty($c['_missing'])
));

$readinessChecks = [

    [
        'label' => 'Qualified candidates',
        'ok' => $remainingVacancies > 0 && $shortlistCount >= $remainingVacancies,
        'detail' => $remainingVacancies === 0
            ? 'No open vacancies remaining.'
            : $shortlistCount . ' shortlisted for '
                . $remainingVacancies . ' open '
                . ($remainingVacancies === 1 ? 'vacancy.' : 'vacancies.'),
    ],

    [
        'label' => 'AI screening',
        'ok' => $candidateTotal > 0 && $unscreenedActive === 0,
        'detail' => $candidateTotal === 0
            ? 'No applicants yet.'
            : ($unscreenedActive === 0
                ? 'All active applicants are screened.'
                : $unscreenedActive . ' active '
                    . ($unscreenedActive === 1 ? 'applicant' : 'applicants')
                    . ' not screened.'),
    ],

    [
        'label' => 'Interviews',
        'ok' => $shortlistCount > 0 && $shortlistInterviewed === $shortlistCount,
        'detail' => $shortlistCount === 0
            ? 'No shortlisted candidates yet.'
            : $shortlistInterviewed . ' of ' . $shortlistCount
                . ' shortlisted candidates have a result.',
    ],

    [
        'label' => 'Documents',
        'ok' => $shortlistCount > 0 && $shortlistMissingDocs === 0,
        'detail' => $shortlistCount === 0
            ? 'No shortlisted candidates yet.'
            : ($shortlistMissingDocs === 0
                ? 'Shortlisted candidates are complete.'
                : $shortlistMissingDocs . ' shortlisted '
                    . ($shortlistMissingDocs === 1 ? 'candidate has' : 'candidates have')
                    . ' missing documents.'),
    ],

    [
        'label' => 'Ready for decision',
        'ok' => $readyForDecision > 0,
        'detail' => $readyForDecision > 0
            ? $readyForDecision . ' passed interview with complete documents.'
            : 'No candidate is ready for a decision yet.',
    ],
];

$readinessPassed = count(array_filter($readinessChecks, fn ($c) => $c['ok']));
$readinessTotal = count($readinessChecks);

if ($vacancies > 0 && $remainingVacancies === 0) {

    $readinessState = 'complete';
    $readinessLabel = 'All vacancies filled';

} elseif ($candidateTotal === 0) {

    $readinessState = 'empty';
    $readinessLabel = 'No applicants yet';

} elseif ($readinessPassed === $readinessTotal) {

    $readinessState = 'ready';
    $readinessLabel = 'Ready for decision';

} else {

    $readinessState = 'attention';
    $readinessLabel = $readinessPassed . ' of ' . $readinessTotal . ' checks passed';
}


/*
|--------------------------------------------------------------------------
| Needs attention
|--------------------------------------------------------------------------
*/

$attentionItems = [];

$addAttention = function (
    int $priority,
    string $tone,
    string $icon,
    string $title,
    string $detail,
    ?string $link = null
) use (&$attentionItems) {

    $attentionItems[] = compact(
        'priority',
        'tone',
        'icon',
        'title',
        'detail',
        'link'
    );
};

if (!empty($job['application_deadline']) && $remainingVacancies > 0) {

    $deadlineTime = strtotime($job['application_deadline']);

    if ($deadlineTime !== false) {

        $daysLeft = (int) floor(($deadlineTime - strtotime('today')) / 86400);

        if ($daysLeft < 0) {

            $addAttention(
                2,
                'warn',
                'fa-calendar-xmark',
                'Application deadline has passed',
                $remainingVacancies . ' '
                    . ($remainingVacancies === 1 ? 'vacancy is' : 'vacancies are')
                    . ' still open.'
            );

        } elseif ($daysLeft <= 7) {

            $addAttention(
                2,
                'info',
                'fa-hourglass-half',
                'Deadline in ' . $daysLeft . ($daysLeft === 1 ? ' day' : ' days'),
                $remainingVacancies . ' '
                    . ($remainingVacancies === 1 ? 'vacancy' : 'vacancies')
                    . ' still open.'
            );
        }
    }
}

foreach ($candidateRows as $c) {

    if (!$c['_active']) {
        continue;
    }

    $name = (string) ($c['fullname'] ?? 'Applicant');
    $link = '?page=review&id=' . (int) ($c['applicant_id'] ?? 0);

    if ($c['_bucket'] === 'passed') {

        $addAttention(
            0,
            'action',
            'fa-user-check',
            'Awaiting hiring decision — ' . $name,
            'Interview passed. HR/Admin to make the final decision.',
            $link
        );
    }

    if (!empty($c['_missing'])) {

        $shown = array_slice($c['_missing'], 0, 3);
        $extra = count($c['_missing']) - count($shown);

        $addAttention(
            1,
            'warn',
            'fa-file-circle-exclamation',
            'Missing documents — ' . $name,
            implode(', ', $shown) . ($extra > 0 ? ' +' . $extra . ' more' : ''),
            $link
        );
    }

    if ($c['_bucket'] === 'pending') {

        $addAttention(
            2,
            'warn',
            'fa-clock',
            'Interview result pending — ' . $name,
            !empty($c['interview_date'])
                ? 'Interview on ' . statistics_date($c['interview_date']) . '.'
                : 'Result has not been recorded.',
            $link
        );
    }

    if ($c['_qualified'] && $c['_bucket'] === 'none') {

        $addAttention(
            2,
            'info',
            'fa-calendar-plus',
            'Interview not scheduled — ' . $name,
            'Recommended by AI, no interview yet.',
            $link
        );
    }

    if ($c['_ai'] === null) {

        $addAttention(
            3,
            'info',
            'fa-robot',
            'Not screened — ' . $name,
            'No AI screening result for this application.',
            $link
        );
    }
}

usort($attentionItems, fn ($a, $b) => $a['priority'] <=> $b['priority']);

$attentionTotal = count($attentionItems);
$attentionShown = array_slice($attentionItems, 0, 8);

?>

<?php require BASE_PATH . '/resources/views/includes/header.php'; ?>

<?php require BASE_PATH . '/resources/views/includes/sidebar.php'; ?>


<div class="main-content">

    <?php require BASE_PATH . '/resources/views/includes/navbar.php'; ?>


    <div class="statistics-page">

        <!-- ==========================================================
            JOB INFORMATION
        =========================================================== -->

        <section class="job-information-card">

            <div class="job-information-item">

                <span class="info-label">
                    Position
                </span>

                <strong>
                    <?= statistics_e($job['position_name'] ?? '—') ?>
                </strong>

            </div>


            <div class="job-information-item">

                <span class="info-label">
                    Department
                </span>

                <strong>
                    <?= statistics_e($job['department_name'] ?? '—') ?>
                </strong>

            </div>


            <div class="job-information-item">

                <span class="info-label">
                    Employment Type
                </span>

                <strong>
                    <?= statistics_e($job['employment_type'] ?? '—') ?>
                </strong>

            </div>


            <div class="job-information-item">

                <span class="info-label">
                    Vacancies
                </span>

                <strong>
                    <?= (int) $vacancies ?>
                </strong>

            </div>


            <div class="job-information-item">

                <span class="info-label">
                    Posted
                </span>

                <strong>
                    <?= statistics_e($createdAt) ?>
                </strong>

            </div>


            <div class="job-information-item">

                <span class="info-label">
                    Deadline
                </span>

                <strong>
                    <?= statistics_e($deadline) ?>
                </strong>

            </div>

        </section>



        <!-- ==========================================================
            SUMMARY CARDS
        =========================================================== -->

        <section class="statistics-grid">


            <div class="stat-card">

                <div class="stat-icon applications">
                    <i class="fa-solid fa-users"></i>
                </div>

                <div class="stat-content">

                    <span>
                        Total Applications
                    </span>

                    <strong>
                        <?= (int) $totalApplications ?>
                    </strong>

                    <small>
                        All submitted applications
                    </small>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon qualified">
                    <i class="fa-solid fa-ranking-star"></i>
                </div>

                <div class="stat-content">

                    <span>
                        AI Recommended
                    </span>

                    <strong>
                        <?= (int) $recommendedCount ?>
                    </strong>

                    <small>
                        Highly Recommended + Recommended
                    </small>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon interview">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>

                <div class="stat-content">

                    <span>
                        Interviews
                    </span>

                    <strong>
                        <?= (int) $interviewCount ?>
                    </strong>

                    <small>
                        <?= (int) $interviewsWithResult ?> with a result
                    </small>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon hired">
                    <i class="fa-solid fa-user-check"></i>
                </div>

                <div class="stat-content">

                    <span>
                        Hired
                    </span>

                    <strong>
                        <?= (int) $hired ?>
                    </strong>

                    <small>
                        <?= (int) $remainingVacancies ?>
                        <?= $remainingVacancies === 1 ? 'vacancy' : 'vacancies' ?>
                        remaining
                    </small>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon rate">
                    <i class="fa-solid fa-chart-line"></i>
                </div>

                <div class="stat-content">

                    <span>
                        Hiring Rate
                    </span>

                    <strong>
                        <?= statistics_e($hiringRate) ?>%
                    </strong>

                    <small>
                        Hired vs. total applications
                    </small>

                </div>

            </div>

        </section>



        <!-- ==========================================================
            RECRUITMENT FUNNEL + VACANCY PROGRESS
        =========================================================== -->

        <section class="progress-card funnel-card">

            <div class="progress-header">

                <div>

                    <h2>
                        Recruitment Funnel
                    </h2>

                    <p>
                        Current pipeline by application status.
                    </p>

                </div>

                <div class="funnel-rejected">
                    <span>Rejected</span>
                    <strong>
                        <?= (int) ($summary['rejected'] ?? 0) ?>
                    </strong>
                </div>

            </div>


            <div class="funnel-track">

                <?php foreach ($funnelStages as $stage): ?>

                    <?php

                    $stageShare = 0;

                    if ($totalApplications > 0) {

                        $stageShare = round(
                            ($stage['count'] / $totalApplications) * 100,
                            1
                        );
                    }

                    ?>

                    <div class="funnel-stage stage-<?= statistics_e($stage['class']) ?>">

                        <span class="funnel-label">
                            <?= statistics_e($stage['label']) ?>
                        </span>

                        <strong class="funnel-count">
                            <?= (int) $stage['count'] ?>
                        </strong>

                        <div class="funnel-bar">
                            <div
                                class="funnel-bar-fill"
                                style="width: <?= $stageShare ?>%;"
                            ></div>
                        </div>

                        <span class="funnel-share">
                            <?= $stageShare ?>% of applicants
                        </span>

                    </div>

                <?php endforeach; ?>

            </div>


            <div class="progress-header vacancy-header">

                <div>

                    <h2>
                        Vacancy Progress
                    </h2>

                    <p>
                        Hiring progress against the
                        available positions.
                    </p>

                </div>

                <strong>
                    <?= (int) $hired ?>
                    /
                    <?= (int) $vacancies ?>
                </strong>

            </div>


            <div class="progress-track">

                <div
                    class="progress-fill"
                    style="width: <?= $vacancyPercentage ?>%;"
                ></div>

            </div>


            <div class="progress-footer">

                <span>
                    <?= (int) $hired ?>
                    hired
                </span>

                <span>
                    <?= (int) $remainingVacancies ?>
                    remaining
                </span>

            </div>

        </section>



        <!-- ==========================================================
            APPLICATION TREND + HIRING READINESS
        =========================================================== -->

        <section class="charts-grid">


            <div class="chart-card trend-card">

                <div class="chart-header">

                    <div>

                        <h2>
                            Application Trend
                        </h2>

                        <p>
                            Applications received over
                            the last 14 days.
                        </p>

                    </div>

                </div>


                <div class="chart-container">

                    <canvas
                        id="applicationTrendChart"
                    ></canvas>

                </div>

            </div>


            <div class="chart-card readiness-card">

                <div class="chart-header">

                    <div>

                        <h2>
                            Hiring Readiness
                        </h2>

                        <p>
                            Can HR/Admin decide yet?
                        </p>

                    </div>

                    <span class="readiness-badge readiness-<?= $readinessState ?>">
                        <?= statistics_e($readinessLabel) ?>
                    </span>

                </div>


                <ul class="readiness-list">

                    <?php foreach ($readinessChecks as $check): ?>

                        <li class="readiness-item <?= $check['ok'] ? 'is-ok' : 'is-warn' ?>">

                            <span class="readiness-icon">
                                <i class="fa-solid <?= $check['ok'] ? 'fa-check' : 'fa-exclamation' ?>"></i>
                            </span>

                            <div>
                                <strong><?= statistics_e($check['label']) ?></strong>
                                <small><?= statistics_e($check['detail']) ?></small>
                            </div>

                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        </section>



        <!-- ==========================================================
            NEEDS ATTENTION + INTERVIEW RESULTS
        =========================================================== -->

        <section class="insights-grid">


            <div class="chart-card attention-card">

                <div class="chart-header">

                    <div>

                        <h2>
                            Needs Attention
                        </h2>

                        <p>
                            Pending actions and missing items.
                        </p>

                    </div>

                    <?php if ($attentionTotal > 0): ?>
                        <span class="attention-count">
                            <?= $attentionTotal ?>
                        </span>
                    <?php endif; ?>

                </div>


                <?php if ($attentionTotal === 0): ?>

                    <div class="ai-empty">
                        <i class="fa-solid fa-circle-check"></i>
                        <strong>Nothing needs attention</strong>
                        <span>No pending actions for this posting.</span>
                    </div>

                <?php else: ?>

                    <ul class="attention-list">

                        <?php foreach ($attentionShown as $item): ?>

                            <li>

                                <?php if ($item['link']): ?>
                                    <a href="<?= statistics_e($item['link']) ?>" class="attention-item tone-<?= statistics_e($item['tone']) ?>">
                                <?php else: ?>
                                    <div class="attention-item tone-<?= statistics_e($item['tone']) ?>">
                                <?php endif; ?>

                                    <span class="attention-icon">
                                        <i class="fa-solid <?= statistics_e($item['icon']) ?>"></i>
                                    </span>

                                    <span class="attention-text">
                                        <strong><?= statistics_e($item['title']) ?></strong>
                                        <span><?= statistics_e($item['detail']) ?></span>
                                    </span>

                                    <?php if ($item['link']): ?>
                                        <i class="fa-solid fa-chevron-right"></i>
                                    <?php endif; ?>

                                <?php if ($item['link']): ?>
                                    </a>
                                <?php else: ?>
                                    </div>
                                <?php endif; ?>

                            </li>

                        <?php endforeach; ?>

                    </ul>

                    <?php if ($attentionTotal > count($attentionShown)): ?>
                        <p class="attention-more">
                            +<?= $attentionTotal - count($attentionShown) ?> more
                            <?= ($attentionTotal - count($attentionShown)) === 1 ? 'item' : 'items' ?>
                        </p>
                    <?php endif; ?>

                <?php endif; ?>

            </div>


            <div class="chart-card interview-card">

                <div class="chart-header">

                    <div>

                        <h2>
                            Interview Results
                        </h2>

                        <p>
                            Outcome of interviews for this posting.
                        </p>

                    </div>

                </div>


                <?php if (empty($interviewed)): ?>

                    <div class="ai-empty">
                        <i class="fa-solid fa-calendar-check"></i>
                        <strong>No interviews yet</strong>
                        <span>Results will appear once interviews are scheduled.</span>
                    </div>

                <?php else: ?>

                    <div class="ai-summary-row cols-3">

                        <div class="ai-metric">
                            <span>Passed</span>
                            <strong><?= (int) $interviewBuckets['passed'] ?></strong>
                        </div>

                        <div class="ai-metric">
                            <span>Pending</span>
                            <strong><?= (int) $interviewBuckets['pending'] ?></strong>
                        </div>

                        <div class="ai-metric">
                            <span>Failed</span>
                            <strong><?= (int) $interviewBuckets['failed'] ?></strong>
                        </div>

                    </div>


                    <ul class="interview-list">

                        <?php foreach ($interviewList as $c): ?>

                            <li>

                                <div class="interview-name">
                                    <strong><?= statistics_e($c['fullname'] ?? '') ?></strong>
                                    <small>
                                        <?= !empty($c['interview_date'])
                                            ? statistics_e(statistics_date($c['interview_date']))
                                            : 'Date not set' ?>
                                    </small>
                                </div>

                                <span class="interview-ai">
                                    <?= $c['_ai'] !== null
                                        ? 'AI ' . number_format($c['_ai'], 0) . '%'
                                        : '' ?>
                                </span>

                                <span class="result-badge result-<?= $c['_bucket'] ?>">
                                    <?= statistics_e($interviewLabels[$c['_bucket']]) ?>
                                </span>

                            </li>

                        <?php endforeach; ?>

                    </ul>

                <?php endif; ?>

            </div>

        </section>



        <!-- ==========================================================
            TOP CANDIDATES / RANKING
        =========================================================== -->

        <section class="applicants-card ranking-card">

            <div class="section-header">

                <div>

                    <h2>
                        Top Candidates
                    </h2>

                    <p>
                        Ranked by AI score, rejected applicants excluded.
                    </p>

                </div>


                <a
                    href="?page=applicants"
                    class="view-all-link"
                >
                    View All Applicants
                    <i class="fa-solid fa-arrow-right"></i>
                </a>

            </div>


            <div class="applicants-table-wrapper">

                <table class="applicants-table">

                    <thead>

                        <tr>
                            <th>#</th>
                            <th>Candidate</th>
                            <th>AI Score</th>
                            <th>Requirement Match</th>
                            <th>Interview</th>
                            <th>Recommendation</th>
                            <th>Status</th>
                            <th></th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php if (!empty($topCandidates)): ?>

                            <?php foreach ($topCandidates as $index => $c): ?>

                                <tr>

                                    <td>
                                        <?php if ($c['_ai'] !== null): ?>
                                            <span class="rank-number <?= $index < 3 ? 'top' : '' ?>">
                                                <?= $index + 1 ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="cell-muted">—</span>
                                        <?php endif; ?>
                                    </td>


                                    <td>

                                        <div class="applicant-name">

                                            <div class="avatar">
                                                <?= statistics_e(strtoupper(substr((string) ($c['fullname'] ?? '?'), 0, 1))) ?>
                                            </div>

                                            <div class="applicant-meta">

                                                <strong>
                                                    <?= statistics_e($c['fullname'] ?? '') ?>
                                                    <?php if (!empty($c['_missing'])): ?>
                                                        <i
                                                            class="fa-solid fa-triangle-exclamation flag-docs"
                                                            title="Missing documents"
                                                        ></i>
                                                    <?php endif; ?>
                                                </strong>

                                                <small>
                                                    <?= statistics_e($c['email'] ?? '') ?>
                                                </small>

                                            </div>

                                        </div>

                                    </td>


                                    <td>
                                        <?php if ($c['_ai'] !== null): ?>
                                            <div class="score-bar" style="--score: <?= (int) round($c['_ai']) ?>">
                                                <span class="score-bar-track"></span>
                                                <?= number_format($c['_ai'], 0) ?>%
                                            </div>
                                        <?php else: ?>
                                            <span class="cell-muted">Not screened</span>
                                        <?php endif; ?>
                                    </td>


                                    <td>
                                        <?php if ($c['_match'] !== null): ?>
                                            <div class="score-bar" style="--score: <?= (int) round($c['_match']) ?>">
                                                <span class="score-bar-track"></span>
                                                <?= number_format($c['_match'], 0) ?>%
                                            </div>
                                        <?php else: ?>
                                            <span class="cell-muted">—</span>
                                        <?php endif; ?>
                                    </td>


                                    <td>
                                        <span class="result-badge result-<?= $c['_bucket'] ?>">
                                            <?= statistics_e($interviewLabels[$c['_bucket']]) ?>
                                        </span>
                                    </td>


                                    <td>
                                        <?php if ($c['_recommendation'] !== ''): ?>
                                            <span class="rec-badge <?= statistics_recommendation_class($c['_recommendation']) ?>">
                                                <?= statistics_e($c['_recommendation']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="cell-muted">—</span>
                                        <?php endif; ?>
                                    </td>


                                    <td>
                                        <span class="application-status <?= statistics_e(statistics_status_class($c['_status'])) ?>">
                                            <?= statistics_e($c['_status']) ?>
                                        </span>
                                    </td>


                                    <td>
                                        <a
                                            href="?page=review&id=<?= (int) ($c['applicant_id'] ?? 0) ?>"
                                            class="applicant-action"
                                            title="Review applicant"
                                        >
                                            <i class="fa-solid fa-arrow-right"></i>
                                        </a>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="8"
                                    class="empty-state"
                                >

                                    <i class="fa-solid fa-users"></i>

                                    <strong>
                                        No candidates yet
                                    </strong>

                                    <span>
                                        Ranked candidates for this
                                        job will appear here.
                                    </span>

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>


            <p class="decision-note">
                <i class="fa-solid fa-circle-info"></i>
                <span>
                    AI scores and rankings are decision support only. The final
                    hiring decision is made by HR/Admin.
                </span>
            </p>

        </section>



        <!-- ==========================================================
            AI SCREENING EVALUATION
        =========================================================== -->

        <section class="chart-card ai-card">

            <div class="chart-header">

                <div>

                    <h2>
                        AI Screening Evaluation
                    </h2>

                    <p>
                        Why the top candidates were scored and recommended.
                    </p>

                </div>

            </div>


            <?php if ($aiScreened === 0): ?>

                <div class="ai-empty">
                    <i class="fa-solid fa-robot"></i>
                    <strong>No screening results yet</strong>
                    <span>AI results will appear as applications are screened.</span>
                </div>

            <?php else: ?>

                <div class="ai-summary-row cols-3">

                    <div class="ai-metric">
                        <span>Screened</span>
                        <strong>
                            <?= $aiScreened ?>
                            /
                            <?= $candidateTotal ?>
                        </strong>
                    </div>

                    <div class="ai-metric">
                        <span>Average score</span>
                        <strong>
                            <?= $aiAverage ?>%
                        </strong>
                    </div>

                    <div class="ai-metric">
                        <span>Top score</span>
                        <strong>
                            <?= number_format($aiDetailCandidates[0]['_ai'] ?? 0, 0) ?>%
                        </strong>
                    </div>

                </div>


                <div class="ai-rec-mix">

                    <?php foreach ($aiRecommendations as $label => $count): ?>

                        <div class="ai-rec-item">
                            <span class="rec-badge <?= statistics_recommendation_class($label) ?>">
                                <?= statistics_e($label) ?>
                            </span>
                            <strong><?= (int) $count ?></strong>
                        </div>

                    <?php endforeach; ?>

                </div>


                <p class="decision-note is-inline">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>
                        This evaluation is advisory. Review each application
                        before deciding.
                    </span>
                </p>


                <div class="ai-eval-list">

                    <?php foreach ($aiDetailCandidates as $index => $c): ?>

                        <?php

                        $metrics = [
                            'Skills' => $c['_skills'],
                            'Experience' => $c['_experience'],
                            'Education' => $c['_education'],
                            'Requirement match' => $c['_match'],
                        ];

                        $metrics = array_filter(
                            $metrics,
                            fn ($value) => $value !== null
                        );

                        $hasDetails = !empty($metrics)
                            || !empty($c['_strengths'])
                            || !empty($c['_concerns']);

                        ?>

                        <details class="ai-eval" <?= $index === 0 ? 'open' : '' ?>>

                            <summary>

                                <span class="rank-number <?= $index < 3 ? 'top' : '' ?>">
                                    <?= $index + 1 ?>
                                </span>

                                <span class="ai-applicant-name">
                                    <?= statistics_e($c['fullname'] ?? '') ?>
                                </span>

                                <span class="score-bar" style="--score: <?= (int) round($c['_ai']) ?>">
                                    <span class="score-bar-track"></span>
                                    <?= number_format($c['_ai'], 0) ?>%
                                </span>

                                <?php if ($c['_recommendation'] !== ''): ?>
                                    <span class="rec-badge <?= statistics_recommendation_class($c['_recommendation']) ?>">
                                        <?= statistics_e($c['_recommendation']) ?>
                                    </span>
                                <?php else: ?>
                                    <span></span>
                                <?php endif; ?>

                                <i class="fa-solid fa-chevron-down ai-eval-chevron"></i>

                            </summary>


                            <div class="ai-eval-body">

                                <?php if (!$hasDetails): ?>

                                    <p class="eval-none">
                                        Detailed evaluation is not available for this candidate.
                                    </p>

                                <?php else: ?>

                                    <div class="eval-metrics">

                                        <?php foreach ($metrics as $label => $value): ?>

                                            <div class="eval-metric">
                                                <span><?= statistics_e($label) ?></span>
                                                <div class="eval-bar">
                                                    <span style="width: <?= (int) round($value) ?>%;"></span>
                                                </div>
                                                <strong><?= number_format($value, 0) ?>%</strong>
                                            </div>

                                        <?php endforeach; ?>

                                    </div>


                                    <div class="eval-notes">

                                        <div class="eval-strengths">

                                            <h3>Strengths</h3>

                                            <?php if (!empty($c['_strengths'])): ?>
                                                <ul>
                                                    <?php foreach ($c['_strengths'] as $item): ?>
                                                        <li><?= statistics_e($item) ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <p class="eval-none">None recorded.</p>
                                            <?php endif; ?>

                                        </div>


                                        <div class="eval-concerns">

                                            <h3>Concerns</h3>

                                            <?php if (!empty($c['_concerns'])): ?>
                                                <ul>
                                                    <?php foreach ($c['_concerns'] as $item): ?>
                                                        <li><?= statistics_e($item) ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <p class="eval-none">None recorded.</p>
                                            <?php endif; ?>

                                        </div>

                                    </div>

                                <?php endif; ?>


                                <div class="eval-footer">

                                    <a
                                        href="?page=review&id=<?= (int) ($c['applicant_id'] ?? 0) ?>"
                                        class="view-all-link"
                                    >
                                        Review application
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </a>

                                </div>

                            </div>

                        </details>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>


    </div>


    <?php require BASE_PATH . '/resources/views/includes/footer.php'; ?>

</div>


<script>

    window.recruitmentStatistics = {

        trendLabels:
            <?= json_encode(
                $trendLabels,
                JSON_UNESCAPED_SLASHES
            ) ?>,

        trendValues:
            <?= json_encode(
                $trendValues,
                JSON_UNESCAPED_SLASHES
            ) ?>

    };

</script>


<?php require BASE_PATH . '/resources/views/includes/scripts.php'; ?>