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

?>

<?php require '../resources/views/includes/header.php'; ?>

<?php require '../resources/views/includes/sidebar.php'; ?>


<div class="main-content">

    <?php require '../resources/views/includes/navbar.php'; ?>


    <div class="statistics-page">


        <!-- ==========================================================
            PAGE HEADER
        =========================================================== -->

        <section class="statistics-header">

            <div class="header-left">

                <a
                    href="?page=recruitment"
                    class="back-link"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to Recruitment
                </a>


                <div class="title-row">

                    <div>

                        <span class="eyebrow">
                            Recruitment Statistics
                        </span>

                        <h1>
                            <?= htmlspecialchars(
                                $job['title']
                            ) ?>
                        </h1>

                        <p>
                            Detailed performance overview
                            for this job posting.
                        </p>

                    </div>


                    <span
                        class="status-badge
                        <?= strtolower(
                            htmlspecialchars(
                                $job['status']
                            )
                        ) ?>"
                    >
                        <?= htmlspecialchars(
                            $job['status']
                        ) ?>
                    </span>

                </div>

            </div>


            <div class="header-action">

                <a
                    href="?page=edit&id=<?= (int) $job['posting_id'] ?>"
                    class="btn-secondary"
                >
                    <i class="fa-solid fa-pen"></i>
                    Edit Post
                </a>

            </div>

        </section>



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


            <!-- Total Applications -->

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



            <!-- Under Review -->

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



            <!-- Interviews -->

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



            <!-- Hired -->

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
                        vacancy
                        <?= $remainingVacancies == 1 ? '' : 'ies' ?>
                        remaining
                    </small>

                </div>

            </div>



            <!-- Rejected -->

            <div class="stat-card">

                <div class="stat-icon rejected">
                    <i class="fa-solid fa-user-xmark"></i>
                </div>

                <div class="stat-content">

                    <span>
                        Rejected
                    </span>

                    <strong>
                        <?= (int) $summary['rejected'] ?>
                    </strong>

                    <small>
                        Applications not selected
                    </small>

                </div>

            </div>



            <!-- Hiring Rate -->

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
            CHARTS
        =========================================================== -->

        <section class="charts-grid">


            <!-- Application Trend -->

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



            <!-- Status Breakdown -->

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
            VACANCY PROGRESS
        =========================================================== -->

        <section class="progress-card">

            <div class="progress-header">

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


            <?php

            $vacancyPercentage = 0;

            if ($vacancies > 0) {

                $vacancyPercentage =
                    min(
                        100,
                        round(
                            ($hired / $vacancies) * 100,
                            1
                        )
                    );
            }

            ?>


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