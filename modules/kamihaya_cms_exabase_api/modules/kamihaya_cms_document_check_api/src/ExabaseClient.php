<?php

declare(strict_types=1);

namespace Drupal\kamihaya_cms_document_check_api;

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
    $this->baseUrl = $this->configFactory->get('kamihaya_cms_document_check_api.settings')->get('endpoint');
  }

  /**
   * Calls the extract endpoint.
   */
  public function postExtract(string $file_path) {
    $file_stream = fopen($file_path, 'r');

    return $this->request(
      'POST', '/extract', [
        'multipart' => [
          [
            'name' => 'file',
            'contents' => $file_stream,
            'filename' => basename($file_path),
            'headers' => [
            // @todo Do we need this dynamically added based on file type.
              'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
          ],
        ],
      ]
    );
  }

  /**
   * Calls the check endpoint.
   */
  public function postCheck(string $summary) {
    return $this->request(
      'POST', '/check', [
        'headers' => [
          'Accept' => 'application/json',
          'Content-Type' => 'application/json',
        ],
        'json' => ['spec_summary' => $summary],
      ],
    );
  }

  /**
   * Calls the check endpoint.
   */
  public function postReCheck(string $summary, string $check_result) {
    return $this->request(
      'POST', '/recheck', [
        'headers' => [
          'Accept' => 'application/json',
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'spec_summary' => $summary,
          'checkresult' => $check_result,
        ],
      ],
    );
  }
}
