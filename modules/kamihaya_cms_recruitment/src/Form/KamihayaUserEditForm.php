<?php

namespace Drupal\kamihaya_cms_recruitment\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\kamihaya_cms_recruitment\Traits\KamihayaUserTrait;
use Drupal\user\Entity\User;
use Drupal\user\ProfileForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the user edit forms.
 *
 * @internal
 */
class KamihayaUserEditForm extends ProfileForm {

  use KamihayaUserTrait;

  /**
   * The current user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $currentUser;

  /**
   * Constructs a new EntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    LanguageManagerInterface $language_manager,
    ?EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL,
    ?TimeInterface $time = NULL,
    ?AccountProxyInterface $current_user = NULL,
  ) {
    parent::__construct($entity_repository, $language_manager, $entity_type_bundle_info, $time);
    $this->currentUser = User::load($current_user->id());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('language_manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $this->setEntity($this->currentUser);
    $form = $this->alterForm($form, $form_state);

    if (!empty($form['account']['roles'])) {
      $form['account']['roles']['#access'] = FALSE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->alterSubmitForm($form, $form_state, $this->currentUser);
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    return $this->alterBuildEntity($form, $form_state, $this->currentUser);
  }

}
