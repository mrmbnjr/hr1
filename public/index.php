<?php

use App\Controllers\LoginController;
use App\Controllers\DashboardController;
use App\Controllers\LogoutController;
use App\Controllers\RecruitmentController;
use App\Controllers\ApplicantController;
use App\Controllers\OnboardingController;
use App\Controllers\HumanCapitalController;
use App\Controllers\EmployeeRecordsController;
use App\Controllers\EmployeeRequestsController;
use App\Controllers\RecruitmentStatisticsController;

session_start();

require '../vendor/autoload.php';

$envFile = dirname(__DIR__) . '/.env';

if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (is_array($lines)) {
        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);

            if (count($parts) !== 2) {
                continue;
            }

            $name = trim($parts[0]);
            $value = trim($parts[1]);

            if ($name === '') {
                continue;
            }

            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

$page = $_GET['page'] ?? 'login';

$uri = $_SERVER['REQUEST_URI'];


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

            (new LoginController())->login();

        } else {

            require '../resources/views/auth/login.php';
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

        require '../resources/views/public/application-success.php';

        break;
        
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


    case 'employee-requests-update':

        (new EmployeeRequestsController())->update();

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
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    case 'logout':

        (new LogoutController())->logout();

        break;


    /*
    |--------------------------------------------------------------------------
    | DEFAULT
    |--------------------------------------------------------------------------
    */

    default:

        http_response_code(404);

        require '../resources/views/misc/soon.php';

        break;
}