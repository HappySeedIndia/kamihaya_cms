<?php

namespace Drupal\kamihaya_cms_deel\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\kamihaya_cms_deel\DeelApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Kamihaya CMS Deel routes.
 */
class People extends ControllerBase {

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
   * Display the list of people.
   *
   * @return array
   *   Returns a render array.
   */
  public function build(): array {

    $header = [
      'full_name' => $this->t('Full Name'),
      'job_title' => $this->t('Job Title'),
      'country' => $this->t('Country'),
      'start_date' => $this->t('Start Date'),
    ];
    $rows = [];
    $peoples = $this->client->getPeople();

    if (!empty($peoples) && isset($peoples['data'])) {
      foreach ($peoples['data'] as $item => $people) {
        $rows[$item]['full_name'] = $people['full_name'];
        $rows[$item]['job_title'] = $people['job_title'];
        $rows[$item]['country'] = $people['country'];
        $rows[$item]['start_date'] = $people['start_date'];
      }
    }
    $build['peoples'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No records found.'),
    ];

    return $build;
  }

}
