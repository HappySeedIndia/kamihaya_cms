<?php

namespace Drupal\kamihaya_cms_views_extension\Plugin\views\filter;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\kamihaya_cms_views_extension\Trait\KamihayaTaxonomyViewsFilterTrait;
use Drupal\shs\Plugin\views\filter\ShsTaxonomyIndexTid;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\taxonomy\VocabularyStorageInterface;
use Drupal\views\Attribute\ViewsFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Filter by term id using Simple hierarchical select widgets.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("kamihaya_taxonomy_index_tid")]
class KamihayaTaxonomyIndexTid extends ShsTaxonomyIndexTid {

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
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, VocabularyStorageInterface $vocabulary_storage, TermStorageInterface $term_storage, Request $request, EntityFieldManagerInterface $entity_field_manager, ?AccountInterface $current_user = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $vocabulary_storage, $term_storage, $current_user);
    $this->request = $request;
    $this->entityFieldManager = $entity_field_manager;
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
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('entity_field.manager'),
      $container->get('current_user')
    );
  }

}
