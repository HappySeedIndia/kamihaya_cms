<?php

namespace Drupal\kamihaya_cms_recruitment\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\kamihaya_cms_recruitment\Traits\KamihayaUserTrait;
use Drupal\user\RegisterForm;

/**
 * Form handler for the user register forms.
 *
 * @internal
 */
class KamihayaUserRegisterForm extends RegisterForm {

  use KamihayaUserTrait;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    return $this->alterForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->alterSubmitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    return $this->alterBuildEntity($form, $form_state);
  }

}
