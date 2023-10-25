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
