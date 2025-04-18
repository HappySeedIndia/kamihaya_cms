(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.customVboFix = {
    attach: function (context, settings) {
      $('.vbo-view-form', context).each(function () {
        Drupal.viewsBulkOperationsFrontUi(this);
      });
    }
  };

  Drupal.behaviors.vboCompareRedirect = {
    attach: function (context, settings) {
      // Ensure the behavior is attached only once
      $(document).off('click.vboCompare').on('click.vboCompare', 'input[data-vbo="vbo-action"][value="Compare"]', function (e) {
        e.preventDefault(); // Prevent default form submission
        let labels = [];
        // Collect all checked VBO checkboxes
        $('input.js-vbo-checkbox:checked', context).each(function () {
          const $checkbox = $(this);
          const label = $checkbox.closest('.form-item').find('label').text().trim();
          if (label) {
            labels.push(label);
          }
        });
        // Encode labels for safe use in URL
        const encodedLabels = encodeURIComponent(labels.join('+'));
        // Get compare URL path from Drupal settings
        const basePath = drupalSettings.vboCompareRedirect.comparePath;
        // Redirect to the comparison page with encoded labels
        window.location.href = `${basePath}/${encodedLabels}`;
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
