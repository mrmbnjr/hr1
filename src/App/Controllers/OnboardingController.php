<?php

namespace App\Controllers;

use App\Models\Onboarding;

class OnboardingController
{
    public function onboarding()
    {
        $model = new Onboarding();
        $employees = $model->getAllOnboarding();
        $totalNewHires = $model->countAll();
        $pending = $model->countStatus("Pending");
        $ongoing = $model->countStatus("Ongoing");
        $completed = $model->countStatus("Completed");

        require '../resources/views/onboarding/index.php';
    }

    public function onboardingView()
    {
        require '../resources/views/onboarding/onboarding-view.php';
    }
}