
(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.checkCookie = function() {
    var result = true;
    const cookie = drupalSettings.modal_dialog.cookie_name;
    const cookie_data = document.cookie.split(" ").join("").split(";");
    if (cookie_data.length > 0) {
      for (var i = 0; i < cookie_data.length; i++) {
        var data = cookie_data[i];
        if (data.indexOf("=") >= 0) {
          var datas = data.split("=");
          if (datas.length == 2) {
            if (datas[0].indexOf(cookie) >= 0 && datas[1] != "") {
              result = false;
            }
          }
        }
      }
    }
    return result;
  };

  Drupal.behaviors.saveCookie = function (type) {
    const cookie = drupalSettings.modal_dialog.cookie_name;
    const expire = new Date("2037/12/31 23:59:59").toGMTString();
    const value = cookie + "=" + type + "; expires=" + expire + "; path=" + drupalSettings.path.baseUrl;
    document.cookie = value;
  };

  Drupal.behaviors.modalDialog = {
    attach: function (context, settings) {
      const default_open = drupalSettings.modal_dialog.default_open;
      const cookie = drupalSettings.modal_dialog.cookie_name;
      const one_time = drupalSettings.modal_dialog.one_time;
      const domain = $(location).attr('host');
      const kamihayaModal = new bootstrap.Modal(document.getElementById('kamihayaModal'), {});
      if (default_open && Drupal.behaviors.checkCookie()) {
        kamihayaModal.toggle();
      }
      $('#kamihayaModal').find('a').each(function () {
        const $this = $(this);
        $(this).click(function () {
          kamihayaModal.hide();
          if (cookie && $this.attr('href').indexOf('#') == 0 && one_time) {
            const path = $this.attr('href').replace('#', '');
            Drupal.behaviors.saveCookie(path);
          }
        });
      });
      $('#kamihayaModal').find('button[aria-label="Close"]').each(function () {
        const $this = $(this);
        $(this).click(function () {
          kamihayaModal.hide();
          Drupal.behaviors.saveCookie('close');
        });
      });
    }
  }
})(jQuery, Drupal, drupalSettings);
