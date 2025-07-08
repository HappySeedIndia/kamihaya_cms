<?php

namespace Drupal\kamihaya_cms_google_map\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides dynamic routes based on configuration.
 */
class RouteProvider implements ContainerInjectionInterface {

  /**
   * Constructs a new RouteProvider object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   */
  public function __construct(protected ConfigFactoryInterface $configFactory) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Provides the routes for this module.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $routes = [];
    $config = $this->configFactory->get('kamihaya_cms_google_map.google_map_with_view.settings');

    // Get custom paths from the configuration.
    $pages = $config->get('pages') ?: [];

    foreach ($pages as $key => $page) {
      if (!empty($page['url_path'])) {
        $routes["kamihaya_cms_google_map.custom_page_{$key}"] = new Route(
          $page['url_path'],
          [
            '_controller' => '\Drupal\kamihaya_cms_google_map\Controller\GoogleMapWithViewController::displayPage',
            '_title' => $page['page_title'] ?? 'Google map with View',
            'config_name' => $key,
          ],
          [
            '_permission' => 'access content',
          ]
        );
      }
    }

    return $routes;
  }
}
