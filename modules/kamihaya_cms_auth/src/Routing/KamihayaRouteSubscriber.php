<?php

namespace Drupal\kamihaya_cms_auth\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class KamihayaRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    if ($route = $collection->get('user.login')) {
      $route->setPath('/kanrisha/login');
    }

    if ($route = $collection->get('user.page')) {
      $requirements = [
        '_permission' => 'access user profiles',
      ];
      // Change the requirements on user page.
      $route->setRequirements($requirements);
    }
  }

}
