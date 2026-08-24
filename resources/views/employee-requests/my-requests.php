<?php

use App\Services\Auth;


/*
|--------------------------------------------------------------------------
| Page Configuration
|--------------------------------------------------------------------------
*/

$pageTitle =
    "My Requests";

$pageCSS =
    "employee-requests.css";

$pageDescription =
    "Submit and track your employee requests.";


/*
|--------------------------------------------------------------------------
| Authorization
|--------------------------------------------------------------------------
*/

Auth::requireRole([
    'EMP'
]);


/*
|--------------------------------------------------------------------------
| Data Defaults
|--------------------------------------------------------------------------
*/

$employee =
    $employee ?? [];

$requests =
    $requests ?? [];


/*
|--------------------------------------------------------------------------
| Request Types
|--------------------------------------------------------------------------
*/

$requestTypes = [

    "Certificate of Employment",

    "Document Request",

    "Profile Update",

    "Payroll Concern",

    "Employment Concern",

    "Other"

];


/*
|--------------------------------------------------------------------------
| Status Metadata
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/

$success =
    $_GET['success'] ?? '';

$error =
    $_GET['error'] ?? '';

?>

<?php require '../resources/views/includes/header.php'; ?>

<?php require '../resources/views/includes/sidebar.php'; ?>


<div class="main-content">

    <?php require '../resources/views/includes/navbar.php'; ?>


    <div class="employee-request-page">


        <!-- ======================================================
             PAGE HEADER
        ======================================================= -->

        <section
            style="
                display:flex;
                align-items:center;
                justify-content:space-between;
                gap:20px;
                margin-bottom:24px;
                flex-wrap:wrap;
            "
        >

            <div>

                <h1
                    style="
                        margin:0 0 6px;
                        font-size:28px;
                    "
                >
                    My Employee Requests
                </h1>


                <p
                    style="
                        margin:0;
                        color:#777;
                    "
                >
                    Submit a request and track its status.
                </p>

            </div>


            <button
                type="button"
                id="openRequestForm"
                class="btn-primary"
            >

                <i class="fa-solid fa-plus"></i>

                New Request

            </button>

        </section>


        <!-- ======================================================
             SUCCESS MESSAGE
        ======================================================= -->

        <?php if ($success === 'created'): ?>

            <div
                style="
                    padding:14px 18px;
                    margin-bottom:20px;
                    border-radius:10px;
                    background:#eaf7ee;
                    color:#237a3b;
                    border:1px solid #bfe4c8;
                "
            >

                <i class="fa-solid fa-circle-check"></i>

                Your request has been submitted successfully.

            </div>

        <?php endif; ?>


        <!-- ======================================================
             ERROR MESSAGE
        ======================================================= -->

        <?php if ($error !== ''): ?>

            <div
                style="
                    padding:14px 18px;
                    margin-bottom:20px;
                    border-radius:10px;
                    background:#fff0f0;
                    color:#a12626;
                    border:1px solid #efc1c1;
                "
            >

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php

                $errorMessage = match ($error) {

                    'invalid-type' =>
                        'Please select a valid request type.',

                    'subject' =>
                        'Please enter a subject.',

                    'description' =>
                        'Please enter a description.',

                    default =>
                        'Unable to submit your request.'

                };

                ?>

                <?= htmlspecialchars($errorMessage) ?>

            </div>

        <?php endif; ?>


        <!-- ======================================================
             EMPLOYEE INFORMATION
        ======================================================= -->

        <section class="table-card">

            <div
                style="
                    padding:24px;
                    display:grid;
                    grid-template-columns:
                        repeat(auto-fit, minmax(180px, 1fr));
                    gap:20px;
                "
            >

                <div>

                    <span
                        style="
                            display:block;
                            font-size:12px;
                            color:#888;
                            margin-bottom:6px;
                        "
                    >
                        Employee
                    </span>

                    <strong>
                        <?= htmlspecialchars(
                            $employee['fullname']
                            ?? 'Employee'
                        ) ?>
                    </strong>

                </div>


                <div>

                    <span
                        style="
                            display:block;
                            font-size:12px;
                            color:#888;
                            margin-bottom:6px;
                        "
                    >
                        Employee Number
                    </span>

                    <strong>
                        <?= htmlspecialchars(
                            $employee['employee_number']
                            ?? '—'
                        ) ?>
                    </strong>

                </div>


                <div>

                    <span
                        style="
                            display:block;
                            font-size:12px;
                            color:#888;
                            margin-bottom:6px;
                        "
                    >
                        Department
                    </span>

                    <strong>
                        <?= htmlspecialchars(
                            $employee['department_name']
                            ?? '—'
                        ) ?>
                    </strong>

                </div>


                <div>

                    <span
                        style="
                            display:block;
                            font-size:12px;
                            color:#888;
                            margin-bottom:6px;
                        "
                    >
                        Position
                    </span>

                    <strong>
                        <?= htmlspecialchars(
                            $employee['job_title']
                            ?? '—'
                        ) ?>
                    </strong>

                </div>

            </div>

        </section>


        <!-- ======================================================
             REQUESTS
        ======================================================= -->

        <section
            class="table-card"
            style="margin-top:20px;"
        >

            <div
                style="
                    padding:22px 24px;
                    border-bottom:1px solid #eee;
                "
            >

                <h2
                    style="
                        margin:0 0 5px;
                        font-size:19px;
                    "
                >
                    My Requests
                </h2>

                <p
                    style="
                        margin:0;
                        color:#888;
                        font-size:14px;
                    "
                >
                    Your submitted employee requests.
                </p>

            </div>


            <div class="table-scroll">

                <table class="employee-request-table">

                    <thead>

                        <tr>

                            <th>
                                Request Type
                            </th>

                            <th>
                                Subject
                            </th>

                            <th>
                                Date Submitted
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                HR Remarks
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php if (empty($requests)): ?>

                            <tr>

                                <td colspan="5">

                                    <div
                                        class="empty-state"
                                        style="padding:50px 20px;"
                                    >

                                        <i
                                            class="fa-solid fa-inbox"
                                        ></i>

                                        <p>
                                            You have not submitted
                                            any requests yet.
                                        </p>

                                    </div>

                                </td>

                            </tr>

                        <?php else: ?>


                            <?php foreach ($requests as $request): ?>

                                <?php

                                $status =
                                    $request['status']
                                    ?? 'Pending';

                                $meta =
                                    $statusMeta[$status]
                                    ?? $statusMeta['Pending'];

                                ?>


                                <tr>


                                    <!-- Request Type -->

                                    <td>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $request[
                                                    'request_type'
                                                ] ?? '—'
                                            ) ?>

                                        </strong>

                                    </td>


                                    <!-- Subject -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $request[
                                                'subject'
                                            ] ?? '—'
                                        ) ?>

                                    </td>


                                    <!-- Date -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $request[
                                                'requested_at'
                                            ] ?? '—'
                                        ) ?>

                                    </td>


                                    <!-- Status -->

                                    <td>

                                        <span
                                            class="badge <?= htmlspecialchars(
                                                $meta['class']
                                            ) ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $meta['label']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- HR Remarks -->

                                    <td>

                                        <?php

                                        $remarks =
                                            trim(
                                                $request[
                                                    'hr_remarks'
                                                ] ?? ''
                                            );

                                        ?>

                                        <?php if ($remarks !== ''): ?>

                                            <?= htmlspecialchars(
                                                $remarks
                                            ) ?>

                                        <?php else: ?>

                                            <span
                                                style="color:#999;"
                                            >
                                                No remarks yet.
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>


                                <!-- Description -->

                                <tr>

                                    <td
                                        colspan="5"
                                        style="
                                            padding-top:0;
                                            border-top:0;
                                        "
                                    >

                                        <div
                                            style="
                                                padding:
                                                    0 16px 16px;
                                                color:#777;
                                                font-size:13px;
                                            "
                                        >

                                            <strong>
                                                Details:
                                            </strong>

                                            <?= htmlspecialchars(
                                                $request[
                                                    'description'
                                                ] ?? '—'
                                            ) ?>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </div>


    <?php require '../resources/views/includes/footer.php'; ?>

</div>


<!-- ==========================================================
     NEW REQUEST MODAL
=========================================================== -->

<div
    id="newRequestModal"
    style="
        display:none;
        position:fixed;
        inset:0;
        z-index:9999;
        align-items:center;
        justify-content:center;
        padding:20px;
    "
>

    <!-- Backdrop -->

    <div
        id="newRequestBackdrop"
        style="
            position:absolute;
            inset:0;
            background:rgba(0,0,0,.45);
        "
    ></div>


    <!-- Modal -->

    <div
        style="
            position:relative;
            z-index:1;
            width:100%;
            max-width:600px;
            max-height:90vh;
            overflow:auto;
            background:#fff;
            border-radius:14px;
            box-shadow:
                0 20px 60px rgba(0,0,0,.2);
        "
    >


        <!-- Header -->

        <div
            style="
                display:flex;
                align-items:center;
                justify-content:space-between;
                padding:22px 24px;
                border-bottom:1px solid #eee;
            "
        >

            <div>

                <span
                    style="
                        display:block;
                        font-size:11px;
                        text-transform:uppercase;
                        letter-spacing:.08em;
                        color:#999;
                        margin-bottom:4px;
                    "
                >
                    Employee Services
                </span>

                <h2
                    style="
                        margin:0;
                        font-size:21px;
                    "
                >
                    Submit a Request
                </h2>

            </div>


            <button
                type="button"
                id="closeRequestModal"
                style="
                    border:0;
                    background:none;
                    font-size:20px;
                    cursor:pointer;
                    color:#777;
                "
                aria-label="Close"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>


        <!-- Form -->

        <form
            method="POST"
            action="?page=my-requests-create"
            style="padding:24px;"
        >


            <!-- Request Type -->

            <div style="margin-bottom:20px;">

                <label
                    for="requestType"
                    style="
                        display:block;
                        margin-bottom:8px;
                        font-weight:600;
                    "
                >
                    Request Type
                </label>


                <select
                    id="requestType"
                    name="request_type"
                    required
                    style="
                        width:100%;
                        padding:11px 12px;
                        border:1px solid #ddd;
                        border-radius:8px;
                    "
                >

                    <option value="">
                        Select request type
                    </option>


                    <?php foreach ($requestTypes as $type): ?>

                        <option
                            value="<?= htmlspecialchars($type) ?>"
                        >

                            <?= htmlspecialchars($type) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Subject -->

            <div style="margin-bottom:20px;">

                <label
                    for="requestSubject"
                    style="
                        display:block;
                        margin-bottom:8px;
                        font-weight:600;
                    "
                >
                    Subject
                </label>


                <input
                    type="text"
                    id="requestSubject"
                    name="subject"
                    maxlength="150"
                    required
                    placeholder="Enter the subject of your request"
                    style="
                        width:100%;
                        padding:11px 12px;
                        border:1px solid #ddd;
                        border-radius:8px;
                    "
                >

            </div>


            <!-- Description -->

            <div style="margin-bottom:24px;">

                <label
                    for="requestDescription"
                    style="
                        display:block;
                        margin-bottom:8px;
                        font-weight:600;
                    "
                >
                    Description
                </label>


                <textarea
                    id="requestDescription"
                    name="description"
                    rows="6"
                    maxlength="2000"
                    required
                    placeholder="Explain what you are requesting..."
                    style="
                        width:100%;
                        padding:11px 12px;
                        border:1px solid #ddd;
                        border-radius:8px;
                        resize:vertical;
                    "
                ></textarea>

            </div>


            <!-- Footer -->

            <div
                style="
                    display:flex;
                    justify-content:flex-end;
                    gap:10px;
                "
            >

                <button
                    type="button"
                    id="cancelRequest"
                    style="
                        padding:10px 18px;
                        border:1px solid #ddd;
                        background:#fff;
                        border-radius:8px;
                        cursor:pointer;
                    "
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="btn-primary"
                >

                    <i class="fa-solid fa-paper-plane"></i>

                    Submit Request

                </button>

            </div>

        </form>

    </div>

</div>


<script>
(function () {

    const modal =
        document.getElementById(
            'newRequestModal'
        );

    const openButton =
        document.getElementById(
            'openRequestForm'
        );

    const closeButton =
        document.getElementById(
            'closeRequestModal'
        );

    const cancelButton =
        document.getElementById(
            'cancelRequest'
        );

    const backdrop =
        document.getElementById(
            'newRequestBackdrop'
        );


    if (!modal || !openButton) {
        return;
    }


    function openModal() {

        modal.style.display = 'flex';

        document.body.style.overflow =
            'hidden';
    }


    function closeModal() {

        modal.style.display = 'none';

        document.body.style.overflow =
            '';
    }


    openButton.addEventListener(
        'click',
        openModal
    );


    closeButton?.addEventListener(
        'click',
        closeModal
    );


    cancelButton?.addEventListener(
        'click',
        closeModal
    );


    backdrop?.addEventListener(
        'click',
        closeModal
    );


    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape' &&
                modal.style.display === 'flex'
            ) {
                closeModal();
            }

        }
    );

})();
</script>