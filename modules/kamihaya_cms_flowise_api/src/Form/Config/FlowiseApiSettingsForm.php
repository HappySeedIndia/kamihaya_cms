<?php

namespace Drupal\kamihaya_cms_flowise_api\Form\Config;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to configure Flowise API settings.
 */
class FlowiseApiSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kamihaya_cms_flowise_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['kamihaya_cms_flowise_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable($this->getEditableConfigNames()[0]);

    $form['flowise_api'] = [
      '#type' => 'details',
      '#title' => $this->t('Flowise API Settings'),
      '#open' => TRUE,
    ];
    $form['flowise_api']['endpoint'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#maxlength' => 255,
      '#size' => 60,
      '#description' => $this->t('Enter your Flowise API endpoint URL.'),
      '#title' => $this->t('API Endpoint'),
      '#default_value' => $config->get('endpoint'),
    ];
    $form['flowise_api']['api_key'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#maxlength' => 255,
      '#size' => 60,
      '#description' => $this->t('Enter your Flowise API key.'),
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('api_key'),
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
