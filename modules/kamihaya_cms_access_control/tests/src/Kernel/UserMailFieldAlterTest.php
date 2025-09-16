<?php

declare(strict_types=1);

namespace Drupal\Tests\kamihaya_cms_access_control\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the user mail field is altered to be display configurable.
 *
 * @group kamihaya_cms_access_control
 */
class UserMailFieldAlterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'kamihaya_cms_access_control',
  ];

  /**
   * Tests that the mail field is set to be display configurable.
   */
  public function testUserMailFieldIsDisplayConfigurable(): void {
    $field_definitions = \Drupal::service('entity_field.manager')
      ->getFieldDefinitions('user', 'user');

    $this->assertArrayHasKey('mail', $field_definitions, 'The user entity has a mail field.');

    $mail_field = $field_definitions['mail'];

    // Assert that the hook implementation set the "view"
    // display configurable flag.
    $this->assertTrue(
      $mail_field->isDisplayConfigurable('view'),
      'The mail field is display configurable for "view".'
    );

    // Optionally check that it is NOT configurable for "form".
    $this->assertFalse(
      $mail_field->isDisplayConfigurable('form'),
      'The mail field is not made display configurable for "form".'
    );
  }

}
