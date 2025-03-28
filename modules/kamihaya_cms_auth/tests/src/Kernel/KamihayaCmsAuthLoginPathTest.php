<?php

declare(strict_types=1);

namespace Drupal\Tests\KamihayaCMS\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test description.
 *
 * @group kamihaya_cms_auth
 */
final class KamihayaCmsAuthLoginPathTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'kamihaya_cms_auth',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installConfig('user');
  }

  /**
   * Test if the new login page url is accessible.
   */
  public function testLoginPageAccess(): void {
    // If the user access /user/login it should be 404.
    $http_kernel = $this->container->get('http_kernel');
    $request = Request::create('/user/login', 'GET', []);
    $response = $http_kernel->handle($request);
    // Expected 404 for the core login form.
    $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    // Check kanrisha login page.
    $request_2 = Request::create('/kanrisha/login', 'GET', []);
    $response_2 = $http_kernel->handle($request_2);
    $this->assertEquals(Response::HTTP_OK, $response_2->getStatusCode());
    // Check general user login page.
    $request_3 = Request::create('/ippan/login', 'GET', []);
    $response_3 = $http_kernel->handle($request_3);
    $this->assertEquals(Response::HTTP_OK, $response_3->getStatusCode());
  }

}
