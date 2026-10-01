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

  // Scroll the enquiry form into view after sending.
  if (/[?&]enquiry=/.test(location.search)) {
    var form = document.querySelector('.rec-alert');
    if (form) form.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
})();
