<?php

namespace App\Controllers;

use App\Models\Dashboard;

class DashboardController
{
    private Dashboard $dashboard;


    public function __construct()
    {
        $this->dashboard = new Dashboard();
    }



    /*
    |--------------------------------------------------------------------------
    | Dashboard Page
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $stats = $this->dashboard->getQuickStats();


        $newEmployees =
            $this->dashboard->getNewEmployees(5);


        $recentActivities =
            $this->dashboard->getRecentActivities(5);



        /*
        |--------------------------------------------------------------------------
        | Initial Chart State
        |--------------------------------------------------------------------------
        */

        $view = $_GET['view'] ?? 'year';


        $year = isset($_GET['year'])
            ? (int) $_GET['year']
            : date('Y');


        $month = isset($_GET['month'])
            ? (int) $_GET['month']
            : date('n');


        $weekStart = $_GET['weekStart']
            ?? date('Y-m-d');



        $chart = $this->dashboard->getApplicantChart(
            $view,
            $year,
            $month,
            $weekStart
        );



        $growthLabels = $chart['labels'];

        $growthData = $chart['data'];

        $chartPeriod = $chart['period'];

        $chartSubtitle = $chart['subtitle'];



        require '../resources/views/dashboard/index.php';
    }





    /*
    |--------------------------------------------------------------------------
    | AJAX Chart Data
    |--------------------------------------------------------------------------
    */

    public function growthData()
    {

        $view = $_GET['view'] ?? 'year';



        $year = isset($_GET['year'])
            ? (int) $_GET['year']
            : date('Y');



        $month = isset($_GET['month'])
            ? (int) $_GET['month']
            : date('n');



        $weekStart =
            $_GET['weekStart']
            ?? date('Y-m-d');




        $chart = $this->dashboard->getApplicantChart(
            $view,
            $year,
            $month,
            $weekStart
        );



        header(
            'Content-Type: application/json'
        );



        echo json_encode([

            'labels' =>
                $chart['labels'],


            'data' =>
                $chart['data'],


            'period' =>
                $chart['period'],


            'subtitle' =>
                $chart['subtitle']

        ]);



        exit;

    }
}