(function ($, Drupal, drupalSettings, once) {

  Drupal.behaviors.kamihaya_cms_google_map = {

    attach: function (context) {
      function initMap($self, latlng) {
        var address = drupalSettings.address;
        var lat = drupalSettings.lat;
        var lng = drupalSettings.lon;
        var latlng = new google.maps.LatLng(lat, lng);
        var map_param = {
          center: latlng,
          tilt: 0,
          disableDefaultUI: true,
          zoomControl: true,
        };
        if (drupalSettings.zoom) {
          map_param.zoom = drupalSettings.zoom;
        }
/*
        var map = new google.maps.Map($self.get(0), map_param);

        var marker = new google.maps.Marker({
          position: latlng,
          map,
          title: drupalSettings.title,
        });
        marker.description = new google.maps.InfoWindow({
          content: drupalSettings.description
        });*/

        var geocoder = new google.maps.Geocoder();
        var geo_param = {}
        if (address) {
          geo_param.address = drupalSettings.address;
        }
        else {
          var lat = drupalSettings.lat;
          var lng = drupalSettings.lon;
          geo_param.location = new google.maps.LatLng(lat, lng);
        }
        geocoder.geocode(geo_param, function (results, status) {
          if (status == 'OK') {
            map_param.center = results[0].geometry.location;
            var map = new google.maps.Map($self.get(0), map_param);
            var marker = new google.maps.Marker({
              position: results[0].geometry.location,
              map: map,
              title: drupalSettings.title,
            });
            const infowindow = new google.maps.InfoWindow({
              content: drupalSettings.description,
              ariaLabel: drupalSettings.title,
            });
            marker.addListener("click", () => {
              infowindow.open({
                anchor: marker,
                map,
              });
            });
          }
        });

        $('#map').addClass('ready');
      };

      $('#map').each(function (index, item) {
        $this = $(this);
        if (drupalSettings.map_height) {
          $this.css('height', drupalSettings.map_height + 'px');
        }
        $this.css('width', '100%');
        if (drupalSettings.map_width) {
          $this.css('max-width', drupalSettings.map_width + 'px');
        }
        initMap($this);
      });
    }
  }

})(jQuery, Drupal, drupalSettings, once);

