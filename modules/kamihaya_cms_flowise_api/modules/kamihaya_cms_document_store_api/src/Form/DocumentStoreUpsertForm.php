<?php

declare(strict_types=1);

namespace Drupal\kamihaya_cms_document_store_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\kamihaya_cms_document_store_api\FlowiseClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure FlowiseApi settings for this site.
 */
class DocumentStoreUpsertForm extends FormBase {

  /**
   * Constructs an FlowiseClient object.
   *
   * @param \Drupal\kamihaya_cms_document_store_api\FlowiseClient $flowiseClient
   *   The Flowise API client.
   */
  public function __construct(
    protected FlowiseClient $flowiseClient
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('kamihaya_cms_document_store_api.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'kamihaya_cms_document_store_upsert';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->configFactory()->getEditable('kamihaya_cms_document_store_api.settings');
    $form['document_store_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Document Store ID'),
      '#default_value' => $config->get('document_store_id'),
      '#required' => TRUE,
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upsert Document Store'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $document_store_id = $form_state->getValue('document_store_id');
    if (empty($document_store_id)) {
      $this->messenger()->addError($this->t('Document Store ID cannot be empty.'));
      return;
    }

    try {
      $response = $this->flowiseClient->upsertDocumenStore($document_store_id);
      if (!empty($response)) {
        $this->messenger()->addStatus($this->t('Document Store %id has been successfully upserted.<ul>
          <li>Total keys: @total</li>
          <li>Added count: @added</li>
          <li>Updated count: @updated</li>
          <li>Deleted count: @deleted</li>
          <li>Skipped count: @skipped</li></ul>', [
            '%id' => $document_store_id,
            '@total' => $response['totalKeys'] ?? 0,
            '@added' => $response['numAdded'] ?? 0,
            '@updated' => $response['numUpdated'] ?? 0,
            '@deleted' => $response['numDeleted'] ?? 0,
            '@skipped' => $response['numSkipped'] ?? 0,
          ]));

      } else {
        $this->messenger()->addError($this->t('Failed to upsert Document Store %id.', ['%id' => $document_store_id]));
      }
    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('An error occurred while upserting the Document Store: @message', ['@message' => $e->getMessage()]));
    }
  }

}
