<?php
$pageTitle = "Human Capital Management";
$pageCSS = "human-capital.css";
$pageDescription = "Manage the Organization for your RAM-YUM Store account — Korean and Japanese Store.";

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
        <h1>Human Capital Management</h1>
        <p>
            Manage the organization's departments, job positions,
            employment types, and organizational structure.
        </p>
    </div>

    <button class="btn-primary">
        <i class="fa-solid fa-plus"></i>
        Add Department
    </button>
</div>


<!-- ============================= -->
<!-- Quick Statistics -->
<!-- ============================= -->

<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fa-solid fa-building"></i>
        </div>

        <div>
            <h2>12</h2>
            <span>Departments</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fa-solid fa-briefcase"></i>
        </div>

        <div>
            <h2>34</h2>
            <span>Job Positions</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fa-solid fa-users"></i>
        </div>

        <div>
            <h2>152</h2>
            <span>Employees</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fa-solid fa-sitemap"></i>
        </div>

        <div>
            <h2>4</h2>
            <span>Employment Types</span>
        </div>
    </div>

</div>



<!-- ============================= -->
<!-- Department Management -->
<!-- ============================= -->

<div class="content-card">

    <div class="card-header">
        <h2>Departments</h2>

        <button class="btn-primary">
            <i class="fa-solid fa-plus"></i>
            New Department
        </button>
    </div>

    <table class="data-table">

        <thead>

        <tr>
            <th>Department</th>
            <th>Description</th>
            <th>Positions</th>
            <th>Employees</th>
            <th>Action</th>
        </tr>

        </thead>

        <tbody>

        <tr>

            <td>Human Resources</td>

            <td>
                Recruitment and employee management.
            </td>

            <td>5</td>

            <td>18</td>

            <td>

                <button class="btn-action">
                    <i class="fa-solid fa-pen"></i>
                </button>

                <button class="btn-action danger">
                    <i class="fa-solid fa-trash"></i>
                </button>

            </td>

        </tr>

        </tbody>

    </table>

</div>



<!-- ============================= -->
<!-- Job Positions -->
<!-- ============================= -->

<div class="content-card">

    <div class="card-header">

        <h2>Job Positions</h2>

        <button class="btn-primary">
            <i class="fa-solid fa-plus"></i>
            Add Position
        </button>

    </div>

    <table class="data-table">

        <thead>

        <tr>

            <th>Position</th>
            <th>Department</th>
            <th>Employment Type</th>
            <th>Vacancies</th>
            <th>Status</th>
            <th>Action</th>

        </tr>

        </thead>

        <tbody>

        <tr>

            <td>HR Officer</td>

            <td>Human Resources</td>

            <td>Full-Time</td>

            <td>2</td>

            <td>
                <span class="status open">
                    Open
                </span>
            </td>

            <td>

                <button class="btn-action">
                    <i class="fa-solid fa-pen"></i>
                </button>

                <button class="btn-action danger">
                    <i class="fa-solid fa-trash"></i>
                </button>

            </td>

        </tr>

        </tbody>

    </table>

</div>



<!-- ============================= -->
<!-- Employment Types -->
<!-- ============================= -->

<div class="content-card">

    <div class="card-header">
        <h2>Employment Types</h2>
    </div>

    <div class="employment-grid">

        <div class="employment-card">

            <i class="fa-solid fa-user-tie"></i>

            <h3>Full-Time</h3>

            <span>18 Positions</span>

        </div>

        <div class="employment-card">

            <i class="fa-solid fa-user-clock"></i>

            <h3>Part-Time</h3>

            <span>6 Positions</span>

        </div>

        <div class="employment-card">

            <i class="fa-solid fa-file-contract"></i>

            <h3>Contract</h3>

            <span>5 Positions</span>

        </div>

        <div class="employment-card">

            <i class="fa-solid fa-graduation-cap"></i>

            <h3>Internship</h3>

            <span>3 Positions</span>

        </div>

    </div>

</div>



<!-- ============================= -->
<!-- Organization Structure -->
<!-- ============================= -->

<div class="content-card">

    <div class="card-header">
        <h2>Organization Structure</h2>
    </div>

    <div class="organization-preview">

        <div class="org-box">
            Company
        </div>

        <div class="org-arrow">
            ↓
        </div>

        <div class="org-row">

            <div class="org-box">
                Human Resources
            </div>

            <div class="org-box">
                Finance
            </div>

            <div class="org-box">
                Operations
            </div>

            <div class="org-box">
                IT
            </div>

        </div>

    </div>

    <?php require '../resources/views/includes/footer.php'; ?>
</div>

<?php require '../resources/views/includes/scripts.php'?>