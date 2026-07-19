<?php
    namespace App\Controllers;

    class RecruitmentController
    {
        public function applicants()
        {
            require '../resources/views/applicants/index.php';
        }

        public function recruitment()
        {
            require '../resources/views/recruitment/index.php';
        }

        public function onboarding()
        {
            require '../resources/views/onboarding/index.php';
        }
    }
