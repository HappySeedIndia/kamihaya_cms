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
      const basePath = drupalSettings.vboCompareRedirect?.comparePath;
      if (!basePath) return;
      console.log('basePath:', basePath);
      $(document).off('click.vboCompare').on('click.vboCompare', 'input[data-vbo="vbo-action"][value="Compare"]', function (e) {
        e.preventDefault();

        // Add a short delay to ensure DOM is stable
        setTimeout(() => {
          const labels = [];
          $('input.js-vbo-checkbox:checked').each(function () {
            const label = $(this).closest('.form-item').find('label').text().trim();
            if (label) {
              labels.push(label);
            }
          });

          if (labels.length === 0) {
            alert('Please select at least one item to compare.');
            return;
          }

          const encodedLabels = encodeURIComponent(labels.join('+'));
          const redirectUrl = `${basePath}/${encodedLabels}`;

          console.log('Redirecting to:', redirectUrl);
          window.location.href = redirectUrl;
        }, 500); // Delay in milliseconds (adjust if needed)
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
