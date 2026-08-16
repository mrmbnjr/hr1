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

switch ($page) {

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new LoginController())->login();
        } else {
            require '../resources/views/auth/login.php';
        }
        break;

    case 'dashboard':
        (new DashboardController())->index();
        break;

    case 'dashboard-growth':
        (new DashboardController())->growthData();
        break;

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

    case 'onboarding':
        (new OnboardingController())->onboarding();
        break;

    case 'onboarding-view':
        (new OnboardingController())->onboardingView();
        break;

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

    case 'employee-records':
        (new EmployeeRecordsController())->employeeRecords();
        break;

    case 'view':
        (new EmployeeRecordsController())->view();
        break;

    case 'employee-requests':
        (new EmployeeRequestsController())->employeeRequests();
        break;

    case 'logout':
        (new LogoutController())->logout();
        break;

    default:
        require '../resources/views/misc/soon.php';
        break;
}