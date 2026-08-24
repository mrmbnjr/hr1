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
use App\Controllers\ProfileController;

session_start();

require '../vendor/autoload.php';

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


    case 'review':

        (new ApplicantController())->review();

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