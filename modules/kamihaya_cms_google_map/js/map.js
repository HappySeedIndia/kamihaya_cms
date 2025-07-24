(function (Drupal, drupalSettings) {

  'use strict';

  let map;
  let markers = [];
  let mapElement;
  let mapInitialized = false;
  let searchButton;
  let searchButtonTimeout;
  let mapOptions = {};
  let autocomplete;
  let locationElement;

  // Define the global callback function before loading the API
  window.loadMap = function () {
    mapElement = document.getElementById('map');
    locationElement = document.getElementById('location-view');

    if (!mapElement) {
      console.warn('Map element not found');
      return;
    }

    // Initialize map on load
    mapElement.style.width = '100%';
    adjustMapHeight();
    // Get user's location.
    navigator.geolocation.getCurrentPosition(success, fail);

    window.addEventListener('resize', adjustMapHeight);
    mapInitialized = true;
  };

  // Initialize the map
  async function initMap(element, latlng) {
    const { Map } = await google.maps.importLibrary("maps");
    // Create a new map instance
    map = new Map(element, {
      zoom: drupalSettings.default_zoom,
      center: latlng,
      tilt: 0,
      disableDefaultUI: true,
      zoomControl: true,
      mapId: 'Kamihaya_google_map',
    });

    if (drupalSettings.show_autocomplete) {
      initAutocomplete();
    }

    const jsonDataPath = drupalSettings.json_data_path;
    if (!jsonDataPath) {
      console.warn('JSON data path is not set in drupalSettings');
      return;
    }

    // Add event listeners for map interactions.
    map.addListener('dragstart', onMapDragStart);
    map.addListener('dragend', onMapDragEnd);
    map.addListener('zoom_changed', onMapZoomChanged);

    element.classList.add('ready');
    google.maps.event.addListenerOnce(map, 'idle', () => {
      displayLocationMarkers();
      // Create　a search button.
      createSearchButton();
    });
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

  // Adjust the locationview height based on the viewport
  function adjustLocationHeight() {
    const offsetTop = locationElement.getBoundingClientRect().top + window.scrollY;
    const availableHeight = window.innerHeight - offsetTop;
    locationElement.style.height = `${availableHeight}px`;
    locationElement.dataset.heightAdjusted = 'true';
  }

  // Display markers on the map based on the current bounds.
  async function displayLocationMarkers() {
    if (!mapInitialized) {
      return;
    }

    // Clear existing markers if any.
    clearMarkers();

    // Disable the search button while fetching data.
    if (searchButton) {
      searchButton.disabled = true;
      searchButton.classList.add('loading');
    }

    const bounds = map.getBounds();
    const ne = bounds.getNorthEast();
    const sw = bounds.getSouthWest();
    let jsonDataPath = drupalSettings.json_data_path;
    if (!jsonDataPath) {
      console.warn('JSON data path is not set in drupalSettings');
      return;
    }
    // Add the path prefix if it exists.
    const baseUrl = drupalSettings.path.baseUrl || '';
    const pathPrefix = drupalSettings.path.pathPrefix || '';
    jsonDataPath = `${baseUrl}${pathPrefix}${jsonDataPath}`;

    const params = new URLSearchParams();

    // Get the current map bounds and set to the params.
    params.append('nelat', ne.lat());
    params.append('nelng', ne.lng());
    params.append('swlat', sw.lat());
    params.append('swlng', sw.lng());
    params.append('_format', 'json');

    // Show loading indicator.
    showLoading();
    // Fetch the location data from the server.
    fetch(`${jsonDataPath}?${params.toString()}`)
      .then((res) => res.json())
      .then((data) => {
        // Display markers on the map.
        updateMarkers(data);
        // Update the location view based on the current map bounds.
        updateLocationView();
        // Hide loading indicator.
        hideLoading();
      }
    )
    .catch((error) => {
      console.error('Error fetching location data:', error);
      // Update the location view based on the current map bounds.
      updateLocationView();
      hideLoading();
    });
  }

  // Display info window with detailed content for the marker.
  function diplayInfoWindow(marker, nid) {
    let detailDataPath = drupalSettings.detail_data_path;
    if (!detailDataPath) {
      console.warn('Detail data path is not set in drupalSettings');
      return;
    }
    // Add the path prefix if it exists.
    const baseUrl = drupalSettings.path.baseUrl || '';
    const pathPrefix = drupalSettings.path.pathPrefix || '';
    detailDataPath = `${baseUrl}${pathPrefix}${detailDataPath}`;

    const params = new URLSearchParams();

    // Set the format as JSON.
    params.append('_format', 'json');

    // Fetch the detail data for the marker.
    fetch(`${detailDataPath}/${nid}?${params.toString()}`)
      .then((res) => res.json())
      .then((data) => {
        if (data[0] && data[0].entity) {
          const infoWindow = new google.maps.InfoWindow({
            content: data[0].entity,
          });
          infoWindow.open(map, marker);
        } else {
          console.warn('No content found for the marker with nid:', nid);
        }
      })
      .catch((error) => {
        console.error('Error fetching detail data:', error);
      });
  }

  // Clear all markers from the map.
  function clearMarkers() {
    markers.forEach(function (marker) {
      marker.setMap(null);
    });
    markers = [];
  }

  async function updateMarkers(data) {
    const { AdvancedMarkerElement } = await google.maps.importLibrary("marker");
    data.forEach((location) => {
      const marker = new AdvancedMarkerElement({
        position: { lat: parseFloat(location.location_lat), lng: parseFloat(location.location_lng) },
        map: map,
        title: location.title,
      });

      // Open the info window on marker click.
      marker.addListener('click', function () {
        diplayInfoWindow(marker, location.nid);
      });
      markers.push(marker);
    });
  }

  // Initialize the Google Places Autocomplete feature.
  async function initAutocomplete () {
    const { places } = await google.maps.importLibrary("places");
    // Get PlaceAutocompleteElement.
    autocomplete = document.getElementById('autocomplete');

    if (!autocomplete) {
      console.warn('Autocomplete element not found');
      return;
    }
    // Add event listener for place selection.
    autocomplete.addEventListener('gmp-select', onPlaceChanged);
  }

  // Handle place selection from the autocomplete input.
  async function onPlaceChanged(event) {
    const { Place } = await google.maps.importLibrary("places");
    const placePrediction = event.placePrediction;

    if (!placePrediction) {
      console.warn('No place selected');
      return;
    }
    const placeId = placePrediction.placeId;
    if (!placeId) {
      console.warn('No place ID found for the selected place');
      return;
    }

    // Clear existing markers.
    clearMarkers();

    const place = new Place({
      id: placeId
    });

    await place.fetchFields({ fields: ['location'] });

    if (place.location) {
      const location = place.location;
      map.setCenter(location);
    } else {
      console.error('No geometry data found');
      return;
    }

    // Create a marker for the selected place.
    displayLocationMarkers();
  }

  // Create the search button for searching within the current map area.
  function createSearchButton() {
    // Create the search button element
    searchButton = document.createElement('button');
    searchButton.className = 'map-search-button btn btn-primary';
    searchButton.innerHTML = '<i class="fas fa-search"></i>' + Drupal.t('SEARCH IN THIS AREA');
    searchButton.type = 'button';

    // Add click event to search in the current area.
    searchButton.addEventListener('click', function () {
      displayLocationMarkers();
      hideSearchButton();
    });

    if (mapElement) {
      mapElement.classList.add('map-container-relative');
      mapElement.appendChild(searchButton);
    }

    hideSearchButton();
  }

  function onMapDragStart() {
    hideSearchButton();
  }

  function onMapDragEnd() {
    showSearchButton();
  }

  function onMapZoomChanged() {
    showSearchButton();
  }

  function showSearchButton() {
    if (searchButton) {
      // Show the search button after a short delay to avoid flickering.
      clearTimeout(searchButtonTimeout);
      searchButtonTimeout = setTimeout(() => {
        if (searchButton.classList.contains('loading')) {
          searchButton.classList.remove('loading');
        }
        searchButton.disabled = false;
        searchButton.style.display = 'block';
      }, 300);
    }
  }

  function hideSearchButton() {
    if (searchButton) {
      clearTimeout(searchButtonTimeout);
      searchButton.style.display = 'none';
    }
  }

  // Show loading indicator while fetching data.
  function showLoading() {
    // Display loading overlay.
    const overlay = document.createElement('div');
    overlay.id = 'map-loading-overlay';
    overlay.className = 'map-loading-overlay';

    // overlay.appendChild(loadingContent);
    document.body.appendChild(overlay);

    // Disable map interaction while loading.
    disableMapInteraction();

    // Disable page scroll while loading.
    document.body.classList.add('map-search-loading');

    const viewContent = document.getElementById('location-view');
    if (viewContent) {
      const viewResult = viewContent.querySelector('.view-content');
      // Hide the view content while loading.
      if (viewResult) {
        // Clear existing content in the view.
        viewResult.innerHTML = '';
        // Show loading indicator in the view content.
        viewResult.innerHTML = '<div class="loading-indicator"><i class="fas fa-spinner fa-spin"></i> ' + Drupal.t('Loading...') + '</div>';
      }
    }
  }

  // Hide loading indicator and enable map interaction.
  function hideLoading() {
    // Remove loading overlay.
    const overlay = document.getElementById('map-loading-overlay');
    if (overlay) {
      overlay.remove();
    }

    // Enable map interaction after loading.
    enableMapInteraction();

    // Enable page scroll after loading.
    document.body.classList.remove('map-search-loading');
  }

  // Disable map interaction to prevent user actions during loading.
  function disableMapInteraction() {
    if (!map) return;

    // Store the current map options to restore later.
    mapOptions = {
      draggable: map.get('draggable'),
      zoomControl: map.get('zoomControl'),
      scrollwheel: map.get('scrollwheel'),
      disableDoubleClickZoom: map.get('disableDoubleClickZoom'),
      gestureHandling: map.get('gestureHandling')
    };

    // Disable map interaction.
    map.setOptions({
      draggable: false,
      zoomControl: false,
      scrollwheel: false,
      disableDoubleClickZoom: true,
      gestureHandling: 'none'
    });
  }

  // Enable map interaction after loading is complete.
  function enableMapInteraction() {
    if (!map) return;

    // Restore the previous map options.
    map.setOptions({
      draggable: mapOptions.draggable !== false,
      zoomControl: mapOptions.zoomControl !== false,
      scrollwheel: mapOptions.scrollwheel !== false,
      disableDoubleClickZoom: mapOptions.disableDoubleClickZoom === true,
      gestureHandling: mapOptions.gestureHandling || 'auto'
    });
  }

  // Update the location view based on the current map bounds.
  function updateLocationView() {
    if (!map || !mapElement) {
      console.warn('Map or map element is not initialized');
      return;
    }

    // Get the current map bounds.
    if (!map.getBounds()) {
      console.warn('Map bounds are not available');
      return;
    }

    const viewElement = document.getElementById('location-view');
    if (!viewElement) {
      console.warn('View content element not found');
      return;
    }

    const bounds = map.getBounds();
    const ne = bounds.getNorthEast();
    const sw = bounds.getSouthWest();
    let ajaxPath = drupalSettings.ajax_path;
    if (!ajaxPath) {
      console.warn('AJAX path is not set in drupalSettings');
      return;
    }
    // Add the path prefix if it exists.
    const baseUrl = drupalSettings.path.baseUrl || '';
    const pathPrefix = drupalSettings.path.pathPrefix || '';
    ajaxPath = `${baseUrl}${pathPrefix}${ajaxPath}`;

    const params = new URLSearchParams();

    // Get the current map bounds and set to the params.
    params.append('route_key', drupalSettings.page_name);
    params.append('nelat', ne.lat());
    params.append('nelng', ne.lng());
    params.append('swlat', sw.lat());
    params.append('swlng', sw.lng());
    params.append('center_lat', map.getCenter().lat());
    params.append('center_lng', map.getCenter().lng());

    fetch(`${ajaxPath}?${params.toString()}`, {
      method: 'GET',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(res => res.json())
      .then(json => {
        json.forEach(command => {
          if (command.command === 'insert' && command.method === 'replaceWith') {
            const target = document.querySelector(command.selector);
            if (target) {
              const viewDiv = document.createElement('div');
              viewDiv.id = 'location-view';
              viewDiv.innerHTML = command.data;
              target.replaceWith(viewDiv);
            }
          }
          const viewContentWrapper = document.getElementsByClassName('view-content-wrapper')[0];
          if (viewContentWrapper && !viewContentWrapper.classList.contains('inilialized')) {
            // Add class to the view element to indicate it has been initialized.
            viewContentWrapper.classList.add('initialized');
          }
        }
        );
      }
      )
      .catch((error) => {
        console.error('Error updating location view:', error);
        const viewContentWrapper = document.getElementsByClassName('view-content-wrapper')[0];
        if (viewContentWrapper && !viewContentWrapper.classList.contains('inilialized')) {
          // Add class to the view element to indicate it has been initialized.
          viewContentWrapper.classList.add('initialized');
        }
      });
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
        const libraries = drupalSettings.show_autocomplete ? '&libraries=places&v=beta' : '';
        script.src = `https://maps.googleapis.com/maps/api/js?key=${drupalSettings.api_key}&callback=loadMap&loading=async${libraries}`;
        script.async = true;
        script.defer = true;
        script.setAttribute('data-google-maps-api', 'true'); // Prevent duplication
        document.head.appendChild(script);
      }
    },
  };

})(Drupal, drupalSettings);
