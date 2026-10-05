document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('lpNavToggle');
    var menu = document.getElementById('lpNavMenu');
    var links = document.querySelectorAll('.lp-nav__link');

    function setOpen(open) {
        menu.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    if (toggle && menu) {
        toggle.addEventListener('click', function () {
            setOpen(!menu.classList.contains('is-open'));
        });
        menu.addEventListener('click', function (e) {
            if (e.target.closest('a')) setOpen(false);
        });
    }

    // Highlight the nav link for the section currently in view.
    var map = {};
    links.forEach(function (a) {
        var id = (a.getAttribute('href') || '').replace('#', '');
        if (id) map[id] = a;
    });

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting && map[entry.target.id]) {
                    links.forEach(function (a) { a.classList.remove('is-active'); });
                    map[entry.target.id].classList.add('is-active');
                }
            });
        }, { rootMargin: '-40% 0px -55% 0px' });

        Object.keys(map).forEach(function (id) {
            var section = document.getElementById(id);
            if (section) observer.observe(section);
        });
    }
});