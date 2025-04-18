<?php

declare(strict_types=1);

namespace Drupal\Tests\kamihaya_cms_language_negotiation\Unit;

use Drupal\kamihaya_cms_language_negotiation\PathProcessor\PathAliasProcessor;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test description.
 */
#[Group('kamihaya_cms_language_negotiation')]
final class PathProcessorTest extends UnitTestCase {

  /**
   * The mocked alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $aliasManager;

  /**
   * The mocked language manager.
   *
   * @var \Drupal\Language\LanguageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $languageManager;

  /**
   * The tested path processor.
   *
   * @var \Drupal\kamihaya_cms_language_negotiation\PathProcessor\PathAliasProcessor
   */
  protected $pathProcessor;

  /**
   * An array of languages.
   */
  protected array $languages;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->aliasManager = $this->createMock('Drupal\path_alias\AliasManagerInterface');
    $this->languageManager = $this->createMock('Drupal\Core\Language\LanguageManagerInterface');

  }

  /**
   * Tests the processInbound method.
   *
   * @see \Drupal\path_alias\PathProcessor\AliasPathProcessor::processInbound
   */
  public function testProcessInbound(): void {
    $language_en = $this->createMock('\Drupal\Core\Language\LanguageInterface');
    $language_en->expects($this->any())
      ->method('getId')
      ->willReturn('en');

    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->willReturn($language_en);

    $this->aliasManager->expects($this->any())
      ->method('getPathByAlias')
      ->willReturn('internal-url');

    $this->pathProcessor = new PathAliasProcessor($this->languageManager, $this->aliasManager);

    $request = Request::create('en/url-alias');
    $this->assertEquals('internal-url', $this->pathProcessor->processInbound('url-alias', $request));
  }

}
