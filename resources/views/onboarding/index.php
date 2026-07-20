<?php
$pageTitle = "Onboarding";
$pageCSS = "onboarding.css";
$pageDescription = "Complete your onboarding process for your RAM-YUM Store account — Korean and Japanese Store.";

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
        <h1>New Hire Onboarding</h1>
        <p>Manage orientation, documents, and onboarding progress.</p>
    </div>


    <!-- STATUS CARDS -->
    <div class="status-cards">

        <div class="status-card">
            <i class="fa-solid fa-user-plus"></i>
            <div>
                <h3><?= $totalNewHires ?? 0 ?></h3>
                <p>New Hires</p>
            </div>
        </div>


        <div class="status-card">
            <i class="fa-solid fa-clock"></i>
            <div>
                <h3><?= $pending ?? 0 ?></h3>
                <p>Pending</p>
            </div>
        </div>


        <div class="status-card">
            <i class="fa-solid fa-spinner"></i>
            <div>
                <h3><?= $ongoing ?? 0 ?></h3>
                <p>Ongoing</p>
            </div>
        </div>


        <div class="status-card">
            <i class="fa-solid fa-circle-check"></i>
            <div>
                <h3><?= $completed ?? 0 ?></h3>
                <p>Completed</p>
            </div>
        </div>

    </div>



    <!-- NEW HIRE TABLE -->

    <div class="content-card">

        <div class="card-header">
            <h2>New Hire Onboarding</h2>
        </div>


        <table>

            <thead>
                <tr>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Start Date</th>
                    <th>Orientation</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>


            <tbody>

            <?php if (!empty($onboardingList)): ?>
            <?php foreach($onboardingList as $hire): ?>

                <tr>

                    <td>
                        <?= $hire['first_name'] . " " . $hire['last_name']; ?>
                    </td>


                    <td>
                        <?= $hire['position']; ?>
                    </td>


                    <td>
                        <?= $hire['start_date']; ?>
                    </td>


                    <td>
                        <?= $hire['orientation_date']; ?>
                    </td>


                    <td>

                        <span class="status <?= strtolower($hire['onboarding_status']); ?>">
                            <?= $hire['onboarding_status']; ?>
                        </span>

                    </td>


                    <td>

                        <a href="?page=onboarding-view&id=<?= $hire['onboarding_id']; ?>"
                           class="btn-view">
                            View
                        </a>

                    </td>

                </tr>


            <?php endforeach; ?>

            <?php else: ?>
                <div class="empty-state">
                    No Applicants found.
                </div>
            <?php endif; ?>
            </tbody>


        </table>

    </div>
</div>
<?php require '../resources/views/includes/footer.php'; ?>