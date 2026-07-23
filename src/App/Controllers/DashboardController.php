<?php

namespace App\Controllers;

use App\Models\Dashboard;

class DashboardController {

    private Dashboard $dashboard;

    public function __construct()
    {
        $this->dashboard = new Dashboard();
    }

    public function index()
    {
        $growth = $this->dashboard->getEmployeeGrowth();

        $growthLabels = [];
        $growthData = [];

        $total = 0;

        foreach ($growth as $row) {
            $growthLabels[] = $row['month'];
            $total += $row['total'];
            $growthData[] = $total;
        }

        $newEmployees = $this->dashboard->getNewEmployees(5);

        $recentActivities = $this->dashboard->getRecentActivities(5);

        require '../resources/views/dashboard/index.php';
    }
}