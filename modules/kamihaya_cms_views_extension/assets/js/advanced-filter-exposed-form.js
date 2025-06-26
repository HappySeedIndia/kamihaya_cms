
(function ($, Drupal, drupalSettings, once) {
  'use strict';
  Drupal.behaviors.customFilterButtonControl = {
    attach: function (context, settings) {
      const exposedSettings = drupalSettings.exposed_form || {};
      const filterNames = exposedSettings.filter_name || [];
      const minlength = exposedSettings.minlength || {};
  
      $('.views-exposed-form', context).each(function () {
        const $form = $(this);
        const $submit = $form.find('input[type="submit"]');
  
        // Make check function available
        Drupal.behaviors.customFilterButtonControl.checkFilters = function ($formRef = $form) {
          let allValid = true;
  
          for (const field of filterNames) {
            const $elements = $formRef.find(`[name^="${field}"]`);
            if (!$elements.length) {
              allValid = false;
              break;
            }
            let isValid = false;
            if ($elements.is('select')) {
              const val = $elements.val();
              isValid = val !== '' && val !== '_none' && val !== 'All';
            } else if ($elements.is(':checkbox, :radio')) {
              isValid = $elements.filter(':checked').length > 0;
            } else if ($elements.is('input[type="text"]')) {
              const val = $elements.val();
              const fieldMinLength = parseInt(minlength[field]) || 0;
              isValid = val.trim().length >= fieldMinLength;
            }
            if (!isValid) {
              allValid = false;
              break;
            }
          }
  
          $submit.prop('disabled', !allValid);
          $submit.parent().toggleClass('disabled', !allValid);
        };
  
        // Initial check
        Drupal.behaviors.customFilterButtonControl.checkFilters($form);
      });
    }
  };  

})(jQuery, Drupal, drupalSettings, once);
