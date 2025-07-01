(function ($, Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.advanceFilterButtonControl = {
    attach: function (context, settings) {
      // Retrieve filter settings passed from backend via drupalSettings.
      const exposedSettings = drupalSettings.exposed_form || {};
      const filterNames = exposedSettings.filter_name || [];
      const minlength = exposedSettings.minlength || {};

      // Loop over each Views exposed form on the page.
      $('.views-exposed-form', context).each(function () {
        const $form = $(this);
        const $submit = $form.find('input[type="submit"]');

        /**
         * Validate all filters defined in `filterNames`.
         * If all filters are valid, enable the submit button.
         * If any is invalid, disable it.
         */
        function checkFilters($formRef = $form) {
          let allValid = true;

          for (const field of filterNames) {
            // Handle multi-field inputs like checkboxes (e.g., field_name[0], [1], etc.)
            const $elements = $formRef.find(`[name^="${field}"]`);
            if (!$elements.length) {
              allValid = false;
              break;
            }

            const tagName = $elements.prop('tagName').toLowerCase();
            const inputType = $elements.attr('type');

            let isValid = true;

            // Handle select inputs
            if (tagName === 'select') {
              const val = $elements.val();
              isValid = val !== '' && val !== '_none' && val !== 'All';
            }
            // Handle checkbox and radio inputs
            else if (inputType === 'checkbox' || inputType === 'radio') {
              isValid = $formRef.find(`[name^="${field}"]:checked`).length > 0;
            }
            // Handle text/autocomplete/textarea fields
            else if (inputType === 'text' || tagName === 'textarea' || $elements.hasClass('form-autocomplete')) {
              const val = $elements.val();
              const fieldMinLength = parseInt(minlength[field]) || 0;
              isValid = val && val.trim().length >= fieldMinLength;
            }
            // Handle date fields
            else if (inputType === 'date' || inputType === 'datetime-local') {
              const val = $elements.val();
              isValid = val !== '';
            }
            // Fallback for other field types
            else {
              const val = $elements.val();
              isValid = val !== '';
            }

            if (!isValid) {
              allValid = false;
              break;
            }
          }

          // Toggle the submit button based on validation result
          $submit.prop('disabled', !allValid);
          $submit.parent().toggleClass('disabled', !allValid);
        }

        // Bind validation check to change/keyup/blur events for each filter field
        for (const field of filterNames) {
          const elements = once(`customFilterField--${field}`, $form.find(`[name^="${field}"]`).toArray());

          elements.forEach(function (el) {
            $(el).on('change keyup blur', function () {
              checkFilters($form);
            });
          });
        }

        // Initial check on page load
        checkFilters($form);
      });
    }
  };

})(jQuery, Drupal, drupalSettings, once);
