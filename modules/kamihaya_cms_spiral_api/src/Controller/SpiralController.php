<?php

namespace Drupal\kamihaya_cms_spiral_api\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\kamihaya_cms_spiral_api\Config\AppConfig;
use Drupal\kamihaya_cms_spiral_api\Service\SpiralApi;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * The base class for SPIRAL controller.
 */
class SpiralController {

  use StringTranslationTrait;

  // SPIRAL session key.
  const SPIRAL_API_SESSION_LOGIN_DATA = "login_data";

  // User status.
  // Temporary.
  const USER_STATUS_KARI = "1";
  // Requested.
  const USER_STATUS_CHECK = "2";
  // Approved.
  const USER_STATUS_OK = "3";
  // Not approved.
  const USER_STATUS_DENY = "4";
  // Withdraw.
  const USER_STATUS_DEL = "5";

  // Password reminder limit.
  const PASS_REMINDER_LIMIT_TIME = "3";

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The session manager.
   *
   * @var \Drupal\Core\Session\SessionManager
   */
  protected $sessionManager;

  /**
   * The site configuration.
   *
   * @var array
   */
  protected $siteConfig;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected $session;

  /**
   * The view data.
   *
   * @var array
   */
  protected $viewData = [];

  /**
   * The SPIRAL API client.
   *
   * @var \Drupal\kamihaya_cms_spiral_api\Service\SpiralApi
   */
  protected $spiralApi;

  /**
   * The constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Session\SessionManager $session_manager
   *   The session manager.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\kamihaya_cms_spiral_api\Service\SpiralApi $spiral_api
   *   The SPIRAL API client.
   */
  public function __construct(
    LoggerInterface $logger,
    ConfigFactoryInterface $config_factory,
    Messenger $messenger,
    TranslationInterface $string_translation,
    AccountInterface $current_user,
    SessionManager $session_manager,
    SessionInterface $session,
    EventDispatcherInterface $event_dispatcher,
    SpiralApi $spiral_api) {
    // Setup the logger.
    $this->logger = $logger;
    // Setup the configuration factory.
    $this->config = $config_factory->get('kamihaya_cms_spiral_api.settings');
    // Setup the messenger.
    $this->messenger = $messenger;
    // Setup the $this->t()
    $this->stringTranslation = $string_translation;
    $this->currentUser = $current_user;
    $this->sessionManager = $session_manager;
    $this->session = $session;
    $this->eventDispatcher = $event_dispatcher;
    $this->spiralApi = $spiral_api;
  }

  /**
   * Create the rancdom number.
   *
   * @param int $length
   *   The lengh.
   */
  public function makeRandStr($length) {
    $str = array_merge(range('a', 'z'), range('0', '9'), range('A', 'Z'));
    $r_str = NULL;
    for ($i = 0; $i < $length; $i++) {
      $r_str .= $str[mt_rand(0, count($str) - 1)];
    }
    return $r_str;
  }

  /**
   * Redirect to the route.
   *
   * @param string $route
   *   The route name.
   */
  protected function redirectRoute($route = 'user.login') {
    $url = Url::fromRoute($route);
    $redirect_response = new RedirectResponse($url->toString());
    $redirect_response->send();
  }

  /**
   * Redirect to the url.
   *
   * @param string $url
   *   The url.
   */
  protected function redirectUrl($url) {
    $redirect_response = new RedirectResponse($url);
    $redirect_response->send();
  }

}
