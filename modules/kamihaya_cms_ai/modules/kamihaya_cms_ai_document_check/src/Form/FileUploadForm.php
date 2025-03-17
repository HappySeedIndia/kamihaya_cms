<?php

namespace Drupal\kamihaya_cms_ai_document_check\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * File upload form.
 */
class FileUploadForm extends FormBase {

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
      '#value' => $this->t('Check starrt'),
      '#attributes' => ['id' => 'document-check-start'],
      '#states' => [
        'visible' => [
          ':input[name="files[file]"]' => ['filled' => TRUE],
        ],
      ],
    ];
    return $form;
  }

  /**
   * Ajax callback for the submit button.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function ajaxSubmitCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $file = $form_state->getValue('file_upload');
    if (!empty($file[0])) {
      $file_entity = File::load($file[0]);
      $file_entity->setPermanent();
      $file_entity->save();

      // 成功メッセージを追加.
      $message = '<p class="success">File uploaded successfully: ' . $file_entity->getFilename() . '</p>';
      $response->addCommand(new HtmlCommand('#upload-message', $message));
    }
    else {
      // エラーメッセージ.
      $message = '<p class="error">Upload failed. Please try again.</p>';
      $response->addCommand(new HtmlCommand('#upload-message', $message));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

}
