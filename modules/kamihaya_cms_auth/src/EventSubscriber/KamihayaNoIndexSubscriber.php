<?php

namespace Drupal\kamihaya_cms_auth\EventSubscriber;

use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds noindex directives to specific routes.
 *
 * This subscriber ensures that protected routes are excluded from
 * search engine indexing by:
 * - Setting the X-Robots-Tag HTTP header.
 * - Injecting a <meta name="robots"> tag into the HTML <head>.
 */
class KamihayaNoIndexSubscriber implements EventSubscriberInterface {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $routeMatch
   *   The current route match service.
   */
  public function __construct(protected CurrentRouteMatch $routeMatch) {
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, string>
   *   An array of event names this subscriber wants to listen to.
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::RESPONSE => 'onResponse',
    ];
  }

  /**
   * Adds noindex directives to matching routes.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The kernel response event.
   *
   * @return void
   */
  public function onResponse(ResponseEvent $event): void {

    // Only process the main HTTP request.
    if (!$event->isMainRequest()) {
      return;
    }

    $route_name = $this->routeMatch->getRouteName();

    // Ensure a valid route name is available.
    if ($route_name === null) {
      return;
    }

    // Skip if this route is not protected.
    if ($route_name !== 'kamihaya_cms_auth.general_login') {
      return;
    }

    $response = $event->getResponse();
    // Add HTTP-level directive.
    $response->headers->set('X-Robots-Tag', 'noindex, nofollow', FALSE);
    // Only modify standard HTML responses.
    if (!$response instanceof Response) {
      return;
    }

    $content = $response->getContent();
    if ($content === false) {
      return;
    }

    // Ensure HTML head exists.
    if (!str_contains($content, '</head>')) {
      return;
    }

    $meta_tag = '<meta name="robots" content="noindex, nofollow">';
    // Prevent duplicate injection.
    if (str_contains($content, $meta_tag)) {
      return;
    }

    $updated_content = preg_replace(
      '/<\/head>/i',
      $meta_tag . PHP_EOL . '</head>',
      $content,
      1
    );

    // preg_replace may return null on error.
    if ($updated_content !== null) {
      $response->setContent($updated_content);
    }
  }

}
