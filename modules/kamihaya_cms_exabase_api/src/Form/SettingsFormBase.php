<?php

declare(strict_types=1);

namespace Drupal\kamihaya_cms_exabase_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure ExabaseApi settings for this site.
 */
class SettingsFormBase extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'kamihaya_cms_exabase_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['kamihaya_cms_exabase_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->configFactory()->getEditable($this->getEditableConfigNames()[0]);
    $form['endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Exabase API Endpoint'),
      '#default_value' => $config->get('endpoint'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // @todo Validate the form here.
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->configFactory()->getEditable($this->getEditableConfigNames()[0]);
    $config->set('endpoint', $form_state->getValue('endpoint'))->save();
    parent::submitForm($form, $form_state);
  }

}
