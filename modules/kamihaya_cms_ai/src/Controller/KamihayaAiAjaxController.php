<?php

namespace Drupal\kamihaya_cms_ai\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for handling the ajax request.
 */
class KamihayaAiAjaxController extends ControllerBase {

  /**
   * The logger channel factory service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new KamihayaAiAjaxController object.
   */
  public function __construct() {
    $this->logger = $this->getLogger('kamihaya_cms_ai');
  }

  /**
   * Handle the ajax request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function handleAjaxRequest(Request $request) {
    return new JsonResponse([
      'message' => '',
      'result' => '',
    ]);
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
    $result = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $result);

    // h3 section title.
    $result = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $result);

    // h4 section title.
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
  protected function convertToTable(array $table_value) {
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
