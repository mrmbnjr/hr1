document.addEventListener('DOMContentLoaded', function () {
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- Mobile navigation ---------- */
    var nav = document.getElementById('lpNav');
    var toggle = document.getElementById('lpNavToggle');
    var menu = document.getElementById('lpNavMenu');
    var links = document.querySelectorAll('.lp-nav__link');

    function setOpen(open) {
        if (!menu || !toggle) return;
        menu.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        var icon = toggle.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-bars', !open);
            icon.classList.toggle('fa-xmark', open);
        }
    }

    if (toggle && menu) {
        toggle.addEventListener('click', function () {
            setOpen(!menu.classList.contains('is-open'));
        });
        menu.addEventListener('click', function (e) {
            if (e.target.closest('a')) setOpen(false);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && menu.classList.contains('is-open')) {
                setOpen(false);
                toggle.focus();
            }
        });
        document.addEventListener('click', function (e) {
            if (menu.classList.contains('is-open') && !e.target.closest('#lpNav')) setOpen(false);
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) setOpen(false);
        });
    }

    /* ---------- Header background on scroll ---------- */
    function onScroll() {
        if (nav) nav.classList.toggle('is-scrolled', window.scrollY > 8);
    }
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    /* ---------- Scroll-spy ---------- */
    var map = {};
    links.forEach(function (a) {
        var id = (a.getAttribute('href') || '').replace('#', '');
        if (id && id.charAt(0) !== '?' && a.getAttribute('href').charAt(0) === '#') map[id] = a;
    });

    function setActive(id) {
        links.forEach(function (a) {
            a.classList.remove('is-active');
            a.removeAttribute('aria-current');
        });
        if (map[id]) {
            map[id].classList.add('is-active');
            map[id].setAttribute('aria-current', 'true');
        }
    }

    // Sections without a nav link of their own map to the nearest relevant one.
    var alias = { glance: 'home', why: 'about', portal: 'careers' };

    if ('IntersectionObserver' in window) {
        var spy = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                var id = entry.target.id;
                setActive(map[id] ? id : (alias[id] || id));
            });
        }, { rootMargin: '-40% 0px -55% 0px' });

        ['home', 'glance', 'about', 'menu', 'gallery', 'why', 'careers', 'portal', 'contact'].forEach(function (id) {
            var s = document.getElementById(id);
            if (s) spy.observe(s);
        });
    }

    /* ---------- Reveal on scroll ---------- */
    var revealEls = document.querySelectorAll('[data-reveal]');
    if ('IntersectionObserver' in window && !reduceMotion) {
        var reveal = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -5% 0px' });
        revealEls.forEach(function (el) { reveal.observe(el); });
    } else {
        revealEls.forEach(function (el) { el.classList.add('is-visible'); });
    }

    /* ---------- Animated counters ---------- */
    var counters = document.querySelectorAll('[data-count]');

    function runCounter(el) {
        var target = parseInt(el.getAttribute('data-count'), 10);
        if (isNaN(target) || target <= 0) return;
        var duration = 1200;
        var start = null;
        function step(ts) {
            if (start === null) start = ts;
            var p = Math.min((ts - start) / duration, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(target * eased).toLocaleString();
            if (p < 1) requestAnimationFrame(step);
            else el.textContent = target.toLocaleString();
        }
        el.textContent = '0';
        requestAnimationFrame(step);
    }

    if ('IntersectionObserver' in window && !reduceMotion) {
        var cObs = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    runCounter(entry.target);
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.6 });
        counters.forEach(function (el) { cObs.observe(el); });
    }

    /* ---------- Gallery lightbox ---------- */
    var lightbox = document.getElementById('lpLightbox');
    if (lightbox) {
        var lightboxImage = lightbox.querySelector('.lp-lightbox__image');
        var lightboxCaption = lightbox.querySelector('.lp-lightbox__caption');
        var lightboxClose = lightbox.querySelector('.lp-lightbox__close');

        document.querySelectorAll('[data-lightbox]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                var thumbnail = trigger.querySelector('img');
                if (!thumbnail || !lightboxImage || !lightboxCaption) return;
                lightboxImage.src = trigger.getAttribute('data-lightbox-src');
                lightboxImage.alt = thumbnail.alt;
                lightboxCaption.textContent = trigger.getAttribute('data-lightbox-caption');
                lightbox.showModal();
            });
        });

        if (lightboxClose) {
            lightboxClose.addEventListener('click', function () { lightbox.close(); });
        }
        lightbox.addEventListener('click', function (e) {
            if (e.target === lightbox) lightbox.close();
        });
    }
});