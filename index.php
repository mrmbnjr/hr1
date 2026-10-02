<?php

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\RecruitmentController;
use App\Controllers\ApplicantController;
use App\Controllers\OnboardingController;
use App\Controllers\HumanCapitalController;
use App\Controllers\EmployeeRecordsController;
use App\Controllers\EmployeeRequestsController;
use App\Controllers\AssignedRequestsController;
use App\Controllers\UserManagementController;
use App\Controllers\ProfileController;
use App\Services\ActivityLogger;

/*
|--------------------------------------------------------------------------
| BOOTSTRAP
|--------------------------------------------------------------------------
| index.php lives in the project root (the subdomain document root). The
| bootstrap loads Composer, .env, helpers, PHP settings and the session.
*/

define('BASE_PATH', __DIR__);

require BASE_PATH . '/src/bootstrap.php';

$page = $_GET['page'] ?? 'login';

if (isset($_SESSION['user_id']) && $page !== 'login') {
    $activityAction = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
        ? 'Submitted ' . $page . ' action'
        : 'Opened ' . $page . ' page';

    ActivityLogger::log($activityAction, $page);
}


/*
|--------------------------------------------------------------------------
| ROUTING
|--------------------------------------------------------------------------
*/

switch ($page) {

    /*
    |--------------------------------------------------------------------------
    | LOGIN
    |--------------------------------------------------------------------------
    */

    case 'login':

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            (new AuthController())->login();

        } else {

            (new AuthController())->showLogin();

        }

        break;


    /*
    |--------------------------------------------------------------------------
    | OTP VERIFICATION
    |--------------------------------------------------------------------------
    */

    case 'verify-otp':

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            (new AuthController())->verifyOtp();

        } else {

            /*
            * OTP uses the same authentication page.
            * Direct GET requests simply return to the login page.
            */
            (new AuthController())->showVerifyOtp();

        }

        break;


    /*
    |--------------------------------------------------------------------------
    | RESEND OTP
    |--------------------------------------------------------------------------
    */

    case 'resend-otp':

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            (new AuthController())->resendOtp();

        } else {

            header('Content-Type: application/json');

            http_response_code(405);

            echo json_encode([
                'success' => false,
                'message' => 'Invalid request method.'
            ]);

            exit;
        }

        break;

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    case 'dashboard':

        (new DashboardController())->index();

        break;


    case 'dashboard-growth':

        (new DashboardController())->growthData();

        break;

    /*
    |--------------------------------------------------------------------------
    | APPLICANTS
    |--------------------------------------------------------------------------
    */

    case 'applicants':

        (new ApplicantController())->index();

        break;


    case 'apply':
        (new ApplicantController())->apply();
        break;

    case 'submit-application':

        (new ApplicantController())->submitApplication();

        break;

    case 'application-success':

        require BASE_PATH . '/resources/views/public/application-success.php';

        break;

    case 'careers':
        $recruitment = new \App\Models\Recruitment();
        $jobs = $recruitment->getAllJobs();

        // Map database columns to the format expected by job-list.php
        $formattedJobs = [];
        foreach ($jobs as $job) {
            if (
                ($job['status'] ?? '') !== 'Open'
                || (int) ($job['applicants'] ?? 0) >= (int) ($job['max_applicants'] ?? 0)
                || (int) ($job['hired'] ?? 0) >= (int) ($job['vacancies'] ?? 0)
            ) {
                continue;
            }

            $formattedJobs[] = [
                'id' => $job['posting_id'],
                'application_token' => $job['application_token'],
                'title' => $job['title'],
                'department' => $job['department_name'],
                'location' => 'Quezon City', // Database doesn't have location in getAllJobs, defaulting to QC
                'employment_type' => $job['employment_type'],
                'vacancies' => $job['vacancies'],
                'applied_count' => $job['applicants'],
                'applicant_limit' => $job['max_applicants'],
                'closing_date' => $job['application_deadline'],
            ];
        }

        // Use $jobs variable so the view picks it up
        $jobs = $formattedJobs;

        require BASE_PATH . '/resources/views/public/job-list.php';
        break;

    case 'job-details':
        if (!isset($_GET['id'])) {
            header('Content-Type: text/html');
            echo '<p>Invalid job ID.</p>';
            exit;
        }

        $recruitment = new \App\Models\Recruitment();
        $job = $recruitment->getJobById((int)$_GET['id']);

        if (!$job) {
            header('Content-Type: text/html');
            echo '<p>Job not found.</p>';
            exit;
        }

        // Return a snippet suitable for the modal
        $h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        ?>
        <div class="job-detail-content">
            <div class="job-detail-heading">
                <div class="job-detail-avatar">
                    <?= $h(strtoupper(substr($job['title'], 0, 1))) ?>
                </div>
                <div>
                    <h2><?= $h($job['title']) ?></h2>
                    <p class="job-detail-department"><?= $h($job['department_name']) ?></p>
                    <?php if (!empty($job['position_name']) || !empty($job['role_name'])): ?>
                        <p class="job-detail-context">
                            <?= $h($job['position_name'] ?? '') ?>
                            <?= !empty($job['position_name']) && !empty($job['role_name']) ? ' &middot; ' : '' ?>
                            <?= $h($job['role_name'] ?? '') ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <dl class="job__meta job-detail-meta">
                <div><dt>Location</dt><dd>Quezon City</dd></div>
                <div><dt>Type</dt><dd><?= $h($job['employment_type']) ?></dd></div>
                <div><dt>Vacancies</dt><dd><?= (int)$job['vacancies'] ?> <?= (int)$job['vacancies'] === 1 ? 'opening' : 'openings' ?></dd></div>
                <div><dt>Deadline</dt><dd><?= $h(date('M j, Y', strtotime($job['application_deadline']))) ?></dd></div>
            </dl>

            <div class="job-detail-section">
                <h3>Job Description</h3>
                <p><?= $h($job['description'] ?? 'No description provided.') ?></p>
            </div>

            <div class="job-detail-section">
                <h3>Requirements</h3>
                <p><?= $h($job['requirements'] ?? 'No specific requirements provided.') ?></p>
            </div>

            <div class="job-detail-actions">
                <a class="btn btn--primary" href="index.php?page=apply&token=<?= (int)$job['posting_id'] ?>">Apply now <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
            </div>
        </div>
        <?php
        exit;

    case 'review':

        (new ApplicantController())->review();

        break;

    case 'download-resume':

        (new ApplicantController())->downloadResume();

        break;

    case 'download-academic-document':

        (new ApplicantController())->downloadAcademicDocument();

        break;

    case 'evaluate-resume':

        (new ApplicantController())->evaluateResume();

        break;

    case 'evaluate-academic-document':

        (new ApplicantController())->evaluateAcademicDocument();

        break;


    case 'scheduleInterview':

        (new ApplicantController())->scheduleInterview();

        break;


    case 'hire-applicant':

        (new ApplicantController())->hire();

        break;

    case 'reject-applicant':

        (new ApplicantController())->reject();

        break;

    /*
    |--------------------------------------------------------------------------
    | RECRUITMENT
    |--------------------------------------------------------------------------
    */

    case 'recruitment':

        (new RecruitmentController())->recruitment();

        break;

    case 'create':

        (new RecruitmentController())->create();

        break;

    case 'store':

        (new RecruitmentController())->store();

        break;

    case 'close':

        (new RecruitmentController())->close();

        break;

    case 'delete':

        (new RecruitmentController())->delete();

        break;

    case 'update':

        (new RecruitmentController())->update();

        break;

    case 'edit':

        (new RecruitmentController())->edit();

        break;

    /*
    |--------------------------------------------------------------------------
    | RECRUITMENT STATISTICS
    |--------------------------------------------------------------------------
    */

    case 'statistics':

        (new RecruitmentController())->statistics();

        break;

    /*
    |--------------------------------------------------------------------------
    | ONBOARDING
    |--------------------------------------------------------------------------
    */

    case 'onboarding':

        (new OnboardingController())->onboarding();

        break;


    case 'onboarding-view':

        (new OnboardingController())->onboardingView();

        break;

    case 'onboarding-request-document':

        (new OnboardingController())->requestDocument();

        break;

    case 'onboarding-update-document':

        (new OnboardingController())->updateDocument();

        break;

    case 'onboarding-delete-document':

        (new OnboardingController())->deleteDocument();

        break;

    case 'onboarding-verify-document':

        (new OnboardingController())->verifyDocument();

        break;

    case 'onboarding-reject-document':

        (new OnboardingController())->rejectDocument();

        break;

    /*
    |--------------------------------------------------------------------------
    | HUMAN CAPITAL
    |--------------------------------------------------------------------------
    */

    case 'human-capital':

        (new HumanCapitalController())->index();

        break;

    case 'save-department':

        (new HumanCapitalController())->saveDepartment();

        break;

    case 'delete-department':

        (new HumanCapitalController())->deleteDepartment();

        break;

    case 'create-position':

        (new HumanCapitalController())->createPosition();

        break;

    case 'delete-position':

        (new HumanCapitalController())->deletePosition();

        break;

    case 'get-department':

        (new HumanCapitalController())->getDepartment();

        break;

    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE RECORDS
    |--------------------------------------------------------------------------
    */

    case 'employee-records':

        (new EmployeeRecordsController())->employeeRecords();

        break;


    case 'view':

        (new EmployeeRecordsController())->view();

        break;

    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE REQUESTS — HR / ADMIN / MANAGER
    |--------------------------------------------------------------------------
    */

    case 'employee-requests':

        (new EmployeeRequestsController())->employeeRequests();

        break;

    /*
    |--------------------------------------------------------------------------
    | USER MANAGEMENT
    |--------------------------------------------------------------------------
    */

    case 'user-management':

        (new UserManagementController())->index();

        break;

    case 'user-management-action':

        (new UserManagementController())->action();

        break;

    /*
    |--------------------------------------------------------------------------
    | PROFILE — EVERY SIGNED-IN ROLE
    |--------------------------------------------------------------------------
    */

    case 'profile':

        (new ProfileController())->index();

        break;

    case 'profile-action':

        (new ProfileController())->update();

        break;

    /*
     * First-login password change. AuthController redirects here after a user
     * with `must_change_password = 1` signs in; it opens the profile page on
     * the Account & Security tab.
     */

    case 'change-password':

        (new ProfileController())->changePassword();

        break;

    case 'my-requests-update':

        (new EmployeeRequestsController())->updateMyRequest();

        break;


    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE — MY REQUESTS
    |--------------------------------------------------------------------------
    */

    case 'my-requests':

        (new EmployeeRequestsController())->myRequests();

        break;

    case 'my-requests-create':

        (new EmployeeRequestsController())->createMyRequest();

        break;

    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE — ASSIGNED REQUESTS
    |--------------------------------------------------------------------------
    |
    | Requests / required actions created by HR or Admin and assigned to
    | the signed-in employee. Distinct from My Requests above.
    |
    | Backed by HR's onboarding document requests, so HR creates, verifies
    | and rejects them from the existing Onboarding module — there is no
    | separate HR page for this feature.
    |
    */

    case 'assigned-requests':

        (new AssignedRequestsController())->index();

        break;

    case 'assigned-requests-submit':

        (new AssignedRequestsController())->submit();

        break;

    case 'assigned-requests-download':

        (new AssignedRequestsController())->download();

        break;

    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    case 'logout':

        (new AuthController())->logout();

        break;

    /*
    |--------------------------------------------------------------------------
    | DEFAULT
    |--------------------------------------------------------------------------
    */

    default:

        http_response_code(404);

        require BASE_PATH . '/resources/views/misc/soon.php';

        break;
}
