(function (Drupal, drupalSettings) {

  'use strict';

  let map;
  let mapElement;
  let mapInitialized = false;

  // Define the global callback function before loading the API
  window.loadMap = function () {
    mapElement = document.getElementById('map');
    if (!mapElement) {
      console.warn('Map element not found');
      return;
    }

    // Initialize map on load
    if (mapElement) {
      mapElement.style.width = '100%';
      adjustMapHeight();
      // Get user's location.
      navigator.geolocation.getCurrentPosition(success, fail);
    }
    window.addEventListener('resize', adjustMapHeight);
    mapInitialized = true;
  };

  // Initialize the map
  function initMap(element, latlng) {
    // Create a new map instance
    map = new google.maps.Map(element, {
      zoom: drupalSettings.default_zoom,
      center: latlng,
      tilt: 0,
      disableDefaultUI: true,
      zoomControl: true,
    });
    element.classList.add('ready');
  }

  // Success and failure handlers for geolocation
  function success(pos) {
    const lat = pos.coords.latitude;
    const lng = pos.coords.longitude;
    const latlng = new google.maps.LatLng(lat, lng);
    // Initialize the map with the user's location
    initMap(mapElement, latlng);
  }

  // Fallback to default location if geolocation fails
  function fail(error) {
    const latlng = new google.maps.LatLng(drupalSettings.default_lat, drupalSettings.default_lon);
    // Initialize the map with the default location
    initMap(mapElement, latlng);
  }

  // Adjust the map height based on the viewport
  function adjustMapHeight() {
    const offsetTop = mapElement.getBoundingClientRect().top + window.scrollY;
    const availableHeight = window.innerHeight - offsetTop;
    mapElement.style.height = `${availableHeight}px`;
    mapElement.dataset.heightAdjusted = 'true';
  }

  Drupal.behaviors.googleMap = {
    attach: function (context, settings) {
      // Prevent re-initializing the map
      if (mapInitialized) {
        return;
      }

      // Get the map element within the current context
      if (!document.getElementById('map')) return;

      // If API is already loaded, initialize immediately
      if (window.google && window.google.maps) {
        window.loadMap();
        return;
      }

      // Avoid loading the script multiple times
      if (!document.querySelector('script[data-google-maps-api]')) {
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${drupalSettings.api_key}&callback=loadMap&loading=async`;
        script.async = true;
        script.defer = true;
        script.setAttribute('data-google-maps-api', 'true'); // Prevent duplication
        document.head.appendChild(script);
      }
    },
  };

})(Drupal, drupalSettings);
