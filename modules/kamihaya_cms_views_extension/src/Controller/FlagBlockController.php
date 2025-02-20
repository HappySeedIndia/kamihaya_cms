<?php

namespace Drupal\kamihaya_cms_views_extension\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides AJAX callback for refreshing the favorite count block.
 */
class FlagBlockController extends ControllerBase {

  /**
   * Constructs a ViewsAjaxController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(protected RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }


  /**
   * Returns the updated Views block.
   */
  public function refreshFlagBlock(Request $request) {
    $view_id = $request->query->get('view_id');
    $display_id = $request->query->get('display_id');

    if ($view_id && $display_id) {
      $view = Views::getView($view_id);
      if ($view) {
        $view->setDisplay($display_id);
        $view->execute();

        // Dependency Injection を使用して View をレンダリング
        $rendered_view = $this->renderer->render($view->buildRenderable($display_id));

        return new JsonResponse(['view' => $rendered_view]);
      }
    }

    return new JsonResponse(['view' => '']);
  }

}
