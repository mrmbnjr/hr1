<?php
$pageTitle = "Employee Records";
$pageCSS = "employee-records.css";
$pageDescription = "The Employee Records module should manage the employees themselves after they've been hired.";

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
        <h1>Employee Records</h1>
        <p>
            View and manage employee information, employment status,
            and personnel records.
        </p>
    </div>

    <button class="btn-primary">
        <i class="fa-solid fa-user-plus"></i>
        Add Employee
    </button>

</div>



<!-- ========================= -->
<!-- Statistics -->
<!-- ========================= -->

<div class="stats-grid">

    <div class="stat-card">

        <div class="stat-icon blue">
            <i class="fa-solid fa-users"></i>
        </div>

        <div>
            <h2>152</h2>
            <span>Total Employees</span>
        </div>

    </div>

    <div class="stat-card">

        <div class="stat-icon green">
            <i class="fa-solid fa-user-check"></i>
        </div>

        <div>
            <h2>120</h2>
            <span>Regular</span>
        </div>

    </div>

    <div class="stat-card">

        <div class="stat-icon orange">
            <i class="fa-solid fa-user-clock"></i>
        </div>

        <div>
            <h2>24</h2>
            <span>Probationary</span>
        </div>

    </div>

    <div class="stat-card">

        <div class="stat-icon purple">
            <i class="fa-solid fa-file-contract"></i>
        </div>

        <div>
            <h2>8</h2>
            <span>Contract</span>
        </div>

    </div>

</div>



<!-- ========================= -->
<!-- Filters -->
<!-- ========================= -->

<div class="filter-card">

    <div class="filter-group">

        <input
            type="text"
            placeholder="Search employee..."
        >

        <select>

            <option>All Departments</option>
            <option>Human Resources</option>
            <option>Finance</option>
            <option>Operations</option>
            <option>IT</option>

        </select>

        <select>

            <option>All Status</option>
            <option>Regular</option>
            <option>Probationary</option>
            <option>Contract</option>

        </select>

    </div>

</div>



<!-- ========================= -->
<!-- Employee Table -->
<!-- ========================= -->

<div class="content-card">

    <div class="card-header">

        <h2>Employee Directory</h2>

    </div>

    <table class="data-table">

        <thead>

        <tr>

            <th>Employee No.</th>
            <th>Name</th>
            <th>Department</th>
            <th>Position</th>
            <th>Status</th>
            <th>Hire Date</th>
            <th>Action</th>

        </tr>

        </thead>

        <tbody>

        <tr>

            <td>EMP-0001</td>

            <td>Juan Dela Cruz</td>

            <td>Human Resources</td>

            <td>HR Officer</td>

            <td>
                <span class="status regular">
                    Regular
                </span>
            </td>

            <td>Jan 10, 2026</td>

            <td>

                <button class="btn-action">
                    <i class="fa-solid fa-eye"></i>
                </button>

                <button class="btn-action">
                    <i class="fa-solid fa-pen"></i>
                </button>

            </td>

        </tr>

        <tr>

            <td>EMP-0002</td>

            <td>Maria Santos</td>

            <td>Finance</td>

            <td>Accountant</td>

            <td>
                <span class="status probation">
                    Probationary
                </span>
            </td>

            <td>Feb 14, 2026</td>

            <td>

                <button class="btn-action">
                    <i class="fa-solid fa-eye"></i>
                </button>

                <button class="btn-action">
                    <i class="fa-solid fa-pen"></i>
                </button>

            </td>

        </tr>

        </tbody>

    </table>

</div>



<!-- ========================= -->
<!-- Recently Hired -->
<!-- ========================= -->

<div class="content-card">

    <div class="card-header">

        <h2>Recently Hired Employees</h2>

    </div>

    <table class="data-table">

        <thead>

        <tr>

            <th>Employee</th>
            <th>Position</th>
            <th>Department</th>
            <th>Hire Date</th>

        </tr>

        </thead>

        <tbody>

        <tr>

            <td>John Reyes</td>
            <td>Software Developer</td>
            <td>IT</td>
            <td>July 18, 2026</td>

        </tr>

        </tbody>

    </table>

</div>

<?php require '../resources/views/includes/footer.php'; ?>