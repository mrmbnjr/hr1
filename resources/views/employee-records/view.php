<?php

$pageTitle       = "Employee Record";
$pageCSS         = "employee-records.css";
$pageDescription = "View employee information and employment records.";

if (!isset($_SESSION['user_id'])) {
    header("Location: /hr1/public/?page=login");
    exit;
}

/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
| $employee should be provided by EmployeeRecordsController.
*/

$employee = $employee ?? null;


/*
|--------------------------------------------------------------------------
| EMPTY RECORD
|--------------------------------------------------------------------------
*/

if (!$employee) {
    $employee = [];
}


/*
|--------------------------------------------------------------------------
| EMPLOYEE DATA
|--------------------------------------------------------------------------
*/

$fullname =
    $employee['fullname']
    ?? 'Unknown Employee';

$email =
    $employee['email']
    ?? '—';

$employeeNumber =
    $employee['employee_number']
    ?? '—';

$department =
    $employee['department_name']
    ?? '—';

$jobTitle =
    $employee['job_title']
    ?? '—';

$employmentType =
    $employee['employment_type']
    ?? '—';

$employmentStatus =
    $employee['employment_status']
    ?? '—';

$hireDate =
    $employee['hire_date']
    ?? '—';

$applicationId =
    $employee['application_id']
    ?? '—';

$applicantId =
    $employee['applicant_id']
    ?? '—';


/*
|--------------------------------------------------------------------------
| STATUS BADGE
|--------------------------------------------------------------------------
*/

$statusClass = match ($employmentStatus) {

    'Regular' =>
        'badge-green',

    'Probationary' =>
        'badge-orange',

    'Contract' =>
        'badge-blue',

    default =>
        'badge-gray'

};


/*
|--------------------------------------------------------------------------
| AVATAR INITIAL
|--------------------------------------------------------------------------
*/

$initial =
    strtoupper(
        substr(
            trim($fullname),
            0,
            1
        )
    );

?>

<?php require '../resources/views/includes/header.php'; ?>
<?php require '../resources/views/includes/sidebar.php'; ?>

<div class="main-content">

    <?php require '../resources/views/includes/navbar.php'; ?>


    <div class="employee-record-page">


        <!-- ==========================================================
            BACK
        =========================================================== -->

        <div class="page-back">

            <a href="?page=employee-records">

                <i class="fa-solid fa-arrow-left"></i>

                Employee Records

            </a>

        </div>


        <?php if (empty($employee)): ?>


            <!-- ======================================================
                EMPTY STATE
            ======================================================= -->

            <section class="table-card">

                <div class="empty-state">

                    <i class="fa-solid fa-user-slash"></i>

                    <p>
                        Employee record not found.
                    </p>

                    <a
                        href="?page=employee-records"
                        class="btn-review">

                        <i class="fa-solid fa-arrow-left"></i>

                        Back to Employee Records

                    </a>

                </div>

            </section>


        <?php else: ?>


            <!-- ======================================================
                EMPLOYEE HEADER
            ======================================================= -->

            <section class="employee-profile-card">

                <div class="employee-profile-main">

                    <div class="avatar-large">

                        <?= htmlspecialchars($initial) ?>

                    </div>


                    <div class="employee-profile-info">

                        <h1>
                            <?= htmlspecialchars($fullname) ?>
                        </h1>

                        <p class="employee-position">

                            <?= htmlspecialchars($jobTitle) ?>

                        </p>

                        <span class="employee-number">

                            <?= htmlspecialchars($employeeNumber) ?>

                        </span>

                    </div>

                </div>


                <div class="employee-profile-status">

                    <span class="badge <?= $statusClass ?>">

                        <?= htmlspecialchars($employmentStatus) ?>

                    </span>

                </div>

            </section>


            <!-- ======================================================
                PERSONAL INFORMATION
            ======================================================= -->

            <section class="record-card">

                <div class="record-card-header">

                    <div>

                        <h2>
                            Personal Information
                        </h2>

                        <p>
                            Basic information associated with the employee.
                        </p>

                    </div>

                </div>


                <div class="record-grid">


                    <div class="record-item">

                        <span class="record-label">
                            Full Name
                        </span>

                        <strong>
                            <?= htmlspecialchars($fullname) ?>
                        </strong>

                    </div>


                    <div class="record-item">

                        <span class="record-label">
                            Email
                        </span>

                        <strong>
                            <?= htmlspecialchars($email) ?>
                        </strong>

                    </div>


                </div>

            </section>


            <!-- ======================================================
                EMPLOYMENT INFORMATION
            ======================================================= -->

            <section class="record-card">

                <div class="record-card-header">

                    <div>

                        <h2>
                            Employment Information
                        </h2>

                        <p>
                            Current employment details and organizational assignment.
                        </p>

                    </div>

                </div>


                <div class="record-grid">


                    <div class="record-item">

                        <span class="record-label">
                            Employee Number
                        </span>

                        <strong>
                            <?= htmlspecialchars($employeeNumber) ?>
                        </strong>

                    </div>


                    <div class="record-item">

                        <span class="record-label">
                            Department
                        </span>

                        <strong>
                            <?= htmlspecialchars($department) ?>
                        </strong>

                    </div>


                    <div class="record-item">

                        <span class="record-label">
                            Position
                        </span>

                        <strong>
                            <?= htmlspecialchars($jobTitle) ?>
                        </strong>

                    </div>


                    <div class="record-item">

                        <span class="record-label">
                            Employment Type
                        </span>

                        <strong>
                            <?= htmlspecialchars($employmentType) ?>
                        </strong>

                    </div>


                    <div class="record-item">

                        <span class="record-label">
                            Employment Status
                        </span>

                        <span>

                            <span class="badge <?= $statusClass ?>">

                                <?= htmlspecialchars($employmentStatus) ?>

                            </span>

                        </span>

                    </div>


                    <div class="record-item">

                        <span class="record-label">
                            Date Hired
                        </span>

                        <strong>
                            <?= htmlspecialchars($hireDate) ?>
                        </strong>

                    </div>


                </div>

            </section>


            <!-- ======================================================
                SYSTEM / RECRUITMENT REFERENCE
            ======================================================= -->

            <section class="record-card">

                <div class="record-card-header">

                    <div>

                        <h2>
                            Record References
                        </h2>

                        <p>
                            Internal references connecting this employee to the hiring process.
                        </p>

                    </div>

                </div>


                <div class="record-grid">


                    <div class="record-item">

                        <span class="record-label">
                            Employee ID
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $employee['employee_id'] ?? '—'
                            ) ?>
                        </strong>

                    </div>


                    <div class="record-item">

                        <span class="record-label">
                            Application ID
                        </span>

                        <strong>
                            <?= htmlspecialchars($applicationId) ?>
                        </strong>

                    </div>


                    <div class="record-item">

                        <span class="record-label">
                            Applicant ID
                        </span>

                        <strong>
                            <?= htmlspecialchars($applicantId) ?>
                        </strong>

                    </div>


                </div>

            </section>


        <?php endif; ?>


    </div>


    <?php require '../resources/views/includes/footer.php'; ?>

</div>


<?php require '../resources/views/includes/scripts.php'; ?>