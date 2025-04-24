(function ($, Drupal) {
  Drupal.behaviors.floatingDivs = {
    attach: function (context, settings) {
      var $floatingBox = $('.anchor-links.sticky', context);
      var $stopContainer = $('.sticky-end', context);
      var lastScrollY = $(window).scrollTop();
      var isDetached = false;

      function checkScroll() {
        var floatingRect = $floatingBox[0].getBoundingClientRect();
        var stopRect = $stopContainer[0].getBoundingClientRect();
        var currentScrollY = $(window).scrollTop();

        // Display the floating box when the top of the floating box is at the top of the viewport
        if (!isDetached && floatingRect.top >= stopRect.top) {
          $floatingBox.addClass("normal-position");
          $floatingBox.removeClass("fade-in");
          isDetached = true;
        }
        // Enable the floating box when the bottom of the floating box is at the bottom of the viewport
        else if (isDetached && (window.innerHeight + currentScrollY) < ($stopContainer.offset().top + $stopContainer.outerHeight())) {
          $floatingBox.removeClass("normal-position");
          $floatingBox.addClass("fade-in");
          isDetached = false;
        }

        lastScrollY = currentScrollY;
      }

      $(window).on("scroll", checkScroll);
      checkScroll();
    }
  };

  Drupal.behaviors.anchorFix = {
    attach: function (context, settings) {

      // Handle anchor link clicks inside the sticky .anchor-links container
      $('.anchor-links.sticky a[href^="#"]', context).each(function () {
        $(this).off('click').on('click', function (event) {
          var targetId = this.hash.substring(1);
          var targetElement = $('#' + targetId);
          var $stickyBox = $('.anchor-links.sticky');

          // Skip scroll behavior if sticky is in normal-position state
          if ($stickyBox.hasClass('normal-position')) {
            return;
          }

          // If target element exists, scroll to it with offset
          if (targetElement.length) {
            event.preventDefault();

            var floatingBoxHeight = $stickyBox.outerHeight();

            // Scroll to the target position + sticky height (pre-adjustment)
            var initialPosition = targetElement.offset().top + floatingBoxHeight;
            $('html, body').animate({ scrollTop: initialPosition }, {
              duration: 0,
              complete: function () {
                // Trigger a custom event after scrolling
                $(document).trigger('anchorScrolled', [targetElement]);
              }
            });
          }
        });
      });

      // Custom event to handle fine adjustment of scroll after animation
      $(document).on('anchorScrolled', function (event, targetElement) {
        var $stickyBox = $('.anchor-links.sticky');
        var floatingBoxHeight = $stickyBox.outerHeight();
        var targetTop = targetElement.offset().top;

        // Wait before final adjustment to ensure any sticky state updates are applied
        setTimeout(function () {
          var updatedHeight = $stickyBox.outerHeight();
          var adjustedPosition = targetTop + updatedHeight;

          // Final scroll adjustment with smooth behavior
          window.scrollTo({ top: adjustedPosition, behavior: "smooth" });
        }, 500);
      });

      // If the page loads with a hash in the URL, scroll to the correct position
      $(window).on('load', function () {
        if (window.location.hash) {
          var targetElement = $(window.location.hash);

          if (targetElement.length) {
            setTimeout(function () {
              var floatingBoxHeight = $('.anchor-links.sticky').outerHeight();
              var targetPosition = targetElement.offset().top - floatingBoxHeight;

              // Scroll to the target position on load
              window.scrollTo({ top: targetPosition, behavior: "smooth" });

              // Trigger custom adjustment after initial scroll
              $(document).trigger('anchorScrolled', [targetElement]);
            }, 1000); // Delay to allow DOM/rendering to complete
          }
        }
      });

    }
  };
})(jQuery, Drupal);