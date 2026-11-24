/* Bia Modas - barra de rolagem sobreposta (nao ocupa espaco no layout) */
(function () {
    'use strict';

    var MIN_THUMB_HEIGHT = 40;
    var root = document.documentElement;
    var bar = null;
    var thumb = null;
    var dragging = false;
    var dragStartY = 0;
    var dragStartScroll = 0;
    var pendingFrame = 0;

    function isScrollLocked() {
        var body = document.body;
        return body.classList.contains('modal-open')
            || body.style.overflow === 'hidden'
            || body.style.overflowY === 'hidden'
            || root.style.overflow === 'hidden';
    }

    function getMetrics() {
        var viewport = window.innerHeight;
        var total = Math.max(root.scrollHeight, document.body.scrollHeight);
        return {
            viewport: viewport,
            total: total,
            maxScroll: total - viewport
        };
    }

    function getThumbHeight(metrics) {
        return Math.max(MIN_THUMB_HEIGHT, (metrics.viewport * metrics.viewport) / metrics.total);
    }

    function render() {
        pendingFrame = 0;
        if (!bar) return;

        var metrics = getMetrics();
        var visible = metrics.maxScroll > 0 && !isScrollLocked();

        bar.classList.toggle('is-visible', visible);
        if (!visible) return;

        var thumbHeight = getThumbHeight(metrics);
        var maxTop = metrics.viewport - thumbHeight;
        var scrollTop = window.scrollY || root.scrollTop || 0;
        var top = (scrollTop / metrics.maxScroll) * maxTop;

        thumb.style.height = thumbHeight + 'px';
        thumb.style.transform = 'translateY(' + Math.min(maxTop, Math.max(0, top)) + 'px)';
    }

    function scheduleRender() {
        if (pendingFrame) return;
        pendingFrame = window.requestAnimationFrame(render);
    }

    function onPointerDown(event) {
        if (event.pointerType === 'mouse' && event.button !== 0) return;
        event.preventDefault();
        dragging = true;
        dragStartY = event.clientY;
        dragStartScroll = window.scrollY || root.scrollTop || 0;
        thumb.classList.add('is-dragging');
        thumb.setPointerCapture(event.pointerId);
    }

    function onPointerMove(event) {
        if (!dragging) return;

        var metrics = getMetrics();
        var thumbHeight = getThumbHeight(metrics);
        var maxTop = metrics.viewport - thumbHeight;
        if (metrics.maxScroll <= 0 || maxTop <= 0) return;

        var delta = ((event.clientY - dragStartY) * metrics.maxScroll) / maxTop;
        window.scrollTo(0, Math.min(metrics.maxScroll, Math.max(0, dragStartScroll + delta)));
    }

    function onPointerUp(event) {
        if (!dragging) return;
        dragging = false;
        thumb.classList.remove('is-dragging');
        if (thumb.hasPointerCapture(event.pointerId)) {
            thumb.releasePointerCapture(event.pointerId);
        }
        scheduleRender();
    }

    function build() {
        if (document.querySelector('.page-scrollbar')) return;
        if (window.matchMedia && window.matchMedia('(hover: none)').matches) return;

        bar = document.createElement('div');
        bar.className = 'page-scrollbar';
        bar.setAttribute('aria-hidden', 'true');

        thumb = document.createElement('div');
        thumb.className = 'page-scrollbar-thumb';

        bar.appendChild(thumb);
        document.body.appendChild(bar);
        root.classList.add('has-overlay-scrollbar');

        thumb.addEventListener('pointerdown', onPointerDown);
        thumb.addEventListener('pointermove', onPointerMove);
        thumb.addEventListener('pointerup', onPointerUp);
        thumb.addEventListener('pointercancel', onPointerUp);

        window.addEventListener('scroll', scheduleRender, { passive: true });
        window.addEventListener('resize', scheduleRender);
        window.addEventListener('load', scheduleRender);
        window.addEventListener('orientationchange', scheduleRender);

        if (typeof window.ResizeObserver !== 'undefined') {
            var resizeObserver = new ResizeObserver(scheduleRender);
            resizeObserver.observe(document.body);
            resizeObserver.observe(root);
        }

        if (typeof window.MutationObserver !== 'undefined') {
            var mutationObserver = new MutationObserver(scheduleRender);
            mutationObserver.observe(document.body, { attributes: true, attributeFilter: ['style', 'class'] });
        }

        render();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', build);
    } else {
        build();
    }
})();
