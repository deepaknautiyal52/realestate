(function () {
  'use strict';

  // Property gallery: clicking a thumbnail swaps the main photo.
  document.querySelectorAll('[data-rec-gallery]').forEach(function (gallery) {
    var main = gallery.querySelector('[data-rec-main]');
    gallery.querySelectorAll('.rec-gallery-thumbs button').forEach(function (btn) {
      btn.addEventListener('click', function () {
        main.src = btn.getAttribute('data-full');
        main.removeAttribute('srcset');
        gallery.querySelectorAll('.rec-gallery-thumbs button').forEach(function (b) { b.classList.toggle('active', b === btn); });
      });
    });
  });

  // Header shadow once the page is scrolled.
  var onScroll = function () { document.body.classList.toggle('rec-scrolled', window.scrollY > 10); };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  // Fade sections and cards in as they scroll into view.
  if ('IntersectionObserver' in window) {
    var targets = document.querySelectorAll('.rec-card, .rec-location, .rec-section, .rec-features .elementor-widget-icon-box, .rec-section-band .elementor-widget-heading');
    if (targets.length) {
      document.documentElement.classList.add('rec-js');
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            var el = entry.target;
            el.classList.add('rec-in');
            io.unobserve(el);
            // Hand transitions back to the element's own hover styles once revealed.
            setTimeout(function () {
              el.classList.remove('rec-reveal', 'rec-in');
              el.style.transitionDelay = '';
            }, 1000);
          }
        });
      }, { rootMargin: '0px 0px -40px 0px' });
      targets.forEach(function (el, i) {
        el.classList.add('rec-reveal');
        el.style.transitionDelay = (i % 3) * 80 + 'ms';
        io.observe(el);
      });
    }
  }

  // Scroll the enquiry form into view after sending.
  if (/[?&]enquiry=/.test(location.search)) {
    var form = document.querySelector('.rec-alert');
    if (form) form.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
})();
