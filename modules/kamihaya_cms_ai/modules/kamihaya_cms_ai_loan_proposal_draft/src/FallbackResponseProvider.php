<?php

declare(strict_types=1);

namespace Drupal\kamihaya_cms_ai_loan_proposal_draft;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\kamihaya_cms_loan_proposal_api\ExabaseClient;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Fallback class for Exabase API Client.
 */
final class FallbackResponseProvider extends ExabaseClient {

  /**
   * The configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The wait second.
   *
   * @var int
   */
  protected $waitSec;

  /**
   * Constructs an ExabaseClient object.
   */
  public function __construct(
    protected ClientInterface $httpClient,
    protected LoggerInterface $logger,
    protected ConfigFactoryInterface $configFactory,
  ) {
    $this->config = $this->configFactory->get('kamihaya_cms_ai_loan_proposal_draft.settings');
    $this->waitSec = $this->config->get('api_error_mode_wait_time') ?: 30;
  }

  /**
   * Calls the get prompt endpoint.
   */
  public function getPrompt() {
    return [
      'pdf_summary' => $this->config->get('pdf_summary_prompt'),
      'loan_document' => $this->config->get('loan_document_prompt'),
    ];
  }

  /**
   * Calls the get company list endpoint.
   */
  public function getCompanyList() {
    return [$this->config->get('company')];
  }

  /**
   * Calls the get company list endpoint.
   */
  public function getCompanyDetail(string $company) {
    return ['content' => $this->config->get('company_detail')];
  }

  /**
   * Calls the extract PDF endpoint.
   */
  public function extractPdf(string $file_path) {
    return [
      'pdf_text' => $this->config->get('pdf_text'),
    ];
  }

  /**
   * Calls the summarize text endpoint.
   */
  public function summarizeText(string $prompt) {
    sleep(intval($this->waitSec));
    return $this->config->get('pdf_summary');
  }

    /**
   * Calls the make loan proposal endpoint.
   */
  public function makeLoanProposal(string $prompt, string $loan, string $company) {
    sleep(intval($this->waitSec));
    return [
      'loan_summary' => $this->config->get('loan_document'),
    ];
  }
}
