<?php

declare(strict_types=1);

namespace Drupal\kamihaya_cms_language_negotiation\PathProcessor;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Path processor to find aliases correctly.
 *
 * @DCG Language Negotiation plugins are called earlier and
 * the AliasPathProcessor is unable to determine the paths of
 * dynamically created nodes
 *
 * @see Drupal\path_alias\PathProcessor\AliasPathProcessor::inbound().s
 */
final class PathAliasProcessor implements InboundPathProcessorInterface {

  /**
   * A Language manager for looking up the current language.
   *
   * @var Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * An alias manager for looking up the system path.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a PathAliasProcessor object.
   */
  public function __construct(
    LanguageManagerInterface $languageManager,
    AliasManagerInterface $alias_manager,
  ) {
    $this->languageManager = $languageManager;
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request): string|NULL {
    $language_code = $this->languageManager->getCurrentLanguage()->getId();
    $path = $this->aliasManager->getPathByAlias($path, $language_code);
    // Add an attribute to prevent that the Redirect Module redirects to the
    // canonical URL when "Enforce clean and canonical URLs" is selected.
    // We use the request object directly as $request is a cloned object
    // made in RedirectRequestSubscriber::onKernelRequestCheckRedirect.
    // @see https://www.drupal.org/project/facets_pretty_paths/issues/2930145
    \Drupal::request()->attributes->set('_disable_route_normalizer', TRUE);
    return $path;
  }

}
