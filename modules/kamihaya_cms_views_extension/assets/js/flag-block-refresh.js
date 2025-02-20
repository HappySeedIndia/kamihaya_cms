
(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.flagBlockRefresh = {
    attach: function (context, settings) {
      once('flagBlockRefresh', $('.flag a', context)).forEach(function (element) {
        $(element).on('click', function () {
          setTimeout(function () {
            $('.flag-block').each(function () {
              var classes = $(this).attr('class');
              var viewIdMatch = classes.match(/view-id-([\w-]+)/);
              var displayIdMatch = classes.match(/view-display-id-([\w-]+)/);

              if (viewIdMatch && displayIdMatch) {
                var viewId = viewIdMatch[1];
                var displayId = displayIdMatch[1];
                var targetBlock = $(this);

                $.ajax({
                  url: '/ajax/flag-block-refresh',
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
      });
    }
  };
})(jQuery, Drupal);
