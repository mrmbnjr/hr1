<?php

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

        .error {
            background: #fdeaea;
            color: #9b1c1c;
            border: 1px solid #efb5b5;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
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
            color: white;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        .submit-button:hover {
            background: #5d1515;
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