<?php

$pageTitle = "Applicants";
$pageCSS = "applicants.css";
$pageDescription = "Review applicants and manage the hiring process.";

if (!isset($_SESSION['user_id'])) {
    header("Location: /hr1/public/?page=login");
    exit;
}

?>

<?php require '../resources/views/includes/header.php'; ?>
<?php require '../resources/views/includes/sidebar.php'; ?>

<div class="main-content">

<?php require '../resources/views/includes/navbar.php'; ?>

<!-- ==========================================
    HERO
========================================== -->

<section class="page-hero">

    <div>
        <span class="hero-tag">
            👥 Recruitment
        </span>

        <h1>Applicants</h1>

        <p>
            Review applicants, schedule interviews, and move candidates
            through the hiring process.
        </p>
    </div>

</section>

<!-- ==========================================
    STATISTICS
========================================== -->

<section class="stats-grid">

    <article class="stat-card">
        <h2><?= $stats['total'] ?? 0 ?></h2>
        <span>Total Applicants</span>
    </article>

    <article class="stat-card">
        <h2><?= $stats['submitted'] ?? 0 ?></h2>
        <span>Applied</span>
    </article>

    <article class="stat-card">
        <h2><?= $stats['interview'] ?? 0 ?></h2>
        <span>Interview</span>
    </article>

    <article class="stat-card">
        <h2><?= $stats['offered'] ?? 0 ?></h2>
        <span>Job Offers</span>
    </article>

</section>

<!-- ==========================================
    FILTERS
========================================== -->

<section class="filter-card">

    <div class="filter-group">

        <input
            type="text"
            placeholder="Search applicant...">

        <?php $positions = $positions ?? []; ?>
        <select>

            <option>All Job Postings</option>

            <?php foreach($positions as $position): ?>

                <option>
                    <?= htmlspecialchars($position['title']) ?>
                </option>

            <?php endforeach; ?>

        </select>

        <select>

            <option>All AI Recommendations</option>
            <option>Highly Recommended</option>
            <option>Recommended</option>
            <option>Consider</option>
            <option>Not Recommended</option>

        </select>

    </div>

</section>

<!-- ==========================================
    KANBAN BOARD
========================================== -->

<section class="kanban-board">

<?php

$applicants = $applicants ?? [];

$columns = [

    "Submitted" => "Applied",

    "Interview" => "Interview",

    "Job Offer" => "Offered",

    "Rejected" => "Rejected"

];

?>

<?php foreach($columns as $status => $title): ?>

<div class="kanban-column">

    <div class="kanban-header">

        <h2><?= $title ?></h2>

    </div>

    <?php

    $found = false;

    foreach($applicants as $applicant):

        if($applicant['application_status'] != $status){
            continue;
        }

        $found = true;

    ?>

    <article class="candidate-card">

        <div class="candidate-header">

            <img
                src="<?= !empty($applicant['photo'])
                    ? htmlspecialchars($applicant['photo'])
                    : '/hr1/public/assets/images/default-user.png'; ?>">

            <div>

                <h3>
                    <?= htmlspecialchars($applicant['fullname']) ?>
                </h3>

                <span>
                    <?= htmlspecialchars($applicant['position']) ?>
                </span>

            </div>

        </div>

        <div class="candidate-score">

            <strong>
                AI Match
            </strong>

            <span>
                <?= $applicant['ai_score'] ?>%
            </span>

        </div>

        <div class="candidate-actions">

            <a
                href="?page=view-applicant&id=<?= $applicant['applicant_id'] ?>"
                class="btn-outline">

                <i class="fa-solid fa-eye"></i>

                View

            </a>

            <?php if(!empty($applicant['resume'])): ?>

            <a
                href="<?= htmlspecialchars($applicant['resume']) ?>"
                target="_blank"
                class="btn-outline">

                <i class="fa-solid fa-file-pdf"></i>

                Resume

            </a>

            <?php endif; ?>

            <?php if($status == "Submitted"): ?>

                <a
                    href="?page=schedule-interview&id=<?= $applicant['application_id'] ?>"
                    class="btn-primary">

                    <i class="fa-solid fa-calendar-days"></i>

                    Schedule

                </a>

            <?php elseif($status == "Interview"): ?>

                <a
                    href="?page=job-offer&id=<?= $applicant['application_id'] ?>"
                    class="btn-success">

                    <i class="fa-solid fa-handshake"></i>

                    Offer

                </a>

            <?php elseif($status == "Job Offer"): ?>

                <a
                    href="?page=onboarding&id=<?= $applicant['application_id'] ?>"
                    class="btn-success">

                    <i class="fa-solid fa-user-check"></i>

                    Onboard

                </a>

            <?php endif; ?>

        </div>

    </article>

    <?php endforeach; ?>

    <?php if(!$found): ?>

        <div class="empty-column">

            No applicants.

        </div>

    <?php endif; ?>

</div>

<?php endforeach; ?>

</section>

</div>

<?php require '../resources/views/includes/footer.php'; ?>