<?php

namespace Drupal\kamihaya_cms_language_negotiation\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\language\Form\NegotiationUrlForm;

/**
 * Stores the site prefix.
 */
class NegotiationForm extends NegotiationUrlForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'language_negotiation_configure_url_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['language.negotiation'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('language.negotiation');

    $default_config = $config->get('site_prefix_language_url');
    $form['site_prefix_language_url']['#tree'] = TRUE;

    $form['site_prefix_language_url']['site_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site prefix'),
      '#maxlength' => 10,
      '#default_value' => $default_config['site_prefix'] ?? '',
      '#size' => 10,
    ];

    $form_state->setRedirect('language.negotiation');

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save selected format (prefix or domain) and site prefix.
    $this->config('language.negotiation')
      ->set('site_prefix_language_url', $form_state->getValue('site_prefix_language_url'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
