<?php
/**
 * RAM-YUM careers and Team 1 HR modules landing page.
 *
 * Data contract (jobs are supplied by the public route; empty fallbacks keep the
 * page safe if rendered outside the router):
 *
 *   $landing['jobs']     => list of [id, title, department, type, slots, deadline, application_token]
 */

$pageTitle = 'Careers at RAM-YUM | Recruitment & Core HR';
$pageCSS   = ['landing.css'];
$pageJS    = ['landing.js'];

if (!isset($landing)) {
    $landing = [
        'jobs' => [],
    ];
}

$jobs     = array_slice($landing['jobs'] ?? [], 0, 3);

$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$jobIcon = static function ($title): string {
    $title = strtolower((string) $title);

    foreach ([
        'cook' => 'fa-utensils',
        'kitchen' => 'fa-utensils',
        'cashier' => 'fa-cash-register',
        'sales' => 'fa-chart-line',
        'manager' => 'fa-user-tie',
        'supervisor' => 'fa-user-tie',
        'driver' => 'fa-truck',
        'warehouse' => 'fa-warehouse',
        'hr' => 'fa-people-group',
    ] as $keyword => $icon) {
        if (strpos($title, $keyword) !== false) {
            return $icon;
        }
    }

    return 'fa-briefcase';
};

$loginUrl   = url('?page=login');
$resetPasswordUrl = url('?page=reset-password');
$careersUrl = url('?page=careers');
$applyUrl   = static fn($token) => 'index.php?page=apply&token=' . rawurlencode((string) $token);
$logoUrl    = asset('images/logo.png');
$heroImageUrl = asset('images/hero-team.jpg');
$journeyImageUrl = asset('images/journey-workspace.jpg');
$year       = date('Y');

/* ---- presentation helpers ---- */
$daysLeft = static function ($deadline) {
    if (empty($deadline)) return null;
    $ts = strtotime((string) $deadline);
    if (!$ts) return null;
    return (int) floor(($ts - strtotime('today')) / 86400);
};
$journey = [
    ['fa-file-pen',        'Apply',       'Choose an open role and submit your application online.'],
    ['fa-clipboard-check','HR review',   'HR reviews your application. AI screening can support this review.'],
    ['fa-comments',        'Interview',   'If selected to continue, meet with the hiring team.'],
    ['fa-shield-halved',   'Verification','HR checks the required credentials and details.'],
    ['fa-handshake',       'Offer',       'Review your offer and confirm your response.'],
    ['fa-seedling',        'Onboarding',  'Complete your new-hire requirements and document requests.'],
];

$portalServices = [
    ['name' => 'Core HR', 'description' => 'People, hiring, onboarding, records, and company structure.', 'icon' => 'fa-people-group'],
    ['name' => 'Workforce Management', 'description' => 'Time, attendance, schedules, and workforce planning.', 'icon' => 'fa-calendar-days'],
    ['name' => 'Performance & Development', 'description' => 'Performance, learning, training, and career growth.', 'icon' => 'fa-chart-line'],
    ['name' => 'Payroll & Benefits', 'description' => 'Payroll, compensation, claims, and employee benefits.', 'icon' => 'fa-money-check-dollar'],
    ['name' => 'Financial Management System', 'description' => 'Financial transactions, reporting, and budget oversight.', 'icon' => 'fa-coins'],
    ['name' => 'Supply Chain & Inventory Management', 'description' => 'Warehousing, procurement, purchasing, and inventory.', 'icon' => 'fa-boxes-stacked'],
    ['name' => 'Fleet & Transportation Management', 'description' => 'Fleet, dispatch, routing, and transport operations.', 'icon' => 'fa-truck-fast'],
    ['name' => 'Facilities & Administrative Management', 'description' => 'Facilities, visitors, documents, and compliance.', 'icon' => 'fa-building'],
    ['name' => 'Product & Inventory Operations System', 'description' => 'Product catalog, stock control, purchasing, and receiving.', 'icon' => 'fa-box-open'],
    ['name' => 'Sales, Supplier & Merch Performance System', 'description' => 'Point of sale, suppliers, reservations, and sales insights.', 'icon' => 'fa-store'],
];
?>
<!DOCTYPE html>
<html lang="en" class="lp-page">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($pageTitle) ?></title>
    <meta name="description" content="Explore career opportunities at RAM-YUM and learn about the recruitment, onboarding, and Core HR systems that support our team.">
    <meta name="theme-color" content="#7b1e2b">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="<?= $e(asset('css/variables.css')) ?>">
    <link rel="stylesheet" href="<?= $e(asset('css/style.css')) ?>">
    <?php foreach ($pageCSS as $css): ?>
        <link rel="stylesheet" href="<?= $e(asset('css/' . $css)) ?>">
    <?php endforeach; ?>
</head>
<body class="lp-body">

<a href="#main" class="lp-skip">Skip to content</a>

<!-- ============ NAVIGATION ============ -->
<header class="lp-nav" id="lpNav">
    <div class="lp-container lp-nav__inner">
        <a href="#home" class="lp-brand" aria-label="RAM-YUM home">
            <img src="<?= $e($logoUrl) ?>" alt="" class="lp-brand__logo" onerror="this.style.display='none'">
            <span class="lp-brand__text">
                <span class="lp-brand__name">RAM-YUM <span>MMS</span></span>
                <span class="lp-brand__subtitle">Careers &amp; People</span>
            </span>
        </a>

        <button class="lp-nav__toggle" id="lpNavToggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="lpNavMenu">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>

        <nav class="lp-nav__menu" id="lpNavMenu" aria-label="Primary">
            <a href="#home" class="lp-nav__link is-active">Home</a>
            <a href="#opportunities" class="lp-nav__link">Open roles</a>
            <a href="#careers" class="lp-nav__link">Hiring process</a>
            <a href="#portal" class="lp-nav__link">Employee portal</a>
            <a href="<?= $e($careersUrl) ?>" class="lp-nav__link">All opportunities</a>
            <a href="<?= $e($loginUrl) ?>" class="lp-btn lp-btn--dark lp-btn--sm lp-nav__login">
                <i class="fa-solid fa-arrow-right-to-bracket" aria-hidden="true"></i> Login Portal
            </a>
        </nav>
    </div>
</header>

<main id="main">

<!-- ============ HERO ============ -->
<section class="lp-hero" id="home">
    <span class="lp-deco lp-deco--ring" aria-hidden="true"></span>
    <span class="lp-deco lp-deco--dots" aria-hidden="true"></span>

    <div class="lp-container lp-hero__grid">
        <div class="lp-hero__copy" data-reveal>
            <span class="lp-eyebrow"><i class="fa-solid fa-people-group" aria-hidden="true"></i> Careers at RAM-YUM</span>
            <h1 class="lp-hero__title">Build your next chapter <span>with our team.</span></h1>
            <p class="lp-hero__lead">
                Explore open roles and follow a clear application journey, supported by connected recruitment,
                onboarding, and Core HR tools.
            </p>
            <div class="lp-actions">
                <a href="#opportunities" class="lp-btn lp-btn--primary lp-btn--lg"><i class="fa-solid fa-briefcase" aria-hidden="true"></i> Explore Open Roles</a>
                <a href="#careers" class="lp-btn lp-btn--outline lp-btn--lg">See the Hiring Process <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
            <p class="lp-hero__note">
                <a href="#portal" class="lp-textlink">Employee login</a>
            </p>
        </div>

        <div class="lp-hero__visual" aria-hidden="true" data-reveal style="--d:120ms">
            <div class="lp-hero__back"></div>
            <div class="lp-hero__frame" style="background-image: url('<?= $e($heroImageUrl) ?>');">
                <div class="lp-hero__art"><i class="fa-solid fa-people-group"></i></div>
                <div class="lp-hero__shade"></div>
            </div>
            <div class="lp-hero__chip lp-hero__chip--top">
                <span class="lp-chip__icon"><i class="fa-solid fa-user-check"></i></span>
                <div><strong>People-first review</strong><small>Hiring decisions stay with HR</small></div>
            </div>
            <div class="lp-hero__chip lp-hero__chip--mid">
                <span class="lp-chip__icon"><i class="fa-solid fa-magnifying-glass-chart"></i></span>
                <div><strong>AI-assisted screening</strong><small>Support for application review</small></div>
            </div>
            <div class="lp-hero__chip lp-hero__chip--bottom">
                <span class="lp-chip__icon"><i class="fa-solid fa-seedling"></i></span>
                <div><strong>From application to onboarding</strong><small>One connected people journey</small></div>
            </div>
        </div>
    </div>
</section>

<!-- ============ CANDIDATE JOURNEY INTRO ============ -->
<section class="lp-section lp-about-section" id="about">
    <div class="lp-container lp-about-card">
      <div class="lp-about">
        <div class="lp-about__media" data-reveal>
            <div class="lp-about__photo" aria-hidden="true" style="background-image: url('<?= $e($journeyImageUrl) ?>');">
                <i class="fa-solid fa-clipboard-check"></i>
            </div>
            <div class="lp-about__badge" aria-hidden="true">
                <strong>Team 1</strong>
                <span>Recruitment<br>&amp; Core HR</span>
            </div>
            <span class="lp-deco lp-deco--bar" aria-hidden="true"></span>
        </div>

        <div class="lp-about__text" data-reveal style="--d:100ms">
            <span class="lp-kicker">Your next opportunity</span>
            <h2 class="lp-h2">A clear path from application to onboarding</h2>
            <p class="lp-lead">
                Our recruitment and onboarding tools help HR organize each stage, from receiving your application
                through reviewing requirements after a successful offer.
            </p>
            <ul class="lp-features">
                <li><span class="lp-ico"><i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i></span><div><strong>Apply online</strong><span>Choose an open role and submit your application and documents.</span></div></li>
                <li><span class="lp-ico"><i class="fa-solid fa-user-check" aria-hidden="true"></i></span><div><strong>Human-led decisions</strong><span>AI tools can support screening; HR reviews candidates and makes hiring decisions.</span></div></li>
                <li><span class="lp-ico"><i class="fa-solid fa-folder-open" aria-hidden="true"></i></span><div><strong>Organized onboarding</strong><span>New hires can complete requested documents through the employee portal.</span></div></li>
            </ul>
        </div>
      </div>
    </div>
</section>

<!-- ============ OPEN OPPORTUNITIES ============ -->
<section class="lp-section lp-section--tint lp-careers-section" id="opportunities">
    <div class="lp-container">
        <header class="lp-head lp-head--row" data-reveal>
            <div>
                <span class="lp-kicker">Careers at RAM-YUM</span>
                <h2 class="lp-h2">Explore open roles</h2>
            </div>
            <a href="<?= $e($careersUrl) ?>" class="lp-btn lp-btn--outline lp-btn--sm">View All Opportunities <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </header>

        <?php if (empty($jobs)): ?>
            <p class="lp-empty"><i class="fa-solid fa-briefcase" aria-hidden="true"></i> No open positions right now. Please check back soon.</p>
        <?php else: ?>
        <div class="lp-jobs">
            <?php foreach ($jobs as $k => $j):
                $left  = $daysLeft($j['deadline'] ?? null);
                $slots = max(0, (int) $j['slots']);
            ?>
                <article class="lp-job" data-reveal style="--d:<?= (int) $k * 80 ?>ms">
                    <span class="lp-job__icon"><i class="fa-solid <?= $e($jobIcon($j['title'])) ?>" aria-hidden="true"></i></span>
                    <div class="lp-job__main">
                        <div class="lp-job__title">
                            <h3><?= $e($j['title']) ?></h3>
                            <span class="lp-badge"><?= $e($j['type']) ?></span>
                            <?php if ($left !== null && $left >= 0 && $left <= 7): ?>
                                <span class="lp-badge lp-badge--warn">Closing soon</span>
                            <?php elseif ($left !== null && $left < 0): ?>
                                <span class="lp-badge lp-badge--off">Closed</span>
                            <?php endif; ?>
                        </div>
                        <ul class="lp-meta">
                            <li><i class="fa-solid fa-building" aria-hidden="true"></i><?= $e($j['department']) ?></li>
                            <li><i class="fa-solid fa-clock" aria-hidden="true"></i><?= $e($j['type']) ?></li>
                            <li><i class="fa-solid fa-user-plus" aria-hidden="true"></i><?= $slots ?> <?= $slots === 1 ? 'opening' : 'openings' ?></li>
                            <?php if (!empty($j['deadline'])): ?>
                                <li><i class="fa-regular fa-calendar" aria-hidden="true"></i>Apply by <?= $e(date('M j, Y', strtotime($j['deadline']))) ?></li>
                            <?php endif; ?>
                        </ul>

                        <?php $benefits = jobBenefits($j); ?>
                        <?php if ($benefits): ?>
                        <ul class="lp-job__benefits" aria-label="Benefits">
                            <?php foreach (array_slice($benefits, 0, 3) as $benefit): ?>
                                <li><i class="fa-solid fa-check" aria-hidden="true"></i> <?= $e($benefit) ?></li>
                            <?php endforeach; ?>
                            <?php if (count($benefits) > 3): ?>
                                <li class="lp-job__benefits-more">+<?= count($benefits) - 3 ?> more</li>
                            <?php endif; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                    <div class="lp-job__slots" data-tip="<?= $slots ?> <?= $slots === 1 ? 'opening' : 'openings' ?> for this role" tabindex="0">
                        <strong><?= $slots ?></strong>
                        <span><?= $slots === 1 ? 'opening' : 'openings' ?></span>
                    </div>
                    <a href="<?= $e($applyUrl($j['application_token'])) ?>" class="lp-btn lp-btn--primary lp-btn--sm lp-job__cta"
                       aria-label="Apply for <?= $e($j['title']) ?> in <?= $e($j['department']) ?>">
                        Apply now <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============ HIRING PROCESS ============ -->
<section class="lp-section" id="careers">
    <div class="lp-container">
        <header class="lp-head" data-reveal>
            <span class="lp-kicker">What to expect</span>
            <h2 class="lp-h2">A straightforward hiring journey</h2>
            <p class="lp-lead">HR manages each stage and will guide applicants who move forward.</p>
        </header>
        <ol class="lp-journey" data-reveal aria-label="Recruitment process">
            <?php foreach ($journey as $n => $s): ?>
                <li class="lp-step" tabindex="0" data-tip="<?= $e($s[2]) ?>">
                    <span class="lp-step__icon"><i class="fa-solid <?= $e($s[0]) ?>" aria-hidden="true"></i></span>
                    <span class="lp-step__num">Step <?= $n + 1 ?></span>
                    <strong><?= $e($s[1]) ?></strong>
                    <small><?= $e($s[2]) ?></small>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<!-- ============ EMPLOYEE PORTAL ============ -->
<section class="lp-section lp-section--tight lp-section--tint" id="portal">
    <div class="lp-container">
        <div class="lp-portal" data-reveal>
            <header class="lp-portal__header">
                <span class="lp-kicker">Merchandising Management System</span>
                <h2 class="lp-h2">MMS Portal</h2>
                <p class="lp-lead">Explore the ten teams supporting people, operations, service, and growth across RAM-YUM.</p>
            </header>
            <div class="lp-portal__grid" role="list" aria-label="MMS Portal teams">
                <?php foreach ($portalServices as $service): ?>
                    <a class="lp-portal__tile" role="listitem" href="<?= $e($loginUrl) ?>" aria-label="<?= $e($service['name'] . ' — sign in to MMS Portal') ?>">
                        <span class="lp-portal__tile-icon" aria-hidden="true">
                            <i class="fa-solid <?= $e($service['icon']) ?>"></i>
                        </span>
                        <span class="lp-portal__tile-copy">
                            <strong><?= $e($service['name']) ?></strong>
                            <small><?= $e($service['description']) ?></small>
                        </span>
                        <i class="fa-solid fa-arrow-right lp-portal__tile-arrow" aria-hidden="true"></i>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="lp-portal__footer">
                <span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> For RAM-YUM team members</span>
                <div class="lp-portal__reset">
                    <p>Forgot your password? Reset it securely to regain access to your account.</p>
                    <a href="<?= $e($resetPasswordUrl) ?>" class="lp-btn lp-btn--primary lp-portal__reset-btn">
                        <i class="fa-solid fa-key" aria-hidden="true"></i> Reset Password
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

</main>

<!-- ============ FOOTER ============ -->
<footer class="lp-footer">
    <div class="lp-container lp-footer__grid">
        <div class="lp-footer__brand">
            <span class="lp-brand__name">RAM-YUM</span>
            <small>Careers &amp; People</small>
            <p>Explore opportunities and learn how recruitment, onboarding, and Core HR support our team.</p>
        </div>
        <nav class="lp-footer__col" aria-label="Footer: company">
            <h3>Applicants</h3>
            <a href="#opportunities">Open roles</a>
            <a href="<?= $e($careersUrl) ?>">All opportunities</a>
            <a href="#careers">Hiring process</a>
        </nav>
        <nav class="lp-footer__col" aria-label="Footer: employees">
            <h3>Employees</h3>
            <a href="#portal">Employee portal</a>
            <a href="<?= $e($loginUrl) ?>">Employee login</a>
        </nav>
    </div>
    <div class="lp-container lp-footer__copy">
        <span>&copy; <?= $e($year) ?> RAM-YUM. All rights reserved.</span>
        <a href="#home">Back to top <i class="fa-solid fa-arrow-up" aria-hidden="true"></i></a>
    </div>
</footer>

<?php foreach ($pageJS as $js): ?>
    <script src="<?= $e(asset('js/' . $js)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
