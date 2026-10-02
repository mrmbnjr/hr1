<?php
// Public careers page – no login, no sidebar. Route: ?page=careers
$pageTitle = 'Careers | Ram Yum Korean & Japanese Store';

// Load real data from the controller/router
if (!isset($jobs)) {
    // Fallback to avoid crash if called directly, though router now provides this
    $jobs = [];
} else {
    // If the router passed $formattedJobs instead of $jobs, use that
    if (isset($formattedJobs)) {
        $jobs = $formattedJobs;
    }
}

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

// Only show jobs that are still open and not past their closing date
$jobs = array_values(array_filter($jobs, fn($j) => strtotime($j['closing_date'] . ' 23:59:59') >= time()));
$locations = array_values(array_unique(array_column($jobs, 'location')));
$types     = array_values(array_unique(array_column($jobs, 'employment_type')));
sort($locations); sort($types);

function timeLeft(string $date): array {
    $secs = strtotime($date . ' 23:59:59') - time();
    $days = (int)floor($secs / 86400);
    if ($days >= 1)  return [$days . ($days === 1 ? ' day left' : ' days left'), $days <= 3];
    $hrs = max(1, (int)floor($secs / 3600));
    return [$hrs . ($hrs === 1 ? ' hour left' : ' hours left'), true];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $h($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Luckiest+Guy&family=Baloo+2:wght@600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="public/assets/css/login.css">
    <link rel="stylesheet" href="public/assets/css/job-list.css">
</head>
<body class="careers">

<header class="careers-top">
    <div class="wrap careers-top__inner">
        <a class="brand" href="index.php?page=careers">
            <img src="public/assets/images/logo.png" alt="Ram Yum Logo" style="height: 40px; width: auto;">
            <div class="brand-text">
                <h2 class="brand-title">RAM-YUM <span>Careers</span></h2>
                <p class="brand-subtitle">Korean &amp; Japanese Store</p>
            </div>
        </a>
        <button
            type="button"
            class="theme-toggle"
            id="themeToggle"
            aria-label="Toggle light and dark theme"
            aria-pressed="false"
        >
            <svg
                class="icon icon-sun"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <circle cx="12" cy="12" r="4.2"></circle>
                <path d="M12 2.5v2.4M12 19.1v2.4M4.6 4.6l1.7 1.7M17.7 17.7l1.7 1.7M2.5 12h2.4M19.1 12h2.4M4.6 19.4l1.7-1.7M17.7 6.3l1.7-1.7"></path>
            </svg>
            <svg
                class="icon icon-moon"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="M20.8 14.3A8.5 8.5 0 1 1 9.7 3.2a6.8 6.8 0 0 0 11.1 11.1z"></path>
            </svg>
        </button>
    </div>
</header>

<main class="wrap">
    <section class="hero">
        <h1>Join the Ram Yum team</h1>
        <p id="resultCount"><?= count($jobs) ?> open <?= count($jobs) === 1 ? 'position' : 'positions' ?></p>
    </section>

    <section class="toolbar" aria-label="Search and filter jobs">
        <label class="search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" id="jobSearch" placeholder="Search by job title or department">
        </label>
        <select id="filterLocation" aria-label="Filter by branch">
            <option value="">All branches</option>
            <?php foreach ($locations as $l): ?><option><?= $h($l) ?></option><?php endforeach; ?>
        </select>
        <select id="filterType" aria-label="Filter by employment type">
            <option value="">All types</option>
            <?php foreach ($types as $t): ?><option><?= $h($t) ?></option><?php endforeach; ?>
        </select>
    </section>

    <section class="job-grid" id="jobGrid">
        <?php foreach ($jobs as $j):
            [$left, $soon] = timeLeft($j['closing_date']);
            $vacancies = max(1, (int)$j['vacancies']);
            $applicantLimit = max(1, (int)$j['applicant_limit']);
            $applicantsApplied = (int)$j['applied_count'];
            $pct = min(100, round($applicantsApplied / $applicantLimit * 100));
            $init  = strtoupper(substr(preg_replace('/[^A-Za-z ]/', '', $j['title']), 0, 1) . (strpos($j['title'], ' ') !== false ? substr(strrchr($j['title'], ' '), 1, 1) : ''));
        ?>
        <article class="job" data-text="<?= $h(strtolower($j['title'] . ' ' . $j['department'])) ?>"
                 data-location="<?= $h($j['location']) ?>" data-type="<?= $h($j['employment_type']) ?>">
            <div class="job__head">
                <span class="job__avatar"><?= $h($init) ?></span>
                <div class="job__title">
                    <h2><?= $h($j['title']) ?></h2>
                    <p><?= $h($j['department']) ?></p>
                </div>
                <span class="badge <?= $soon ? 'badge--soon' : 'badge--open' ?>"><?= $soon ? 'Closing soon' : 'Open' ?></span>
            </div>

            <dl class="job__meta">
                <div><dt>Branch</dt><dd><?= $h($j['location']) ?></dd></div>
                <div><dt>Type</dt><dd><?= $h($j['employment_type']) ?></dd></div>
                <div><dt>Vacancies</dt><dd><?= $vacancies ?> <?= $vacancies === 1 ? 'opening' : 'openings' ?></dd></div>
                <div><dt>Application Deadline</dt><dd><?= $h(date('M j, Y', strtotime($j['closing_date']))) ?></dd></div>
            </dl>

            <div class="job__progress">
                <div class="job__progress-text">
                    <span><?= $applicantsApplied ?> applied of <?= $applicantLimit ?> applicant limit</span>
                    <span class="<?= $soon ? 'is-soon' : '' ?>"><i class="fa-regular fa-clock"></i> <?= $h($left) ?></span>
                </div>
                <div class="bar"><span style="width: <?= $pct ?>%"></span></div>
            </div>

            <div class="job__actions">
                <button class="btn btn--ghost btn-view-details" data-id="<?= (int)$j['id'] ?>">
                    <i class="fa-regular fa-eye"></i> View details
                </button>
                <a class="btn btn--primary" href="index.php?page=apply&amp;token=<?= rawurlencode((string)$j['application_token']) ?>">Apply now <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
            </div>
        </article>
        <?php endforeach; ?>
    </section>

    <div class="empty" id="emptyState" <?= $jobs ? 'hidden' : '' ?>>
        <i class="fa-solid fa-bowl-rice"></i>
        <h3>No positions match your search</h3>
        <p>Try a different keyword or clear the filters. New roles are posted here as they open.</p>
    </div>
</main>

<footer class="careers-foot">
    <div class="wrap">&copy; <?= date('Y') ?> Ram Yum Korean &amp; Japanese Store</div>
</footer>

<!-- Job Details Modal -->
<div id="jobModal" class="modal" hidden>
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <button class="modal-close" aria-label="Close modal">&times;</button>
        <div id="modalBody">
            <!-- Content loaded via AJAX -->
            <div class="modal-loader">
                <i class="fa-solid fa-spinner fa-spin"></i> Loading...
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const q = document.getElementById('jobSearch');
    const loc = document.getElementById('filterLocation');
    const type = document.getElementById('filterType');
    const cards = [...document.querySelectorAll('.job')];
    const count = document.getElementById('resultCount');
    const empty = document.getElementById('emptyState');

    function apply() {
        const term = q.value.trim().toLowerCase();
        let shown = 0;
        cards.forEach(c => {
            const ok = (!term || c.dataset.text.includes(term))
                    && (!loc.value || c.dataset.location === loc.value)
                    && (!type.value || c.dataset.type === type.value);
            c.hidden = !ok;
            if (ok) shown++;
        });
        count.textContent = shown + ' of ' + cards.length + ' open ' + (cards.length === 1 ? 'position' : 'positions');
        empty.hidden = shown !== 0;
    }
    [q, loc, type].forEach(el => el.addEventListener('input', apply));

    // Share the persisted theme preference used by the login page.
    const themeToggle = document.getElementById('themeToggle');
    const themeRoot = document.documentElement;
    const themeKey = 'ramyum-theme';
    let savedTheme = null;

    try {
        savedTheme = localStorage.getItem(themeKey);
    } catch {}

    function applyTheme(theme) {
        const isDark = theme === 'dark';
        if (isDark) {
            themeRoot.setAttribute('data-theme', 'dark');
        } else {
            themeRoot.removeAttribute('data-theme');
        }
        themeToggle.setAttribute('aria-pressed', String(isDark));
    }

    const initialTheme = savedTheme ||
        (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    applyTheme(initialTheme);

    themeToggle.addEventListener('click', () => {
        const nextTheme = themeRoot.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        applyTheme(nextTheme);
        try {
            localStorage.setItem(themeKey, nextTheme);
        } catch {}
    });

    // Modal Logic
    const modal = document.getElementById('jobModal');
    const modalBody = document.getElementById('modalBody');
    const modalClose = modal.querySelector('.modal-close');
    const viewButtons = document.querySelectorAll('.btn-view-details');

    const openModal = async (id) => {
        modal.hidden = false;
        document.body.classList.add('modal-open');
        modalBody.innerHTML = `
            <div class="modal-loader">
                <i class="fa-solid fa-spinner fa-spin"></i> Loading details...
            </div>`;

        try {
            const res = await fetch(`index.php?page=job-details&id=${id}`);
            if (!res.ok) throw new Error('Failed to fetch job details');
            const html = await res.text();
            modalBody.innerHTML = html;
        } catch (err) {
            modalBody.innerHTML = `
                <div class="modal-loader">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <p>Error loading details. Please try again.</p>
                </div>`;
        }
    };

    const closeModal = () => {
        modal.hidden = true;
        document.body.classList.remove('modal-open');
    };

    viewButtons.forEach(btn => btn.addEventListener('click', () => openModal(btn.dataset.id)));
    modalClose.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal || e.target.classList.contains('modal-overlay')) closeModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modal.hidden) closeModal(); });
})();
</script>
</body>
</html>