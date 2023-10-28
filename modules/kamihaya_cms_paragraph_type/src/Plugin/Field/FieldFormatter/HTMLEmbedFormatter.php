<?php

namespace Drupal\kamihaya_cms_paragraph_type\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'htmlembed_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "htmlembed_formatter",
 *   label = @Translation("Html embed formatter"),
 *   field_types = {
 *     "string_long"
 *   }
 * )
 */
class HTMLEmbedFormatter extends FormatterBase {

  /**
   * The currently active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

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
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The currently active route match object.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    RouteMatchInterface $route_match) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->routeMatch = $route_match;
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
      $container->get('current_route_match'));
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + [
      'allowed_tags' => '',
      'edit_preview' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['allowed_tags'] = [
      '#title' => $this->t('Allowed tags'),
      '#type' => 'textfield',
      '#maxlength' => 128,
      '#default_value' => $this->getSetting('allowed_tags'),
      '#description' => $this->t('Space separated list of HTML tags.'),
    ];
    $form['edit_preview'] = [
      '#title' => $this->t('Preview on edit page'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('edit_preview'),
    ];
    return $form;
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
    $route_name = $this->routeMatch->getRouteName();
    $allowed_tags = $this->prepareTags($this->getSetting('allowed_tags'));
    if (($route_name !== 'entity.node.edit_form'
      || ($this->getSetting('edit_preview') && $route_name === 'entity.node.edit_form'))
      && !empty($allowed_tags)) {
      foreach ($items as $delta => $item) {
        $elements[$delta] = [
          '#markup' => $item->value,
          '#allowed_tags' => $allowed_tags,
        ];
      }
    }
    else {
      foreach ($items as $delta => $item) {
        $elements[$delta] = [
          '#plain_text' => $item->value,
        ];
      }
    }
    return $elements;
  }

  /**
   * Prepare tags.
   *
   * @param string $tags
   *   Tags string.
   *
   * @return string[]
   *   The list of tags.
   */
  protected function prepareTags($tags) {
    $prepared = [];
    foreach ($this->explode($tags) as $tag) {
      $prepared[] = trim($tag);
    }

    return $prepared;
  }

  /**
   * Explode the text string.
   *
   * @param string $text
   *   The text to be exploded.
   *
   * @return string[]
   *   The array of exploded text.
   */
  protected function explode($text) {
    return preg_split('/[,\s]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
  }

}
