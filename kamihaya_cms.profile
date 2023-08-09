<?php

use Drupal\cohesion\Form\AccountSettingsForm;

/**
 * Implements hook_install_tasks_alter().
 */
function kamihaya_cms_install_tasks_alter(array &$tasks) {
  // Decorate the site configuration form to allow the user to configure their
  // shield settings at install time.
  // $tasks['install_configure_form']['function'] = AccountSettingsForm::class;
}

/**
 * Implements hook_modules_installed().
 */
function kamihaya_cms_modules_installed($modules) {
  // if (\Drupal::state()
  //   ->get('system_test.verbose_module_hooks')) {
  //   foreach ($modules as $module) {
  //     drupal_set_message(t('hook_modules_installed fired for @module', array(
  //       '@module' => $module,
  //     )));
  //   }
  // }
  
  // TODO: Import default content
  // Due to an error while importing default content, move it inside the hooks.
  // \Drupal::service('module_installer')->install(['kamihaya_cms_default_content']);
}
