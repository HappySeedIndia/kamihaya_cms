<?php

namespace Drupal\kamihaya_cms_custom_js_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'js_file_script_tag' formatter.
 */
#[FieldFormatter(
  id: "js_file_script_tag",
  label: new TranslatableMarkup("Script Tag Embed"),
  field_types: ["js_file"]
)]
class JsFileScriptTagFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    protected FileUrlGeneratorInterface $fileUrlGenerator
  ) {
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
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'async' => FALSE,
      'defer' => FALSE,
      'inline' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['inline'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Inline JavaScript'),
      '#default_value' => $this->getSetting('inline'),
      '#description' => $this->t('If checked, JavaScript will be embedded inline instead of using a src attribute.'),
    ];

    $elements['async'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Async loading'),
      '#default_value' => $this->getSetting('async'),
      '#description' => $this->t('Add async attribute to script tag (only for external files).'),
      '#states' => [
        'visible' => [
          ':input[name*="inline"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $elements['defer'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Defer loading'),
      '#default_value' => $this->getSetting('defer'),
      '#description' => $this->t('Add defer attribute to script tag (only for external files).'),
      '#states' => [
        'visible' => [
          ':input[name*="inline"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    if ($this->getSetting('inline')) {
      $summary[] = $this->t('Inline JavaScript');
    }
    else {
      $attributes = [];
      if ($this->getSetting('async')) {
        $attributes[] = 'async';
      }
      if ($this->getSetting('defer')) {
        $attributes[] = 'defer';
      }
      if (!empty($attributes)) {
        $summary[] = $this->t('Attributes: @attributes', ['@attributes' => implode(', ', $attributes)]);
      }
      else {
        $summary[] = $this->t('External file (no special attributes)');
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $file = File::load($item->target_id);
      if (!$file) {
        continue;
      }

      if ($this->getSetting('inline')) {
        // Inline JavaScript
        $uri = $file->getFileUri();
        $content = file_get_contents($uri);
        $elements[$delta]['#attached']['html_head'][] = [
          [
            '#tag' => 'script',
            '#value' => $content,
            '#attributes' => [
              'type' => 'text/javascript',
            ],
          ],
          "kamihaya_cms_custom_js_field_{$delta}",
        ];
      }
      else {
        // External JavaScript file with cache busting
        $url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());

        // Add timestamp as query parameter for cache busting
        $timestamp = $file->getChangedTime();
        $separator = (strpos($url, '?') !== FALSE) ? '&' : '?';
        $url_with_timestamp = $url . $separator . 'v=' . $timestamp;

        $attributes = ['type' => 'text/javascript'];

        if ($this->getSetting('async')) {
          $attributes['async'] = 'async';
        }
        if ($this->getSetting('defer')) {
          $attributes['defer'] = 'defer';
        }

        $elements[$delta] = [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#attributes' => $attributes + ['src' => $url_with_timestamp],
        ];
        $elements[$delta]['#attached']['html_head'][] = [
          [
            '#tag' => 'script',
            '#attributes' => [
              'src' => $url_with_timestamp,
              'type' => 'text/javascript',
            ],
          ],
          "kamihaya_cms_custom_js_field_{$delta}",
        ];
      }
    }

    return $elements;
  }

}
