<?php

namespace Drupal\kamihaya_cms_ai_loan_proposal_draft\Controller;

use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\kamihaya_cms_ai\Controller\KamihayaAiAjaxController;
use Drupal\kamihaya_cms_loan_proposal_api\ExabaseClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for handling the ajax request.
 */
class KamihayaAiLoanProposalDraftAjaxController extends KamihayaAiAjaxController {

  const SESSION_KEY = 'kamihaya_ai_loan_proposal_draft';

  /**
   * Constructs a new KamihayaAiLoanProposalDraftAjaxController object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system.
   * @param \Drupal\kamihaya_cms_exabase_api\ExabaseClient $exabaseClient
   *   The Exabase client.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function __construct(
    protected FileSystemInterface $fileSystem,
    protected ExabaseClient $exabaseClient,
    protected Request $request,
  ) {
    $this->logger = $this->getLogger('kamihaya_ai_loan_proposal_draft');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('file_system'),
    $container->get('kamihaya_cms_loan_proposal_api.client'),
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
      $result = $this->getPrompt();
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
   *
   * @return array
   *   The result of the summarization.
   */
  private function summarizeDodument(array $data) {
    $session = $this->request->getSession();
    if (!empty($session->get(self::SESSION_KEY))) {
      $session->remove(self::SESSION_KEY);
    }

    if (empty($data['company']) || empty($data['fid'])) {
      return [
        'status' => 'error',
        'message' => $this->t('The request is invalid.'),
      ];
    }

    $fid = $data['fid'];
    $company = $data['company'];
    if (!$fid || !$company) {
      return [
        'status' => 'error',
        'message' => $this->t('The request is invalid.'),
      ];
    }
    $file = File::load($fid);
    if (!$file) {
      return [
        'status' => 'error',
        'message' => $this->t('File not found.'),
      ];
    }
    $uri = $file->getFileUri();
    $file_path = $this->fileSystem->realpath($uri);
    try {
      // Call the extract endpoint.
      $result = $this->exabaseClient->extractPdf($file_path);
      if (empty($result || empty($result['pdf_text']))) {
        return [
          'status' => 'error',
          'message' => $this->t('Failed to extract the document.'),
        ];
      }
      $pdf_text = $result['pdf_text'];

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
          'message' => $this->t('Failed to summarize the document.'),
        ];
      }

      $file_name = substr($file->getFilename(), 0, strrpos($file->getFilename(), '.'));
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
      ];
      $session->set(self::SESSION_KEY, $api_response);

      return [
        'status' => 'success',
        'message' => $this->t('Document summarized.'),
        'pdf_summary' => $this->formatResult($result),
        'pdf_summary_prompt' => $this->formatResult($summary_prompt),
        'pdf_summary_used_prompt' => $this->formatResult($prompt),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Exception occurred while summarizing the document: ' . $e->getMessage());
      return [
        'status' => 'error',
        'message' => $this->t('Failed to summarize the document.'),
      ];
    }
    finally {
      // Delete the file.
      $file->delete();
    }
  }

  /**
   * Draft the loan proposal.
   */
  private function draftLoanProposal() {
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
      $result = $this->exabaseClient->makeLoanProposal($prompt, $api_response['pdf_summary'], $company_detail);
      if (empty($result) || empty($result['loan_summary'])) {
        $session->remove(self::SESSION_KEY);
        return [
          'status' => 'error',
          'message' => $this->t('Failed to draft the loan proposal.'),
        ];
      }

      return [
        'status' => 'success',
        'message' => $this->t('Loan proposal drafted.'),
        'loan_document_prompt' => $this->formatResult($loan_prompt),
        'loan_document_used_prompt' => $this->formatResult($prompt),
        'company_detail' => $this->formatResult($company_detail),
        'loan_summary' => $this->formatResult($result['loan_summary']),
      ];
    }
    catch (\Exception $e) {
      $session->remove(self::SESSION_KEY);
      $this->logger->error('Exception occurred while drafting the loan proposal: ' . $e->getMessage());
      return [
        'status' => 'error',
        'message' => $this->t('Failed to draft the loan proposal.'),
      ];
    }
  }

  /**
   * Get the prompt to summarize the document.
   *
   * @return array
   *   The prompt to summarize the document.
   */
  private function getPrompt() {
    try {
      // Get the prompt.
      $result = $this->exabaseClient->getPrompt();
      if (empty($result) || empty($result['pdf_summary']) || empty($result['loan_document'])) {
        return [
          'status' => 'error',
          'message' => $this->t('Failed to get the prompt.'),
        ];
      }

      return [
        'status' => 'success',
        'message' => $this->t('Prompt retrieved.'),
        'pdf_summary_prompt' => $result['pdf_summary'],
        'loan_document_prompt' => $result['loan_document'],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Exception occurred while getting the prompt: ' . $e->getMessage());
      return [
        'status' => 'error',
        'message' => $this->t('Failed to get the prompt.'),
      ];
    }
  }
}
