<?php

declare(strict_types=1);

namespace Drupal\kamihaya_cms_loan_proposal_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\kamihaya_cms_exabase_api\ExabaseClientBase;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Exabase API Client.
 */
final class ExabaseClient extends ExabaseClientBase {

  /**
   * Constructs an ExabaseClient object.
   */
  public function __construct(
    protected ClientInterface $httpClient,
    protected LoggerInterface $logger,
    protected ConfigFactoryInterface $configFactory,
  ) {
    $this->baseUrl = $this->configFactory->get('kamihaya_cms_loan_proposal_api.settings')->get('endpoint');
  }

  /**
   * Calls the get prompt endpoint.
   */
  public function getPrompt() {
    return $this->request(
      'GET', '/prompt_config', [
        'headers' => [
          'Accept' => 'application/json',
        ],
      ],
    );
  }

  /**
   * Calls the get company list endpoint.
   */
  public function getCompanyList() {
    return $this->request(
      'GET', '/companies', [
        'headers' => [
          'Accept' => 'application/json',
        ],
      ],
    );
  }

  /**
   * Calls the get company list endpoint.
   */
  public function getCompanyDetail(string $company) {
    return $this->request(
      'GET', "/company/$company", [
        'headers' => [
          'Accept' => 'application/json',
        ],
      ],
    );
  }

  /**
   * Calls the extract PDF endpoint.
   */
  public function extractPdf(string $file_path) {
    $file_stream = fopen($file_path, 'r');

    return $this->request(
      'POST', '/read_pdf', [
        'multipart' => [
          [
            'name' => 'file',
            'contents' => $file_stream,
            'filename' => basename($file_path),
            'headers' => [
              'Content-Type' => 'application/pdf',
            ],
          ],
        ],
      ]
    );
  }

  /**
   * Calls the summarize text endpoint.
   */
  public function summarizeText(string $prompt) {
    return $this->request(
      'POST', '/summary_pdf', [
        'headers' => [
          'Accept' => 'application/json',
          'Content-Type' => 'application/json',
        ],
        'json' => ['prompt' => $prompt],
      ],
    );
  }

    /**
   * Calls the make loan proposal endpoint.
   */
  public function makeLoanProposal(string $prompt, string $loan, string $company) {
    return $this->request(
      'POST', '/make_loan_doc', [
        'headers' => [
          'Accept' => 'application/json',
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'prompt' => $prompt,
          'loan' => $loan,
          'company' => $company,
        ],
      ],
    );
  }

}
