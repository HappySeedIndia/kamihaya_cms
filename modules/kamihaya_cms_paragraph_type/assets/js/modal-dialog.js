
(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.modalDialog = {
    attach: function (context, settings) {
      const default_open = drupalSettings.modal_dialog.default_open;
      const cookie = drupalSettings.modal_dialog.cookie_name;
      const one_time = drupalSettings.modal_dialog.one_time;
      const kamihayaModal = new bootstrap.Modal(document.getElementById('kamihayaModal'), {});
      if (default_open && !Cookies.get(cookie)) {
        kamihayaModal.toggle();
      }
      $('#kamihayaModal').find('a').each(function () {
        const $this = $(this);
        $(this).click(function () {
          kamihayaModal.toggle();
          if (cookie && $this.attr('href').indexOf('#') == 0 && one_time) {
            const path = $this.attr('href').replace('#', '');
            Cookies.set(cookie, path, { path: drupalSettings.path.baseUrl });
          }
        });
      });
    }
  }
})(jQuery, Drupal, drupalSettings);
