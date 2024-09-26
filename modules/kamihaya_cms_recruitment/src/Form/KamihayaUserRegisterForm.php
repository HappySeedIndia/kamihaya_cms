<?php

namespace Drupal\kamihaya_cms_recruitment\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RegisterForm;

/**
 * Form handler for the user register forms.
 *
 * @internal
 */
class KamihayaUserRegisterForm extends RegisterForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
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
    }
    return $form;
  }

}
