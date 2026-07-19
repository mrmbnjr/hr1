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
        <svg class="badge-svg" viewBox="0 0 200 200">
          <polygon class="badge-seal" points="100.0,4.0 113.3,16.0 129.7,8.7 138.6,24.3 156.4,22.3 160.1,39.9 177.7,43.6 175.7,61.4 191.3,70.3 184.0,86.7 196.0,100.0 184.0,113.3 191.3,129.7 175.7,138.6 177.7,156.4 160.1,160.1 156.4,177.7 138.6,175.7 129.7,191.3 113.3,184.0 100.0,196.0 86.7,184.0 70.3,191.3 61.4,175.7 43.6,177.7 39.9,160.1 22.3,156.4 24.3,138.6 8.7,129.7 16.0,113.3 4.0,100.0 16.0,86.7 8.7,70.3 24.3,61.4 22.3,43.6 39.9,39.9 43.6,22.3 61.4,24.3 70.3,8.7 86.7,16.0" />
          <circle class="badge-gold-ring" cx="100" cy="100" r="80" />
          <circle class="badge-face" cx="100" cy="100" r="72" />
          <circle class="badge-inner-line" cx="100" cy="100" r="45" />

          <path id="badgeTopArc" d="M 44.0,85.0 A 58,58 0 0 1 156.0,85.0" fill="none" />
          <path id="badgeBottomArc" d="M 43.8,126.2 A 62,62 0 0 0 156.2,126.2" fill="none" />

          <text class="badge-text-top">
            <textPath href="#badgeTopArc" startOffset="50%" text-anchor="middle">RAM-YUM</textPath>
          </text>
          <text class="badge-text-bottom">
            <textPath href="#badgeBottomArc" startOffset="50%" text-anchor="middle" letter-spacing="0.5">KOREAN &amp; JAPANESE STORE</textPath>
          </text>
        </svg>
        <div class="badge-bowl" role="img" aria-label="Bowl of ramen">🍜</div>
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
          <span class="btn-label">Login</span>
          <span class="mascot" aria-hidden="true">
            <svg viewBox="0 0 44 44">
              <circle cx="22" cy="22" r="21" fill="#ff5c50"></circle>
              <circle cx="22" cy="22" r="21" fill="url(#mascotShine)" opacity="0.5"></circle>
              <ellipse class="mascot-cheek" cx="9.5" cy="27" rx="3.4" ry="2.4" fill="#ff8f83"></ellipse>
              <ellipse class="mascot-cheek" cx="34.5" cy="27" rx="3.4" ry="2.4" fill="#ff8f83"></ellipse>
              <g class="mascot-eyes" stroke="#3a0d0d" stroke-width="2.6" stroke-linecap="round" fill="none">
                <path d="M11.5 20.5c1.3-1.6 4-1.6 5.3 0"></path>
                <path d="M27.2 20.5c1.3-1.6 4-1.6 5.3 0"></path>
              </g>
              <defs>
                <radialGradient id="mascotShine" cx="35%" cy="25%" r="60%">
                  <stop offset="0%" stop-color="#ffffff" stop-opacity="0.55"></stop>
                  <stop offset="100%" stop-color="#ffffff" stop-opacity="0"></stop>
                </radialGradient>
              </defs>
            </svg>
          </span>
        </button>
      </form>
    </section>
  </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>