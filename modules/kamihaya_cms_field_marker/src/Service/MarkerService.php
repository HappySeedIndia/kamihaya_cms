<?php

namespace Drupal\kamihaya_cms_field_marker\Service;

use Drupal\Core\Entity\EntityFieldManagerInterface;
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
   */
  public function __construct(protected EntityFieldManagerInterface $fieldManager) {
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
  public function createMarker(FieldConfig $field_config, $value, bool $is_node_page = TRUE) {
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
      $condition_time = (new \DateTime($condition))->getTimestamp();
      $field_time = (new \DateTime($value))->getTimestamp();
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
  }
}

