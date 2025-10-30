(function (Drupal, drupalSettings) {

  Drupal.behaviors.processFlagAlert = {
    attach: function (context, settings) {
      if (!drupalSettings.flag_alert.message && !drupalSettings.unflag_alert.message) {
        return; // No messages to display, exit early.
      }

      // Check if the flag observer is already initialized to avoid duplicate observers.
      if (context.flagObserverInitialized) return;
      context.flagObserverInitialized = true;

      const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
          // Only process childList mutations.
          if (mutation.type !== 'childList') return;

          // Process added nodes that are flag elements.
          mutation.addedNodes.forEach(node => {
            if (!(node instanceof HTMLElement)) return;
            if (!node.classList.contains('flag')) return;

            const action = node.classList.contains('action-flag') ? 'unflag' : 'flag';
            if (action === 'flag' && drupalSettings.flag_alert.message) {
              showCustomPopup('flag-alert', drupalSettings.flag_alert);
            } else if (action === 'unflag' && drupalSettings.unflag_alert.message) {
              showCustomPopup('unflag-alert', drupalSettings.unflag_alert);
            } else {
              console.warn('Unknown action:', action);
            }
            return;
          });
        });
      });

      observer.observe(context, {
        childList: true,
        subtree: true,
      });
    }
  }

  // Function to show custom popup alerts.
  function showCustomPopup(alert_type, alert_message) {
    let alert = document.querySelector(`.${alert_type}`);
    let flag_alert = document.querySelector('.flag-alert');
    if (flag_alert) {
      // Remove the existing flag alert if it exists.
      flag_alert.classList.remove('show');
      flag_alert.addEventListener('transitionend', () => flag_alert.remove());
    }
    let unflag_alert = document.querySelector('.unflag-alert');
    if (unflag_alert) {
      // Remove the existing unflag alert if it exists.
      unflag_alert.classList.remove('show');
      unflag_alert.addEventListener('transitionend', () => unflag_alert.remove());
    }
    let message = alert_message.message || '';
    let opacity = alert_message.opacity || 1.0;
    let position = alert_message.position || 'top';
    let link_url = alert_message.link_url || '';
    let link_label = alert_message.link_label || '';

    // If a link URL is provided, append it to the message.
    if (link_url) {
      message += ` <a href="${link_url}" class="flag-alert-link">${link_label}</a>`;
    }
    if (!alert) {
      // Create a new alert element
      alert = document.createElement('div');
      alert.classList.add(alert_type, position);
      // Create a message element
      const message_tag = document.createElement('div');
      message_tag.classList.add('message');
      message_tag.innerHTML = message;
      // Create a close button
      const closeButton = document.createElement('button');
      closeButton.className = 'close';
      closeButton.textContent = '×';
      closeButton.addEventListener('click', () => {
        alert.classList.remove('show');
        alert.addEventListener('transitionend', () => alert.remove());
      });
      document.body.appendChild(alert);
      alert.appendChild(message_tag);
      alert.appendChild(closeButton);
    }

    alert.style.opacity = opacity;
    requestAnimationFrame(() => alert.classList.add('show'));

    // Remove the alert after a delay
    setTimeout(() => {
      fadeOut(alert, opacity, 500); // Fade out the alert after showing it
    }, 2500);
  }

  // Function to fade out an element.
  function fadeOut(element, default_opacity = 1.0, duration = 500) {
    let opacity = default_opacity;
    const interval = 16;
    const decrement = interval / duration;

    function step() {
      opacity -= decrement;
      if (opacity <= 0) {
        opacity = 0;
        element.style.opacity = 0;
        element.classList.remove('show');
      } else {
        element.style.opacity = opacity;
        requestAnimationFrame(step);
      }
    }

    step();
  }

})(Drupal, drupalSettings);
