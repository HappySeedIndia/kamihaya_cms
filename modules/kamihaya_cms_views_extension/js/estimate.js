(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.estimateCalculator = {
    attach: function (context, settings) {
      function calculateTotal() {
        var total = 0;

        $('.views-field-kamihaya-cms-views-extension-estimate span[data-price]', context).each(function () {
          var price = parseFloat($(this).attr('data-total-price')) || 0;
          total += price;
        });
        $('.view-footer p .estimate-total').text(total.toFixed(2)); // Update total in a specific element
      }

      // Call initially on page load
      calculateTotal();

      $('.views-field-kamihaya-cms-views-extension-quantity .js-kamihaya-cms-quantity', context).each(function () {
        $(this).on('change', function () {

          let quantity = parseInt($(this).val());

          // Disallow negative numbers for estimation.
          if (quantity < 1) {
            $(this).val(1);
            quantity = 1;
          }

          // Get the price from the estimate field
          const priceField = $(this)
            .closest('.views-field-kamihaya-cms-views-extension-quantity') // Find the closest parent field
            .siblings('.views-field-kamihaya-cms-views-extension-estimate') // Find the next sibling field
            .find('span'); // Get the <p> element inside it

          const price = parseFloat(priceField.data('price')) || 0; // Ensure trimmed text
          // Calculate total
          const total = price * quantity;
          if (priceField.length) {
            priceField.text(total);
            priceField.attr('data-total-price', total);
            calculateTotal();
          }
        });
      });
    }
  };
})(jQuery, Drupal);
