<?php

namespace Drupal\kamihaya_cms_ai_loan_proposal_draft\Controller;

use Drupal\kamihaya_cms_ai\Controller\KamihayaAiControllerBase;
use Drupal\kamihaya_cms_ai_loan_proposal_draft\Form\ProcessForm;
use Drupal\kamihaya_cms_ai_loan_proposal_draft\Form\CompanySelectForm;

/**
 * Controller for handling the loan proposal draft.
 */
class KamihayaAiLoanProposalDraftController extends KamihayaAiControllerBase {

  /**
   * Set the title of the page.
   *
   * @return string
   *   The title of the page.
   */
  public function title() {
    return $this->t('Loan Proposal Draft');
  }

  /**
   * Get the config names.
   *
   * @return string
   *   The config name.
   */
  protected function getConfigNames() {
    return 'kamihaya_cms_ai_loan_proposal_draft.settings';
  }

  /**
   * Set the content of the page.
   *
   * @return array
   *   The content of the page.
   */
  public function content() {
    $theme = parent::content();
    $config = $this->config($this->getConfigNames());

    $first_screne = $this->formBuilder->getForm(CompanySelectForm::class);
    $theme['#chat_body'] = $first_screne;
    $process_form = $this->formBuilder->getForm(ProcessForm::class);
    $theme['#process_form'] = $process_form;
    $theme['#step_design'] = $config->get('step_design');
    $theme['#edit_prompt'] = TRUE;
    $theme['#attached']['library'][] = 'kamihaya_cms_ai_loan_proposal_draft/kamihaya_ai.loan_proposal_draft';
    $theme['#attached']['drupalSettings']['ajax_url'] = 'ajax-handler-loan-proposal-draft';
    $theme['#attached']['drupalSettings']['process_name'] = $this->t('Loan proposal');
    $theme['#attached']['drupalSettings']['task_name'] = $this->t('Loan proposal drafting');
    $theme['#attached']['drupalSettings']['last_task_name'] = $this->t('Research on historical records and borrower companies');
    return $theme;
  }

    /**
   * {@inheritdoc}
   */
  protected function getSteps() {
    $steps = parent::getSteps();
    $steps['summarize'] = [
      'name' => $this->t('Summarize PDF text'),
    ];
    $steps['draft'] = [
      'name' => $this->t('Loan Proposal Draft'),
    ];

    return $steps;
  }

}
