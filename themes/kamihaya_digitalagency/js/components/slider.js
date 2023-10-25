/**
   * Easy selector helper function
   */
const select = (el, all = false) => {
  el = el.trim()
  if (all) {
    return [...document.querySelectorAll(el)]
  } else {
    return document.querySelector(el)
  }
}

/**
 * Easy event listener function
 */
const on = (type, el, listener, all = false) => {
  let selectEl = select(el, all)
  if (selectEl) {
    if (all) {
      selectEl.forEach(e => e.addEventListener(type, listener))
    } else {
      selectEl.addEventListener(type, listener)
    }
  }
}

/**
 * Init swiper sliders
 */
function initSwiper() {
  document.querySelectorAll('.swiper').forEach(function(swiper) {
    let config = JSON.parse(swiper.querySelector('.swiper-config').innerHTML.trim());
    new Swiper(swiper, config);
  });
}
window.addEventListener('load', initSwiper);
