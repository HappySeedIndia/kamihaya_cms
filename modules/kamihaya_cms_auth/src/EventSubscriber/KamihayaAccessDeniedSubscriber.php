<?php

namespace Drupal\kamihaya_cms_auth\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class KamihayaAccessDeniedSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected AccountInterface $account,
    protected ConfigFactoryInterface $configFactory,
    protected RouteMatchInterface $routeMatch,
    protected AliasManagerInterface $aliasManager,
    protected LanguageManagerInterface $languageManager) {
  }

  public static function getSubscribedEvents() {
    // Set higher priority than core's AccessDeniedSubscriber (priority 75).
    $events[KernelEvents::EXCEPTION][] = ['onException', 100];
    return $events;
  }

  public function onException(ExceptionEvent $event) {
    $exception = $event->getThrowable();
    if (!$exception instanceof AccessDeniedHttpException) {
      return;
    }
    $route_name = $this->routeMatch->getRouteName();

    // For anonymous users accessing user.page, show 403 error page.
    if ($route_name === 'user.page' && !$this->account->isAuthenticated()) {
      // Stop event propagation to prevent core's redirect behavior.
      $event->stopPropagation();

      // Get the configured 403 page path.
      $config = $this->configFactory->get('system.site');
      $page_404 = $config->get('page.404');

      if ($page_404) {
        // Get current language.
        $current_language = $this->languageManager->getCurrentLanguage();
        $langcode = $current_language->getId();

        // Convert system path to alias if exists.
        $alias = $this->aliasManager->getAliasByPath($page_404, $langcode);

        // Add language prefix if multilingual site with path prefix.
        if ($this->languageManager->isMultilingual()) {
          $default_language = $this->languageManager->getDefaultLanguage();

          // Only add prefix if not default language or if prefix is configured for default.
          $negotiation_config = $this->configFactory->get('language.negotiation');
          $prefixes = $negotiation_config->get('url.prefixes');

          if ($langcode !== $default_language->getId() || !empty($prefixes[$langcode])) {
            if (!empty($prefixes[$langcode])) {
              $alias = '/' . $prefixes[$langcode] . $alias;
            }
          }
        }

        // Redirect to the alias (or system path if no alias exists).
        $response = new RedirectResponse($alias, 303);
        $event->setResponse($response);
      }
      else {
        $redirect_url = Url::fromRoute('system.404', [], ['absolute' => TRUE]);
        // Redirect to a default system 403 route if available.
        $response = new RedirectResponse($redirect_url->toString(), 303);
      }

      $event->setResponse($response);
    }

  }

}
