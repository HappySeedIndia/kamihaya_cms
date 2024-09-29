<?php

namespace Drupal\kamihaya_cms_recruitment\Traits;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;

/**
 * The assertion trait for common usage.
 */
trait KamihayaUserTrait {

  /**
   * Alter user form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  protected function alterForm(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    if (!empty($form['account']['status'])) {
      $form['account']['status']['#access'] = FALSE;
    }
    if (!empty($form['account']['notify'])) {
      $form['account']['notify']['#access'] = FALSE;
    }
    if (!empty($form['account']['roles'])) {
      $options = &$form['account']['roles']['#options'];
      foreach ($options as $key => $value) {
        if ($key === 'applicant' || $key === 'recruiter') {
          continue;
        }
        unset($options[$key]);
      }
      $form['account']['roles']['#type'] = 'radios';
      $form['account']['roles']['#required'] = TRUE;
      if (!$this->entity->isNew()) {
        $roles = $this->entity->getRoles();
        $role = in_array('applicant', $roles) ? 'applicant' : (in_array('recruiter', $roles) ? 'recruiter' : '');
        $form['account']['roles']['#default_value'] = $role;
      }
    }
    return $form;
  }

  /**
   * Alter validate user form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\user\UserInterface $user
   *   The current user.
   */
  protected function alterSubmitForm(array &$form, FormStateInterface $form_state, ?UserInterface $user = NULL) {
    if (!empty($user) && $user->hasRole(('administrator'))) {
      $account = $this->entity;
      $roles = $account->getRoles();
      $roles[] = 'administrator';
      $account->set('roles', $roles);
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * Alter validate user form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\user\UserInterface $user
   *   The current user.
   */
  protected function alterBuildEntity(array &$form, FormStateInterface $form_state, ?UserInterface $user = NULL) {
    $roles = ['authenticated'];
    if (is_string($form_state->getValue('roles'))) {
      $roles[] = $form_state->getValue('roles');
      if (!empty($user) && $user->hasRole(('administrator'))) {
        $roles[] = 'administrator';
      }
      $form_state->setValue('roles', $roles);
    }
    return parent::buildEntity($form, $form_state);
  }

}
