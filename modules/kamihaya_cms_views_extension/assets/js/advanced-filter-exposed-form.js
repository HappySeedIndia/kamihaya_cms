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
         * Enable the search button if ANY of the filter fields is valid.
         */
        function checkFilters($formRef = $form) {
          let anyValid = false;

          for (const field of filterNames) {
            const $elements = $formRef.find(`[name^="${field}"]`);
            if (!$elements.length) {
              continue;
            }

            const tagName = $elements.prop('tagName')?.toLowerCase();
            const inputType = $elements.attr('type');
            let isValid = false;

            if (tagName === 'select') {
              const val = $elements.val();
              isValid = val !== '' && val !== '_none' && val !== 'All';
            } else if (inputType === 'checkbox' || inputType === 'radio') {
              isValid = $formRef.find(`[name^="${field}"]:checked`).length > 0;
            } else if (inputType === 'text' || tagName === 'textarea' || $elements.hasClass('form-autocomplete')) {
              const val = $elements.val();
              const fieldMinLength = parseInt(minlength[field]) || 0;
              isValid = val && val.trim().length >= fieldMinLength;
            } else if (inputType === 'date' || inputType === 'datetime-local') {
              const val = $elements.val();
              isValid = val !== '';
            } else {
              const val = $elements.val();
              isValid = val !== '';
            }

            // If any one field is valid, break early
            if (isValid) {
              anyValid = true;
              break;
            }
          }

          $submit.prop('disabled', !anyValid);
          $submit.parent().toggleClass('disabled', !anyValid);
        }

        // Bind event listeners once per field
        for (const field of filterNames) {
          const elements = once(`customFilterField--${field}`, $form.find(`[name^="${field}"]`).toArray());
          elements.forEach(function (el) {
            $(el).on('change keyup blur', function () {
              checkFilters($form);
            });
          });
        }

        // Run on initial page load
        checkFilters($form);
      });
    }
  };
})(jQuery, Drupal, drupalSettings, once);
