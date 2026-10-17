<?php
/**
 * RAM-YUM public landing page.
 *
 * Data contract (jobs are supplied by the public route; empty fallbacks keep the
 * page safe if rendered outside the router):
 *
 *   $landing['stats']    => ['branches' => int, 'employees' => int, 'open_positions' => int]
 *   $landing['jobs']     => list of [id, title, department, type, slots, deadline, application_token]
 */

$pageTitle = 'RAM-YUM | Korean & Japanese Store';
$pageCSS   = ['landing.css'];
$pageJS    = ['landing.js'];

if (!isset($landing)) {
    $landing = [
        'stats' => ['branches' => 3, 'employees' => 48, 'open_positions' => 4],
        'jobs' => [],
    ];
}

$stats    = $landing['stats'] ?? ['branches' => 3, 'employees' => 48, 'open_positions' => 4];
$jobs     = array_slice($landing['jobs'] ?? [], 0, 3);

$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$loginUrl   = url('?page=login');
$resetPasswordUrl = url('?page=reset-password');
$careersUrl = url('?page=careers');
$applyUrl   = static fn($token) => 'index.php?page=apply&token=' . rawurlencode((string) $token);
$logoUrl    = asset('images/logo.png');
$heroImg    = asset('images/hero/hero-food.jpg');
$aboutImg   = asset('images/about/about-store.jpg');
$year       = date('Y');

/* ---- presentation helpers ---- */
$daysLeft = static function ($deadline) {
    if (empty($deadline)) return null;
    $ts = strtotime((string) $deadline);
    if (!$ts) return null;
    return (int) floor(($ts - strtotime('today')) / 86400);
};
$jobIcon = static function ($title) {
    $t = strtolower((string) $title);
    if (strpos($t, 'kitchen') !== false || strpos($t, 'cook') !== false || strpos($t, 'chef') !== false) return 'fa-kitchen-set';
    if (strpos($t, 'cashier') !== false) return 'fa-cash-register';
    if (strpos($t, 'manager') !== false || strpos($t, 'supervisor') !== false) return 'fa-clipboard-user';
    return 'fa-bell-concierge';
};

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

$menuItems = [
    ['name' => 'Bibimbap', 'description' => 'Savory beef, crisp vegetables, and a sunny egg over warm rice.', 'price' => 249, 'category' => 'Korean', 'badge' => 'Best seller', 'image' => 'menu/bibimbap.jpg'],
    ['name' => 'Crispy Korean Chicken', 'description' => 'Golden, crunchy chicken glazed with a sweet-and-spicy sauce.', 'price' => 289, 'category' => 'Korean', 'badge' => 'Fan favorite', 'image' => 'menu/fried-chicken.jpg'],
    ['name' => 'Chicken Katsu Curry', 'description' => 'Crisp chicken cutlet with rich Japanese curry and fluffy rice.', 'price' => 269, 'category' => 'Japanese', 'badge' => 'Comfort pick', 'image' => 'menu/katsu-curry.jpg'],
    ['name' => 'Tonkotsu Ramen', 'description' => 'A creamy pork broth with noodles, chashu, scallions, and egg.', 'price' => 299, 'category' => 'Japanese', 'badge' => 'Best seller', 'image' => 'menu/ramen.jpg'],
    ['name' => 'Samgyupsal Set', 'description' => 'Sizzling Korean pork belly served with rice and classic sides.', 'price' => 329, 'category' => 'Korean', 'badge' => 'Shareable', 'image' => 'menu/samgyupsal.jpg'],
    ['name' => 'Spicy Tteokbokki', 'description' => 'Chewy rice cakes simmered in a bold, sweet gochujang sauce.', 'price' => 199, 'category' => 'Korean', 'badge' => 'A little spicy', 'image' => 'menu/tteokbokki.jpg'],
];

$galleryItems = [
    ['image' => 'gallery/storefront.jpg', 'alt' => 'RAM-YUM storefront with its Korean and Japanese Store sign', 'caption' => 'Welcome to RAM-YUM'],
    ['image' => 'gallery/interior.jpg', 'alt' => 'Bright RAM-YUM store interior with shelves and seating', 'caption' => 'A look inside our store'],
    ['image' => 'gallery/food-spread.jpg', 'alt' => 'A spread of RAM-YUM meals ready to enjoy', 'caption' => 'Made for sharing'],
    ['image' => 'menu/bibimbap.jpg', 'alt' => 'Korean bibimbap topped with vegetables and a sunny egg', 'caption' => 'Colorful bibimbap'],
    ['image' => 'menu/samgyupsal.jpg', 'alt' => 'Korean samgyupsal meal served with rice and side dishes', 'caption' => 'Sizzling samgyupsal'],
    ['image' => 'menu/ramen.jpg', 'alt' => 'Japanese ramen with chashu pork, scallions, and egg', 'caption' => 'A comforting bowl of ramen'],
];

$journey = [
    ['fa-file-pen',       'Apply',         'Pick a role and submit your details and resume online.'],
    ['fa-robot',          'AI Validation', 'Your qualifications are checked automatically against the role.'],
    ['fa-clipboard-check','Screening',     'HR reviews your match and any suggested alternative roles.'],
    ['fa-comments',       'Interview',     'Meet the branch team for a short, friendly conversation.'],
    ['fa-handshake',      'Hire',          'Receive your offer and confirm your start date.'],
    ['fa-seedling',       'Onboarding',    'Complete requirements and get set up in the employee portal.'],
];

$jobSalary = static function ($job) {
    // Use real data once the jobs query provides it
    if (!empty($job['salary'])) return (string) $job['salary'];

    // DEMO ONLY: placeholder ranges until salary is stored per job
    $t = strtolower((string) ($job['title'] ?? ''));
    if (strpos($t, 'manager') !== false || strpos($t, 'supervisor') !== false) return '₱28,000 – ₱35,000 / month';
    if (strpos($t, 'kitchen') !== false || strpos($t, 'cook') !== false || strpos($t, 'chef') !== false) return '₱15,000 – ₱18,000 / month';
    if (strpos($t, 'cashier') !== false) return '₱14,000 – ₱16,500 / month';
    return '₱13,500 – ₱16,000 / month';
};
?>
<!DOCTYPE html>
<html lang="en" class="lp-page">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($pageTitle) ?></title>
    <meta name="description" content="Discover RAM-YUM Korean and Japanese favorites, meet our team, and explore career opportunities.">
    <meta name="theme-color" content="#7b1e2b">
    <script>document.documentElement.classList.add('lp-js');</script>

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
                <span class="lp-brand__name">RAM-YUM <span>Store</span></span>
                <span class="lp-brand__subtitle">Korean &amp; Japanese Store</span>
            </span>
        </a>

        <button class="lp-nav__toggle" id="lpNavToggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="lpNavMenu">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>

        <nav class="lp-nav__menu" id="lpNavMenu" aria-label="Primary">
            <a href="#home" class="lp-nav__link is-active">Home</a>
            <a href="#about" class="lp-nav__link">About Us</a>
            <a href="#menu" class="lp-nav__link">Menu</a>
            <a href="#gallery" class="lp-nav__link">Gallery</a>
            <a href="#careers" class="lp-nav__link">Careers</a>
            <a href="#contact" class="lp-nav__link">Contact Us</a>
            <a href="#portal" class="lp-btn lp-btn--dark lp-btn--sm lp-nav__login">
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
            <span class="lp-eyebrow"><i class="fa-solid fa-bowl-rice" aria-hidden="true"></i> Korean &amp; Japanese Store</span>
            <h1 class="lp-hero__title">Authentic flavors, <span>served with care.</span></h1>
            <p class="lp-hero__lead">
                RAM-YUM brings Korean and Japanese favorites to your neighborhood, backed by a
                dedicated team that grows together across every branch.
            </p>
            <div class="lp-actions">
                <a href="#menu" class="lp-btn lp-btn--primary lp-btn--lg"><i class="fa-solid fa-utensils" aria-hidden="true"></i> Explore the Menu</a>
                <a href="<?= $e($careersUrl) ?>" class="lp-btn lp-btn--outline lp-btn--lg">Join Our Team <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
            <p class="lp-hero__note">
                <a href="#about" class="lp-textlink">Explore our company</a>
                <span aria-hidden="true">·</span>
                <a href="<?= $e($loginUrl) ?>" class="lp-textlink">Employee login</a>
            </p>
        </div>

        <div class="lp-hero__visual" aria-hidden="true" data-reveal style="--d:120ms">
            <div class="lp-hero__back"></div>
            <div class="lp-hero__frame" <?php if ($heroImg !== ''): ?>style="background-image:url('<?= $e($heroImg) ?>')"<?php endif; ?>>
                <div class="lp-hero__art"><i class="fa-solid fa-bowl-food"></i></div>
                <div class="lp-hero__shade"></div>
            </div>
            <div class="lp-hero__chip lp-hero__chip--top">
                <span class="lp-chip__icon"><i class="fa-solid fa-star"></i></span>
                <div><strong>Quality first</strong><small>Fresh, every day</small></div>
            </div>
            <div class="lp-hero__chip lp-hero__chip--mid">
                <span class="lp-chip__icon"><i class="fa-solid fa-location-dot"></i></span>
                <div><strong><?= (int) $stats['branches'] ?> branches</strong><small>Across Metro Manila</small></div>
            </div>
            <div class="lp-hero__chip lp-hero__chip--bottom">
                <span class="lp-chip__icon"><i class="fa-solid fa-user-plus"></i></span>
                <div><strong>We're hiring</strong><small><?= (int) $stats['open_positions'] ?> open positions</small></div>
            </div>
        </div>
    </div>
</section>

<!-- ============ ABOUT ============ -->
<section class="lp-section lp-about-section" id="about">
    <div class="lp-container lp-about-card">
      <div class="lp-about">
        <div class="lp-about__media" data-reveal>
            <div class="lp-about__photo" <?php if ($aboutImg !== ''): ?>style="background-image:url('<?= $e($aboutImg) ?>')"<?php endif; ?> aria-hidden="true">
                <i class="fa-solid fa-utensils"></i>
            </div>
            <div class="lp-about__badge" aria-hidden="true">
                <strong>RAM-YUM</strong>
                <span>Korean &amp; Japanese<br>favorites</span>
            </div>
            <span class="lp-deco lp-deco--bar" aria-hidden="true"></span>
        </div>

        <div class="lp-about__text" data-reveal style="--d:100ms">
            <span class="lp-kicker">About RAM-YUM</span>
            <h2 class="lp-h2">A neighborhood store built on Korean and Japanese flavors</h2>
            <p class="lp-lead">
                RAM-YUM serves ramen, rice meals, and everyday favorites with consistent quality and
                friendly service, and a workplace where every team member can build a career.
            </p>
            <ul class="lp-features">
                <li><span class="lp-ico"><i class="fa-solid fa-utensils" aria-hidden="true"></i></span><div><strong>Korean &amp; Japanese menu</strong><span>Familiar favorites, prepared consistently.</span></div></li>
                <li><span class="lp-ico"><i class="fa-solid fa-heart" aria-hidden="true"></i></span><div><strong>Made with care</strong><span>Good food and friendly service, every day.</span></div></li>
                <li><span class="lp-ico"><i class="fa-solid fa-seedling" aria-hidden="true"></i></span><div><strong>Careers that grow</strong><span>One connected system for hiring, onboarding and employee records.</span></div></li>
            </ul>
        </div>
      </div>
    </div>
</section>

<!-- ============ BEST SELLERS ============ -->
<section class="lp-section lp-section--tint" id="menu">
    <div class="lp-container">
        <header class="lp-head lp-head--row" data-reveal>
            <div>
                <span class="lp-kicker">Made for your cravings</span>
                <h2 class="lp-h2">Menu favorites</h2>
            </div>
            <span class="lp-menu__note"><i class="fa-solid fa-star" aria-hidden="true"></i> A few of our most-loved bites</span>
        </header>
        <div class="lp-menu-grid">
            <?php foreach ($menuItems as $i => $item): ?>
                <article class="lp-menu-card" data-reveal style="--d:<?= (int) ($i % 3) * 80 ?>ms">
                    <div class="lp-menu-card__photo">
                        <img src="<?= $e(asset('images/' . $item['image'])) ?>" alt="<?= $e($item['name']) ?>" loading="lazy">
                        <span class="lp-menu-card__badge"><i class="fa-solid fa-star" aria-hidden="true"></i><?= $e($item['badge']) ?></span>
                    </div>
                    <div class="lp-menu-card__body">
                        <div class="lp-menu-card__meta">
                            <span class="lp-menu-card__category"><?= $e($item['category']) ?></span>
                            <span class="lp-menu-card__price">₱<?= number_format((int) $item['price']) ?></span>
                        </div>
                        <h3><?= $e($item['name']) ?></h3>
                        <p><?= $e($item['description']) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ GALLERY ============ -->
<section class="lp-section lp-gallery-section" id="gallery">
    <div class="lp-container">
        <header class="lp-head" data-reveal>
            <span class="lp-kicker">A little taste of RAM-YUM</span>
            <h2 class="lp-h2">From our store to your table</h2>
            <p class="lp-lead">Take a closer look at our space and some of the dishes we love to share.</p>
        </header>
        <div class="lp-gallery" data-reveal>
            <?php foreach ($galleryItems as $i => $item): ?>
                <button class="lp-gallery__item lp-gallery__item--<?= (int) ($i + 1) ?>" type="button"
                        data-lightbox data-lightbox-src="<?= $e(asset('images/' . $item['image'])) ?>"
                        data-lightbox-caption="<?= $e($item['caption']) ?>"
                        aria-label="Enlarge photo: <?= $e($item['caption']) ?>">
                    <img src="<?= $e(asset('images/' . $item['image'])) ?>" alt="<?= $e($item['alt']) ?>" loading="lazy">
                    <span class="lp-gallery__caption"><?= $e($item['caption']) ?></span>
                    <span class="lp-gallery__zoom" aria-hidden="true"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<dialog class="lp-lightbox" id="lpLightbox" aria-label="Photo viewer">
    <button class="lp-lightbox__close" type="button" aria-label="Close photo">
        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
    </button>
    <figure>
        <img class="lp-lightbox__image" src="" alt="">
        <figcaption class="lp-lightbox__caption"></figcaption>
    </figure>
</dialog>

<!-- ============ WHY RAM-YUM (BENTO) ============ -->
<section class="lp-section" id="why">
    <div class="lp-container">
        <header class="lp-head" data-reveal>
            <span class="lp-kicker">Why RAM-YUM</span>
            <h2 class="lp-h2">What we stand for</h2>
        </header>

        <div class="lp-bento">
            <article class="lp-bx lp-bx--quality" data-reveal tabindex="0">
                <span class="lp-bx__icon"><i class="fa-solid fa-award" aria-hidden="true"></i></span>
                <h3>Quality</h3>
                <p>Fresh ingredients and consistent recipes at every branch, so the bowl you love tastes the same wherever you are.</p>
                <i class="fa-solid fa-bowl-food lp-bx__bg" aria-hidden="true"></i>
            </article>
            <article class="lp-bx lp-bx--people" data-reveal style="--d:80ms" tabindex="0">
                <span class="lp-bx__icon"><i class="fa-solid fa-people-group" aria-hidden="true"></i></span>
                <h3>People</h3>
                <p>A respectful team culture where everyone is supported, trained and heard.</p>
                <a href="<?= $e($careersUrl) ?>" class="lp-link">Meet the team <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </article>
            <article class="lp-bx lp-bx--service" data-reveal style="--d:160ms" tabindex="0">
                <span class="lp-bx__icon"><i class="fa-solid fa-handshake-angle" aria-hidden="true"></i></span>
                <div><h3>Service</h3><p>Warm, attentive service that makes guests return.</p></div>
            </article>
            <article class="lp-bx lp-bx--growth" data-reveal style="--d:240ms" tabindex="0">
                <span class="lp-bx__icon"><i class="fa-solid fa-arrow-trend-up" aria-hidden="true"></i></span>
                <div><h3>Growth</h3><p>Training and opportunities to move forward with us.</p></div>
            </article>
        </div>
    </div>
</section>

<!-- ============ CAREERS ============ -->
<section class="lp-section lp-section--tint lp-careers-section" id="careers">
    <div class="lp-container">
        <header class="lp-head lp-head--row" data-reveal>
            <div>
                <span class="lp-kicker">Careers</span>
                <h2 class="lp-h2">Join the team. Here's how it works.</h2>
            </div>
            <a href="<?= $e($careersUrl) ?>" class="lp-btn lp-btn--outline lp-btn--sm">View All Opportunities <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
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

        <h3 class="lp-subhead" data-reveal>Current opportunities</h3>

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
                            <li><i class="fa-solid fa-peso-sign" aria-hidden="true"></i><?= $e($jobSalary($j)) ?></li>
                            <?php if (!empty($j['deadline'])): ?>
                                <li><i class="fa-regular fa-calendar" aria-hidden="true"></i>Apply by <?= $e(date('M j, Y', strtotime($j['deadline']))) ?></li>
                            <?php endif; ?>
                        </ul>
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

<!-- ============ EMPLOYEE PORTAL ============ -->
<section class="lp-section lp-section--tight" id="portal">
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

<!-- ============ CONTACT ============ -->
<section class="lp-section lp-section--compact" id="contact">
    <div class="lp-container">
        <header class="lp-head lp-head--row" data-reveal>
            <div>
                <span class="lp-kicker">Contact Us</span>
                <h2 class="lp-h2">Get in touch</h2>
            </div>
        </header>
        <div class="lp-contacts" data-reveal>
            <a class="lp-contact" href="https://www.google.com/maps/search/?api=1&amp;query=<?= $e(rawurlencode('RAM-YUM Head office Quezon City, Metro Manila')) ?>" target="_blank" rel="noopener">
                <span class="lp-ico"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></span>
                <span><strong>Head office</strong><small>Quezon City, Metro Manila</small></span>
                <i class="fa-solid fa-arrow-up-right-from-square lp-contact__go" aria-hidden="true"></i>
            </a>
            <a class="lp-contact" href="mailto:ramyum.store@gmail.com">
                <span class="lp-ico"><i class="fa-solid fa-envelope" aria-hidden="true"></i></span>
                <span><strong>Email</strong><small>ramyum.store@gmail.com</small></span>
                <i class="fa-solid fa-arrow-right lp-contact__go" aria-hidden="true"></i>
            </a>
            <a class="lp-contact" href="tel:0280000000">
                <span class="lp-ico"><i class="fa-solid fa-phone" aria-hidden="true"></i></span>
                <span><strong>Phone</strong><small>(02) 8000 0000</small></span>
                <i class="fa-solid fa-arrow-right lp-contact__go" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</section>

</main>

<!-- ============ FOOTER ============ -->
<footer class="lp-footer">
    <div class="lp-container lp-footer__grid">
        <div class="lp-footer__brand">
            <span class="lp-brand__name">RAM-YUM</span>
            <small>Korean &amp; Japanese Store</small>
            <p>Authentic flavors, served with care, and a team that grows together.</p>
        </div>
        <nav class="lp-footer__col" aria-label="Footer: company">
            <h3>Company</h3>
            <a href="#about">About Us</a>
            <a href="#menu">Menu favorites</a>
            <a href="#gallery">Gallery</a>
            <a href="#contact">Contact Us</a>
        </nav>
        <nav class="lp-footer__col" aria-label="Footer: careers">
            <h3>Careers</h3>
            <a href="#careers">How we hire</a>
            <a href="<?= $e($careersUrl) ?>">Open positions</a>
            <a href="<?= $e($loginUrl) ?>">Employee Login</a>
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