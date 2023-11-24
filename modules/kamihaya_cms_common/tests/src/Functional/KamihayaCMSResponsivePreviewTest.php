<?php

namespace Drupal\Tests\kamihaya_cms_common\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;

class KamihayaCMSResponsivePreviewTest extends BrowserTestBase {

  /**
   * Disable strict config schema checks in this test.
   *
   * @var bool
   */
  // @codingStandardsIgnoreStart
  protected $strictConfigSchema = FALSE;
  // @codingStandardsIgnoreEnd

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['toolbar', 'responsive_preview', 'kamihaya_cms_common'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'gin';

  /**
   * The user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $previewUser;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->previewUser = $this->drupalCreateUser([
      'access responsive preview',
      'access toolbar',
      'administer responsive preview',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function testResponsivePreviews(): void {
     $edit = [];

    $this->drupalLogin($this->previewUser);
    $tab_xpath = '//nav[@id="toolbar-bar"]//div[contains(@class, "toolbar-tab-responsive-preview")]';
    $this->assertSession()->elementExists('xpath', $tab_xpath);

    $devices = array_keys($this->getDefaultDevices(TRUE));
    $this->assertDeviceListEquals($devices);
  }

  /**
   * Return the default devices.
   *
   * @param bool $enabled_only
   *   Whether return only devices enabled by default.
   *
   * @return array
   *   An array of the default devices.
   */
  protected function getDefaultDevices($enabled_only = FALSE) {
    $devices = [
      'galaxy_s7' => 'Galaxy S7',
      'galaxy_s9' => 'Galaxy S9',
      'galaxy_tab_s4' => 'Galaxy Tab S4',
      'galaxy_tab_2_10' => 'Galaxy Tab 2 10"',
      'ipad_pro' => 'iPad Pro',
      'ipad_air_2' => 'iPad Air 2',
      'iphone_7' => 'iPhone 7',
      'iphone_7plus' => 'iPhone 7+',
      'iphone_xs' => 'iPhone XS',
      'iphone_xs_max' => 'iPhone XS Max',
    ];

    if ($enabled_only) {
      return $devices;
    }

    $devices += [
      'large' => 'Typical desktop',
      'medium' => 'Tablet',
      'small' => 'Smart phone',
    ];

    return $devices;
  }

  /**
   * Tests exposed devices in the responsive preview list.
   *
   * @param array $devices
   *   An array of devices to check.
   */
  protected function assertDeviceListEquals(array $devices) {
    $device_buttons = $this->xpath('//button[@data-responsive-preview-name]');
    $this->assertTrue(count($devices) === count($device_buttons));

    foreach ($device_buttons as $button) {
      $name = $button->getAttribute('data-responsive-preview-name');
      $this->assertTrue(!empty($name) && in_array($name, $devices), new FormattableMarkup('%name device shown', ['%name' => $name]));
    }
  }

}
