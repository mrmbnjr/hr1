<?php
$pageTitle = "Applicant Management";
$pageCSS = "applicants.css";
$pageDescription = "Manage applicant profiles, resumes, AI screening results, interview schedules, and hiring decisions.";

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
    =========================================== -->

    <section class="applicant-hero">

        <div>
            <span class="hero-tag">
                <i class="fa-solid fa-users"></i>
                Applicant Management
            </span>

            <h1>Applicant Profiles</h1>

            <p>
                Manage applicant information, uploaded resumes,
                AI screening results, interview schedules,
                and hiring decisions.
            </p>
        </div>

    </section>

    <!-- ==========================================
        SEARCH & FILTER
    =========================================== -->

    <section class="filter-card">

        <div class="filter-group">

            <input
                type="text"
                id="searchApplicant"
                placeholder="Search applicant...">

            <select id="filterPosition">
                <option value="">All Positions</option>

                <?php if (!empty($positions)): ?>
                    <?php foreach ($positions as $position): ?>
                        <option value="<?= htmlspecialchars($position['title']); ?>">
                            <?= htmlspecialchars($position['title']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        No Positions found.
                    </div>
                 <?php endif; ?>

            </select>

            <select id="filterStatus">
                <option value="">Application Status</option>
                <option value="Submitted">Submitted</option>
                <option value="AI Screened">AI Screened</option>
                <option value="Shortlisted">Shortlisted</option>
                <option value="Interview">Interview</option>
                <option value="Job Offer">Job Offer</option>
                <option value="Hired">Hired</option>
                <option value="Rejected">Rejected</option>
            </select>

        </div>

    </section>

    <!-- ==========================================
        APPLICANT LIST
    =========================================== -->

    <section class="applicant-grid">

        <?php if (!empty($applicants)): ?>

            <?php foreach ($applicants as $applicant): ?>

                <article class="applicant-card">

                    <!-- Applicant Profile -->

                    <div class="profile-header">

                        <img
                            src="<?= !empty($applicant['photo'])
                                ? htmlspecialchars($applicant['photo'])
                                : '/hr1/public/assets/images/default-user.png'; ?>"
                            alt="Applicant">

                        <div>

                            <h2>
                                <?= htmlspecialchars($applicant['fullname']); ?>
                            </h2>

                            <p>
                                <?= htmlspecialchars($applicant['position']); ?>
                            </p>

                            <span class="status <?= strtolower(str_replace(' ','-',$applicant['application_status'])); ?>">
                                <?= htmlspecialchars($applicant['application_status']); ?>
                            </span>

                        </div>

                    </div>

                    <!-- Personal Information -->

                    <div class="profile-info">

                        <div>
                            <strong>Email</strong>
                            <span><?= htmlspecialchars($applicant['email']); ?></span>
                        </div>

                        <div>
                            <strong>Phone</strong>
                            <span><?= htmlspecialchars($applicant['phone']); ?></span>
                        </div>

                        <div>
                            <strong>Applied</strong>
                            <span>
                                <?= date("F d, Y", strtotime($applicant['application_date'])); ?>
                            </span>
                        </div>

                    </div>

                    <!-- Resume -->

                    <div class="resume-box">

                        <h3>
                            <i class="fa-solid fa-file-pdf"></i>
                            Resume
                        </h3>

                        <?php if (!empty($applicant['resume'])): ?>

                            <a
                                href="<?= htmlspecialchars($applicant['resume']); ?>"
                                target="_blank"
                                class="btn-outline">

                                <i class="fa-solid fa-download"></i>
                                View Resume

                            </a>

                        <?php else: ?>

                            <span class="empty-text">
                                No resume uploaded.
                            </span>

                        <?php endif; ?>

                    </div>

                    <!-- AI Screening -->

                    <div class="screening-box">

                        <div class="screening-header">

                            <h3>
                                <i class="fa-solid fa-robot"></i>
                                AI Screening
                            </h3>

                            <span class="score">
                                <?= $applicant['ai_score']; ?>%
                            </span>

                        </div>

                        <div class="progress">
                            <div
                                class="progress-bar"
                                style="width:<?= $applicant['ai_score']; ?>%;">
                            </div>
                        </div>

                        <p>
                            <?= htmlspecialchars($applicant['screening_summary']); ?>
                        </p>

                    </div>

                    <!-- Interview -->

                    <div class="interview-box">

                        <h3>
                            <i class="fa-solid fa-calendar-days"></i>
                            Interview Schedule
                        </h3>

                        <?php if (!empty($applicant['interview_date'])): ?>

                            <span>
                                <?= date("F d, Y", strtotime($applicant['interview_date'])); ?>
                            </span>

                        <?php else: ?>

                            <span class="empty-text">
                                Not yet scheduled
                            </span>

                        <?php endif; ?>

                    </div>

                    <!-- Hiring Decision -->

                    <div class="decision-box">

                        <h3>
                            <i class="fa-solid fa-scale-balanced"></i>
                            Hiring Decision
                        </h3>

                        <span class="decision <?= strtolower($applicant['hiring_decision']); ?>">
                            <?= htmlspecialchars($applicant['hiring_decision']); ?>
                        </span>

                    </div>

                    <!-- Actions -->

                    <div class="applicant-actions">

                        <a
                            href="?page=view-applicant&id=<?= $applicant['applicant_id']; ?>"
                            class="btn-outline">

                            <i class="fa-solid fa-eye"></i>
                            View

                        </a>

                        <a
                            href="?page=edit-applicant&id=<?= $applicant['applicant_id']; ?>"
                            class="btn-outline">

                            <i class="fa-solid fa-pen"></i>
                            Edit

                        </a>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty-state">

                <i class="fa-solid fa-users-slash"></i>

                <h3>No Applicants Found</h3>

                <p>
                    There are currently no applicants in the system.
                </p>

            </div>

        <?php endif; ?>

    </section>

</div>

<?php require '../resources/views/includes/footer.php'; ?>