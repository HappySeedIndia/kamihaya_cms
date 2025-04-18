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
    attach(context, settings) {
      const basePath = drupalSettings.vboCompareRedirect.comparePath;
      if (!basePath) {
        return;
      }
      // Use once() to ensure the event is bound only once per element
      $(document).off('click.vboCompare').on('click.vboCompare', 'input[data-vbo="vbo-action"][value="Compare"]', function (e) {
        e.preventDefault();
        const labels = [];
        // Collect all checked VBO checkbox labels
        $('input.js-vbo-checkbox:checked', context).each(function () {
          const label = $(this).closest('.form-item').find('label').text().trim();
          if (label) {
            labels.push(label);
          }
        });
        const encodedLabels = encodeURIComponent(labels.join('+'));
        window.location.href = `${basePath}/${encodedLabels}`;
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
