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
    APPLICANT MANAGER
========================================== -->

<?php

$applicants = $applicants ?? [];

$statusCounts = [];

foreach ($applicants as $applicant) {

    $status = $applicant['application_status'];

    if (!isset($statusCounts[$status])) {
        $statusCounts[$status] = 0;
    }

    $statusCounts[$status]++;
}

$currentStatus = $_GET['status'] ?? 'All';

?>

<section class="applicant-manager">

    <aside class="pipeline-sidebar">

        <h2>Hiring Pipeline</h2>

        <a
            href="?page=applicants"
            class="<?= $currentStatus == 'All' ? 'active' : '' ?>">

            All Applicants

            <span><?= count($applicants) ?></span>

        </a>

        <?php

        $statuses = [

            "Submitted",
            "AI Screened",
            "Shortlisted",
            "Interview",
            "Job Offer",
            "Hired",
            "Rejected"

        ];

        foreach($statuses as $status):

        ?>

        <a

            href="?page=applicants&status=<?= urlencode($status) ?>"

            class="<?= $currentStatus == $status ? 'active' : '' ?>">

            <?= htmlspecialchars($status) ?>

            <span><?= $statusCounts[$status] ?? 0 ?></span>

        </a>

        <?php endforeach; ?>

    </aside>

    <div class="applicant-list">

        <div class="list-header">

            <div class="col-name">Applicant</div>

            <div class="col-position">Position</div>

            <div class="col-score">AI Match</div>

            <div class="col-status">Status</div>

            <div class="col-actions">Actions</div>

        </div>

        <?php

        $found = false;

        foreach($applicants as $applicant):

            if(

                $currentStatus != "All"

                &&

                $applicant['application_status'] != $currentStatus

            ){

                continue;

            }

            $found = true;

        ?>

        <div class="applicant-row">

            <div class="col-name">

                <strong>

                    <?= htmlspecialchars($applicant['fullname']) ?>

                </strong>

            </div>

            <div class="col-position">

                <?= htmlspecialchars($applicant['position']) ?>

            </div>

            <div class="col-score">

                <?= (int)$applicant['ai_score'] ?>%

            </div>

            <div class="col-status">

                <span class="status-badge">

                    <?= htmlspecialchars($applicant['application_status']) ?>

                </span>

            </div>

            <div class="col-actions">

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

                <?php if($applicant['application_status']=="Submitted"): ?>

                    <a

                        href="?page=schedule-interview&id=<?= $applicant['application_id'] ?>"

                        class="btn-primary">

                        Schedule

                    </a>

                <?php elseif($applicant['application_status']=="Interview"): ?>

                    <a

                        href="?page=job-offer&id=<?= $applicant['application_id'] ?>"

                        class="btn-success">

                        Offer

                    </a>

                <?php elseif($applicant['application_status']=="Job Offer"): ?>

                    <a

                        href="?page=onboarding&id=<?= $applicant['application_id'] ?>"

                        class="btn-success">

                        Onboard

                    </a>

                <?php endif; ?>

            </div>

        </div>

        <?php endforeach; ?>

        <?php if(!$found): ?>

        <div class="empty-column">

            No applicants found.

        </div>

        <?php endif; ?>

    </div>

</section>
</div>

<?php require '../resources/views/includes/footer.php'; ?>