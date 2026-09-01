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
        "Location: /hr1/public/?page=login"
    );

    exit;
}


$applicationTrend = $applicationTrend ?? [];
$job = $job ?? [];
$summary = $summary ?? [];
$interviewCount = $interviewCount ?? 0;
$totalApplications = $totalApplications ?? 0;
$statusBreakdown = $statusBreakdown ?? [];
$hired = $hired ?? 0;
$remainingVacancies = $remainingVacancies ?? 0;
$hiringRate = $hiringRate ?? 0;
$recentApplicants = $recentApplicants ?? [];
$vacancies = $vacancies ?? 0;


$statusLabels = [
    'Submitted',
    'Under Review',
    'Interview',
    'Hired',
    'Rejected'
];


$trendLabels = [];

$trendValues = [];

foreach ($applicationTrend as $date => $count) {

    $trendLabels[] =
        date('M d', strtotime($date));

    $trendValues[] =
        (int) $count;
}


$deadline = $job['application_deadline']
    ? date(
        'M d, Y',
        strtotime($job['application_deadline'])
    )
    : 'No deadline';


$createdAt = $job['created_at']
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


$trendVolume = array_sum($trendValues);


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


$aiScreened = 0;
$aiScoreSum = 0.0;
$aiRecommendations = [
    'Highly Recommended' => 0,
    'Recommended' => 0,
    'Consider' => 0,
    'Not Recommended' => 0,
];

foreach ($recentApplicants as $applicantRow) {

    if (
        !isset($applicantRow['ai_score'])
        || $applicantRow['ai_score'] === null
        || $applicantRow['ai_score'] === ''
    ) {
        continue;
    }

    $aiScreened++;
    $aiScoreSum += (float) $applicantRow['ai_score'];

    $recommendation = $applicantRow['recommendation'] ?? '';

    if (array_key_exists($recommendation, $aiRecommendations)) {
        $aiRecommendations[$recommendation]++;
    }
}

$aiRecentCount = count($recentApplicants);
$aiAverage = $aiScreened > 0
    ? round($aiScoreSum / $aiScreened, 1)
    : 0;


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

?>

<?php require '../resources/views/includes/header.php'; ?>

<?php require '../resources/views/includes/sidebar.php'; ?>


<div class="main-content">

    <?php require '../resources/views/includes/navbar.php'; ?>


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
                    <?= htmlspecialchars(
                        $job['position_name'] ?? '—'
                    ) ?>
                </strong>

            </div>


            <div class="job-information-item">

                <span class="info-label">
                    Department
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $job['department_name'] ?? '—'
                    ) ?>
                </strong>

            </div>


            <div class="job-information-item">

                <span class="info-label">
                    Employment Type
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $job['employment_type']
                    ) ?>
                </strong>

            </div>


            <div class="job-information-item">

                <span class="info-label">
                    Vacancies
                </span>

                <strong>
                    <?= $vacancies ?>
                </strong>

            </div>


            <div class="job-information-item">

                <span class="info-label">
                    Posted
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $createdAt
                    ) ?>
                </strong>

            </div>


            <div class="job-information-item">

                <span class="info-label">
                    Deadline
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $deadline
                    ) ?>
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
                        <?= $totalApplications ?>
                    </strong>

                    <small>
                        All submitted applications
                    </small>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon review">
                    <i class="fa-solid fa-file-circle-check"></i>
                </div>

                <div class="stat-content">

                    <span>
                        Under Review
                    </span>

                    <strong>
                        <?= (int) $summary['under_review'] ?>
                    </strong>

                    <small>
                        Currently being evaluated
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
                        <?= $interviewCount ?>
                    </strong>

                    <small>
                        Interviews scheduled
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
                        <?= $hired ?>
                    </strong>

                    <small>
                        <?= $remainingVacancies ?>
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
                        <?= $hiringRate ?>%
                    </strong>

                    <small>
                        Hired vs. total applications
                    </small>

                </div>

            </div>

        </section>



        <!-- ==========================================================
            RECRUITMENT FUNNEL
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

                    <div class="funnel-stage stage-<?= htmlspecialchars($stage['class']) ?>">

                        <span class="funnel-label">
                            <?= htmlspecialchars($stage['label']) ?>
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
                    <?= $hired ?>
                    /
                    <?= $vacancies ?>
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
                    <?= $hired ?>
                    hired
                </span>

                <span>
                    <?= $remainingVacancies ?>
                    remaining
                </span>

            </div>

        </section>



        <!-- ==========================================================
            CHARTS
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


            <div class="chart-card status-card">

                <div class="chart-header">

                    <div>

                        <h2>
                            Application Status
                        </h2>

                        <p>
                            Current candidate pipeline.
                        </p>

                    </div>

                </div>


                <div class="status-chart-container">

                    <canvas
                        id="statusBreakdownChart"
                    ></canvas>

                </div>


                <div class="status-legend">

                    <?php foreach (
                        $statusBreakdown
                        as $status => $count
                    ): ?>

                        <div class="legend-item">

                            <span
                                class="legend-dot
                                status-<?= strtolower(
                                    str_replace(
                                        ' ',
                                        '-',
                                        $status
                                    )
                                ) ?>"
                            ></span>

                            <span>
                                <?= htmlspecialchars(
                                    $status
                                ) ?>
                            </span>

                            <strong>
                                <?= (int) $count ?>
                            </strong>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </section>



        <!-- ==========================================================
            AI SCREENING + JOB PERFORMANCE
        =========================================================== -->

        <section class="insights-grid">


            <div class="chart-card ai-card">

                <div class="chart-header">

                    <div>

                        <h2>
                            AI Screening Results
                        </h2>

                        <p>
                            Scores for the latest applicants
                            on this posting.
                        </p>

                    </div>

                </div>


                <?php if ($aiRecentCount === 0): ?>

                    <div class="ai-empty">
                        <i class="fa-solid fa-robot"></i>
                        <strong>No applicants to screen</strong>
                        <span>AI results will appear as applications arrive.</span>
                    </div>

                <?php else: ?>

                    <div class="ai-summary-row">

                        <div class="ai-metric">
                            <span>Screened</span>
                            <strong>
                                <?= $aiScreened ?>
                                /
                                <?= $aiRecentCount ?>
                            </strong>
                        </div>

                        <div class="ai-metric">
                            <span>Average score</span>
                            <strong>
                                <?= $aiScreened > 0 ? $aiAverage . '%' : '—' ?>
                            </strong>
                        </div>

                    </div>


                    <div class="ai-rec-mix">

                        <?php foreach ($aiRecommendations as $label => $count): ?>

                            <div class="ai-rec-item">
                                <span class="rec-badge <?= statistics_recommendation_class($label) ?>">
                                    <?= htmlspecialchars($label) ?>
                                </span>
                                <strong><?= (int) $count ?></strong>
                            </div>

                        <?php endforeach; ?>

                    </div>


                    <ul class="ai-applicant-list">

                        <?php foreach ($recentApplicants as $applicant): ?>

                            <?php
                            $hasScore = isset($applicant['ai_score'])
                                && $applicant['ai_score'] !== null
                                && $applicant['ai_score'] !== '';
                            ?>

                            <li>

                                <div class="ai-applicant-name">
                                    <?= htmlspecialchars($applicant['fullname']) ?>
                                </div>

                                <?php if ($hasScore): ?>

                                    <div class="score-pill">
                                        <span
                                            class="score-dot"
                                            style="--score: <?= (int) $applicant['ai_score'] ?>"
                                        ></span>
                                        <?= number_format((float) $applicant['ai_score'], 0) ?>%
                                    </div>

                                    <span class="rec-badge <?= statistics_recommendation_class($applicant['recommendation'] ?? '') ?>">
                                        <?= htmlspecialchars($applicant['recommendation'] ?? '') ?>
                                    </span>

                                <?php else: ?>

                                    <span class="ai-pending">Not screened</span>

                                <?php endif; ?>

                            </li>

                        <?php endforeach; ?>

                    </ul>

                <?php endif; ?>

            </div>


            <div class="chart-card performance-card">

                <div class="chart-header">

                    <div>

                        <h2>
                            Job Performance
                        </h2>

                        <p>
                            Key hiring metrics for this posting.
                        </p>

                    </div>

                </div>


                <table class="performance-table">

                    <tbody>

                        <tr>
                            <th>Total applications</th>
                            <td><?= $totalApplications ?></td>
                        </tr>

                        <tr>
                            <th>Submitted</th>
                            <td><?= (int) ($summary['submitted'] ?? 0) ?></td>
                        </tr>

                        <tr>
                            <th>Under review</th>
                            <td><?= (int) ($summary['under_review'] ?? 0) ?></td>
                        </tr>

                        <tr>
                            <th>Interview (status)</th>
                            <td><?= (int) ($summary['interview'] ?? 0) ?></td>
                        </tr>

                        <tr>
                            <th>Scheduled interviews</th>
                            <td><?= $interviewCount ?></td>
                        </tr>

                        <tr>
                            <th>Hired</th>
                            <td><?= $hired ?></td>
                        </tr>

                        <tr>
                            <th>Rejected</th>
                            <td><?= (int) ($summary['rejected'] ?? 0) ?></td>
                        </tr>

                        <tr>
                            <th>Vacancies</th>
                            <td><?= $vacancies ?></td>
                        </tr>

                        <tr>
                            <th>Remaining vacancies</th>
                            <td><?= $remainingVacancies ?></td>
                        </tr>

                        <tr>
                            <th>Hiring rate</th>
                            <td><?= $hiringRate ?>%</td>
                        </tr>

                        <tr>
                            <th>Applications (14 days)</th>
                            <td><?= (int) $trendVolume ?></td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </section>



        <!-- ==========================================================
            RECENT APPLICANTS
        =========================================================== -->

        <section class="applicants-card">

            <div class="section-header">

                <div>

                    <h2>
                        Recent Applicants
                    </h2>

                    <p>
                        Latest candidates who applied
                        for this position.
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

                            <th>
                                Applicant
                            </th>

                            <th>
                                Email
                            </th>

                            <th>
                                Date Applied
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php if (
                            !empty($recentApplicants)
                        ): ?>

                            <?php foreach (
                                $recentApplicants
                                as $applicant
                            ): ?>

                                <tr>

                                    <td>

                                        <div class="applicant-name">

                                            <div class="avatar">

                                                <?= strtoupper(
                                                    substr(
                                                        $applicant['fullname'],
                                                        0,
                                                        1
                                                    )
                                                ) ?>

                                            </div>

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $applicant['fullname']
                                                ) ?>
                                            </strong>

                                        </div>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $applicant['email']
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= date(
                                            'M d, Y',
                                            strtotime(
                                                $applicant['applied_at']
                                            )
                                        ) ?>

                                    </td>


                                    <td>

                                        <span
                                            class="application-status
                                            status-<?= strtolower(
                                                str_replace(
                                                    ' ',
                                                    '-',
                                                    $applicant[
                                                        'application_status'
                                                    ]
                                                )
                                            ) ?>"
                                        >
                                            <?= htmlspecialchars(
                                                $applicant[
                                                    'application_status'
                                                ]
                                            ) ?>
                                        </span>

                                    </td>


                                    <td>

                                        <a
                                            href="?page=review&id=<?= (int) $applicant['applicant_id'] ?>"
                                            class="applicant-action"
                                            title="View applicant"
                                        >
                                            <i class="fa-solid fa-arrow-right"></i>
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="5"
                                    class="empty-state"
                                >

                                    <i class="fa-solid fa-users"></i>

                                    <strong>
                                        No applicants yet
                                    </strong>

                                    <span>
                                        Applications for this
                                        job will appear here.
                                    </span>

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>


    </div>


    <?php require '../resources/views/includes/footer.php'; ?>

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
            ) ?>,

        statusLabels:
            <?= json_encode(
                array_keys(
                    $statusBreakdown
                ),
                JSON_UNESCAPED_SLASHES
            ) ?>,

        statusValues:
            <?= json_encode(
                array_values(
                    $statusBreakdown
                ),
                JSON_UNESCAPED_SLASHES
            ) ?>

    };

</script>


<?php require '../resources/views/includes/scripts.php'; ?>
