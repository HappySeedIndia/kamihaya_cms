<?php

declare(strict_types=1);

namespace Drupal\Tests\kamihaya_cms_language_negotiation\Unit;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Session\AccountInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\kamihaya_cms_language_negotiation\Plugin\LanguageNegotiation\SitePrefixLanguageNegotiationUrl;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests site prefix language negotiation plugin.
 */
#[Group('kamihaya_cms_language_negotiation')]
final class SiteAndLanguagePrefixNegotiationTest extends UnitTestCase {

  /**
   * Language manager service.
   */
  protected ConfigurableLanguageManagerInterface $languageManager;

  /**
   * Current user.
   */
  protected AccountInterface $user;
  /**
   * An array of languages.
   */
  protected array $languages;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $language_th = $this->createMock('\Drupal\Core\Language\LanguageInterface');
    $language_th->expects($this->any())
      ->method('getId')
      ->willReturn('th');
    $language_en = $this->createMock('\Drupal\Core\Language\LanguageInterface');
    $language_en->expects($this->any())
      ->method('getId')
      ->willReturn('en');
    $languages = [
      'th' => $language_th,
      'en' => $language_en,
    ];
    $this->languages = $languages;

    // Create a language manager stub.
    $language_manager = $this->getMockBuilder('Drupal\language\ConfigurableLanguageManagerInterface')
      ->getMock();
    $language_manager->expects($this->any())
      ->method('getLanguages')
      ->willReturn($languages);
    $this->languageManager = $language_manager;

    // Create a user stub.
    $this->user = $this->getMockBuilder('Drupal\Core\Session\AccountInterface')
      ->getMock();

    $cache_contexts_manager = $this->getMockBuilder('Drupal\Core\Cache\Context\CacheContextsManager')
      ->disableOriginalConstructor()
      ->getMock();
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);
    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests path prefix language negotiation and outbound path processing.
   *
   * @dataProvider providerTestPathPrefix
   */
  public function testNegotiatedResponse($prefix, $prefixes, $expected_langcode, $expected_outbound_prefix): void {
    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->willReturn($this->languages[(in_array($expected_langcode, [
        'en',
        'th',
      ])) ? $expected_langcode : 'en']);

    $config = $this->getConfigFactoryStub([
      'language.negotiation' => [
        'url' => [
          'source' => SitePrefixLanguageNegotiationUrl::CONFIG_PATH_PREFIX,
          'prefixes' => $prefixes,
        ],
        'site_prefix_language_url' => [
          'site_prefix' => 'th',
        ],
      ],
    ]);

    $request = Request::create($prefix . '/foo', 'GET');
    // Base class for language negotiation methods.
    $method = new SitePrefixLanguageNegotiationUrl();
    $method->setLanguageManager($this->languageManager);
    $method->setConfig($config);
    $method->setCurrentUser($this->user);
    $lang_code = $method->getLangcode($request);

    $this->assertEquals($expected_langcode, $lang_code);

    $cacheability = new BubbleableMetadata();
    // @todo Fix getId() returns null.
    $options = [];
    $path = $method->processOutbound('/foo', $options, $request, $cacheability);
    $expected_cacheability = new BubbleableMetadata();
    if ($expected_langcode) {
      $this->assertSame($expected_outbound_prefix . '/foo', $path);
      $expected_cacheability->setCacheContexts(['languages:' . LanguageInterface::TYPE_URL]);
      $this->assertEquals($expected_cacheability, $cacheability);
    }
    else {
      $this->assertFalse($expected_outbound_prefix);
    }
  }

  /**
   * Provides data for the path prefix test.
   *
   * @return array
   *   An array of data for checking path prefix negotiation.
   */
  public static function providerTestPathPrefix() {
    $path_prefix_configuration[] = [
      'prefix' => '/th/th',
      'prefixes' => [
        'th' => 'th',
        'en-uk' => 'en',
      ],
      'expected_langcode' => 'th',
      'expected_outbound_prefix' => '/th/th',
    ];
    $path_prefix_configuration[] = [
      'prefix' => '/th/en',
      'prefixes' => [
        'th' => 'th',
        'en' => 'en',
      ],
      'expected_langcode' => 'en',
      'expected_outbound_prefix' => '/th',
    ];

    // @todo Fix this test.
    /*$path_prefix_configuration[] = [
    'prefix' => '/th',
    'prefixes' => [
    'th' => 'th',
    'en' => 'en',
    ],
    'expected_langcode' => 'en',
    'expected_outbound_prefix' => FALSE,
    ];*/
    // No configuration.
    $path_prefix_configuration[] = [
      'prefix' => 'th',
      'prefixes' => [],
      'expected_langcode' => FALSE,
      'expected_outbound_prefix' => FALSE,
    ];
    // Non-matching prefix.
    $path_prefix_configuration[] = [
      'prefix' => 'th',
      'prefixes' => [
        'en-uk' => 'en',
      ],
      'expected_langcode' => FALSE,
      'expected_outbound_prefix' => FALSE,
    ];
    // Non-existing language.
    $path_prefix_configuration[] = [
      'prefix' => 'it',
      'prefixes' => [
        'it' => 'it',
        'en-uk' => 'en',
      ],
      'expected_langcode' => FALSE,
      'expected_outbound_prefix' => FALSE,
    ];
    return $path_prefix_configuration;
  }

}
