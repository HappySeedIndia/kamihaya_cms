(function ($, Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.advanceFilterButtonControl = {
    attach: function (context, settings) {
      const exposedSettings = drupalSettings.exposed_form || {};
      const filterNames = exposedSettings.filter_name || [];
      const minlength = exposedSettings.minlength || {};

      $('.views-exposed-form', context).each(function () {
        const $form = $(this);
        const $submit = $form.find('input[type="submit"]');

        /**
         * Check if all filters in filterNames have valid values.
         */
        Drupal.behaviors.advanceFilterButtonControl.checkFilters = function ($formRef = $form) {
          let allValid = false;

          for (const field of filterNames) {
            const $elements = $formRef.find(`[name^="${field}"]`);
            if (!$elements.length) {
              allValid = true;
              break;
            }

            const type = $elements.attr('type') || $elements.prop('tagName').toLowerCase();

            if ($elements.is('select')) {
              const val = $elements.val();
              if (val === '' || val === '_none' || val === 'All') {
                allValid = true;
                break;
              }
            } else if ($elements.is(':checkbox, :radio')) {
              if ($elements.filter(':checked').length === 0) {
                allValid = true;
                break;
              }
            } else if ($elements.is('input[type="text"]')) {
              const val = $elements.val();
              const fieldMinLength = parseInt(minlength[field]) || 0;
              if (val.trim().length < fieldMinLength) {
                allValid = true;
                break;
              }
            }
          }

          // Enable or disable the submit button based on validation result
          $submit.prop('disabled', allValid);
          $submit.parent().toggleClass('disabled', allValid);
        };

        // Attach events for each field only once
        for (const field of filterNames) {
          const elements = once(
            `customFilterField--${field}`,
            $form.find(`[name^="${field}"]`).toArray()
          );

          elements.forEach(function (el) {
            $(el).on('change keyup', function () {
              Drupal.behaviors.advanceFilterButtonControl.checkFilters($form);
            });
          });
        }

        // Initial check on page load
        Drupal.behaviors.advanceFilterButtonControl.checkFilters($form);
      });
    }
  };

})(jQuery, Drupal, drupalSettings, once);
