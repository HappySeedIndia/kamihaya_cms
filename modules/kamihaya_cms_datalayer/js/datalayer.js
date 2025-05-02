/**
 * @file
 * Kamihaya CMS Datalayer behaviors.
 */

jQuery(document).ready(function ($) {
  window.dataLayer = window.dataLayer || [];

  function getCookie(cname) {
    let name = cname + "=";
    let ca = document.cookie.split(';');
    for(let i = 0; i < ca.length; i++) {
      let c = ca[i];
      while (c.charAt(0) == ' ') {
        c = c.substring(1);
      }
      if (c.indexOf(name) == 0) {
        return c.substring(name.length, c.length);
      }
    }
    return "";
  }

  const gyoshu = getCookie('SK_GYOUSHU');
  window.dataLayer.push({
    gyoshu: gyoshu,
  });
});


(function ($, Drupal) {
  window.dataLayer = window.dataLayer || [];
  Drupal.behaviors.dataLayer = {
    attach: function (context, settings) {
      $('#kamihayaModal').find('a').each(function () {
        $(this).click(function () {

          const path = $(this).attr('href').replace('#', '');
          // This does not edit an existing object; instead, it adds a new object to the dataLayer array.
          // GTM will then use the latest value when evaluating triggers and variables.
          window.dataLayer.push({
            gyoshu: path,
          });
        });
      });
    }
  }
})(jQuery, Drupal);
