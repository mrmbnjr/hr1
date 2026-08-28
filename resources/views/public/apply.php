<?php

$job = isset($job) && is_array($job) ? $job : [];

$error = $_SESSION['application_error'] ?? null;

unset($_SESSION['application_error']);

$applicationToken =
    htmlspecialchars(
        $job['application_token'],
        ENT_QUOTES,
        'UTF-8'
    );
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Apply - <?= htmlspecialchars($job['title']) ?>
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family:
                Arial,
                Helvetica,
                sans-serif;
            background: #f5f3f0;
            color: #2d2424;
        }

        .application-page {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }

        .job-card,
        .application-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow:
                0 4px 20px
                rgba(0, 0, 0, 0.08);
        }

        .brand {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #741b1b;
            margin-bottom: 15px;
        }

        h1 {
            margin: 0 0 10px;
            font-size: 30px;
        }

        h2 {
            margin-top: 0;
        }

        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
        }

        .job-meta span {
            padding: 7px 12px;
            border-radius: 20px;
            background: #f0ece8;
            font-size: 13px;
        }

        .job-section {
            margin-top: 25px;
        }

        .job-section h3 {
            margin-bottom: 8px;
        }

        /*
         * Application error / duplicate warning
         */
        .error {
            display: flex;
            align-items: flex-start;
            gap: 14px;

            background: #fff8e7;
            color: #5d1515;

            border: 1px solid #c89b3c;
            border-left: 5px solid #741b1b;

            padding: 18px 20px;
            border-radius: 10px;

            margin: 0 0 24px;
        }

        .error-icon {
            flex-shrink: 0;

            width: 38px;
            height: 38px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #741b1b;
            color: #ffffff;

            border-radius: 50%;

            font-size: 18px;
            font-weight: 700;
        }

        .error-content {
            flex: 1;
        }

        .error-title {
            margin: 0 0 6px;

            font-size: 17px;
            font-weight: 700;

            color: #741b1b;
        }

        .error-message {
            margin: 0;

            line-height: 1.5;
            font-size: 14px;
        }

        .error-help {
            margin: 7px 0 0;

            color: #704f4f;
            font-size: 13px;
            line-height: 1.5;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            font-weight: 600;
            font-size: 14px;
        }

        input,
        textarea {
            width: 100%;
            padding: 12px;

            border: 1px solid #d5ceca;
            border-radius: 7px;

            font-size: 14px;
            font-family: inherit;

            background: #ffffff;
            color: #2d2424;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: #741b1b;
        }

        .required {
            color: #a51d1d;
        }

        .submit-button {
            margin-top: 25px;

            width: 100%;
            padding: 14px;

            border: none;
            border-radius: 7px;

            background: #741b1b;
            color: #ffffff;

            font-size: 15px;
            font-weight: 700;

            cursor: pointer;

            transition:
                background 0.2s ease,
                transform 0.1s ease;
        }

        .submit-button:hover {
            background: #5d1515;
        }

        .submit-button:active {
            transform: translateY(1px);
        }

        .file-note {
            font-size: 12px;
            color: #777;
        }

        @media (max-width: 700px) {

            .application-page {
                margin: 15px auto;
                padding: 10px;
            }

            .job-card,
            .application-card {
                padding: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .error {
                padding: 16px;
            }

            .error-icon {
                width: 34px;
                height: 34px;
                font-size: 16px;
            }

            .error-title {
                font-size: 16px;
            }

        }

    </style>

</head>

<body>

<div class="application-page">

    <div class="application-card">

        <h2>
            Apply for this Position
        </h2>

        <p>
            Please complete the form below and upload
            your resume.
        </p>


        <?php if ($error !== null): ?>

            <div
                class="error"
                role="alert"
                aria-live="polite"
            >

                <div class="error-icon">
                    !
                </div>

                <div class="error-content">

                    <h3 class="error-title">
                        Application Already Submitted
                    </h3>

                    <p class="error-message">
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                    <p class="error-help">
                        You cannot submit another application
                        for the same position using the same
                        email address.
                    </p>

                </div>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action="?page=submit-application"
            enctype="multipart/form-data"
        >

            <input
                type="hidden"
                name="application_token"
                value="<?= $applicationToken ?>"
            >


            <div class="form-grid">

                <div class="form-group">

                    <label>
                        First Name
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="first_name"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Middle Name
                    </label>

                    <input
                        type="text"
                        name="middle_name"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Last Name
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="last_name"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Email
                        <span class="required">*</span>
                    </label>

                    <input
                        type="email"
                        name="email"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Phone Number
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="phone"
                        required
                    >

                </div>


                <div class="form-group full">

                    <label>
                        Academic Certificate / Diploma
                        <?php if (!empty($job['academic_document_required'])): ?><span class="required">*</span><?php endif; ?>
                    </label>

                    <input
                        type="file"
                        name="academic_document"
                        accept=".pdf,.jpg,.jpeg,.png"
                        <?= !empty($job['academic_document_required']) ? 'required' : '' ?>
                    >

                    <span class="file-note">
                        PDF, JPG, or PNG. <?= !empty($job['academic_document_required']) ? 'Required for this position.' : 'Optional.' ?>
                    </span>

                </div>

                <div class="form-group full">

                    <label>
                        Address
                    </label>

                    <textarea
                        name="address"
                    ></textarea>

                </div>


                <div class="form-group full">

                    <label>
                        Resume
                        <span class="required">*</span>
                    </label>

                    <input
                        type="file"
                        name="resume"
                        accept=".pdf,.doc,.docx"
                        required
                    >

                    <span class="file-note">
                        PDF, DOC, or DOCX. Maximum 5 MB.
                    </span>

                </div>

            </div>


            <button
                type="submit"
                class="submit-button"
            >
                Submit Application
            </button>

        </form>

    </div>

</div>

</body>

</html>