(function ($, Drupal, drupalSettings, once) {
  Drupal.behaviors.dynamicFilterLabel = {
    attach: function (context) {
      const map = drupalSettings.dynamicFilterLabel?.map || {};
      const currentTermId = drupalSettings.dynamicFilterLabel?.currentTermId || null;

      if (!Object.keys(map).length) {
        return;
      }

      // ✅ Apply immediately if we have a known current term
      if (currentTermId && map[currentTermId]) {
        applyLabelMapping(map[currentTermId], context);
      }

      // ✅ Attach to selects only once per render
      once('dynamicFilterLabel', '.views-exposed-form select', context).forEach(function (element) {
        $(element).on('change', function () {
          const val = $(this).val();
          if (!val || !map[val]) {
            restoreOriginalSummaryLabels(context);
            return;
          }
          applyLabelMapping(map[val], context);
        });
      });

      // ---- Helper Functions ----
      function applyLabelMapping(labels, context) {
        // Update summary titles.
        $('.summary-title', context).each(function () {
          const $summary = $(this);
          if (!$summary.data('original-filter-label')) {
            $summary.data('original-filter-label', $summary.text().trim());
          }
          const original = $summary.data('original-filter-label');
          const match = Object.keys(labels).find(
            key => original.toLowerCase() === key.toLowerCase()
          );
          if (match && labels[match]) {
            $summary.text(labels[match]);
          } else {
            $summary.text(original);
          }
        });
      }

      function restoreOriginalSummaryLabels(context) {
        $('.summary-title', context).each(function () {
          const $summary = $(this);
          if ($summary.data('original-filter-label')) {
            $summary.text($summary.data('original-filter-label'));
          }
        });
      }
    },
  };
})(jQuery, Drupal, drupalSettings, once);
