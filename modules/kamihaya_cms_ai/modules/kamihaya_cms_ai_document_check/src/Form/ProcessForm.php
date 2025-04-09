<?php

namespace Drupal\kamihaya_cms_ai_document_check\Form;

use Drupal\kamihaya_cms_ai\Form\ProcessFormBase;

/**
 * Process form.
 */
class ProcessForm extends ProcessFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'document_check_process_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getMessage() {
    return $this->t('Would you like to start a new loan proposal process,<br/> or continue with the ongoing work?');
  }

}
