<!DOCTYPE html>
<html lang="en">

<head>

      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">

      <title><?= $pageTitle ?? "RAM-YUM Recruitment System" ?></title>

      <!-- Google Font -->
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
      <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
      <link href="https://fonts.googleapis.com/css2?family=Luckiest+Guy&display=swap" rel="stylesheet">

      <!-- Global CSS -->
      <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/variables.css">
      <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
      <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/layout.css">

      <?php
      if (isset($pageStyles) && is_array($pageStyles)) {
            foreach ($pageStyles as $style) {
            echo '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/' . htmlspecialchars($style) . '">';
        }
      }
      ?>

</head>

<body>