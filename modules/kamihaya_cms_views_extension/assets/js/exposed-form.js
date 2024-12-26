
(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.checkCookie = function () {
    const fields = Object.entries(drupalSettings.exposed_form);
    if (fields.length > 0) {
      for (const [key, value] of fields) {
        if ($('.views-exposed-form input[name="' + key + '"]').val().length < value) {
          $('.views-exposed-form input[type="submit"]').prop("disabled", true);
          $('.views-exposed-form input[type="submit"]').parent().addClass('disabled');
        }
        $('.views-exposed-form input[name="' + key + '"]').on('keyup', function () {
          var text_count = $(this).val().length;
          if (text_count >= value) {
            $('.views-exposed-form input[type="submit"]').prop("disabled", false);
            $('.views-exposed-form input[type="submit"]').parent().removeClass('disabled');
          }
          else {
            $('.views-exposed-form input[type="submit"]').prop("disabled", true);
            $('.views-exposed-form input[type="submit"]').parent().addClass('disabled');
          }
        });
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
