<?php

namespace Drupal\kamihaya_cms_google_map\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\views\Views;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for Ajax location filtering.
 */
class LocationFilterController extends ControllerBase implements ContainerInjectionInterface{

  /**
   * Ajax callback to update view with location filter.
   */
  public function updateLocationView(Request $request) {
    $route_key = $request->query->get('route_key');
    if (empty($route_key)) {
      throw new NotFoundHttpException();
    }
    $config = $this->config("kamihaya_cms_google_map.google_map_with_view.page.{$route_key}");
    $view_name = $config->get('view_name');
    $view_display = $config->get('view_display') ?? 'default';
    if (empty($view_name)) {
      throw new NotFoundHttpException();
    }
    $response = new AjaxResponse();
    $wrapper_id = 'location-view';

    // Load and execute the view
    $view = Views::getView($view_name);
    if (!$view) {
      $response->addCommand(new ReplaceCommand("#{$wrapper_id}", '<div class="error">View not found</div>'));
      return $response;
    }

    $view->setDisplay($view_display); // Adjust display ID as needed

    // The coordinates will be picked up by hook_query_location_filter_alter
    // No need to set exposed input since we're not using exposed filters

    // Execute the view
    $view->execute();

    // Render the view
    $rendered_view = $view->render();

    // Add the replace command
    $response->addCommand(new ReplaceCommand("#{$wrapper_id}", $rendered_view));

    return $response;
  }
}
