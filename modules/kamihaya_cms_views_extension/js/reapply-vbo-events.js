(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.customVboFix = {
    attach: function (context, settings) {
      $('.vbo-view-form', context).each(function () {
        Drupal.viewsBulkOperationsFrontUi(this);
      });
    }
  };
})(jQuery, Drupal);