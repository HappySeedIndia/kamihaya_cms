<?php

namespace Drupal\kamihaya_digitalagency;

use Drupal\bootstrap5\SettingsManager;
use Drupal\Core\Form\FormStateInterface;

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
    if (empty($form['body_details']['b5_body_bg_schema']['#options'])) {
      return;
    }

    $options_color = $form['body_details']['b5_body_bg_schema']['#options'];
    $options_color['black'] = 'black';

    $form['body_details']['b5_body_bg_schema']['#options'] = $options_color;

    if (!empty($form['nav_details']['b5_navbar_bg_schema']['#options'])) {
      $form['nav_details']['b5_navbar_bg_schema']['#options'] = $options_color;
    }
    if (!empty($form['nav_details']['b5_navbar_bg_schema']['#options'])) {
      $form['nav_details']['b5_navbar_bg_schema']['#options'] = $options_color;
    }
    if (!empty($form['footer_details']['b5_footer_bg_schema']['#options'])) {
      $form['footer_details']['b5_footer_bg_schema']['#options'] = $options_color;
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

}
