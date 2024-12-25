<?php

namespace Drupal\kamihaya_cms_views_extension\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\views\Views;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Cancel a user account.
 */
#[Action(
  id: 'vbo_redirect_to_view_action',
  label: new TranslatableMarkup('Redirect to another view.'),
  type: 'node'
)]
class RedirectToViewAction extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface {

  /**
   * Object constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin Id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\views_bulk_operations\Plugin\Action\Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected readonly AccountInterface $currentUser,
    protected readonly ModuleHandlerInterface $moduleHandler,
    ConfigFactoryInterface $config_factory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state) {
    $view_display = $this->view->storage->id() . ':' . $this->view->current_display;
    $options = ['' => $this->t('-Select-')];
    $options += Views::getViewsAsOptions(FALSE, 'all', $view_display, FALSE, TRUE);

    $form['redirect_view'] = [
      '#type' => 'select',
      '#title' => $this->t('Redirect view'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $values['redirect_view'],
      '#description' => $this->t('Select the view to redirect to.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $this->executeMultiple([$entity]);
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $names = [];
    foreach ($entities as $entity) {
      $names[] = $entity->label();
    }

    $param_string = implode('+', $names);

    $view = str_replace(':', '.', $this->configuration['redirect_view']);

    $url = Url::fromRoute("view.$view", [
      'arg_0' => $param_string,
    ]);

    $response = new RedirectResponse($url->toString());
    $response->send();

    return [];
  }
  /**
   * {@inheritdoc}
   */
  public function access($object, $account = NULL, $context = NULL) {
    return $object->access('view', $account, TRUE);
  }

}
