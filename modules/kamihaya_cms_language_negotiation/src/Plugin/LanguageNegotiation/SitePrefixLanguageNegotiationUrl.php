<?php

namespace Drupal\kamihaya_cms_language_negotiation\Plugin\LanguageNegotiation;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\language\Attribute\LanguageNegotiation;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language via URL prefix and site prefix.
 */
#[LanguageNegotiation(
  id: SitePrefixLanguageNegotiationUrl::METHOD_ID,
  name: new TranslatableMarkup('Site Prefix URL Negotiation'),
  types: [LanguageInterface::TYPE_INTERFACE,
    LanguageInterface::TYPE_CONTENT,
    LanguageInterface::TYPE_URL,
  ],
  weight: -8,
  description: new TranslatableMarkup("Language and Site from the URL (Path prefix)."),
  config_route_name: 'kamihaya_cms_language_negotiation.negotiation_url'
)]
class SitePrefixLanguageNegotiationUrl extends LanguageNegotiationUrl {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'site-prefix-language-url';

  /**
   * URL language negotiation: use the path prefix as URL language indicator.
   */
  const CONFIG_PATH_PREFIX = 'path_prefix';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(?Request $request = NULL) {
    // Returns the specified language code from url.
    $langcode = NULL;

    if ($request && $this->languageManager) {
      $languages = $this->languageManager->getLanguages();
      $config = $this->config->get('language.negotiation')->get('url');
      $site_prefix_config = $this->config->get('language.negotiation')->get('site_prefix_language_url')['site_prefix'];

      if ($config['source'] == SitePrefixLanguageNegotiationUrl::CONFIG_PATH_PREFIX) {
        $request_path = urldecode(trim($request->getPathInfo(), '/'));
        $path_args = explode('/', $request_path);
        $site_prefix = array_shift($path_args);
        $lang_prefix = array_shift($path_args);
        // Search prefix within added languages.
        $negotiated_language = FALSE;
        foreach ($languages as $language) {
          $lang_code = $language->getId();
          if (isset($config['prefixes'][$lang_code]) && $config['prefixes'][$lang_code] == $lang_prefix && $site_prefix == $site_prefix_config) {
            $negotiated_language = $language;
            break;
          }
        }

        if ($negotiated_language) {
          $langcode = $negotiated_language->getId();
        }
      }
    }
    return $langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $config = $this->config->get('language.negotiation')->get('url');
    $site_prefix_config = $this->config->get('language.negotiation')->get('site_prefix_language_url')['site_prefix'];
    $languages = array_map(fn($lang) => $lang->getId(), array_values($this->languageManager->getLanguages()));
    if ($config['source'] == SitePrefixLanguageNegotiationUrl::CONFIG_PATH_PREFIX) {
      $parts = explode('/', trim($path, '/'));

      if (in_array($site_prefix_config, $parts, TRUE)) {
        // Build a combined list of language and site prefix.
        $all_parts = array_merge($languages, [$site_prefix_config]);
        // Exclude the site prefix and language prefix from the path.
        $filtered_parts = array_diff($parts, $all_parts);
        $site_prefix = array_shift($parts);
        $lang_prefix = array_shift($parts);
        $parts = array_values($filtered_parts);

        // Search prefix within added languages.
        foreach ($languages as $lang_code) {
          if (isset($config['prefixes'][$lang_code]) && $config['prefixes'][$lang_code] == $lang_prefix && $site_prefix == $site_prefix_config) {
            $path = '/' . implode('/', $parts);
            break;
          }
          elseif (isset($config['prefixes'][$lang_code]) && $config['prefixes'][$lang_code] != $lang_prefix && $site_prefix == $site_prefix_config) {
            $path = '/' . implode('/', $parts);
            break;
          }
        }
      }
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], ?Request $request = NULL, ?BubbleableMetadata $bubbleable_metadata = NULL) {
    $languages = array_flip(array_keys($this->languageManager->getLanguages()));
    $site_prefix_config = $this->config->get('language.negotiation')->get('site_prefix_language_url')['site_prefix'];
    // Language can be passed as an option, or we go for current URL language.
    if (!isset($options['language']) || ($options['language'] instanceof LanguageInterface && $options['language']->getId() == LanguageInterface::LANGCODE_NOT_SPECIFIED)) {
      // @todo Do we need to pass LanguageInterface::TYPE_URL in getCurrentLanguage().
      $language_url = $this->languageManager->getCurrentLanguage();
      $options['language'] = $language_url;
    }
    // We allow only added languages here.
    elseif (!is_object($options['language']) || !isset($languages[$options['language']->getId()])) {
      return $path;
    }

    $parts = explode('/', trim($path, '/'));
    // Add site prefix in the url, if it doesn't exist.
    if (!in_array($site_prefix_config, $parts, TRUE)) {
      // $options['prefix'] = '/thai/' . $options['language']->getId();
      if ($options['language']->getId() == $this->languageManager->getDefaultLanguage()->getId()) {
        $path = '/' . $site_prefix_config . $path;
      }
      else {
        $path = '/' . $site_prefix_config . '/' . $options['language']->getId() . $path;
      }
    }

    $config = $this->config->get('language.negotiation')->get('url');
    if ($config['source'] == SitePrefixLanguageNegotiationUrl::CONFIG_PATH_PREFIX) {
      if (is_object($options['language']) && !empty($config['prefixes'][$options['language']->getId()])) {
        if (!isset($options['language']) || ($options['language'] instanceof LanguageInterface && $options['language']->getId() == LanguageInterface::LANGCODE_NOT_SPECIFIED)) {
          $language_url = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL);
          $options['language'] = $language_url;
        }

        if ($bubbleable_metadata) {
          $bubbleable_metadata->addCacheContexts(['languages:' . LanguageInterface::TYPE_URL]);
        }
      }
    }

    return $path;
  }

}
