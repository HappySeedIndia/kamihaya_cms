<?php

namespace Drupal\kamihaya_cms_deel\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\kamihaya_cms_deel\DeelApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Kamihaya CMS Deel routes.
 */
class Contracts extends ControllerBase {

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
      'title' => $this->t('Title'),
      'type' => $this->t('Type'),
      'status' => $this->t('Status'),
    ];
    $rows = [];
    $contracts = $this->client->getContracts();
    if (!empty($contracts) && isset($contracts['data'])) {
      foreach ($contracts['data'] as $item => $contract) {
        $rows[$item]['id'] = $contract['id'];
        $rows[$item]['title'] = $contract['title'];
        $rows[$item]['type'] = $contract['type'];
        $rows[$item]['status'] = $contract['status'];
      }
    }
    $build['contracts'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No records found.'),
    ];

    return $build;
  }

}
