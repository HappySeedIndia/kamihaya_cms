
(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.checkCookie = function () {
    const fields = Object.entries(drupalSettings.exposed_form);
    if (fields.length > 0) {
      for (const [key, value] of fields) {
        if ($('input[name="' + key + '"]').val().length < value) {
          $('input[type="submit"]').prop("disabled", true);
          $('input[type="submit"]').parent().addClass('disabled');
        }
        $('input[name="' + key + '"]').on('keyup', function () {
          var text_count = $(this).val().length;
          if (text_count >= value) {
            $('input[type="submit"]').prop("disabled", false);
            $('input[type="submit"]').parent().removeClass('disabled');
          }
          else {
            $('input[type="submit"]').prop("disabled", true);
            $('input[type="submit"]').parent().addClass('disabled');
          }
        });
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
