<?php

namespace Drupal\kamihaya_cms_deel\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\kamihaya_cms_deel\DeelApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Kamihaya CMS Deel routes.
 */
class Managers extends ControllerBase {

  /**
   * The kamihaya_cms_deel.client service.
   */
  protected DeelApi $client;

  /**
   * The controller constructor.
   *
   * @param \Drupal\kamihaya_cms_deel\DeelApi $client
   *   The kamihaya_cms_deel.client service.
   */
  public function __construct(DeelApi $client) {
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('kamihaya_cms_deel.client')
    );
  }

  /**
   * Display the list of managers.
   *
   * @return array
   *   Returns a render array.
   */
  public function build(): array {

    $header = [
      'id' => $this->t('ID'),
      'first_name' => $this->t('First Name'),
      'last_name' => $this->t('Last Name'),
      'email' => $this->t('Email'),
    ];
    $rows = [];
    $managers = $this->client->getManagers();

    if (!empty($managers) && isset($managers['data'])) {
      foreach ($managers['data'] as $item => $manager) {
        $rows[$item]['id'] = $manager['id'];
        $rows[$item]['first_name'] = $manager['first_name'];
        $rows[$item]['last_name'] = $manager['last_name'];
        $rows[$item]['email'] = $manager['email'];
      }
    }
    $build['managers'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No records found.'),
    ];

    return $build;
  }

}
