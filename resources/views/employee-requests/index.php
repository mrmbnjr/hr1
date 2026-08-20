<?php

$pageTitle       = "Employee Requests";
$pageCSS         = "employee-requests.css";
$pageJS          = "employee-requests.js";
$pageDescription = "Review and manage employee requests.";

if (!isset($_SESSION['user_id'])) {
    header("Location: /hr1/public/?page=login");
    exit;
}

$requests    = $requests ?? [];
$departments = $departments ?? [];

$statusMeta = [

    "Pending" => [
        "label" => "Pending",
        "class" => "badge-gray"
    ],

    "Approved" => [
        "label" => "Approved",
        "class" => "badge-green"
    ],

    "Rejected" => [
        "label" => "Rejected",
        "class" => "badge-red"
    ],

    "Completed" => [
        "label" => "Completed",
        "class" => "badge-green"
    ]

];

$requestTypes = [

    "Certificate of Employment",
    "Document Request",
    "Profile Update",
    "Payroll Concern",
    "Employment Concern",
    "Other"

];

?>

<?php require '../resources/views/includes/header.php'; ?>
<?php require '../resources/views/includes/sidebar.php'; ?>

<div class="main-content">

    <?php require '../resources/views/includes/navbar.php'; ?>

    <div class="employee-request-page">

        <!-- ==========================================================
            FILTER BAR
        =========================================================== -->

        <section class="filter-bar">

            <!-- Department -->

            <select id="departmentFilter">

                <option value="All">
                    All Departments
                </option>

                <?php foreach ($departments as $department): ?>

                    <option
                        value="<?= htmlspecialchars(
                            $department['department_name']
                        ) ?>">

                        <?= htmlspecialchars(
                            $department['department_name']
                        ) ?>

                    </option>

                <?php endforeach; ?>

            </select>


            <!-- Request Type -->

            <select id="requestTypeFilter">

                <option value="All">
                    All Request Types
                </option>

                <?php foreach ($requestTypes as $requestType): ?>

                    <option value="<?= htmlspecialchars($requestType) ?>">

                        <?= htmlspecialchars($requestType) ?>

                    </option>

                <?php endforeach; ?>

            </select>


            <!-- Status -->

            <select id="statusFilter">

                <option value="All">
                    All Status
                </option>

                <option value="Pending">
                    Pending
                </option>

                <option value="Approved">
                    Approved
                </option>

                <option value="Rejected">
                    Rejected
                </option>

                <option value="Completed">
                    Completed
                </option>

            </select>


            <!-- Sort -->

            <select id="sortFilter">

                <option value="newest">
                    Newest
                </option>

                <option value="oldest">
                    Oldest
                </option>

                <option value="name-az">
                    Employee Name (A-Z)
                </option>

                <option value="name-za">
                    Employee Name (Z-A)
                </option>

                <option value="status">
                    Status
                </option>

            </select>

        </section>


        <!-- ==========================================================
            REQUEST TABLE
        =========================================================== -->

        <section class="table-card">

            <div class="table-scroll">

                <table
                    class="employee-request-table"
                    id="employeeRequestTable">

                    <thead>

                        <tr>

                            <th>
                                Employee
                            </th>

                            <th>
                                Department
                            </th>

                            <th>
                                Request Type
                            </th>

                            <th>
                                Date Submitted
                            </th>

                            <th>
                                Status
                            </th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (empty($requests)): ?>

                            <tr class="empty-row">

                                <td colspan="6">

                                    <div class="empty-state">

                                        <i class="fa-solid fa-inbox"></i>

                                        <p>
                                            No employee requests found.
                                        </p>

                                    </div>

                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($requests as $request): ?>

                                <?php

                                $status = $request['status'] ?? 'Pending';

                                $meta =
                                    $statusMeta[$status]
                                    ?? $statusMeta['Pending'];

                                $fullName = trim(
                                    $request['fullname'] ?? ''
                                );

                                if ($fullName === '') {
                                    $fullName = 'Unknown Employee';
                                }

                                $initial = strtoupper(
                                    substr($fullName, 0, 1)
                                );

                                ?>

                                <tr
                                    class="request-row"

                                    data-id="<?= htmlspecialchars(
                                        $request['request_id'] ?? ''
                                    ) ?>"

                                    data-department="<?= htmlspecialchars(
                                        $request['department_name'] ?? ''
                                    ) ?>"

                                    data-request-type="<?= htmlspecialchars(
                                        $request['request_type'] ?? ''
                                    ) ?>"

                                    data-status="<?= htmlspecialchars(
                                        $status
                                    ) ?>"

                                    data-name="<?= htmlspecialchars(
                                        $fullName
                                    ) ?>"

                                    data-date="<?= htmlspecialchars(
                                        $request['requested_at'] ?? ''
                                    ) ?>">

                                    <!-- Employee -->

                                    <td>

                                        <div class="request-cell">

                                            <div class="avatar-circle">

                                                <?= htmlspecialchars(
                                                    $initial
                                                ) ?>

                                            </div>

                                            <div>

                                                <strong>

                                                    <?= htmlspecialchars(
                                                        $fullName
                                                    ) ?>

                                                </strong>

                                                <span class="sub-text">

                                                    <?= htmlspecialchars(
                                                        $request[
                                                            'employee_number'
                                                        ] ?? ''
                                                    ) ?>

                                                </span>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- Department -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $request['department_name']
                                            ?? '—'
                                        ) ?>

                                    </td>


                                    <!-- Request Type -->

                                    <td>

                                        <strong class="request-type">

                                            <?= htmlspecialchars(
                                                $request['request_type']
                                                ?? '—'
                                            ) ?>

                                        </strong>

                                        <?php if (!empty($request['subject'])): ?>

                                            <span class="sub-text">

                                                <?= htmlspecialchars(
                                                    $request['subject']
                                                ) ?>

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- Date Submitted -->

                                    <td>

                                        <?php if (!empty(
                                            $request['requested_at']
                                        )): ?>

                                            <?= htmlspecialchars(
                                                $request['requested_at']
                                            ) ?>

                                        <?php else: ?>

                                            —

                                        <?php endif; ?>

                                    </td>


                                    <!-- Status -->

                                    <td>

                                        <span
                                            class="badge <?= htmlspecialchars(
                                                $meta['class']
                                            ) ?>">

                                            <?= htmlspecialchars(
                                                $meta['label']
                                            ) ?>

                                        </span>

                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>


            <!-- ======================================================
                TABLE FOOTER
            ======================================================= -->

            <div class="table-footer">

                <span
                    class="results-count"
                    id="resultsCount">
                </span>


                <div
                    class="pagination"
                    id="pagination">

                    <button
                        class="page-btn"
                        id="prevPage"
                        type="button"
                        aria-label="Previous page">

                        <i class="fa-solid fa-chevron-left"></i>

                    </button>


                    <div
                        class="page-numbers"
                        id="pageNumbers">
                    </div>


                    <button
                        class="page-btn"
                        id="nextPage"
                        type="button"
                        aria-label="Next page">

                        <i class="fa-solid fa-chevron-right"></i>

                    </button>

                </div>

            </div>

        </section>

    </div>


    <?php require '../resources/views/includes/footer.php'; ?>

</div>


<?php require '../resources/views/includes/scripts.php'; ?>