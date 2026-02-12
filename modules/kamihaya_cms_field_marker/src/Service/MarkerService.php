<?php

namespace Drupal\kamihaya_cms_field_marker\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\field\Entity\FieldConfig;

/**
 * Provides marker generation logic based on field settings.
 *
 * This service is responsible for:
 * - Checking whether a marker should be displayed for a given field.
 * - Generating marker HTML tags based on the field value and configuration.
 */
class MarkerService {

  /**
   * Constructs a MarkerService object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $fieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   */
  public function __construct(protected EntityFieldManagerInterface $fieldManager, protected ConfigFactoryInterface $configFactory) {
    // Constructor injection for the field manager service.
  }

  /**
   * Builds the marker HTML tag based on the field config and value.
   *
   * @param \Drupal\field\Entity\FieldConfig $field_config
   *   The field configuration.
   * @param string $value
   *   The field value.
   * @param bool $is_node_page
   *   Whether the context is a node detail page or a view/list.
   *
   * @return string|null
   *   The marker HTML string, or NULL if not displayed.
   */
  public function createMarker(FieldConfig $field_config, $value, bool $is_node_page = TRUE): ?string {
    $marker_setting = $field_config->getThirdPartySettings('kamihaya_cms_field_marker');
    if (empty($marker_setting['display_marker'])) {
      return $value;
    }
    $field_name = $field_config->getName();
    $field_type = $field_config->getType();
    $condition = $marker_setting['condtion'];
    $display = FALSE;
    if ($field_type === 'datetime') {
      $operator = $marker_setting['operator'];
      $condition_time = $this->getSystemTime($condition);
      $field_time = $this->getSystemTime($value);
      $display = eval("return $condition_time $operator $field_time;");
    }
    if ($field_type === 'boolean') {
      $display = $condition ? !$value : $value;
    }
    $marker_label = $marker_setting['marker_label'];
    $position = str_replace('_', '-', ($is_node_page ? $marker_setting['marker_position'] : $marker_setting['marker_view_position']));
    if ($display) {
      $name = str_replace('_', '-', $field_name);
      return "<div class='kamihaya-marker marker-{$position} marker-{$name}'>{$marker_label}</div>";
    }
    return null;
  }

  /**
   * Converts a date string to a system timestamp.
   *
   * @param string $date
   *   The date string to convert.
   *
   * @return int
   *   The system timestamp.
   */
  private function getSystemTime($date) {
    $site_timezone = $this->configFactory->get('system.date')->get('timezone.default');
    $default_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);
    $date_time = new \DateTime($date, $default_timezone);
    $date_time->setTimezone(new \DateTimeZone($site_timezone));
    return mktime(0, 0, 0, date('n', $date_time->getTimestamp()), date('j', $date_time->getTimestamp()), date('Y', $date_time->getTimestamp()));
  }
}

