<?php

namespace Drupal\kamihaya_cms_google_map\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Url;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for Google Map with View pages.
 */
class GoogleMapWithViewController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Constructs a new GoogleMapWithViewController.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   *   The block manager.
   */
  public function __construct(
    protected BlockManagerInterface $blockManager) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block')
    );
  }

  /**
   * Display Google Map with View page.
   *
   * @param string $config_name
   *   The configuration name.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return array
   *   The render array.
   */
  public function displayPage($config_name, Request $request) {
    $config = $this->config('kamihaya_cms_google_map.google_map_with_view.page.' . $config_name);

    if ($config->isNew()) {
      throw new NotFoundHttpException();
    }

    $settings = $config->get();

    // Display page title if set.
    if (!empty($settings['page_title'])) {
      $build['#title'] = $settings['page_title'];
    }

    // Get Google Map Block render array.
    $google_map_block = $this->getGoogleMapBlock();

    // Get View render array.
    $view_render = $this->getViewRender($settings['view_name'], $settings['view_display'] ?? 'default');

    if ($google_map_block && $view_render) {
      $ajax_path = Url::fromRoute('kamihaya_cms_google_map.ajax_update')->toString();
      $build = [
        '#theme' => 'google_map_with_view',
        '#google_map' => $google_map_block,
        '#view_content' => $view_render,
        '#pc_position' => $settings['pc_position'] ?? 'top',
        '#sp_position' => $settings['sp_position'] ?? 'top',
        '#show_autocomplete' => $settings['show_autocomplete'] ?? FALSE,
        '#autocomplete_position' => $settings['autocomplete_position'] ?? 'top',
        '#autocomplete_label' => $settings['autocomplete_label'] ?? $this->t('Address'),
        '#autocomplete_placeholder' => $settings['autocomplete_placeholder'] ?? $this->t('Search location...'),
        '#attached' => [
          'library' => ['kamihaya_cms_google_map/google_map_with_view'],
          'drupalSettings' => [
            'page_name' => $config_name,
            'json_data_path' => $settings['json_data_path'] ?? '',
            'detail_data_path' => $settings['detail_data_path'] ?? '',
            'ajax_path' => $ajax_path ?? '',
            'show_autocomplete' => $settings['show_autocomplete'] ?? FALSE,
            'map_min_height_pc' => $settings['map_min_height_pc'] ?? '',
            'view_height_sp' => $settings['view_height_sp'] ?? '',
          ],
        ],
      ];
    }

    return $build;
  }

    /**
   * Get Google Map Block render array.
   */
  private function getGoogleMapBlock() {
    $plugin_block = $this->blockManager->createInstance('google_map_block', []);

    if ($plugin_block) {
      $render = $plugin_block->build();
      return $render;
    }

    return NULL;
  }

  /**
   * Get View render array.
   */
  private function getViewRender($view_name = NULL, $view_display = NULL) {
    $view = Views::getView($view_name);
    if ($view) {
      $display_id = $view_display ?? 'default';
      $view->setDisplay($display_id);
      $view->preExecute();
      $view->execute();
      return $view->buildRenderable($display_id);
    }
    return NULL;
  }

}
