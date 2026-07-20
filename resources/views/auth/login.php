<?php
$pageTitle = "Login";
$pageCSS = "login.css";
$pageDescription = "Login to your RAM-YUM Store account — Korean and Japanese Store.";
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

  <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle light and dark theme" aria-pressed="false">
    <svg class="icon icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <circle cx="12" cy="12" r="4.2"></circle>
      <path d="M12 2.5v2.4M12 19.1v2.4M4.6 4.6l1.7 1.7M17.7 17.7l1.7 1.7M2.5 12h2.4M19.1 12h2.4M4.6 19.4l1.7-1.7M17.7 6.3l1.7-1.7"></path>
    </svg>
    <svg class="icon icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M20.8 14.3A8.5 8.5 0 1 1 9.7 3.2a6.8 6.8 0 0 0 11.1 11.1z"></path>
    </svg>
  </button>

  <main class="page">
    <section class="login-card" aria-labelledby="brandTitle">

      <div class="badge" aria-hidden="true">
        <img src="../public/assets/images/logo.png">
      </div>

      <h1 class="brand" id="brandTitle">RAM-YUM <span>Store</span></h1>
      <h2 class="page-title">Login</h2>

      <form class="login-form" id="loginForm" method="POST" action="/hr1/public/index.php?page=login" novalidate>
        <div class="field">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" autocomplete="username" spellcheck="false" required />
        </div>

        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" autocomplete="current-password" required />
        </div>

        <p class="form-message" id="formMessage" role="alert" aria-live="polite"></p>

        <button type="submit" class="login-btn" id="loginBtn">
            <span class="btn-label">LOGIN</span>
        </button>      
      </form>
    </section>
  </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>