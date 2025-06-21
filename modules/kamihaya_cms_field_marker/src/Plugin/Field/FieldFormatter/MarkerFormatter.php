<?php

namespace Drupal\kamihaya_cms_field_marker\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\kamihaya_cms_field_marker\Service\MarkerService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'htmlembed_formatter' formatter.
 *
 */
#[FieldFormatter(
  id: 'marker',
  label: new TranslatableMarkup('Marker'),
  field_types: [
    'boolean',
    'datetime',
  ],
)]
class MarkerFormatter extends FormatterBase {

  /**
   * Constructs a FormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param Drupal\kamihaya_cms_field_marker\Service\MarkerService $markeService
   *   The marker service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    protected MarkerService $markeService,) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('kamihaya_cms_field_marker.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $field_config = $this->fieldDefinition;
    foreach ($items as $delta => $item) {
      $value = $item->value;

      // Generate the marker HTML tag using the MarkerService.
      $marker_html = $this->markeService->createMarker($field_config, $value);
      if (empty($marker_html)) {
        continue;
      }
      // Add the marker HTML to the elements array.
      $elements[$delta] = [
        '#markup' => $marker_html,
        '#allowed_tags' => ['div', 'i'],
      ];
    }
    return $elements;
  }

}
