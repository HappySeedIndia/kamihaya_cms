<?php

namespace Drupal\kamihaya_cms_spiral_api\Event;

/**
 * Defines events for the externalauth module.
 *
 * @see \Drupal\externalauth\Event\ExternalAuthRegisterEvent
 * @see \Drupal\externalauth\Event\ExternalAuthAuthmapAlterEvent
 */
final class SpiralEvents {

  /**
   * Name of the event fired after a Spiral member information modified.
   *
   * @Event
   *
   * @see \Drupal\spiral_api\Event\SpiralModifyEvent
   *
   * @var string
   */
  const MODIFY = 'spiral_api.modify';

}
