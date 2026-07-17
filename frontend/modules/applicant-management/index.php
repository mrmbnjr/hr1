<?php

require_once "../../../server/bootstrap.php";
require_once "../../../server/middleware/auth.php";
require_once "../../../server/middleware/role.php";

requireRole([
    'admin',
    'hr',
    'manager'
]);

$pageTitle = "Applicant Management";

$pageStyles = [
    "layout.css",
    "applicant-management.css"
];

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$position = $_GET['position'] ?? '';
$status = $_GET['status'] ?? '';

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

$totalApplicants = 0;
$newApplications = 0;
$interviewApplicants = 0;
$hiredApplicants = 0;

/* Total Applicants */

$query = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM applications
");

if ($query) {
    $totalApplicants = mysqli_fetch_assoc($query)['total'];
}

/* Submitted */

$query = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM applications
    WHERE application_status='Submitted'
");

if ($query) {
    $newApplications = mysqli_fetch_assoc($query)['total'];
}

/* Interview */

$query = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM applications
    WHERE application_status='Interview'
");

if ($query) {
    $interviewApplicants = mysqli_fetch_assoc($query)['total'];
}

/* Hired */

$query = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM applications
    WHERE application_status='Hired'
");

if ($query) {
    $hiredApplicants = mysqli_fetch_assoc($query)['total'];
}

/*
|--------------------------------------------------------------------------
| Position Dropdown
|--------------------------------------------------------------------------
*/

$positions = mysqli_query($conn,"
    SELECT
        position_id,
        title
    FROM job_positions
    WHERE status='Open'
    ORDER BY title ASC
");

/*
|--------------------------------------------------------------------------
| Applicant List
|--------------------------------------------------------------------------
*/

$sql = "

SELECT

    applications.application_id,

    applications.application_status,

    applications.applied_at,

    applicants.applicant_id,

    applicants.first_name,

    applicants.middle_name,

    applicants.last_name,

    job_positions.title,

    ai_screening.match_score,

    ai_screening.recommendation

FROM applications

INNER JOIN applicants

ON applications.applicant_id = applicants.applicant_id

INNER JOIN job_positions

ON applications.position_id = job_positions.position_id

LEFT JOIN ai_screening

ON applications.application_id = ai_screening.application_id

WHERE 1=1

";

if (!empty($search)) {

    $search = mysqli_real_escape_string($conn,$search);

    $sql .= "

    AND (

        applicants.first_name LIKE '%$search%'

        OR applicants.last_name LIKE '%$search%'

        OR applicants.email LIKE '%$search%'

    )

    ";

}

if (!empty($position)) {

    $position = (int)$position;

    $sql .= "

    AND job_positions.position_id=$position

    ";

}

if (!empty($status)) {

    $status = mysqli_real_escape_string($conn,$status);

    $sql .= "

    AND applications.application_status='$status'

    ";

}

$sql .= "

ORDER BY applications.applied_at DESC

";

$applications = mysqli_query($conn,$sql);

include "../../includes/header.php";

?>

<div class="layout">

    <?php include "../../includes/sidebar.php"; ?>

    <main class="main-content">

        <?php include "../../includes/navbar.php"; ?>

        <section class="page-content">

            <div class="page-header">

                <div>

                    <h1>Applicant Management</h1>

                    <p>

                        Manage applicants, review submitted applications,
                        monitor AI screening results, and track recruitment progress.

                    </p>

                </div>

            </div>

            <!-- Statistics -->

            <div class="stats-grid">

                <div class="stat-card">

                    <h3>Total Applicants</h3>

                    <span><?= $totalApplicants ?></span>

                </div>

                <div class="stat-card">

                    <h3>New Applications</h3>

                    <span><?= $newApplications ?></span>

                </div>

                <div class="stat-card">

                    <h3>Interview</h3>

                    <span><?= $interviewApplicants ?></span>

                </div>

                <div class="stat-card">

                    <h3>Hired</h3>

                    <span><?= $hiredApplicants ?></span>

                </div>

            </div>

            <!-- Toolbar -->

            <form method="GET" class="toolbar">

                <input
                    type="text"
                    name="search"
                    placeholder="Search applicant..."
                    value="<?= htmlspecialchars($search) ?>"
                >

                <select name="position">

                    <option value="">All Positions</option>

                    <?php while($row=mysqli_fetch_assoc($positions)): ?>

                        <option
                            value="<?= $row['position_id']; ?>"
                            <?= ($position==$row['position_id']) ? 'selected' : '' ?>
                        >

                            <?= htmlspecialchars($row['title']); ?>

                        </option>

                    <?php endwhile; ?>

                </select>

                <select name="status">

                    <option value="">All Status</option>

                    <option value="Submitted">Submitted</option>
                    <option value="AI Screened">AI Screened</option>
                    <option value="Shortlisted">Shortlisted</option>
                    <option value="Interview">Interview</option>
                    <option value="Job Offer">Job Offer</option>
                    <option value="Hired">Hired</option>
                    <option value="Rejected">Rejected</option>

                </select>

                <button type="submit">

                    Search

                </button>

            </form>

            <!-- Table -->

            <div class="table-card">

                <table>

                    <thead>

                        <tr>

                            <th>Applicant</th>

                            <th>Position Applied</th>

                            <th>Applied Date</th>

                            <th>AI Score</th>

                            <th>Status</th>

                            <th>Actions</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if(mysqli_num_rows($applications)>0): ?>

                        <?php while($row=mysqli_fetch_assoc($applications)): ?>

                        <tr>

                            <td>

                                <?= htmlspecialchars(

                                    $row['first_name'].' '.
                                    $row['middle_name'].' '.
                                    $row['last_name']

                                ); ?>

                            </td>

                            <td>

                                <?= htmlspecialchars($row['title']); ?>

                            </td>

                            <td>

                                <?= date("M d, Y",strtotime($row['applied_at'])); ?>

                            </td>

                            <td>

                                <?php

                                if($row['match_score']){

                                    echo $row['match_score'].'%';

                                }else{

                                    echo '-';

                                }

                                ?>

                            </td>

                            <td>

                                <?= htmlspecialchars($row['application_status']); ?>

                            </td>

                            <td>

                                <a
                                    href="view.php?id=<?= $row['application_id']; ?>"
                                    class="btn-view"
                                >

                                    View

                                </a>

                            </td>

                        </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="6" class="empty-state">

                                <h3>No applicants found.</h3>

                                <p>

                                    Applications submitted through the public
                                    application page will appear here.

                                </p>

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

<?php include "../../includes/footer.php"; ?>