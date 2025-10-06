(function (Drupal) {
  Drupal.behaviors.autoColorBorder = {
    attach(context) {
      // Select all Color Field swatch elements
      context.querySelectorAll('.color_field__swatch').forEach(swatch => {
        // Find closest parent with a background color, fallback to body
        let parent = swatch.parentElement;
        let bgColor = null;

        while (parent && parent !== document.body) {
          const bg = getComputedStyle(parent).backgroundColor;
          // Ignore transparent backgrounds
          if (bg && !bg.includes('rgba(0, 0, 0, 0)') && !bg.includes('transparent')) {
            bgColor = bg;
            break;
          }
          parent = parent.parentElement;
        }
        if (!bgColor) {
          bgColor = getComputedStyle(document.body).backgroundColor;
        }

        const fg = getComputedStyle(swatch).backgroundColor;

        // Convert RGB to numbers
        const [r1, g1, b1] = bgColor.match(/\d+/g).map(Number);
        const [r2, g2, b2] = fg.match(/\d+/g).map(Number);

        // Color difference
        const diff = Math.sqrt(
          (r1 - r2) ** 2 +
          (g1 - g2) ** 2 +
          (b1 - b2) ** 2
        );

        // Only add border if color is too close to background
        if (diff < 50) {
          const bgBrightness = (r1 * 299 + g1 * 587 + b1 * 114) / 1000;
          if (bgBrightness > 128) {
            swatch.style.border = '1px solid rgba(0,0,0,0.3)';
          } else {
            swatch.style.border = '1px solid rgba(255,255,255,0.4)';
          }
        } else {
          // Remove border if contrast is enough
          swatch.style.border = 'none';
        }
      });

      // Ensure Bootstrap tooltips are initialized.
      if (typeof bootstrap === 'undefined' || typeof bootstrap.Tooltip === 'undefined') {
        console.warn('Bootstrap Tooltip is not available. Skipping auto color border behavior.');
        return;
      }
      var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
      var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
      })
    }
  };
})(Drupal);
