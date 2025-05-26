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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->configFactory()->getEditable($this->getEditableConfigNames()[0]);

    if (empty($form['api_error_mode_settings'])) {
      return $form;
    }

    // Add API error mode settings temporary reslt.
    $form['api_error_mode_settings']['api_temporary_result'] = [
      '#type' => 'details',
      '#title' => $this->t('Temporary Result'),
      '#description' => $this->t('This is the result that will be used when the API error mode is enabled.'),
    ];

    $form['api_error_mode_settings']['api_temporary_result']['company'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Company Name'),
      '#description' => $this->t('Enter the company name.'),
      '#default_value' => $config->get('company'),
    ];
    $form['api_error_mode_settings']['api_temporary_result']['company_detail'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Company Detail'),
      '#description' => $this->t('Enter the company detail.'),
      '#default_value' => $config->get('company_detail'),
    ];
    $form['api_error_mode_settings']['api_temporary_result']['pdf_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('PDF Text'),
      '#description' => $this->t('Enter the PDF text.'),
      '#default_value' => $config->get('pdf_text'),
    ];
    $form['api_error_mode_settings']['api_temporary_result']['pdf_summary_prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('PDF Summary Prompt'),
      '#description' => $this->t('Enter the PDF summary prompt.'),
      '#default_value' => $config->get('pdf_summary_prompt'),
    ];
    $form['api_error_mode_settings']['api_temporary_result']['pdf_summary'] = [
      '#type' => 'textarea',
      '#title' => $this->t('PDF Summary'),
      '#description' => $this->t('Enter the PDF summary.'),
      '#default_value' => $config->get('pdf_summary'),
    ];
    $form['api_error_mode_settings']['api_temporary_result']['loan_document_prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Loan Document Prompt'),
      '#description' => $this->t('Enter the loan document prompt.'),
      '#default_value' => $config->get('loan_document_prompt'),
    ];
    $form['api_error_mode_settings']['api_temporary_result']['loan_document'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Loan Document'),
      '#description' => $this->t('Enter the loan document.'),
      '#default_value' => $config->get('loan_document'),
    ];

    return $form;
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
