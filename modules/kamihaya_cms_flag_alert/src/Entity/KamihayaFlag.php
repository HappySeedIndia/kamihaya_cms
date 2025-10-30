<?php

namespace Drupal\kamihaya_cms_flag_alert\Entity;

use Drupal\flag\Entity\Flag;

/**
 * Defines the KamihayaFlag entity.
 *
 * This class extends the Flag entity to add custom functionality for the
 * Kamihaya CMS Flag Alert module.
 */
class KamihayaFlag extends Flag {

  /**
   * The alert message displayed when a flag is set.
   * @var string
   */
  protected $flag_alert_message;

  /**
   * The link URL for the alert message when a flag is set.
   * @var string
   */
  protected $flag_alert_message_link_url;

  /**
   * The label for the link in the alert message when a flag is set.
   * @var string
   */
  protected $flag_alert_message_link_label;

  /**
   * The color of the alert message when a flag is set.
   * @var string
   */
  protected $flag_alert_message_color;

  /**
   * The background color of the alert message when a flag is set.
   * @var string
   */
  protected $flag_alert_message_bg_color;

  /**
   * The opacity of the background color of the alert message when a flag is set.
   * @var float
   */
  protected $flag_alert_message_bg_color_opacity = 1.0;

  /**
   * The position of the alert message on the page.
   * @var string
   */
  protected $flag_alert_position = 'top';

  /**
   * The alert message displayed when a flag is removed.
   * @var string
   */
  protected $unflag_alert_message;

  /**
   * The link URL for the alert message when a flag is removed.
   * @var string
   */
  protected $unflag_alert_message_link_url;

  /**
   * The label for the link in the alert message when a flag is removed.
   * @var string
   */
  protected $unflag_alert_message_link_label;

  /**
   * The color of the alert message when a flag is removed.
   * @var string
   */
  protected $unflag_alert_message_color;

  /**
   * The background color of the alert message when a flag is removed.
   * @var string
   */
  protected $unflag_alert_message_bg_color;

  /**
   * The opacity of the background color of the alert message when a flag is removed.
   * @var float
   */
  protected $unflag_alert_message_bg_color_opacity = 1.0;

  /**
   * The position of the unflag alert message on the page.
   * @var string
   */
  protected $unflag_alert_position = 'top';

  /**
   * Gets the flag alert message.
   *
   * @return string
   *   The flag alert message.
   */
  public function getFlagAlertMessage() {
    return $this->flag_alert_message;
  }

  /**
   * Sets the flag alert message.
   *
   * @param string $message
   *   The message to set.
   */
  public function setFlagAlertMessage($message) {
    $this->flag_alert_message = $message;
  }

  /**
   * Gets the flag alert message link URL.
   *
   * @return string
   *   The URL of the flag alert message link.
   */
  public function getFlagAlertMessageLinkUrl() {
    return $this->flag_alert_message_link_url;
  }

  /**
   * Sets the flag alert message link URL.
   *
   * @param string $url
   *   The URL to set.
   */
  public function setFlagAlertMessageLinkUrl($url) {
    $this->flag_alert_message_link_url = $url;
  }

  /**
   * Gets the flag alert message link label.
   *
   * @return string
   *   The label of the flag alert message link.
   */
  public function getFlagAlertMessageLinkLabel() {
    return $this->flag_alert_message_link_label;
  }

  /**
   * Sets the flag alert message link label.
   *
   * @param string $label
   *   The label to set.
   */
  public function setFlagAlertMessageLinkLabel($label) {
    $this->flag_alert_message_link_label = $label;
  }

  /**
   * Gets the flag alert message color.
   *
   * @return string
   *   The color of the flag alert message.
   */
  public function getFlagAlertMessageColor() {
    return $this->flag_alert_message_color;
  }

  /**
   * Sets the flag alert message color.
   *
   * @param string $color
   *   The color to set.
   */
  public function setFlagAlertMessageColor($color) {
    $this->flag_alert_message_color = $color;
  }

  /**
   * Gets the flag alert message background color.
   *
   * @return string
   *   The background color of the flag alert message.
   */
  public function getFlagAlertMessageBgColor() {
    return $this->flag_alert_message_bg_color;
  }

  /**
   * Sets the flag alert message background color.
   *
   * @param string $color
   *   The background color to set.
   */
  public function setFlagAlertMessageBgColor($color) {
    $this->flag_alert_message_bg_color = $color;
  }

  /**
   * Gets the flag alert message background color opacity.
   *
   * @return float
   *   The opacity of the background color of the flag alert message.
   */
  public function getFlagAlertMessageBgColorOpacity() {
    return $this->flag_alert_message_bg_color_opacity;
  }

  /**
   * Sets the flag alert message background color opacity.
   *
   * @param float $opacity
   *   The opacity to set (0.0 to 1.0).
   */
  public function setFlagAlertMessageBgColorOpacity($opacity) {
    if ($opacity < 0.0 || $opacity > 1.0) {
      throw new \InvalidArgumentException('Opacity must be between 0.0 and 1.0.');
    }
    $this->flag_alert_message_bg_color_opacity = $opacity;
  }

  /**
   * Gets the flag alert position.
   *
   * @return string
   *   The position of the flag alert on the page.
   */
  public function getFlagAlertPosition() {
    return $this->flag_alert_position;
  }

  /**
   * Sets the flag alert position.
   *
   * @param string $position
   *   The position to set.
   */
  public function setFlagAlertPosition($position) {
    $this->flag_alert_position = $position;
  }

  /**
   * Gets the unflag alert message.
   *
   * @return string
   *   The unflag alert message.
   */
  public function getUnflagAlertMessage() {
    return $this->unflag_alert_message;
  }

  /**
   * Sets the unflag alert message.
   *
   * @param string $message
   *   The message to set.
   */
  public function setUnflagAlertMessage($message) {
    $this->unflag_alert_message = $message;
  }

  /**
   * Gets the unflag alert message link URL.
   *
   * @return string
   *   The URL of the unflag alert message link.
   */
  public function getUnflagAlertMessageLinkUrl() {
    return $this->unflag_alert_message_link_url;
  }

  /**
   * Sets the unflag alert message link URL.
   *
   * @param string $url
   *   The URL to set.
   */
  public function setUnflagAlertMessageLinkUrl($url) {
    $this->unflag_alert_message_link_url = $url;
  }

  /**
   * Gets the unflag alert message link label.
   *
   * @return string
   *   The label of the unflag alert message link.
   */
  public function getUnflagAlertMessageLinkLabel() {
    return $this->unflag_alert_message_link_label;
  }

  /**
   * Sets the unflag alert message link label.
   *
   * @param string $label
   *   The label to set.
   */
  public function setUnflagAlertMessageLinkLabel($label) {
    $this->unflag_alert_message_link_label = $label;
  }

  /**
   * Gets the unflag alert message color.
   *
   * @return string
   *   The color of the unflag alert message.
   */
  public function getUnflagAlertMessageColor() {
    return $this->unflag_alert_message_color;
  }

  /**
   * Sets the unflag alert message color.
   *
   * @param string $color
   *   The color to set.
   */
  public function setUnflagAlertMessageColor($color) {
    $this->unflag_alert_message_color = $color;
  }

  /**
   * Gets the unflag alert message background color.
   *
   * @return string
   *   The background color of the unflag alert message.
   */
  public function getUnflagAlertMessageBgColor() {
    return $this->unflag_alert_message_bg_color;
  }

  /**
   * Sets the unflag alert message background color.
   *
   * @param string $color
   *   The background color to set.
   */
  public function setUnflagAlertMessageBgColor($color) {
    $this->unflag_alert_message_bg_color = $color;
  }

  /**
   * Gets the unflag alert message background color opacity.
   *
   * @return float
   *   The opacity of the background color of the unflag alert message.
   */
  public function getUnflagAlertMessageBgColorOpacity() {
    return $this->unflag_alert_message_bg_color_opacity;
  }

  /**
   * Sets the unflag alert message background color opacity.
   *
   * @param float $opacity
   *   The opacity to set (0.0 to 1.0).
   */
  public function setUnflagAlertMessageBgColorOpacity($opacity) {
    if ($opacity < 0.0 || $opacity > 1.0) {
      throw new \InvalidArgumentException('Opacity must be between 0.0 and 1.0.');
    }
    $this->unflag_alert_message_bg_color_opacity = $opacity;
  }

  /**
   * Gets the unflag alert position.
   *
   * @return string
   *   The position of the unflag alert on the page.
   */
  public function getUnflagAlertPosition() {
    return $this->unflag_alert_position;
  }

  /**
   * Sets the unflag alert position.
   *
   * @param string $position
   *   The position to set.
   */
  public function setUnflagAlertPosition($position) {
    $this->unflag_alert_position = $position;
  }
}
