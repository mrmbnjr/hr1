<?php

$pageTitle       = "Employee Requests";
$pageCSS         = "employee-requests.css";
$pageJS          = "employee-requests.js";
$pageDescription = "Review and manage employee requests.";

if (!isset($_SESSION['user_id'])) {
    header("Location: /hr1/public/?page=login");
    exit;
}

/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
| $requests and $departments should be provided by
| EmployeeRequestController.
*/

$requests    = $requests ?? [];
$departments = $departments ?? [];


/*
|--------------------------------------------------------------------------
| REQUEST STATUS
|--------------------------------------------------------------------------
*/

$statusMeta = [

    "Pending" => [
        "label" => "Pending",
        "class" => "badge-gray"
    ],

    "Under Review" => [
        "label" => "Under Review",
        "class" => "badge-blue"
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

                <option value="Employment Certificate">
                    Employment Certificate
                </option>

                <option value="Profile Update">
                    Profile Update
                </option>

                <option value="Record Correction">
                    Record Correction
                </option>

                <option value="Other HR Request">
                    Other HR Request
                </option>

            </select>


            <!-- Status -->

            <select id="statusFilter">

                <option value="All">
                    All Status
                </option>

                <option value="Pending">
                    Pending
                </option>

                <option value="Under Review">
                    Under Review
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

                            <th class="col-actions">
                                Actions
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

                                $status =
                                    $request['request_status']
                                    ?? 'Pending';

                                $meta =
                                    $statusMeta[$status]
                                    ?? $statusMeta['Pending'];

                                ?>


                                <tr

                                    class="request-row"

                                    data-id="<?=
                                        htmlspecialchars(
                                            $request['request_id']
                                        )
                                    ?>"

                                    data-department="<?=
                                        htmlspecialchars(
                                            $request['department_name']
                                            ?? ''
                                        )
                                    ?>"

                                    data-request-type="<?=
                                        htmlspecialchars(
                                            $request['request_type']
                                            ?? ''
                                        )
                                    ?>"

                                    data-status="<?=
                                        htmlspecialchars($status)
                                    ?>"

                                    data-name="<?=
                                        htmlspecialchars(
                                            $request['fullname']
                                            ?? ''
                                        )
                                    ?>"

                                    data-date="<?=
                                        htmlspecialchars(
                                            $request['created_at']
                                            ?? ''
                                        )
                                    ?>"

                                >


                                    <!-- Employee -->

                                    <td>

                                        <div class="request-cell">


                                            <div class="avatar-circle">

                                                <?=
                                                    strtoupper(
                                                        substr(
                                                            $request['fullname']
                                                            ?? '?',
                                                            0,
                                                            1
                                                        )
                                                    )
                                                ?>

                                            </div>


                                            <div>

                                                <strong>

                                                    <?=
                                                        htmlspecialchars(
                                                            $request['fullname']
                                                            ?? 'Unknown Employee'
                                                        )
                                                    ?>

                                                </strong>


                                                <span class="sub-text">

                                                    <?=
                                                        htmlspecialchars(
                                                            $request['employee_number']
                                                            ?? ''
                                                        )
                                                    ?>

                                                </span>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- Department -->

                                    <td>

                                        <?=
                                            htmlspecialchars(
                                                $request['department_name']
                                                ?? '—'
                                            )
                                        ?>

                                    </td>


                                    <!-- Request Type -->

                                    <td>

                                        <strong class="request-type">

                                            <?=
                                                htmlspecialchars(
                                                    $request['request_type']
                                                    ?? '—'
                                                )
                                            ?>

                                        </strong>


                                        <?php if (!empty(
                                            $request['request_title']
                                        )): ?>

                                            <span class="sub-text">

                                                <?=
                                                    htmlspecialchars(
                                                        $request[
                                                            'request_title'
                                                        ]
                                                    )
                                                ?>

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- Date -->

                                    <td>

                                        <?=
                                            htmlspecialchars(
                                                $request['created_at']
                                                ?? '—'
                                            )
                                        ?>

                                    </td>


                                    <!-- Status -->

                                    <td>

                                        <span
                                            class="badge
                                            <?= $meta['class'] ?>">

                                            <?= $meta['label'] ?>

                                        </span>

                                    </td>


                                    <!-- Actions -->

                                    <td class="col-actions">

                                        <a
                                            href="?page=employee-request-view&id=<?=
                                                urlencode(
                                                    $request['request_id']
                                                )
                                            ?>"
                                            class="btn-review">

                                            <i class="fa-solid fa-eye"></i>

                                            View

                                        </a>

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
                        type="button">

                        <i class="fa-solid fa-chevron-left"></i>

                    </button>


                    <div
                        class="page-numbers"
                        id="pageNumbers">
                    </div>


                    <button
                        class="page-btn"
                        id="nextPage"
                        type="button">

                        <i class="fa-solid fa-chevron-right"></i>

                    </button>


                </div>

            </div>


        </section>


    </div>


    <?php require '../resources/views/includes/footer.php'; ?>

</div>


<?php require '../resources/views/includes/scripts.php'; ?>