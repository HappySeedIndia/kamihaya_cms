(function (Drupal) {
  'use strict';

  Drupal.behaviors.checkboxesAjax = {
    attach: function (context, settings) {
      // Process all elements with checkboxes-with-ajax class
      const checkboxWrappers = context.querySelectorAll('.kamihaya-ajax-trigger');

      checkboxWrappers.forEach(function (checkboxWrapper) {
        if (!checkboxWrapper.hasAttribute('data-ajax-processed')) {
          checkboxWrapper.setAttribute('data-ajax-processed', 'true');

          // Get trigger field name from data-trigger-field attribute
          const triggerFieldName = checkboxWrapper.getAttribute('data-trigger');

          if (!triggerFieldName) {
            return; // Skip if trigger field name is not specified
          }

          checkboxWrapper.addEventListener('change', function (e) {
            if (e.target.type === 'checkbox') {
              // Find trigger field by name attribute
              const triggerField = document.querySelector('input[name="' + triggerFieldName + '"]');
              if (triggerField) {
                triggerField.value = Date.now();
                triggerField.dispatchEvent(new Event('change', { bubbles: true }));
              }
            }
          });
        }
      });
    }
  };

})(Drupal);
