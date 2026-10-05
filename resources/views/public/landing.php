<?php
/**
 * RAM-YUM public landing page.
 *
 * Data contract (all supplied by LandingController; demo fallbacks below let the
 * page render standalone before controller wiring, per project convention):
 *
 *   $landing['stats']    => ['branches' => int, 'employees' => int, 'open_positions' => int]
 *   $landing['branches'] => list of [name, address, hours, contact, status]
 *   $landing['jobs']     => list of [id, title, branch, type, slots, deadline]
 */

$pageTitle = 'RAM-YUM | Korean & Japanese Store';
$pageCSS   = ['landing.css'];
$pageJS    = ['landing.js'];

if (!isset($landing)) {
    $landing = [
        'stats' => ['branches' => 3, 'employees' => 48, 'open_positions' => 4],
        'branches' => [
            ['name' => 'Main Branch',  'address' => 'Quezon City, Metro Manila', 'hours' => '10:00 AM – 10:00 PM', 'contact' => '(02) 8000 0001', 'status' => 'Open'],
            ['name' => 'North Branch', 'address' => 'Quezon City, Metro Manila', 'hours' => '10:00 AM – 9:00 PM',  'contact' => '(02) 8000 0002', 'status' => 'Open'],
            ['name' => 'South Branch', 'address' => 'Metro Manila',             'hours' => '11:00 AM – 9:00 PM',  'contact' => '(02) 8000 0003', 'status' => 'Open'],
        ],
        'jobs' => [
            ['id' => 1, 'title' => 'Service Crew',    'branch' => 'Main Branch',  'type' => 'Full-time', 'slots' => 3, 'deadline' => null],
            ['id' => 2, 'title' => 'Kitchen Staff',   'branch' => 'North Branch', 'type' => 'Full-time', 'slots' => 2, 'deadline' => null],
            ['id' => 3, 'title' => 'Cashier',         'branch' => 'South Branch', 'type' => 'Part-time', 'slots' => 1, 'deadline' => null],
        ],
    ];
}

$stats    = $landing['stats'];
$branches = $landing['branches'];
$jobs     = array_slice($landing['jobs'], 0, 3);

$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$loginUrl   = url('?page=login');
$careersUrl = url('?page=careers');
$applyUrl   = static fn($id) => 'index.php?page=careers&job=' . urlencode((string) $id);
$logoUrl    = asset('images/logo.png');
$heroImg    = is_file(PUBLIC_PATH . '/assets/images/landing/hero.jpg')
    ? asset('images/landing/hero.jpg')
    : '';
$year       = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($pageTitle) ?></title>
    <meta name="description" content="RAM-YUM is a Korean & Japanese store. Explore our branches, join our team, and access the employee portal.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="<?= $e(asset('css/style.css')) ?>">
    <?php foreach ($pageCSS as $css): ?>
        <link rel="stylesheet" href="<?= $e(asset('css/' . $css)) ?>">
    <?php endforeach; ?>
</head>
<body class="lp-body">

<!-- ============ NAVIGATION ============ -->
<header class="lp-nav" id="lpNav">
    <div class="lp-container lp-nav__inner">
        <a href="#home" class="lp-brand" aria-label="RAM-YUM home">
            <img src="<?= $e($logoUrl) ?>" alt="" class="lp-brand__logo" onerror="this.style.display='none'">
            <span class="lp-brand__name">RAM-YUM</span>
        </a>

        <button class="lp-nav__toggle" id="lpNavToggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="lpNavMenu">
            <i class="fa-solid fa-bars"></i>
        </button>

        <nav class="lp-nav__menu" id="lpNavMenu" aria-label="Primary">
            <a href="#home" class="lp-nav__link is-active">Home</a>
            <a href="#about" class="lp-nav__link">About Us</a>
            <a href="#branches" class="lp-nav__link">Our Branches</a>
            <a href="#careers" class="lp-nav__link">Careers</a>
            <a href="#contact" class="lp-nav__link">Contact Us</a>
            <a href="<?= $e($loginUrl) ?>" class="lp-btn lp-btn--primary lp-btn--sm">
                <i class="fa-solid fa-right-to-bracket"></i> Login
            </a>
        </nav>
    </div>
</header>

<main>

<!-- ============ HERO ============ -->
<section class="lp-hero" id="home">
    <div class="lp-container lp-hero__grid">
        <div class="lp-hero__copy">
            <span class="lp-eyebrow"><i class="fa-solid fa-bowl-rice"></i> Korean &amp; Japanese Store</span>
            <h1 class="lp-hero__title">Authentic flavors, <span>served with care.</span></h1>
            <p class="lp-hero__lead">
                RAM-YUM brings Korean and Japanese favorites to your neighborhood, backed by a
                dedicated team that grows together across every branch.
            </p>
            <div class="lp-actions">
                <a href="#about" class="lp-btn lp-btn--primary">Explore Our Company</a>
                <a href="<?= $e($careersUrl) ?>" class="lp-btn lp-btn--outline">Join Our Team</a>
            </div>
            <ul class="lp-hero__facts">
                <li><i class="fa-solid fa-location-dot"></i> <?= (int) $stats['branches'] ?> branches</li>
                <li><i class="fa-solid fa-users"></i> <?= (int) $stats['employees'] ?> team members</li>
                <li><i class="fa-solid fa-briefcase"></i> <?= (int) $stats['open_positions'] ?> open roles</li>
            </ul>
        </div>

        <div class="lp-hero__visual" aria-hidden="true">
            <div class="lp-hero__frame" <?php if ($heroImg !== ''): ?>style="background-image:url('<?= $e($heroImg) ?>')"<?php endif; ?>>
                <div class="lp-hero__art">
                    <i class="fa-solid fa-bowl-food"></i>
                </div>
            </div>
            <div class="lp-hero__chip lp-hero__chip--top">
                <i class="fa-solid fa-star"></i>
                <div><strong>Quality first</strong><small>Fresh, every day</small></div>
            </div>
            <div class="lp-hero__chip lp-hero__chip--bottom">
                <i class="fa-solid fa-user-plus"></i>
                <div><strong>We're hiring</strong><small><?= (int) $stats['open_positions'] ?> open positions</small></div>
            </div>
        </div>
    </div>
</section>

<!-- ============ ABOUT ============ -->
<section class="lp-section" id="about">
    <div class="lp-container lp-about">
        <div class="lp-about__text">
            <span class="lp-eyebrow lp-eyebrow--plain">About RAM-YUM</span>
            <h2 class="lp-h2">A neighborhood store built on Korean and Japanese flavors</h2>
            <p>
                RAM-YUM is a Korean &amp; Japanese store serving ramen, rice meals, and
                everyday favorites. We focus on consistent quality, friendly service, and a
                workplace where every team member can build a career.
            </p>
            <p>
                Behind every branch is a team supported by one connected system for hiring,
                onboarding, and employee records, so people and operations stay in step.
            </p>
        </div>
        <div class="lp-about__points">
            <div class="lp-mini"><i class="fa-solid fa-utensils"></i><div><strong>Korean &amp; Japanese menu</strong><span>Familiar favorites, prepared consistently.</span></div></div>
            <div class="lp-mini"><i class="fa-solid fa-store"></i><div><strong>Multiple branches</strong><span>Close to where our customers are.</span></div></div>
            <div class="lp-mini"><i class="fa-solid fa-seedling"></i><div><strong>Careers that grow</strong><span>Open roles and clear onboarding.</span></div></div>
        </div>
    </div>
</section>

<!-- ============ AT A GLANCE ============ -->
<section class="lp-section lp-section--tint" id="glance">
    <div class="lp-container">
        <header class="lp-head">
            <span class="lp-eyebrow lp-eyebrow--plain">RAM-YUM at a Glance</span>
            <h2 class="lp-h2">Our company in numbers</h2>
        </header>
        <div class="lp-stats">
            <div class="lp-stat">
                <span class="lp-stat__icon"><i class="fa-solid fa-store"></i></span>
                <strong class="lp-stat__value" data-count="<?= (int) $stats['branches'] ?>"><?= (int) $stats['branches'] ?></strong>
                <span class="lp-stat__label">Branches</span>
            </div>
            <div class="lp-stat">
                <span class="lp-stat__icon"><i class="fa-solid fa-users"></i></span>
                <strong class="lp-stat__value" data-count="<?= (int) $stats['employees'] ?>"><?= (int) $stats['employees'] ?></strong>
                <span class="lp-stat__label">Employees</span>
            </div>
            <div class="lp-stat">
                <span class="lp-stat__icon"><i class="fa-solid fa-briefcase"></i></span>
                <strong class="lp-stat__value" data-count="<?= (int) $stats['open_positions'] ?>"><?= (int) $stats['open_positions'] ?></strong>
                <span class="lp-stat__label">Open positions</span>
            </div>
        </div>
    </div>
</section>

<!-- ============ BRANCHES ============ -->
<section class="lp-section" id="branches">
    <div class="lp-container">
        <header class="lp-head">
            <span class="lp-eyebrow lp-eyebrow--plain">Our Branches</span>
            <h2 class="lp-h2">Find a RAM-YUM near you</h2>
        </header>

        <?php if (empty($branches)): ?>
            <p class="lp-empty">Branch information will be available soon.</p>
        <?php else: ?>
        <div class="lp-grid lp-grid--3">
            <?php foreach ($branches as $b): ?>
                <article class="lp-card lp-branch">
                    <div class="lp-branch__top">
                        <h3><?= $e($b['name']) ?></h3>
                        <span class="lp-badge lp-badge--ok"><?= $e($b['status'] ?? 'Open') ?></span>
                    </div>
                    <ul class="lp-info">
                        <li><i class="fa-solid fa-location-dot"></i><span><?= $e($b['address']) ?></span></li>
                        <li><i class="fa-regular fa-clock"></i><span><?= $e($b['hours']) ?></span></li>
                        <li><i class="fa-solid fa-phone"></i><span><?= $e($b['contact']) ?></span></li>
                    </ul>
                </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============ WHY RAM-YUM ============ -->
<section class="lp-section lp-section--tint" id="why">
    <div class="lp-container">
        <header class="lp-head">
            <span class="lp-eyebrow lp-eyebrow--plain">Why RAM-YUM</span>
            <h2 class="lp-h2">What we stand for</h2>
        </header>
        <div class="lp-grid lp-grid--4">
            <div class="lp-card lp-value"><span class="lp-value__icon"><i class="fa-solid fa-award"></i></span><h3>Quality</h3><p>Fresh ingredients and consistent recipes at every branch.</p></div>
            <div class="lp-card lp-value"><span class="lp-value__icon"><i class="fa-solid fa-handshake-angle"></i></span><h3>Service</h3><p>Warm, attentive service that makes guests return.</p></div>
            <div class="lp-card lp-value"><span class="lp-value__icon"><i class="fa-solid fa-people-group"></i></span><h3>People</h3><p>A respectful team culture where everyone is supported.</p></div>
            <div class="lp-card lp-value"><span class="lp-value__icon"><i class="fa-solid fa-arrow-trend-up"></i></span><h3>Growth</h3><p>Training and opportunities to move forward with us.</p></div>
        </div>
    </div>
</section>

<!-- ============ CAREERS ============ -->
<section class="lp-section" id="careers">
    <div class="lp-container">
        <header class="lp-head lp-head--row">
            <div>
                <span class="lp-eyebrow lp-eyebrow--plain">Careers</span>
                <h2 class="lp-h2">Current opportunities</h2>
            </div>
            <a href="<?= $e($careersUrl) ?>" class="lp-btn lp-btn--outline lp-btn--sm">View All Opportunities <i class="fa-solid fa-arrow-right"></i></a>
        </header>

        <?php if (empty($jobs)): ?>
            <p class="lp-empty">No open positions right now. Please check back soon.</p>
        <?php else: ?>
        <div class="lp-grid lp-grid--3">
            <?php foreach ($jobs as $j): ?>
                <article class="lp-card lp-job">
                    <div class="lp-job__top">
                        <h3><?= $e($j['title']) ?></h3>
                        <span class="lp-badge"><?= $e($j['type']) ?></span>
                    </div>
                    <ul class="lp-info">
                        <li><i class="fa-solid fa-location-dot"></i><span><?= $e($j['branch']) ?></span></li>
                        <li><i class="fa-solid fa-user-group"></i><span><?= (int) $j['slots'] ?> <?= (int) $j['slots'] === 1 ? 'slot' : 'slots' ?> available</span></li>
                        <?php if (!empty($j['deadline'])): ?>
                            <li><i class="fa-regular fa-calendar"></i><span>Apply by <?= $e(date('M j, Y', strtotime($j['deadline']))) ?></span></li>
                        <?php endif; ?>
                    </ul>
                    <a href="<?= $e($applyUrl($j['id'])) ?>" class="lp-link">View &amp; apply <i class="fa-solid fa-arrow-right"></i></a>
                </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============ EMPLOYEE PORTAL ============ -->
<section class="lp-section lp-section--tight" id="portal">
    <div class="lp-container">
        <div class="lp-portal">
            <div class="lp-portal__icon"><i class="fa-solid fa-id-badge"></i></div>
            <div class="lp-portal__text">
                <h2>Employee Portal</h2>
                <p>Current RAM-YUM team members can sign in to view their profile, records, and updates.</p>
            </div>
            <a href="<?= $e($loginUrl) ?>" class="lp-btn lp-btn--gold">Employee Login <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>
</section>

<!-- ============ CONTACT ============ -->
<section class="lp-section" id="contact">
    <div class="lp-container">
        <header class="lp-head">
            <span class="lp-eyebrow lp-eyebrow--plain">Contact Us</span>
            <h2 class="lp-h2">Get in touch</h2>
        </header>
        <div class="lp-grid lp-grid--3">
            <div class="lp-card lp-contact"><i class="fa-solid fa-location-dot"></i><div><strong>Head office</strong><span>Quezon City, Metro Manila</span></div></div>
            <div class="lp-card lp-contact"><i class="fa-solid fa-envelope"></i><div><strong>Email</strong><span>hello@ramyum.example</span></div></div>
            <div class="lp-card lp-contact"><i class="fa-solid fa-phone"></i><div><strong>Phone</strong><span>(02) 8000 0000</span></div></div>
        </div>
    </div>
</section>

</main>

<!-- ============ FOOTER ============ -->
<footer class="lp-footer">
    <div class="lp-container lp-footer__inner">
        <div class="lp-footer__brand">
            <span class="lp-brand__name">RAM-YUM</span>
            <small>Korean &amp; Japanese Store</small>
        </div>
        <nav class="lp-footer__nav" aria-label="Footer">
            <a href="#about">About Us</a>
            <a href="#branches">Our Branches</a>
            <a href="#careers">Careers</a>
            <a href="#contact">Contact Us</a>
            <a href="<?= $e($loginUrl) ?>">Employee Login</a>
        </nav>
    </div>
    <div class="lp-container lp-footer__copy">&copy; <?= $e($year) ?> RAM-YUM. All rights reserved.</div>
</footer>

<?php foreach ($pageJS as $js): ?>
    <script src="<?= $e(asset('js/' . $js)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>