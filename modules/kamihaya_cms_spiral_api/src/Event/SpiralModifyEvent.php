<?php

namespace Drupal\kamihaya_cms_spiral_api\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Notify event listeners about an externalauth user registration.
 */
class SpiralModifyEvent extends Event {

  /**
   * The spiral user data array.
   *
   * @var array
   */
  protected $userData;

  /**
   * Constructs an spiral modify event object.
   *
   * @param array $userData
   *   The array of user data.
   */
  public function __construct(array $userData) {
    $this->userData = $userData;
  }

  /**
   * Gets the spiral user data.
   *
   * @return array
   *   The spiral user data.
   */
  public function getUserData() {
    return $this->userData;
  }

}
