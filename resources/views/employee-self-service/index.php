<?php
$pageTitle = "Employee Self-Service";
$pageCSS = "employee-self-service.css";
$pageDescription = "The Employees manage and view their own information.";

if (!isset($_SESSION['user_id'])) {
    header("Location: /hr1/public/?page=login");
    exit;
}
?>

<?php require '../resources/views/includes/header.php'; ?>
<?php require '../resources/views/includes/sidebar.php'; ?>

<div class="main-content">

    <?php require '../resources/views/includes/navbar.php'; ?>

<div class="page-header">

    <div>

        <h1>Employee Self-Service</h1>

        <p>
            Access your personal information, employment details,
            documents, and requests.
        </p>

    </div>

</div>



<!-- ============================= -->
<!-- Employee Profile -->
<!-- ============================= -->

<div class="profile-card">

    <div class="profile-avatar">

        <i class="fa-solid fa-user"></i>

    </div>

    <div class="profile-info">

        <h2>Juan Dela Cruz</h2>

        <p>HR Officer</p>

        <span>Human Resources Department</span>

    </div>

</div>



<!-- ============================= -->
<!-- Quick Summary -->
<!-- ============================= -->

<div class="stats-grid">

    <div class="stat-card">

        <div class="stat-icon blue">

            <i class="fa-solid fa-calendar-check"></i>

        </div>

        <div>

            <h2>12</h2>

            <span>Leave Balance</span>

        </div>

    </div>

    <div class="stat-card">

        <div class="stat-icon green">

            <i class="fa-solid fa-clock"></i>

        </div>

        <div>

            <h2>98%</h2>

            <span>Attendance</span>

        </div>

    </div>

    <div class="stat-card">

        <div class="stat-icon orange">

            <i class="fa-solid fa-file"></i>

        </div>

        <div>

            <h2>5</h2>

            <span>Documents</span>

        </div>

    </div>

    <div class="stat-card">

        <div class="stat-icon purple">

            <i class="fa-solid fa-bell"></i>

        </div>

        <div>

            <h2>2</h2>

            <span>Pending Requests</span>

        </div>

    </div>

</div>



<!-- ============================= -->
<!-- Personal Information -->
<!-- ============================= -->

<div class="content-card">

    <div class="card-header">

        <h2>Personal Information</h2>

        <button class="btn-primary">

            <i class="fa-solid fa-pen"></i>

            Edit Profile

        </button>

    </div>

    <table class="info-table">

        <tr>
            <th>Employee Number</th>
            <td>EMP-0001</td>
        </tr>

        <tr>
            <th>Email</th>
            <td>juan@example.com</td>
        </tr>

        <tr>
            <th>Phone</th>
            <td>09123456789</td>
        </tr>

        <tr>
            <th>Address</th>
            <td>Dasmariñas City, Cavite</td>
        </tr>

    </table>

</div>



<!-- ============================= -->
<!-- Employment Information -->
<!-- ============================= -->

<div class="content-card">

    <div class="card-header">

        <h2>Employment Information</h2>

    </div>

    <table class="info-table">

        <tr>
            <th>Department</th>
            <td>Human Resources</td>
        </tr>

        <tr>
            <th>Position</th>
            <td>HR Officer</td>
        </tr>

        <tr>
            <th>Employment Status</th>
            <td>Regular</td>
        </tr>

        <tr>
            <th>Hire Date</th>
            <td>January 10, 2026</td>
        </tr>

    </table>

</div>



<!-- ============================= -->
<!-- Documents -->
<!-- ============================= -->

<div class="content-card">

    <div class="card-header">

        <h2>My Documents</h2>

    </div>

    <table class="data-table">

        <thead>

        <tr>

            <th>Document</th>
            <th>Status</th>
            <th>Action</th>

        </tr>

        </thead>

        <tbody>

        <tr>

            <td>Employment Contract</td>

            <td>

                <span class="status verified">

                    Available

                </span>

            </td>

            <td>

                <button class="btn-action">

                    <i class="fa-solid fa-download"></i>

                </button>

            </td>

        </tr>

        <tr>

            <td>Company ID</td>

            <td>

                <span class="status verified">

                    Available

                </span>

            </td>

            <td>

                <button class="btn-action">

                    <i class="fa-solid fa-download"></i>

                </button>

            </td>

        </tr>

        </tbody>

    </table>

</div>



<!-- ============================= -->
<!-- Requests -->
<!-- ============================= -->

<div class="content-card">

    <div class="card-header">

        <h2>Requests</h2>

        <button class="btn-primary">

            <i class="fa-solid fa-plus"></i>

            New Request

        </button>

    </div>

    <table class="data-table">

        <thead>

        <tr>

            <th>Request</th>
            <th>Date</th>
            <th>Status</th>

        </tr>

        </thead>

        <tbody>

        <tr>

            <td>Leave Application</td>

            <td>July 18, 2026</td>

            <td>

                <span class="status pending">

                    Pending

                </span>

            </td>

        </tr>

        <tr>

            <td>Certificate of Employment</td>

            <td>July 10, 2026</td>

            <td>

                <span class="status approved">

                    Approved

                </span>

            </td>

        </tr>

        </tbody>

    </table>

</div>



<!-- ============================= -->
<!-- Company Announcements -->
<!-- ============================= -->

<div class="content-card">

    <div class="card-header">

        <h2>Company Announcements</h2>

    </div>

    <div class="announcement-list">

        <div class="announcement-item">

            <h3>Holiday Notice</h3>

            <p>
                Office will be closed on July 27 due to the
                National Holiday.
            </p>

        </div>

        <div class="announcement-item">

            <h3>Monthly Meeting</h3>

            <p>
                Department meeting will be held on July 30
                at 9:00 AM.
            </p>

        </div>

    </div>

    <?php require '../resources/views/includes/footer.php'; ?>
</div>

<?php require '../resources/views/includes/scripts.php'?>