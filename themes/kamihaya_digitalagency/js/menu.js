/**
 * @file
 * Functions for supporting tertiary navigation.
 */
(function ($) {
  'use strict';
  Drupal.behaviors.menuBehavior = {
    attach: function () {
      $('.navbar-nav>.nav-item>.dropdown-menu .nav-item.dropdown>.dropdown-toggle').on('click', function (event) {
        return false;
      });
    }
  }

})(jQuery);
