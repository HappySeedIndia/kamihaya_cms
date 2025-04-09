<?php

namespace Drupal\kamihaya_cms_ai\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process form base.
 */
class ProcessFormBase extends FormBase {

  public function __construct(protected AccountProxyInterface $currentUser) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'process_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user = User::load($this->currentUser->id());
    $name = $user->hasField('field_name') && !empty($user->get('field_name')->value) ? $user->get('field_name')->value : $user->getAccountName();

    $form['welcome'] = [
      '#type' => 'markup',
      '#markup' => "<p class='welcome'>" . $this->t("Hello @name!", ['@name' => $name]) . '</p>',
    ];

    $form['message'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->getMessage() . '</p>',
    ];

    $form['actions'] = [
      '#type' => 'details',
      '#title' => $this->t('Actions'),
      '#open' => TRUE,
    ];


    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['create'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create'),
      '#attributes' => ['id' => 'create-new-process'],
    ];

    $form['actions']['continue_process'] = [
      '#type' => 'submit',
      '#value' => $this->t('Resume'),
      '#attributes' => [
        'id' => 'continue-process',
        'class' => ['btn-secondary'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

  /**
   * Get the message.
   *
   * @return string
   *   The message.
   */
  protected function getMessage() {
    return '';
  }

}
