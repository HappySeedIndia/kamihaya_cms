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

}
