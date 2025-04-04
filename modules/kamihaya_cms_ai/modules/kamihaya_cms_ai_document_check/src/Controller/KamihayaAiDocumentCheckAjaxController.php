<?php

namespace Drupal\kamihaya_cms_ai_document_check\Controller;

use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\kamihaya_cms_ai\Controller\KamihayaAiAjaxController;
use Drupal\kamihaya_cms_exabase_api\ExabaseClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for handling the ajax request.
 */
class KamihayaAiDocumentCheckAjaxController extends KamihayaAiAjaxController {

  const SESSION_KEY = 'kamihaya_ai_document_check';

  /**
   * Constructs a new KamihayaAiDocumentCheckAjaxController object.
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
    $this->logger = $this->getLogger('kamihaya_cms_ai_document_check');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('file_system'),
    $container->get('kamihaya_cms_exabase_api.client'),
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

    if ($step === 'copyright_check') {
      $result = $this->checkCopyright();
    }

    if ($step === 'company_check') {
      $result = $this->checkCompanyRule();
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

    if (empty($data['fid'])) {
      return [
        'status' => 'error',
        'message' => $this->t('The request is invalid.'),
      ];
    }
    $fid = $data['fid'];
    if (!$fid) {
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
      $result = $this->exabaseClient->postExtract($file_path);
      if (empty($result || empty($result['spec_summary']))) {
        return [
          'status' => 'error',
          'message' => $this->t('Failed to summarize the document.'),
        ];
      }

      $file_name = substr($file->getFilename(), 0, strrpos($file->getFilename(), '.'));
      // Save the result to the session.
      $api_response = [
        'spec_summary' => $result['spec_summary'],
        'file_name' => $file_name,
      ];
      $session->set(self::SESSION_KEY, $api_response);

      $file_name = '<div class="file-name">' . $file_name . '</div>';
      return [
        'status' => 'success',
        'message' => $this->t('Document summarized.'),
        'spec_summary' => $file_name . $this->formatResult($result['spec_summary']),
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
   * Check the document for copyright.
   */
  private function checkCopyright() {
    $session = $this->request->getSession();
    $api_response = $session->get(self::SESSION_KEY);
    if (empty($api_response) || empty($api_response['spec_summary'])) {
      return [
        'status' => 'error',
        'message' => $this->t('Failed to check copyright.'),
      ];
    }
    try {
      // Call the check endpoint.
      $result = $this->exabaseClient->postCheck($api_response['spec_summary']);
      if (empty($result) || empty($result['checkresult'])) {
        $session->remove(self::SESSION_KEY);
        return [
          'status' => 'error',
          'message' => $this->t('Failed to check copyright.'),
        ];
      }

      $api_response['checkresult'] = $result['checkresult'];
      $session->set(self::SESSION_KEY, $api_response);

      $file_name = '<div class="file-name">' . $api_response['file_name'] . '</div>';
      return [
        'status' => 'success',
        'message' => $this->t('Copyright check completed.'),
        'checkresult' => $file_name . $this->formatResult($result['checkresult']),
      ];
    }
    catch (\Exception $e) {
      $session->remove(self::SESSION_KEY);
      $this->logger->error('Exception occurred while chacking copyright of the document: ' . $e->getMessage());
      return [
        'status' => 'error',
        'message' => $this->t('Failed to check copyright.'),
      ];
    }
  }

  /**
   * Check the document for company rule.
   */
  private function checkCompanyRule() {
    $session = $this->request->getSession();
    $api_response = $session->get(self::SESSION_KEY);
    if (empty($api_response) || empty($api_response['spec_summary']) || empty($api_response['checkresult'])) {
      return [
        'status' => 'error',
        'message' => $this->t('Failed to check company rule.'),
      ];
    }

    try {
      // Call the check endpoint.
      $result = $this->exabaseClient->postReCheck($api_response['spec_summary'], $api_response['checkresult']);
      $file_name = '<div class="file-name">' . $api_response['file_name'] . '</div>';

      $session->remove(self::SESSION_KEY);
      if (empty($result) || empty($result['recheckresult'])) {
        return [
          'status' => 'error',
          'message' => $this->t('Failed to check company rule.'),
        ];
      }
      return [
        'status' => 'success',
        'message' => $this->t('Company rule check completed.'),
        'recheckresult' => $file_name . $this->formatResult($result['recheckresult']),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Exception occurred while chacking company rule of the document: ' . $e->getMessage());
      return [
        'status' => 'error',
        'message' => $this->t('Failed to check company rule.'),
      ];
    }
    finally {
      $session->remove(self::SESSION_KEY);
    }
  }

  /**
   * Format the result.
   *
   * @param string $result
   * @return string
   *   The formatted result.
   */
  protected function formatResult($result) {
    $result = $this->convertToHtml($result);
    $result_array = explode(PHP_EOL, $result);
    if (empty($result_array) || count($result_array) === 1) {
      return $result;
    }
    $formated_result = '';
    for ($idx = 0; $idx < count($result_array); $idx++) {
      // Skip empty lines.
      if (empty(trim($result_array[$idx]))) {
        continue;
      }

      // Add the line if the value only contains '-' or ' '.
      $stripped_value = str_replace(['-', ' '], '', $result_array[$idx]);
      if (empty($stripped_value)) {
        $formated_result .= '<hr>';
        continue;
      }

      // Skip lines that only contain '-', '|' or ' '.
      $stripped_value = str_replace(['-', '|', ' '], '', $result_array[$idx]);
      if (empty($stripped_value)) {
        continue;
      }

      // Check if the line contains a list.
      if (strpos($result_array[$idx], '- ') === 0 || strpos($result_array[$idx], '  - ') === 0 || strpos($result_array[$idx], '   - ') === 0) {
        $sub_list = FALSE;
        $list = FALSE;
        $formated_result .= '<ul>';
        while (!empty($result_array[$idx]) && (strpos($result_array[$idx], '- ') === 0 || strpos($result_array[$idx], '  - ') === 0 || strpos($result_array[$idx], '   - ') === 0)) {
          if (strpos($result_array[$idx], '- ') === 0) {
            $list = TRUE;
          }
          if (strpos($result_array[$idx], '- ') === 0 && $sub_list) {
            $formated_result .= '</ul>';
            $sub_list = FALSE;
          }
          if ((strpos($result_array[$idx], '  - ') === 0 || strpos($result_array[$idx], '   - ') === 0) && !$sub_list) {
            if ($list) {
              $formated_result .= '<ul>';
            }
            $sub_list = TRUE;
          }
          $formated_result .= '<li>' . str_replace('- ', '', $result_array[$idx]) . '</li>';
          $idx++;
        }
        if ($list && $sub_list) {
          $formated_result .= '</ul>';
        }
        $formated_result .= '</ul>';
        $idx--;
        continue;
      }

      if (strpos($result_array[$idx], '|') === FALSE) {
        // Check if the line contains a header tag.
        $p_flg = strpos($result_array[$idx], '<h3>') === FALSE && strpos($result_array[$idx], '<h4>') === FALSE;
        // Replace spaces with non-breaking spaces.
        $result_array[$idx] = str_replace(' ', '&nbsp;', $result_array[$idx]);
        $formated_result .= $p_flg ? "<p>{$result_array[$idx]}</p>" : $result_array[$idx];
        continue;
      }

      // Check if the line contains a table.
      $table_value = [];
      while (strpos($result_array[$idx], '|') !== FALSE) {
        $table_value[] = $result_array[$idx];
        $idx++;
      }
      if (!empty($table_value)) {
        $table_end = $idx;
        $formated_result .= $this->convertToTable($table_value);
        $idx--;
      }
    }
    return $formated_result;
  }

  /**
   * Convert the result to HTML.
   *
   * @param string $result
   *   The result to be converted.
   * @return string
   *   The result in HTML format.
   */
  protected function convertToHtml($result) {
    // Bold text.
    $result = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $result);

    // h2 section title.
    $result = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $result);

    // h3 section title.
    $result = preg_replace('/^#### (.+)$/m', '<h4>$1</h4>', $result);

    // HR tag.
    $result = preg_replace('/^---$/m', '<hr>', $result);

    return $result;
  }

  /**
   * Convert the result to table.
   *
   * @param array $table_value
   *   The table value.
   * @return string
   *   The result in table format.
   */
  private function convertToTable(array $table_value) {
    if (empty($table_value)) {
      return '';
    }
    $table = '<table class="table table-bordered result-table my-4">';
    $heads = explode('|', $table_value[0]);
    if (!empty($heads)) {
      $table .= '<thead><tr>';
      foreach ($heads as $head) {
        if (empty($head)) {
          continue;
        }
        $table .= '<th>' . $head . '</th>';
      }
      $table .= '</tr></thead>';
    }
    $table .= '<tbody>';
    for ($i = 1; $i < count($table_value); $i++) {
      if (strpos($table_value[$i], '|') === FALSE) {
        break;
      }
      $stripped_value = str_replace(['-', '|', ' '], '', $table_value[$i]);
      if (empty($stripped_value)) {
        continue;
      }

      $row = explode('|', $table_value[$i]);
      $table .= '<tr>';
      foreach ($row as $cell) {
        if (empty($cell)) {
          continue;
        }
        $table .= '<td>' . $cell . '</td>';
      }
      $table .= '</tr>';
    }
    $table .= '</tbody></table>';
    return $table;
  }

}
