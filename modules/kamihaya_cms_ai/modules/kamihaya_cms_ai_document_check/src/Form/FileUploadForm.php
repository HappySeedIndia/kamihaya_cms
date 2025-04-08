<?php

namespace Drupal\kamihaya_cms_ai_document_check\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * File upload form.
 */
class FileUploadForm extends FormBase {

  public function __construct(protected AccountProxyInterface $currentUser) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'file_upload_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user = User::load($this->currentUser->id());
    $name = $user->hasField('field_name') && !empty($user->get('field_name')->value) ? $user->get('field_name')->value : $user->getAccountName();

    $form['welcome'] = [
      '#type' => 'markup',
      '#markup' => "<p class='welcome'>" . $this->t("Hello @name!", ['@name' => $name]) . '</p>',
    ];

    $form['message'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t("Let's check your document.") . '</p>',
    ];

    $form['file_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload a file'),
      '#upload_location' => 'public://document_chheck/documents/',
      '#description' => $this->t('Allowed extensions: doc, docx'),
      '#upload_validators' => [
        'file_validate_extensions' => ['doc docx'],
      ],
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Check start'),
      '#attributes' => ['id' => 'document-check-start'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

}
