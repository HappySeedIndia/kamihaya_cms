<?php

namespace Drupal\kamihaya_cms_ai_loan_proposal_draft\Form\Config;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\kamihaya_cms_ai\Form\Config\KamihayaAiSettingsBase;

/**
 * Class Kamihaya AI Loan Proposal Draft Settings.
 */
class KamihayaAiLoanProposalDraftSettings extends KamihayaAiSettingsBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kamihaya_cms_ai_loan_proposal_draft_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['kamihaya_cms_ai_loan_proposal_draft.settings'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getSteps()
  {
    return [
      'summarize' => $this->t('Summary Extraction'),
      'draft' => $this->t('Loan Proposal Draft'),
    ];
  }

}
