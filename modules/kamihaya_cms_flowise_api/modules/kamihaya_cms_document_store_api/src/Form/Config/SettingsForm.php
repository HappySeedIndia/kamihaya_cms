<?php

declare(strict_types=1);

namespace Drupal\kamihaya_cms_document_store_api\Form\Config;

use Drupal\Core\Form\FormStateInterface;
use Drupal\kamihaya_cms_flowise_api\Form\SettingsFormBase;

/**
 * Configure FlowiseApi settings to upsert document store.
 */
class SettingsForm extends SettingsFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'kamihaya_cms_document_store_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['kamihaya_cms_document_store_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->configFactory()->getEditable($this->getEditableConfigNames()[0]);
    $form = parent::buildForm($form, $form_state);
    $form['document_store_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Document Store ID'),
      '#default_value' => $config->get('document_store_id'),
      '#required' => TRUE,
    ];
    $form['upsert_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Upsert Interval'),
      '#options' => [
        '1800' => $this->t('30 minutes'),
        '3600' => $this->t('1 hour'),
        '7200' => $this->t('2 hours'),
        '18000' => $this->t('5 hours'),
        '43200' => $this->t('12 hours'),
        '86400' => $this->t('1 day'),
        'none' => $this->t('Never'),
      ],
      '#default_value' => $config->get('upsert_interval') ?: '3600',
      '#description' => $this->t('Select how often to upsert documents. <br>This depends on the cron interval of your site. <br>Set to "Never" to disable automatic upsert.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->configFactory()->getEditable($this->getEditableConfigNames()[0]);
    $config->set('document_store_id', $form_state->getValue('document_store_id'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
