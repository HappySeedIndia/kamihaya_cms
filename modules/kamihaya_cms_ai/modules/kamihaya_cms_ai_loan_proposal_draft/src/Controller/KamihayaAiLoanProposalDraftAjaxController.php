<?php

namespace Drupal\kamihaya_cms_ai_loan_proposal_draft\Controller;

use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\kamihaya_cms_ai\Controller\KamihayaAiAjaxController;
use Drupal\kamihaya_cms_loan_proposal_api\ExabaseClient;
use Drupal\kamihaya_cms_ai_loan_proposal_draft\FallbackResponseProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for handling the ajax request.
 */
class KamihayaAiLoanProposalDraftAjaxController extends KamihayaAiAjaxController {

  const SESSION_KEY = 'kamihaya_ai_loan_proposal_draft';

  /**
   * The configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a new KamihayaAiLoanProposalDraftAjaxController object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system.
   * @param \Drupal\kamihaya_cms_loan_proposal_api\ExabaseClient $exabaseClient
   *   The Exabase client.
   * @param \Drupal\kamihaya_cms_ai_loan_proposal_draft\FallbackResponseProvider $fallbackClient
   *   The fallback response provider.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function __construct(
    protected FileSystemInterface $fileSystem,
    protected ExabaseClient $exabaseClient,
    protected FallbackResponseProvider $fallbackClient,
    protected Request $request,
  ) {
    parent::__construct();
    $this->logger = $this->getLogger('kamihaya_ai_loan_proposal_draft');
    $this->entityTypeManager();
    // Call config function to initialize ConfigFactory.
    $this->config('kamihaya_cms_ai_loan_proposal_draft.settings');
    $this->config = $this->configFactory->getEditable('kamihaya_cms_ai_loan_proposal_draft.settings');

    if ($this->config->get('api_error_mode')) {
      $this->exabaseClient = $this->fallbackClient;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system'),
      $container->get('kamihaya_cms_loan_proposal_api.client'),
      $container->get('kamihaya_cms_ai_loan_proposal_draft.fallback_client'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * Handle the ajax request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function handleAjaxRequest(Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    if (empty($data) || empty($data['step'])) {
      return new JsonResponse([
        'status' => 'error',
        'message' => $this->t('The request is invalid.'),
      ], 500);
    }
    $step = $data['step'];
    if ($step === 'summarize') {
      $result = $this->summarizeDodument($data);
    }

    if ($step === 'draft') {
      $result = $this->draftLoanProposal();
    }

    if ($step === 'prompt') {
      $result = $this->getPrompt($data);
    }

    if (empty($result)) {
      return new JsonResponse([
        'status' => 'error',
        'message' => $this->t('Failed handling the request.'),
      ], 500);
    }
    $status = $result['status'] === 'success' ? 200 : 500;
    return new JsonResponse($result, $status);
  }

  /**
   * Summarize the document.
   *
   * @param array $data
   *   The request data.
   * @param bool $error_mode
   *   Whether to use the error mode or not.
   *
   * @return array
   *   The result of the summarization.
   */
  private function summarizeDodument(array $data, $error_mode = FALSE) {
    $session = $this->request->getSession();

    if (empty($data['company']) || empty($data['fid'])) {
      if (!empty($session->get(self::SESSION_KEY))) {
        $session->remove(self::SESSION_KEY);
      }
      return [
        'status' => 'error',
        'message' => $this->t('The request is invalid.'),
      ];
    }

    $fid = $data['fid'];
    $company = $data['company'];
    $pdf_text = '';
    $file_name = '';
    $file = NULL;

    // Get the file.
    $file = File::load($fid);

    // Get the pdf text and the file name from the session if it is already in the session when the file is removed.
    // This meeasn this process is 're-draft'.
    $api_response = $session->get(self::SESSION_KEY);
    if (empty($file) && !empty($api_response)) {
      $file_name = !empty($api_response['file_name']) ? $api_response['file_name'] : '';
      $pdf_text = !empty($api_response['pdf_text']) ? $api_response['pdf_text'] : '';
    }

    if (!$error_mode) {
      // Remove the session key if it is already set.
      $session->remove(self::SESSION_KEY);
    }

    if (empty($file) && empty($pdf_text) && !$error_mode) {
      return [
        'status' => 'error',
        'message' => $this->t('File not found.'),
      ];
    }

    try {
      if (!empty($file)) {
        $uri = $file->getFileUri();
        $file_path = $this->fileSystem->realpath($uri);
        $file_name = substr($file->getFilename(), 0, strrpos($file->getFilename(), '.'));
        $api_response['file_name'] = $file_name;
        $session->set(self::SESSION_KEY, $api_response);
        // Call the extract endpoint.
        $result = $this->exabaseClient->extractPdf($file_path);
        if (empty($result || empty($result['pdf_text']))) {
          return [
            'status' => 'error',
            'message' => $this->t('Failed to extract the text from the PDF.'),
          ];
        }
        $pdf_text = $result['pdf_text'];
        $api_response['pdf_text'] = $pdf_text;
        $session->set(self::SESSION_KEY, $api_response);
      }
      if ($error_mode && empty($pdf_text)) {
        // Call the extract endpoint.
        $result = $this->exabaseClient->extractPdf('');
        if (empty($result || empty($result['pdf_text']))) {
          return [
            'status' => 'error',
            'message' => $this->t('Failed to extract the text from the PDF.'),
          ];
        }
        $pdf_text = $result['pdf_text'];
      }

      if (empty($pdf_text)) {
        return [
          'status' => 'error',
          'message' => $this->t('Failed to extract the text from the PDF.'),
        ];
      }

      // Get the prompt to summarize the document.
      $result = $this->exabaseClient->getPrompt();
      if (empty($result) || empty($result['pdf_summary']) || empty($result['loan_document'])) {
        return [
          'status' => 'error',
          'message' => $this->t('Failed to get the pdf summary prompt.'),
        ];
      }
      $summary_prompt = $result['pdf_summary'];
      $loan_prompt = $result['loan_document'];

      if (empty($data['summary_prompt'])) {
        $prompt = $summary_prompt;
      }
      else {
        $prompt = $data['summary_prompt'];
      }

      // Call the summarize endpoint.
      $result = $this->exabaseClient->summarizeText($prompt . PHP_EOL . $pdf_text);
      if (empty($result)) {
        return [
          'status' => 'error',
          'message' => $this->t('Failed to summarize the text from the PDF.'),
        ];
      }

      // Save the result to the session.
      $api_response = [
        'company' => $company,
        'file_name' => $file_name,
        'pdf_text' => $pdf_text,
        'pdf_summary_prompt' => $summary_prompt,
        'pdf_summary_used_prompt' => $prompt,
        'pdf_summary' => $result,
        'loan_document_prompt' => $loan_prompt,
        'loan_document_used_prompt' => empty($data['loan_prompt']) ? $loan_prompt : $data['loan_prompt'],
        'used_company_detail' => empty($data['company_detail']) ? null : $data['company_detail'],
      ];
      $session->set(self::SESSION_KEY, $api_response);

      return [
        'status' => 'success',
        'message' => $this->t('Text of the PDF summarized.'),
        'pdf_summary' => $this->formatResult($result),
        'pdf_summary_prompt' => $this->formatResult($summary_prompt),
        'pdf_summary_used_prompt' => $this->formatResult($prompt),
        'api_error_mode' => $error_mode || $this->config->get('api_error_mode'),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Exception occurred while summarizing the text from PDF: ' . $e->getMessage());
      if ($this->config->get('api_error_mode')) {
        return [
          'status' => 'error',
          'message' => $this->t('Failed to summarize the text from the PDF.'),
        ];
      }
      $this->exabaseClient = $this->fallbackClient;
      return $this->summarizeDodument($data, TRUE);
    }
    finally {
      if (!empty($file)) {
        // Delete the file.
        $file->delete();
      }
    }
  }

  /**
   * Draft the loan proposal.
   *
   * @param bool $error_mode
   *   Whether to use the error mode or not.
   */
  private function draftLoanProposal($error_mode = FALSE) {
    $session = $this->request->getSession();
    $api_response = $session->get(self::SESSION_KEY);
    if (empty($api_response) || empty($api_response['company']) || empty($api_response['pdf_summary'])) {
      return [
        'status' => 'error',
        'message' => $this->t('Failed to draft the loan proposal.'),
      ];
    }
    try {
      // Get cpompany detail.
      $result = $this->exabaseClient->getCompanyDetail($api_response['company']);
      if (empty($result) || empty($result['content'])) {
        return [
          'status' => 'error',
          'message' => $this->t('Failed to get company detail.'),
        ];
      }
      $company_detail = $result['content'];
      $api_response['company_detail'] = $company_detail;
      $session->set(self::SESSION_KEY, $api_response);

      if (empty($api_response['used_company_detail'])) {
        $detail = $company_detail;
      }
      else {
        $detail = $api_response['used_company_detail'];
      }

      if (empty($api_response['loan_document_prompt'])) {
        $result = $this->exabaseClient->getPrompt();
        if (empty($result) || empty($result['loan_document'])) {
          return [
            'status' => 'error',
            'message' => $this->t('Failed to get the loan document prompt.'),
          ];
        }
        $loan_prompt = $result['loan_document'];
        $api_response['loan_document_prompt'] = $loan_prompt;
        $session->set(self::SESSION_KEY, $api_response);
      }
      $loan_prompt = $api_response['loan_document_prompt'];

      if (empty($api_response['loan_document_used_prompt'])) {
        $prompt = $loan_prompt;
      }
      else {
        $prompt = $api_response['loan_document_used_prompt'];
      }

      // Call the draft loan proposal endpoint.
      $result = $this->exabaseClient->makeLoanProposal($prompt, $api_response['pdf_summary'], $detail);
      if (empty($result) || empty($result['loan_summary'])) {
        $session->remove(self::SESSION_KEY);
        return [
          'status' => 'error',
          'message' => $this->t('Failed to draft the loan proposal.'),
        ];
      }

      $this->saveLoanProposal($result, $detail);
      return [
        'status' => 'success',
        'message' => $this->t('Loan proposal drafted.'),
        'loan_document_prompt' => $this->formatResult($loan_prompt),
        'loan_document_used_prompt' => $this->formatResult($prompt),
        'company_detail' => $this->formatResult($company_detail),
        'used_company_detail' => $this->formatResult($detail),
        'loan_summary' => $this->formatResult($result['loan_summary']),
        'api_error_mode' => $error_mode || $this->config->get('api_error_mode'),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Exception occurred while drafting the loan proposal: ' . $e->getMessage());
      if ($this->config->get('api_error_mode')) {
        $session->remove(self::SESSION_KEY);
        return [
          'status' => 'error',
          'message' => $this->t('Failed to draft the loan proposal.'),
        ];
      }
      $this->exabaseClient = $this->fallbackClient;
      return $this->draftLoanProposal(TRUE);
    }
  }

  /**
   * Get the prompt and the company detail to summarize the document.
   *
   * @param array $data
   *   The request data.
   * @param bool $error_mode
   *   Whether to use the error mode or not.
   *
   * @return array
   *   The prompt and the company detail to summarize the document.
   */
  private function getPrompt(array $data, $error_mode = FALSE) {
    if (empty($data) || empty($data['company'])) {
      return [
        'status' => 'error',
        'message' => $this->t('The request is invalid.'),
      ];
    }

    try {
      $response = [];
      // Get the prompt.
      $result = $this->exabaseClient->getPrompt();
      if (empty($result) || empty($result['pdf_summary']) || empty($result['loan_document'])) {
        return [
          'status' => 'error',
          'message' => $this->t('Failed to get the prompt.'),
        ];
      }
      $response['pdf_summary_prompt'] = $result['pdf_summary'];
      $response['loan_document_prompt'] = $result['loan_document'];

      // Get cpompany detail.
      $result = $this->exabaseClient->getCompanyDetail($data['company']);
      if (empty($result) || empty($result['content'])) {
        return [
          'status' => 'error',
          'message' => $this->t('Failed to get company detail.'),
        ];
      }

      $response['status'] = 'success';
      $response['message'] = $this->t('Prompt and company detail retrieved.');
      $response['company_detail'] = $result['content'];
      $response['api_error_mode'] = $error_mode || $this->config->get('api_error_mode');

      return $response;
    }
    catch (\Exception $e) {
      $this->logger->error('Exception occurred while getting the prompt: ' . $e->getMessage());
      if ($this->config->get('api_error_mode')) {
        return [
          'status' => 'error',
          'message' => $this->t('Failed to get the prompt.'),
        ];
      }
      $this->exabaseClient = $this->fallbackClient;
      return $this->getPrompt($data, TRUE);
    }
  }

  /**
   * Save summary details in a entity.
   *
   * @param $result
   * @param $company_detail
   * @return void
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function saveLoanProposal($result, $company_detail) {
    $session = $this->request->getSession();
    $api_response = $session->get(self::SESSION_KEY);
    $node = $this->entityTypeManager->getStorage('node')->create([
      'type' => 'loan_proposal',
      'title' => 'Loan Proposal: ' . $this->currentUser()->getEmail() . '-' .time() ,
      'field_company_name' => $api_response['company'],
      'body' => [
        'value' => $company_detail,
        'format' => 'full_html',
      ],
      'field_pdf_summary' => [
        'value' => $api_response['pdf_summary'],
        'format' => 'full_html',
      ],
      'field_pdf_prompt' => [
        'value' => $api_response['pdf_summary_used_prompt'],
        'format' => 'full_html',
      ],
      'field_loan_document' => [
        'value' => $api_response['loan_document_used_prompt'],
        'format' => 'full_html',
      ],
      'field_loan_summary' => [
        'value' => $result['loan_summary'],
        'format' => 'full_html',
      ],
    ]);
    $node->save();
  }
}
