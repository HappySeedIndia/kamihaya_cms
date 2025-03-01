
(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.flagBlockRefresh = {
    attach: function (context, settings) {
      once('flagBlockRefresh', $('.action-flag a, .action-unflag a', context)).forEach(function (element) {
        Drupal.behaviors.addFlagEvent(element);
      });

      const observer = new MutationObserver(function (mutationsList) {
        mutationsList.forEach(function (mutation) {
          if (mutation.type === 'childList') {
            mutation.addedNodes.forEach(function (addedNode) {
              if (addedNode.nodeType === 1 && (addedNode.matches('.action-flag') || addedNode.matches('.action-unflag'))) {
                var links = addedNode.querySelectorAll('a');
                links.forEach(function (link) {
                  Drupal.behaviors.addFlagEvent(link);
                });
              }
            });
          }
        });
      });

      observer.observe(document.body, {
        childList: true,
        subtree: true
      });
    }
  };

  Drupal.behaviors.addFlagEvent = function (link) {
    $(link).on('click', function (e) {
      setTimeout(function () {
        $('.flag-block').each(function () {
          const classes = $(this).attr('class');
          const viewIdMatch = classes.match(/view-id-([\w-]+)/);
          const displayIdMatch = classes.match(/view-display-id-([\w-]+)/);

          if (viewIdMatch && displayIdMatch) {
            const viewId = viewIdMatch[1];
            const displayId = displayIdMatch[1];
            const targetBlock = $(this);
            const default_lang = drupalSettings.default_language || 'en';
            const lang = $('html').attr('lang');
            const url = default_lang === lang
              ? '/ajax/flag-block-refresh'
              : '/' + lang + '/ajax/flag-block-refresh';
            $.ajax({
              url: url,
              type: 'GET',
              data: {
                view_id: viewId,
                display_id: displayId
              },
              success: function (data) {
                if (data.view) {
                  targetBlock.html(data.view);
                }
              }
            });
          }
        });
      }, 250);
    });
  };

})(jQuery, Drupal, drupalSettings);
