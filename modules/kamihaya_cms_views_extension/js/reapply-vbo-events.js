(function ($, Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.customVboFix = {
    attach: function (context, settings) {
      $('.vbo-view-form', context).each(function () {
        Drupal.viewsBulkOperationsFrontUi(this);
      });
    }
  };

  Drupal.behaviors.vboCompareRedirect = {
    attach(context) {
      const basePath = drupalSettings.vboCompareRedirect?.comparePath;
      if (!basePath) {
        return;
      }

      // Bind the click handler exactly once per Compare button in this context.
      once('vboCompare', 'input[data-vbo="vbo-action"][value="Compare"]', context)
        .forEach((el) => {
          $(el).on('click', (e) => {
            e.preventDefault();

            const labels = [];
            $('input.js-vbo-checkbox:checked').each(function () {
              const text = $(this).closest('.form-item').find('label').text().trim();
              if (text) {
                labels.push(text);
              }
            });

            const encoded = encodeURIComponent(labels.join('+'));
            window.location.href = `${basePath}/${encoded}`;
          });
        });
    }
  };

})(jQuery, Drupal, drupalSettings, once);
