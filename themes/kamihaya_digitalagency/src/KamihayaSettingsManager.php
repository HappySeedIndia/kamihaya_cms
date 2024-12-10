<?php

namespace Drupal\kamihaya_digitalagency;

use Drupal\Core\Form\FormStateInterface;
use Drupal\bootstrap5\SettingsManager;

/**
 * Kamihaya theme settings manager.
 */
class KamihayaSettingsManager extends SettingsManager {

  /**
   * Alters theme settings form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The form id.
   *
   * @see hook_form_alter()
   */
  public function themeSettingsAlter(array &$form, FormStateInterface $form_state, $form_id) {
    parent::themeSettingsAlter($form, $form_state, $form_id);
    if (!empty($form['logo']['#title'])) {
      $form['logo']['#title'] = t('Logo');
    }

    if (!empty($form['logo']['settings'])) {
      $form['logo']['settings']['logo_url'] = [
        '#type' => 'url',
        '#title' => $this->t('Logo URL'),
        '#default_value' => theme_get_setting('logo_url'),
        '#description' => $this->t("The URL of the logo. Please leave it empty if you want to link to the front page."),
      ];
    }
    if (empty($form['body_details']['b5_body_bg_schema']['#options']) || empty($form['body_details']['b5_body_schema']['#options'])) {
      return;
    }

    $options_color = $form['body_details']['b5_body_bg_schema']['#options'];
    $options_color['black'] = 'black';
    $options_color['custom'] = 'custom';
    $options_theme = $form['body_details']['b5_body_schema']['#options'];
    $options_theme['custom'] = 'custom';

    $form['body_details']['b5_body_bg_schema']['#options'] = $options_color;

    if (!empty($form['nav_details']['b5_navbar_bg_schema']['#options'])) {
      $form['nav_details']['b5_navbar_bg_schema']['#options'] = $options_color;
    }
    if (!empty($form['footer_details']['b5_footer_bg_schema']['#options'])) {
      $form['footer_details']['b5_footer_bg_schema']['#options'] = $options_color;
    }

    $form['body_details']['b5_body_schema']['#options'] = $options_theme;

    if (!empty($form['nav_details']['b5_navbar_schema']['#options'])) {
      $form['nav_details']['b5_navbar_schema']['#options'] = $options_theme;
    }
    if (!empty($form['footer_details']['b5_footer_schema']['#options'])) {
      $form['footer_details']['b5_footer_schema']['#options'] = $options_theme;
    }

    $tmp_field = $form['body_details']['b5_body_bg_schema'];
    unset($form['body_details']['b5_body_bg_schema']);

    $form['body_details']['body_schema_custom_theme'] = [
      '#type' => 'details',
      '#title' => $this->t('Body custom theme'),
      '#open' => FALSE,
      '#states' => [
        'visible' => [
          'select[name="b5_body_schema"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $form['body_details']['body_schema_custom_theme']['b5_body_text_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Body text color:'),
      '#default_value' => theme_get_setting('b5_body_text_color'),
      '#description' => $this->t("Custom text color of the body."),
    ];

    $form['body_details']['body_schema_custom_theme']['b5_body_link_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Body link color:'),
      '#default_value' => theme_get_setting('b5_body_link_color'),
      '#description' => $this->t("Custom link color of the body."),
    ];

    $form['body_details']['body_schema_custom_theme']['b5_body_link_hover_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Body link hover color:'),
      '#default_value' => theme_get_setting('b5_body_link_hover_color'),
      '#description' => $this->t("Custom link hover color of the body."),
    ];

    $form['body_details']['b5_body_bg_schema'] = $tmp_field;

    $form['body_details']['body_schema_custom_bg'] = [
      '#type' => 'details',
      '#title' => $this->t('Body custom theme'),
      '#open' => FALSE,
      '#states' => [
        'visible' => [
          'select[name="b5_body_bg_schema"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $form['body_details']['body_schema_custom_bg']['b5_body_bg_schema_custom'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Body custom background:'),
      '#default_value' => theme_get_setting('b5_body_bg_schema_custom'),
      '#description' => $this->t("Custom background color of the body."),
    ];

    $form['body_details']['body_schema_custom_bg']['b5_body_light_bg_schema_custom'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Body custom light background:'),
      '#default_value' => theme_get_setting('b5_body_light_bg_schema_custom'),
      '#description' => $this->t("Custom light background color of the body."),
    ];

    $form['body_details']['body_schema_custom_bg']['b5_body_secondary_bg_schema_custom'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Body custom secondary background:'),
      '#default_value' => theme_get_setting('b5_body_secondary_bg_schema_custom'),
      '#description' => $this->t("Custom secondary background color of the body."),
    ];

    $tmp_field = $form['nav_details']['b5_navbar_bg_schema'];
    unset($form['nav_details']['b5_navbar_bg_schema']);

    $form['nav_details']['navbar_schema_custom_theme'] = [
      '#type' => 'details',
      '#title' => $this->t('Navbar custom theme'),
      '#open' => FALSE,
      '#states' => [
        'visible' => [
          'select[name="b5_navbar_schema"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $form['nav_details']['navbar_schema_custom_theme']['b5_navbar_text_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Navbar text color:'),
      '#default_value' => theme_get_setting('b5_navbar_text_color'),
      '#description' => $this->t("Custom text color of the navbar."),
    ];

    $form['nav_details']['navbar_schema_custom_theme']['b5_navbar_link_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Navbar link color:'),
      '#default_value' => theme_get_setting('b5_navbar_link_color'),
      '#description' => $this->t("Custom link color of the navbar."),
    ];

    $form['nav_details']['navbar_schema_custom_theme']['b5_navbar_link_hover_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Navbar link hover color:'),
      '#default_value' => theme_get_setting('b5_navbar_link_hover_color'),
      '#description' => $this->t("Custom link hover color of the navbar."),
    ];

    $form['nav_details']['navbar_schema_custom_theme']['b5_navbar_link_active_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Navbar link active color:'),
      '#default_value' => theme_get_setting('b5_navbar_link_active_color'),
      '#description' => $this->t("Custom link active color of the navbar."),
    ];

    $form['nav_details']['navbar_schema_custom_theme']['b5_navbar_dropdown_hover_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Navbar dropdown hover color:'),
      '#default_value' => theme_get_setting('b5_navbar_dropdown_hover_color'),
      '#description' => $this->t("Custom dropdown hover color of the navbar."),
    ];

    $form['nav_details']['navbar_schema_custom_theme']['b5_navbar_dropdown_hover_bk_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Navbar dropdown hover background color:'),
      '#default_value' => theme_get_setting('b5_navbar_dropdown_hover_bk_color'),
      '#description' => $this->t("Custom dropdown background hover color of the navbar."),
    ];

    $form['nav_details']['navbar_schema_custom_theme']['b5_navbar_dropdown_active_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Navbar dropdown active color:'),
      '#default_value' => theme_get_setting('b5_navbar_dropdown_active_color'),
      '#description' => $this->t("Custom dropdown active color of the navbar."),
    ];

    $form['nav_details']['navbar_schema_custom_theme']['b5_navbar_dropdown_active_bg_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Navbar dropdown active background color:'),
      '#default_value' => theme_get_setting('b5_navbar_dropdown_active_bg_color'),
      '#description' => $this->t("Custom dropdown background active color of the navbar."),
    ];

    $form['nav_details']['b5_navbar_bg_schema'] = $tmp_field;

    $form['nav_details']['navbar_schema_custom_bg'] = [
      '#type' => 'details',
      '#title' => $this->t('Navbar custom theme'),
      '#open' => FALSE,
      '#states' => [
        'visible' => [
          'select[name="b5_navbar_bg_schema"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $form['nav_details']['navbar_schema_custom_bg']['b5_navbar_bg_schema_custom'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Navbar custom background:'),
      '#default_value' => theme_get_setting('b5_navbar_bg_schema_custom'),
      '#description' => $this->t("Custom background color of the navbar."),
    ];

    $form['nav_details']['navbar_schema_custom_bg']['b5_navbar_secondary_bg_schema_custom'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Navbar custom secondary background:'),
      '#default_value' => theme_get_setting('b5_navbar_secondary_bg_schema_custom'),
      '#description' => $this->t("Custom background secondary color of the navbar."),
    ];

    $form['nav_details']['b5_navbar_sp_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Navbar mobile menu position:'),
      '#default_value' => theme_get_setting('b5_navbar_sp_position'),
      '#description' => $this->t("Position of mobile menu."),
      '#options' => [
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
      ],
    ];

    $form['nav_details']['b5_navbar_sp_revert'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Navbar mobile revert position'),
      '#default_value' => theme_get_setting('b5_navbar_sp_revert'),
      '#description' => $this->t("Revert the position in the mobile display."),
    ];

    $tmp_field = $form['footer_details']['b5_footer_bg_schema'];
    unset($form['footer_details']['b5_footer_bg_schema']);

    $form['footer_details']['footer_schema_custom_theme'] = [
      '#type' => 'details',
      '#title' => $this->t('Footer custom theme'),
      '#open' => FALSE,
      '#states' => [
        'visible' => [
          'select[name="b5_footer_schema"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $form['footer_details']['footer_schema_custom_theme']['b5_footer_text_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Footer text color:'),
      '#default_value' => theme_get_setting('b5_footer_text_color'),
      '#description' => $this->t("Custom text color of the footer."),
    ];

    $form['footer_details']['footer_schema_custom_theme']['b5_footer_link_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Footer link color:'),
      '#default_value' => theme_get_setting('b5_footer_link_color'),
      '#description' => $this->t("Custom link color of the footer."),
    ];

    $form['footer_details']['footer_schema_custom_theme']['b5_footer_link_hover_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Footer link hover color:'),
      '#default_value' => theme_get_setting('b5_footer_link_hover_color'),
      '#description' => $this->t("Custom link hover color of the footer."),
    ];

    $form['footer_details']['footer_schema_custom_theme']['b5_footer_link_active_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Footer link active color:'),
      '#default_value' => theme_get_setting('b5_footer_link_active_color'),
      '#description' => $this->t("Custom link active color of the footer."),
    ];

    $form['footer_details']['b5_footer_bg_schema'] = $tmp_field;

    $form['footer_details']['footer_schema_custom_bg'] = [
      '#type' => 'details',
      '#title' => $this->t('Footer custom theme'),
      '#open' => FALSE,
      '#states' => [
        'visible' => [
          'select[name="b5_footer_bg_schema"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $form['footer_details']['footer_schema_custom_bg']['b5_footer_bg_schema_custom'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Footer custom background:'),
      '#default_value' => theme_get_setting('b5_footer_bg_schema_custom'),
      '#description' => $this->t("Custom background color of the footer."),
    ];

    $form['footer_details']['footer_schema_custom_bg']['b5_footer_secondary_bg_schema_custom'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Footer custom secondary background:'),
      '#default_value' => theme_get_setting('b5_footer_secondary_bg_schema_custom'),
      '#description' => $this->t("Custom background secondary color of the footer."),
    ];

    $form['footer_details']['b5_footer_width_ratio'] = [
      '#type' => 'select',
      '#title' => $this->t('Footer width ratio:'),
      '#default_value' => theme_get_setting('b5_footer_width_ratio'),
      '#description' => $this->t("Footer left and right width ratio."),
      '#options' => [
        '3:7' => $this->t('3:7'),
        '4:6' => $this->t('4:6'),
        '5:5' => $this->t('5:5'),
        '6:4' => $this->t('6:4'),
        '7:3' => $this->t('7:3'),
      ],
    ];

    $form['footer_details']['b5_footer_sp_rightt_top'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Footer mobile right top'),
      '#default_value' => theme_get_setting('b5_footer_sp_rightt_top'),
      '#description' => $this->t("Place footer right on the top of footer in the mobile display."),
    ];

    $text_formats = [];
    if (!empty($form['text_formats'])) {
      $text_formats = $form['text_formats'];
      unset($form['text_formats']);
    }

    $form['site_common'] = [
      '#type' => 'details',
      '#title' => $this->t('Site common'),
      '#group' => 'sw',
      '#open' => TRUE,
    ];

    $form['site_common']['site_max_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Site max width(px)'),
      '#default_value' => theme_get_setting('site_max_width'),
    ];

    $form['site_common']['b5_back_to_top_shape'] = [
      '#type' => 'select',
      '#title' => $this->t('Gack to top link shape:'),
      '#default_value' => theme_get_setting('b5_back_to_top_shape'),
      '#description' => $this->t("The shape of the back to top link."),
      '#options' => [
        'circle' => $this->t('Circle'),
        'scquare' => $this->t('Scquare'),
        'text' => $this->t('With text'),
      ],
    ];

    $form['button_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Button colors'),
      '#description' => $this->t("Customize the colors of the buttons."),
      '#open' => TRUE,
    ];

    $form['button_details']['button_radius'] = [
      '#type' => 'number',
      '#minimum' => 0,
      '#title' => $this->t('Button radius:'),
      '#default_value' => theme_get_setting('button_radius'),
    ];

    $form['button_details']['primary_button'] = [
      '#type' => 'details',
      '#title' => $this->t('Primary button:'),
      '#open' => FALSE,
    ];

    $form['button_details']['primary_button']['primary_button_text_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Primary button text color:'),
      '#default_value' => theme_get_setting('primary_button_text_color'),
      '#description' => $this->t("Custom text color of the primary button."),
    ];

    $form['button_details']['primary_button']['primary_button_bg_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Primary button background color:'),
      '#default_value' => theme_get_setting('primary_button_bg_color'),
      '#description' => $this->t("Custom background color of the primary button."),
    ];

    $form['button_details']['primary_button']['primary_button_bd_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Primary button border color:'),
      '#default_value' => theme_get_setting('primary_button_bd_color'),
      '#description' => $this->t("Custom border color of the primary button."),
    ];

    $form['button_details']['primary_button']['primary_button_hover_text_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Primary button hover text color:'),
      '#default_value' => theme_get_setting('primary_button_hover_text_color'),
      '#description' => $this->t("Custom hover text color of the primary button."),
    ];

    $form['button_details']['primary_button']['primary_button_hover_bg_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Primary button hover color:'),
      '#default_value' => theme_get_setting('primary_button_hover_bg_color'),
      '#description' => $this->t("Custom hover color of the primary button."),
    ];

    $form['button_details']['primary_button']['primary_button_hover_bd_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Primary button hover border color:'),
      '#default_value' => theme_get_setting('primary_button_hover_bd_color'),
      '#description' => $this->t("Custom hover border color of the primary button."),
    ];

    $form['button_details']['primary_button']['primary_button_active_text_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Primary button active text color:'),
      '#default_value' => theme_get_setting('primary_button_active_text_color'),
      '#description' => $this->t("Custom active text color of the primary button."),
    ];

    $form['button_details']['primary_button']['primary_button_active_bg_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Primary button active color:'),
      '#default_value' => theme_get_setting('primary_button_active_bg_color'),
      '#description' => $this->t("Custom active color of the primary button."),
    ];

    $form['button_details']['primary_button']['primary_button_active_bd_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Primary button active border color:'),
      '#default_value' => theme_get_setting('primary_button_active_bd_color'),
      '#description' => $this->t("Custom active border color of the primary button."),
    ];

    $form['button_details']['secondary_button'] = [
      '#type' => 'details',
      '#title' => $this->t('Secondary button:'),
      '#open' => FALSE,
    ];

    $form['button_details']['secondary_button']['secondary_button_text_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Secondary button text color:'),
      '#default_value' => theme_get_setting('secondary_button_text_color'),
      '#description' => $this->t("Custom text color of the secondary button."),
    ];

    $form['button_details']['secondary_button']['secondary_button_bg_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Secondary button background color:'),
      '#default_value' => theme_get_setting('secondary_button_bg_color'),
      '#description' => $this->t("Custom background color of the secondary button."),
    ];

    $form['button_details']['secondary_button']['secondary_button_bd_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Secondary button border color:'),
      '#default_value' => theme_get_setting('secondary_button_bd_color'),
      '#description' => $this->t("Custom border color of the secondary button."),
    ];

    $form['button_details']['secondary_button']['secondary_button_hover_text_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Secondary button hover text color:'),
      '#default_value' => theme_get_setting('secondary_button_hover_text_color'),
      '#description' => $this->t("Custom hover text color of the secondary button."),
    ];

    $form['button_details']['secondary_button']['secondary_button_hover_bg_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Secondary button hover color:'),
      '#default_value' => theme_get_setting('secondary_button_hover_bg_color'),
      '#description' => $this->t("Custom hover color of the secondary button."),
    ];

    $form['button_details']['secondary_button']['secondary_button_hover_bd_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Secondary button hover border color:'),
      '#default_value' => theme_get_setting('secondary_button_hover_bd_color'),
      '#description' => $this->t("Custom hover border color of the secondary button."),
    ];

    $form['button_details']['secondary_button']['secondary_button_active_text_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Secondary button active text color:'),
      '#default_value' => theme_get_setting('secondary_button_active_text_color'),
      '#description' => $this->t("Custom active text color of the secondary button."),
    ];

    $form['button_details']['secondary_button']['secondary_button_active_bg_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Secondary button active color:'),
      '#default_value' => theme_get_setting('secondary_button_active_bg_color'),
      '#description' => $this->t("Custom active color of the secondary button."),
    ];

    $form['button_details']['secondary_button']['secondary_button_active_bd_color'] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Secondary button active border color:'),
      '#default_value' => theme_get_setting('secondary_button_active_bd_color'),
      '#description' => $this->t("Custom active border color of the secondary button."),
    ];

    if (!empty($text_formats)) {
      $form['text_formats'] = $text_formats;
    }

    if (\Drupal::moduleHandler()->moduleExists('color_field')) {

      foreach ($form as &$children) {
        if (!is_array($children) || empty($children['#type'])) {
          continue;
        }
        if ($children['#type'] === 'details') {
          foreach ($children as $name => &$element) {
            if (!is_array($element)) {
              continue;
            }
            if (strpos($name, 'bg_schema_custom') !== FALSE) {
              $this->addColorSpectrum($name, $element);
              continue;
            }
            if (!is_array($element) || empty($element['#type']) || $element['#type'] !== 'details') {
              continue;
            }
            foreach ($element as $child_name => &$child_element) {
              if (strpos($child_name, 'color') === FALSE && strpos($child_name, 'bg_schema_custom') === FALSE) {
                continue;
              }
              $this->addColorSpectrum($child_name, $child_element);
            }
          }
        }
      }
    }

    $subtheme = [];
    if (!empty($form['subtheme'])) {
      $subtheme = $form['subtheme'];
      unset($form['subtheme']);
    }
    $form['teaser'] = [
      '#type' => 'details',
      '#title' => $this->t('Teaser'),
      '#group' => 'pd',
      '#open' => TRUE,
    ];

    $form['teaser']['teaser_show_author_info'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show author info'),
      '#description' => '',
      '#default_value' => theme_get_setting('teaser_show_author_info'),
    ];

    $form['teaser']['teaser_show_post_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show post date'),
      '#description' => '',
      '#default_value' => theme_get_setting('teaser_show_post_date'),
    ];

    if (!empty($subtheme)) {
      $form['subtheme'] = $subtheme;
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

}
