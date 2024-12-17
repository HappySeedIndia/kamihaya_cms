<?php

namespace Drupal\kamihaya_cms_views_extension\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\kamihaya_cms_views_extension\Trait\KamihayaTaxonomyViewsFilterTrait;
use Drupal\shs\Plugin\views\filter\ShsTaxonomyIndexTidDepth;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\taxonomy\VocabularyStorageInterface;
use Drupal\views\Attribute\ViewsFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Filter handler for taxonomy terms with depth.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("kamihaya_taxonomy_index_tid_depth")]
class KamihayaTaxonomyIndexTidDepth extends ShsTaxonomyIndexTidDepth {

  use KamihayaTaxonomyViewsFilterTrait;

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructor for the class.
   *
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   The Plugin id.
   * @param mixed $plugin_definition
   *   The Plugin definition.
   * @param \Drupal\taxonomy\VocabularyStorageInterface $vocabulary_storage
   *   The vocabulary storage.
   * @param \Drupal\taxonomy\TermStorageInterface $term_storage
   *   The term storage.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, VocabularyStorageInterface $vocabulary_storage, TermStorageInterface $term_storage, Connection $database, Request $request, EntityFieldManagerInterface $entity_field_manager, RouteMatchInterface $route_match, ?AccountInterface $current_user = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $vocabulary_storage, $term_storage, $database, $current_user);
    $this->request = $request;
    $this->entityFieldManager = $entity_field_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('taxonomy_vocabulary'),
      $container->get('entity_type.manager')->getStorage('taxonomy_term'),
      $container->get('database'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('entity_field.manager'),
      $container->get('current_route_match'),
      $container->get('current_user')
    );
  }

}
