<?php

namespace Drupal\kamihaya_cms_commerce;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a breadcrumb builder for Commerce Products.
 */
class CommerceProductBreadCrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The entity repository manager.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the TermBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getRouteName() == 'entity.commerce_product.canonical'
      && $route_match->getParameter('commerce_product') instanceof ProductInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();

    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
    $product = $route_match->getParameter('commerce_product');
    $breadcrumb->addCacheableDependency($product);

    $term = $this->entityTypeManager->getStorage('commerce_product')->load($product->id());

    $breadcrumb->addCacheableDependency($term);
    $breadcrumb->addLink(Link::createFromRoute($term->label(), 'entity.commerce_product.canonical', ['commerce_product' => $product->id()]));
    // This breadcrumb builder is based on a route parameter, and hence it
    // depends on the 'route' cache context.
    $breadcrumb->addCacheContexts(['route']);

    return $breadcrumb;
  }

}
