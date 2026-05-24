(function () {
    'use strict';

    var DESKTOP_VISIBLE = 4;
    var MOBILE_MQ = window.matchMedia('(max-width: 767px)');

    function isMobileColumn() {
        return MOBILE_MQ.matches;
    }

    function initCarousel(root) {
        var viewport = root.querySelector('.slingan-blog-roll__viewport');
        var track = root.querySelector('.slingan-blog-roll__track');
        var prev = root.querySelector('.slingan-blog-roll__nav--prev');
        var next = root.querySelector('.slingan-blog-roll__nav--next');

        if (!viewport || !track || !prev || !next) {
            return;
        }

        var slides = track.querySelectorAll('.slingan-tile');
        var total = slides.length;
        var startIndex = 0;

        function gapPx() {
            var gap = parseFloat(window.getComputedStyle(track).gap);
            return Number.isFinite(gap) ? gap : 16;
        }

        function maxStartIndex() {
            return Math.max(0, total - DESKTOP_VISIBLE);
        }

        function offsetForIndex(index) {
            if (index <= 0 || !slides[0] || !slides[index]) {
                return 0;
            }

            if (isMobileColumn()) {
                return Math.round(slides[index].offsetTop - slides[0].offsetTop);
            }

            return Math.round(slides[index].offsetLeft - slides[0].offsetLeft);
        }

        function syncMobileViewportHeight() {
            if (!isMobileColumn() || total === 0) {
                viewport.style.maxHeight = '';
                return;
            }

            var gap = gapPx();
            var height = 0;
            var count = Math.min(DESKTOP_VISIBLE, total);

            for (var i = 0; i < count; i += 1) {
                height += slides[i].getBoundingClientRect().height;
                if (i > 0) {
                    height += gap;
                }
            }

            viewport.style.maxHeight = Math.ceil(height) + 'px';
        }

        function applyTransform(offset) {
            if (isMobileColumn()) {
                track.style.transform = 'translate3d(0, -' + offset + 'px, 0)';
                return;
            }

            track.style.transform = 'translate3d(-' + offset + 'px, 0, 0)';
        }

        function updateNav() {
            var showNav = total > DESKTOP_VISIBLE;

            if (!showNav) {
                prev.hidden = true;
                next.hidden = true;
                return;
            }

            prev.hidden = startIndex === 0;
            next.hidden = startIndex >= maxStartIndex();
        }

        function update() {
            var maxStart = maxStartIndex();

            if (startIndex < 0) {
                startIndex = 0;
            }
            if (startIndex > maxStart) {
                startIndex = maxStart;
            }

            syncMobileViewportHeight();
            applyTransform(offsetForIndex(startIndex));
            updateNav();
        }

        prev.addEventListener('click', function () {
            startIndex -= 1;
            update();
        });

        next.addEventListener('click', function () {
            startIndex += 1;
            update();
        });

        window.addEventListener('resize', update);

        if (typeof MOBILE_MQ.addEventListener === 'function') {
            MOBILE_MQ.addEventListener('change', update);
        } else if (typeof MOBILE_MQ.addListener === 'function') {
            MOBILE_MQ.addListener(update);
        }

        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(update);
        }

        requestAnimationFrame(function () {
            requestAnimationFrame(update);
        });
    }

    function boot() {
        document.querySelectorAll('[data-slingan-blog-roll]').forEach(initCarousel);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
