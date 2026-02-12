<?php

namespace Drupal\kamihaya_cms_paragraph_type\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'paragraph_type_limiter' formatter.
 *
 */
#[FieldFormatter(
  id: 'paragraph_type_limiter',
  label: new TranslatableMarkup('Paragraph Type Limiter'),
  field_types: [
    'entity_reference_revisions',
  ],
)]
class ParagraphTypeLimiterFormatter extends FormatterBase {

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    protected EntityTypeManagerInterface $entityTypeManager) {
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'target_type' => '',
      'limit' => 1,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    // Get the field definition to retrieve allowed paragraph types.
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
    $field_definition = $this->fieldDefinition;
    $handler_settings = $field_definition->getSetting('handler_settings');
    $target_bundles = [];
    if ($field_definition instanceof FieldConfig) {
      $handler_settings = $field_definition->getSetting('handler_settings');
      $target_bundles = $handler_settings['target_bundles'] ?? [];
    }
    if ($field_definition instanceof BaseFieldDefinition) {
      // If the field definition is not a FieldConfig, load the FieldConfig.
      $field_name = $field_definition->getName();
      $entity_type = $field_definition->getTargetEntityTypeId();
      $field_configs = \Drupal::entityTypeManager()
        ->getStorage('field_config')
        ->loadByProperties([
          'field_name' => $field_name,
          'entity_type' => $entity_type,
        ]);

      if (empty($field_configs)) {
        // If no field config is found, try to load it by field name.
        $field_config = FieldConfig::load($field_name);
        if ($field_config) {
          $handler_settings = $field_config->getSetting('handler_settings');
          $target_bundles = $handler_settings['target_bundles'] ?? [];
        }
      }
      else {
        // If multiple configs are found, use the first one.
        $config = reset($field_configs);
        $handler_settings = $config->getSetting('handler_settings');
        $target_bundles = $handler_settings['target_bundles'] ?? [];
      }
    }

    $default_target_type = $this->getSetting('target_type') ?: reset($target_bundles);
    $element['target_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Paragraph type to display'),
      '#options' => $target_bundles,
      '#default_value' => $default_target_type,
      '#description' => $this->t('Only Paragraphs of this type will be displayed.'),
      '#required' => TRUE,
    ];

    $element['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit'),
      '#default_value' => $this->getSetting('limit'),
      '#min' => 1,
      '#description' => $this->t('Maximum number of Paragraphs to display.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Display only @type, up to @limit items.', [
      '@type' => $this->getSetting('target_type'),
      '@limit' => $this->getSetting('limit'),
    ]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $target_type = $this->getSetting('target_type');
    $limit = (int) $this->getSetting('limit');
    $view_builder = $this->entityTypeManager->getViewBuilder('paragraph');
    $count = 0;

    foreach ($items as $delta => $item) {
      /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
      $paragraph = $item->entity;
      if ($paragraph && $paragraph->bundle() === $target_type) {
        $elements[$delta] = $view_builder->view($paragraph, 'default');
        $count++;
        if ($count >= $limit) {
          break;
        }
      }
    }
    return $elements;
  }

}
