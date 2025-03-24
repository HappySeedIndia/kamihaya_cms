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
})(jQuery, Drupal);
