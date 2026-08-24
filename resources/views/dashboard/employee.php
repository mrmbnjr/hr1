<?php

$pageTitle =
    "Employee Dashboard";

$pageCSS =
    "dashboard.css";

$pageDescription =
    "Your employee dashboard.";


if (!isset($_SESSION['user_id'])) {

    header(
        "Location: /hr1/public/?page=login"
    );

    exit;
}


$employee =
    $employee ?? [];

$requests =
    $requests ?? [];

$totalRequests =
    $totalRequests ?? 0;

$pendingRequests =
    $pendingRequests ?? 0;

$approvedRequests =
    $approvedRequests ?? 0;

$rejectedRequests =
    $rejectedRequests ?? 0;

$completedRequests =
    $completedRequests ?? 0;

$recentRequests =
    $recentRequests ?? [];

?>

<?php require '../resources/views/includes/header.php'; ?>

<?php require '../resources/views/includes/sidebar.php'; ?>


<div class="main-content">

    <?php require '../resources/views/includes/navbar.php'; ?>


    <!-- ======================================================
         WELCOME
    ======================================================= -->

    <section
        class="dashboard-card"
        style="
            margin-bottom:24px;
            padding:28px;
        "
    >

        <div>

            <p
                style="
                    margin:0 0 6px;
                    color:#888;
                    font-size:14px;
                "
            >
                Employee Dashboard
            </p>


            <h1
                style="
                    margin:0 0 8px;
                    font-size:28px;
                "
            >

                Welcome,
                <?= htmlspecialchars(
                    $employee['fullname']
                    ?? 'Employee'
                ) ?>!

            </h1>


            <p
                style="
                    margin:0;
                    color:#777;
                "
            >
                Here's a quick overview of your
                employee information and requests.
            </p>

        </div>

    </section>


    <!-- ======================================================
         EMPLOYEE INFORMATION
    ======================================================= -->

    <section
        class="dashboard-card"
        style="
            margin-bottom:24px;
            padding:24px;
        "
    >

        <div
            class="card-header"
            style="
                margin-bottom:20px;
            "
        >

            <div>

                <h2>
                    My Information
                </h2>

                <p>
                    Your current employee information.
                </p>

            </div>

        </div>


        <div
            style="
                display:grid;
                grid-template-columns:
                    repeat(
                        auto-fit,
                        minmax(180px, 1fr)
                    );
                gap:20px;
            "
        >

            <!-- Employee Number -->

            <div>

                <small
                    style="
                        display:block;
                        color:#888;
                        margin-bottom:6px;
                    "
                >
                    Employee Number
                </small>

                <strong>

                    <?= htmlspecialchars(
                        $employee[
                            'employee_number'
                        ] ?? '—'
                    ) ?>

                </strong>

            </div>


            <!-- Department -->

            <div>

                <small
                    style="
                        display:block;
                        color:#888;
                        margin-bottom:6px;
                    "
                >
                    Department
                </small>

                <strong>

                    <?= htmlspecialchars(
                        $employee[
                            'department_name'
                        ] ?? '—'
                    ) ?>

                </strong>

            </div>


            <!-- Position -->

            <div>

                <small
                    style="
                        display:block;
                        color:#888;
                        margin-bottom:6px;
                    "
                >
                    Position
                </small>

                <strong>

                    <?= htmlspecialchars(
                        $employee[
                            'job_title'
                        ] ?? '—'
                    ) ?>

                </strong>

            </div>


            <!-- Employment Status -->

            <div>

                <small
                    style="
                        display:block;
                        color:#888;
                        margin-bottom:6px;
                    "
                >
                    Employment Status
                </small>

                <strong>

                    <?= htmlspecialchars(
                        $employee[
                            'employment_status'
                        ] ?? '—'
                    ) ?>

                </strong>

            </div>


            <!-- Hire Date -->

            <div>

                <small
                    style="
                        display:block;
                        color:#888;
                        margin-bottom:6px;
                    "
                >
                    Hire Date
                </small>

                <strong>

                    <?php if (
                        !empty(
                            $employee['hire_date']
                        )
                    ): ?>

                        <?= htmlspecialchars(
                            date(
                                'M d, Y',
                                strtotime(
                                    $employee[
                                        'hire_date'
                                    ]
                                )
                            )
                        ) ?>

                    <?php else: ?>

                        —

                    <?php endif; ?>

                </strong>

            </div>


            <!-- Email -->

            <div>

                <small
                    style="
                        display:block;
                        color:#888;
                        margin-bottom:6px;
                    "
                >
                    Email
                </small>

                <strong>

                    <?= htmlspecialchars(
                        $employee[
                            'email'
                        ] ?? '—'
                    ) ?>

                </strong>

            </div>

        </div>

    </section>


    <!-- ======================================================
         REQUEST STATISTICS
    ======================================================= -->

    <section
        class="stats-grid"
        style="
            margin-bottom:24px;
        "
    >

        <!-- Total -->

        <a
            href="/hr1/public/?page=my-requests"
            class="stat-card-link"
        >

            <article class="stat-card">

                <div class="stat-icon">

                    <i
                        class="fa-solid fa-file-lines"
                    ></i>

                </div>


                <div>

                    <small>
                        My Requests
                    </small>

                    <h2>
                        <?= $totalRequests ?>
                    </h2>

                    <span>
                        Total submitted requests
                    </span>

                </div>

            </article>

        </a>


        <!-- Pending -->

        <a
            href="/hr1/public/?page=my-requests"
            class="stat-card-link"
        >

            <article class="stat-card">

                <div class="stat-icon">

                    <i
                        class="fa-solid fa-clock"
                    ></i>

                </div>


                <div>

                    <small>
                        Pending
                    </small>

                    <h2>
                        <?= $pendingRequests ?>
                    </h2>

                    <span>
                        Requests awaiting review
                    </span>

                </div>

            </article>

        </a>


        <!-- Approved -->

        <a
            href="/hr1/public/?page=my-requests"
            class="stat-card-link"
        >

            <article class="stat-card">

                <div class="stat-icon">

                    <i
                        class="fa-solid fa-circle-check"
                    ></i>

                </div>


                <div>

                    <small>
                        Approved
                    </small>

                    <h2>
                        <?= $approvedRequests ?>
                    </h2>

                    <span>
                        Approved requests
                    </span>

                </div>

            </article>

        </a>


        <!-- Completed -->

        <a
            href="/hr1/public/?page=my-requests"
            class="stat-card-link"
        >

            <article class="stat-card">

                <div class="stat-icon">

                    <i
                        class="fa-solid fa-check-double"
                    ></i>

                </div>


                <div>

                    <small>
                        Completed
                    </small>

                    <h2>
                        <?= $completedRequests ?>
                    </h2>

                    <span>
                        Completed requests
                    </span>

                </div>

            </article>

        </a>

    </section>


    <!-- ======================================================
         RECENT REQUESTS
    ======================================================= -->

    <section
        class="dashboard-card"
        style="
            margin-bottom:24px;
        "
    >

        <div
            class="card-header"
        >

            <div>

                <h2>
                    Recent Requests
                </h2>

                <p>
                    Your latest employee requests.
                </p>

            </div>


            <a
                href="/hr1/public/?page=my-requests"
                class="view-all-btn"
            >

                View All

                <i
                    class="fa-solid fa-arrow-right"
                ></i>

            </a>

        </div>


        <div
            class="activity-list"
            style="padding:0 24px 20px;"
        >

            <?php if (
                !empty($recentRequests)
            ): ?>


                <?php foreach (
                    $recentRequests
                    as $request
                ): ?>

                    <?php

                    $status =
                        $request['status']
                        ?? 'Pending';


                    $statusClass =
                        match ($status) {

                            'Approved' =>
                                'badge-green',

                            'Completed' =>
                                'badge-green',

                            'Rejected' =>
                                'badge-red',

                            default =>
                                'badge-gray'

                        };

                    ?>


                    <div
                        class="activity-item"
                    >

                        <div>

                            <h3>

                                <?= htmlspecialchars(
                                    $request[
                                        'request_type'
                                    ] ?? 'Request'
                                ) ?>

                            </h3>


                            <p>

                                <?= htmlspecialchars(
                                    $request[
                                        'subject'
                                    ] ?? '—'
                                ) ?>

                            </p>

                        </div>


                        <div
                            style="
                                display:flex;
                                align-items:center;
                                gap:12px;
                            "
                        >

                            <span
                                class="badge <?= $statusClass ?>"
                            >

                                <?= htmlspecialchars(
                                    $status
                                ) ?>

                            </span>


                            <?php if (
                                !empty(
                                    $request[
                                        'requested_at'
                                    ]
                                )
                            ): ?>

                                <span>

                                    <?= htmlspecialchars(
                                        date(
                                            'M d',
                                            strtotime(
                                                $request[
                                                    'requested_at'
                                                ]
                                            )
                                        )
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                <?php endforeach; ?>


            <?php else: ?>


                <div
                    style="
                        padding:40px 0;
                        text-align:center;
                        color:#888;
                    "
                >

                    <i
                        class="fa-solid fa-inbox"
                        style="
                            font-size:30px;
                            margin-bottom:12px;
                        "
                    ></i>


                    <p>
                        You have not submitted
                        any requests yet.
                    </p>


                    <a
                        href="/hr1/public/?page=my-requests"
                        class="btn-primary"
                    >

                        <i
                            class="fa-solid fa-plus"
                        ></i>

                        Submit a Request

                    </a>

                </div>


            <?php endif; ?>

        </div>

    </section>


    <!-- ======================================================
         QUICK ACTION
    ======================================================= -->

    <section
        class="dashboard-card"
        style="
            padding:24px;
        "
    >

        <div
            style="
                display:flex;
                align-items:center;
                justify-content:space-between;
                gap:20px;
                flex-wrap:wrap;
            "
        >

            <div>

                <h2
                    style="
                        margin:0 0 6px;
                    "
                >
                    Need something from HR?
                </h2>


                <p
                    style="
                        margin:0;
                        color:#888;
                    "
                >
                    Submit a new employee request
                    and track its progress.
                </p>

            </div>


            <a
                href="/hr1/public/?page=my-requests"
                class="btn-primary"
            >

                <i
                    class="fa-solid fa-file-circle-plus"
                ></i>

                My Requests

            </a>

        </div>

    </section>


    <?php require '../resources/views/includes/footer.php'; ?>

</div>


<?php require '../resources/views/includes/scripts.php'; ?>