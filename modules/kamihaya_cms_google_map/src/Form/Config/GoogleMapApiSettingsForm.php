<?php

namespace Drupal\kamihaya_cms_google_map\Form\Config;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to configure Google Map API settings.
 */
class GoogleMapApiSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kamihaya_cms_google_map_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['kamihaya_cms_google_map.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $site_config = $this->config('kamihaya_cms_google_map.settings');

    $form['google_map'] = [
      '#type' => 'details',
      '#title' => $this->t('Google Map API Settings'),
      '#open' => TRUE,
    ];
    $form['google_map']['google_map_api_key'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#maxlength' => 255,
      '#size' => 60,
      '#description' => $this->t('Enter your Google Map API key. This is required for the map to function properly.'),
      '#title' => $this->t('API Key'),
      '#default_value' => $site_config->get('google_map_api_key'),
    ];
    $form['google_map']['google_map_center_latitude'] = [
      '#type' => 'number',
      '#title' => $this->t('Center Latitude'),
      '#default_value' => $site_config->get('google_map_center_latitude'),
      '#step' => 0.000001,
      '#description' => $this->t('Enter the latitude for the center of the map.'),
    ];
    $form['google_map']['google_map_center_longitude'] = [
      '#type' => 'number',
      '#title' => $this->t('Center Longitude'),
      '#default_value' => $site_config->get('google_map_center_longitude'),
      '#step' => 0.000001,
      '#description' => $this->t('Enter the longitude for the center of the map.'),
    ];
    $form['google_map']['google_map_zoom_level'] = [
      '#type' => 'number',
      '#title' => $this->t('Zoom Level'),
      '#default_value' => $site_config->get('google_map_zoom_level'),
      '#min' => 0,
      '#max' => 21,
      '#step' => 1,
      '#description' => $this->t('Set the zoom level for the map.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable($this->getEditableConfigNames()[0]);
    $form_state->cleanValues();
    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }


}
