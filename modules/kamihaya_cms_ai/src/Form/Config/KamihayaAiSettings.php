<?php

namespace Drupal\kamihaya_cms_ai\Form\Config;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Kamihaya AI Settings.
 */
class KamihayaAiSettings extends ConfigFormBase {

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface|null $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface|null $moduleHandler
   *   The module handler.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    protected $typedConfigManager = NULL,
    protected $moduleHandler = NULL,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('config.factory'),
    $container->get('config.typed'),
    $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kamihaya_ai_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['kamihaya_ai.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('kamihaya_cms_ai.settings');
    // Design settings.
    $form['design'] = [
      '#type' => 'details',
      '#title' => $this->t('Design'),
      '#open' => TRUE,
    ];

    // Text color.
    $form['design']['color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Text color'),
      '#default_value' => $config->get('color'),
    ];

    // Glass color.
    $form['design']['glass_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Glass color'),
      '#default_value' => $config->get('glass_color'),
    ];

    // Background settings.
    $form['design']['background'] = [
      '#type' => 'radios',
      '#title' => $this->t('Background'),
      '#required' => TRUE,
      '#options' => [
        'color' => $this->t('Color'),
        'image' => $this->t('Image'),
        'video' => $this->t('Video'),
      ],
      '#default_value' => $config->get('background'),
    ];

    // Background color settings.
    $form['design']['background_color'] = [
      '#type' => 'details',
      '#title' => $this->t('Background Color Settings'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="background"]' => ['value' => 'color'],
        ],
      ],
    ];

    // Background color.
    $form['design']['background_color']['bg_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Background color'),
      '#default_value' => $config->get('bg_color'),
    ];

    // Background item colors.
    foreach (range(0, 2) as $i) {
      $form['design']['background_color']["bg_item_color_$i"] = [
        '#type' => 'textfield',
        '#maxlength' => 7,
        '#size' => 7,
        '#title' => $this->t("Background item color $i"),
        '#default_value' => $config->get("bg_item_color_$i"),
      ];
    }

    // Second mode required.
    $form['design']['second_mode_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Second mode required'),
      '#default_value' => $config->get('second_mode_required'),
    ];

    // Second mode settings.
    $form['design']['second_mode'] = [
      '#type' => 'details',
      '#title' => $this->t('Second Mode Settings'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="second_mode_required"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Default mode name.
    $form['design']['second_mode']['default_mode_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default mode name'),
      '#default_value' => $config->get('default_mode_name'),
      '#states' => [
        'visible' => [
          ':input[name="second_mode"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Second mode name.
    $form['design']['second_mode']['second_mode_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Second mode name'),
      '#default_value' => $config->get('second_mode_name'),
      '#states' => [
        'visible' => [
          ':input[name="second_mode"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Second mode text color.
    $form['design']['second_mode']['second_text_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Text color'),
      '#default_value' => $config->get('second_text_color'),
      '#states' => [
        'visible' => [
          ':input[name="second_mode"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Second mode glass color.
    $form['design']['second_mode']['second_glass_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Glass color'),
      '#default_value' => $config->get('glass_color'),
      '#states' => [
        'visible' => [
          ':input[name="second_mode"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Second mode background color settings.
    $form['design']['second_mode']['second_mode_color'] = [
      '#type' => 'details',
      '#title' => $this->t('Second Mode Color Settings'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          [':input[name="second_mode"]' => ['checked' => TRUE]],
          'and',
          [':input[name="background"]' => ['value' => 'color']],
        ],
      ],
    ];

    // Second mode background color.
    $form['design']['second_mode']['second_mode_color']['second_bg_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Background color'),
      '#default_value' => $config->get('bg_color'),
    ];

    // Second mode background item colors.
    foreach (range(0, 2) as $i) {
      $form['design']['second_mode']['second_mode_color']["second_bg_item_color_$i"] = [
        '#type' => 'textfield',
        '#maxlength' => 7,
        '#size' => 7,
        '#title' => $this->t("Background item color $i"),
        '#default_value' => $config->get("bg_item_color_$i"),
      ];
    }

    // Background image wrapper.
    $form['design']['bg_image_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => ['bg-image-wrapper'],
      ],
      'bg_image' => [
        '#type' => 'managed_file',
        '#title' => $this->t('Background Image'),
        '#upload_location' => 'public://kamihaya_ai/background/image/',
        '#upload_validators' => [
          'file_validate_extensions' => ['gif png jpg jpeg svg webp'],
          'file_validate_size' => [25600000],
        ],
        '#default_value' => $config->get('bg_image'),
      ],
      '#states' => [
        'visible' => [
          [':input[name="background"]' => ['value' => 'image']],
        ],
      ],
    ];

    // Seond mode background image wrapper.
    $form['design']['second_mode']['second_bg_image_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => ['second-bg-image-wrapper'],
      ],
      'second_bg_image' => [
        '#type' => 'managed_file',
        '#title' => $this->t('Background Image'),
        '#upload_location' => 'public://kamihaya_ai/background/image/',
        '#upload_validators' => [
          'file_validate_extensions' => ['gif png jpg jpeg svg webp'],
          'file_validate_size' => [25600000],
        ],
        '#default_value' => $config->get('second_bg_image'),
      ],
      '#states' => [
        'visible' => [
          [':input[name="second_mode"]' => ['checked' => TRUE]],
          'and',
          [':input[name="background"]' => ['value' => 'image']],
        ],
      ],
    ];

    // Background video.
    $form['design']['bg_video_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => ['bg-video-wrapper'],
      ],
      'bg_video' => [
        '#type' => 'managed_file',
        '#title' => $this->t('Background Video'),
        '#upload_location' => 'public://kamihaya_ai/background/video/',
        '#upload_validators' => [
          'file_validate_extensions' => ['mp4 webm ogg'],
          'file_validate_size' => [25600000],
        ],
        '#default_value' => $config->get('background_video'),
      ],
      '#states' => [
        'visible' => [
          [':input[name="background"]' => ['value' => 'video']],
        ],
        'required' => [
          [':input[name="background"]' => ['value' => 'video']],
        ],
      ],
    ];

    // Second mode background video wrapper.
    $form['design']['second_mode']['second_bg_video_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => ['bg-video-wrapper'],
      ],
      'second_bg_video' => [
        '#type' => 'managed_file',
        '#title' => $this->t('Background Video'),
        '#upload_location' => 'public://kamihaya_ai/background/video/',
        '#upload_validators' => [
          'file_validate_extensions' => ['mp4 webm ogg'],
          'file_validate_size' => [25600000],
        ],
        '#default_value' => $config->get('second_bg_video'),
      ],
      '#states' => [
        'visible' => [
          [':input[name="second_mode"]' => ['checked' => TRUE]],
          'and',
          [':input[name="background"]' => ['value' => 'video']],
        ],
      ],
    ];

    if ($this->moduleHandler->moduleExists('color_field')) {
      $this->changeFieldToColor($form['design']);
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('kamihaya_cms_ai.settings');
    $form_state->cleanValues();

    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'color') !== FALSE) {
        $value = $this->getRgbColor($value);
      }
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Change the field to color.
   *
   * @param array $form
   *   The form to change the field to color.
   */
  private function changeFieldToColor(array &$form) {
    foreach ($form as $name => &$element) {
      if (!is_array($element) || empty($element['#type'])) {
        continue;
      }
      if ($element['#type'] === 'details') {
        $this->changeFieldToColor($element);
        continue;
      }
      if ($element['#type'] !== 'textfield' || strpos($name, 'color') === FALSE) {
        continue;
      }
      $this->addColorSpectrum($name, $element);
    }
  }

  /**
   * Add color spectrum to the element.
   *
   * @param string $name
   *   The name of the element.
   * @param array $element
   *   The element to add the color spectrum to.
   */
  private function addColorSpectrum($name, array &$element) {
    $palette = [
      ["#000", "#444", "#666", "#999", "#ccc", "#eee", "#f3f3f3", "#fff"],
      ["#f00", "#f90", "#ff0", "#0f0", "#0ff", "#00f", "#90f", "#f0f"],
      ["#f4cccc", "#fce5cd", "#fff2cc", "#d9ead3", "#d0e0e3", "#cfe2f3", "#d9d2e9", "#ead1dc"],
      ["#ea9999", "#f9cb9c", "#ffe599", "#b6d7a8", "#a2c4c9", "#9fc5e8", "#b4a7d6", "#d5a6bd"],
      ["#e06666", "#f6b26b", "#ffd966", "#93c47d", "#76a5af", "#6fa8dc", "#8e7cc3", "#c27ba0"],
      ["#c00", "#e69138", "#f1c232", "#6aa84f", "#45818e", "#3d85c6", "#674ea7", "#a64d79"],
      ["#900", "#b45f06", "#bf9000", "#38761d", "#134f5c", "#0b5394", "#351c75", "#741b47"],
      ["#600", "#783f04", "#7f6000", "#274e13", "#0c343d", "#073763", "#20124d", "#4c1130"],
    ];
    $settings = [
      'show_input' => TRUE,
      'show_palette' => TRUE,
      'palette' => $palette,
      'show_palette_only' => FALSE,
      'show_buttons' => TRUE,
      'cancel_text' => $this->t('Cancel'),
      'choose_text' => $this->t('Choose'),
      'allow_empty' => TRUE,
    ];

    $element[$name] = $element;
    $element['#type'] = 'container';

    $element['opacity'] = [
      '#type' => 'value',
      '#value' => NULL,
    ];
    $element['#uid'] = str_replace('_', '-', $name);
    $element['#attributes']['id'] = $element['#uid'];
    $element['#attributes']['class'][] = 'js-color-field-widget-spectrum';
    $element['#attached']['drupalSettings']['color_field']['color_field_widget_spectrum'][$element['#uid']] = $settings;
    $element['#attached']['library'][] = 'color_field/color-field-widget-spectrum';
    $element[$name]['#attributes']['class'][] = 'js-color-field-widget-spectrum__color';
    $element['opacity']['#attributes']['class'][] = 'js-color-field-widget-spectrum__opacity';

  }

  /**
   * Helper function to get RGB color from hex.
   *
   * @param string $color
   *   Hex color.
   *
   * @return string
   *   RGB color.
   */
  private function getRgbColor($color) {
    $red = (($color & 0xFF0000) >> 16);
    $green = (($color & 0x00FF00) >> 8);
    $blue = (($color & 0x0000FF));
    return "$red, $green, $blue";
  }

}
