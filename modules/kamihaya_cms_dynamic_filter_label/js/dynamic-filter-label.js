(function ($, Drupal, drupalSettings, once) {
  Drupal.behaviors.dynamicFilterLabel = {
    attach: function (context) {
      const map = drupalSettings.dynamicFilterLabel || {};
      if (!Object.keys(map).length) {
        return;
      }

      // Use once() so we only attach to selects once per render.
      once('dynamicFilterLabel', '.views-exposed-form select', context).forEach(function (element) {
        $(element).on('change', function () {
          const val = $(this).val();
          const labels = map[val] || {};

          // Handle .summary-title updates.
          $('.summary-title', context).each(function () {
            const $summary = $(this);

            // Store the original text the first time.
            if (!$summary.data('original-filter-label')) {
              $summary.data('original-filter-label', $summary.text().trim());
            }

            const original = $summary.data('original-filter-label');
            const currentText = $summary.text().trim();
            const keyMatch = Object.keys(labels).find(
              key => currentText.toLowerCase() === key.toLowerCase() ||
                     original.toLowerCase() === key.toLowerCase()
            );

            if (keyMatch && labels[keyMatch]) {
              // Apply mapped label
              $summary.text(labels[keyMatch]);
            } else {
              // Restore original if no mapping found for this selection
              $summary.text(original);
            }
          });

          // Handle form label updates (e.g., exposed filters)
          // Object.entries(labels).forEach(([key, labelText]) => {
          //   const $label = $('label[for="edit-' + key + '"]', context);
          //   if ($label.length) {
          //     $label.text(labelText);
          //   } else {
          //     $('[id*="' + key + '"]', context)
          //       .closest('.form-item')
          //       .find('label')
          //       .first()
          //       .text(labelText);
          //   }
          // });
        });
      });
    },
  };
})(jQuery, Drupal, drupalSettings, once);
