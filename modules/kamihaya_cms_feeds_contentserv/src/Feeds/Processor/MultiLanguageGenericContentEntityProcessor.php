<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Feeds\Processor;

/**
 * Provides a generic multi language content entity processor.
 *
 * @FeedsProcessor(
 *   id = "multi_language_entity",
 *   form = {
 *     "configuration" = "Drupal\kamihaya_cms_feeds_contentserv\Feeds\Processor\Form\MultiLanguageDefaultEntityProcessorForm",
 *     "option" = "Drupal\feeds\Feeds\Processor\Form\EntityProcessorOptionForm",
 *   },
 *   deriver = "Drupal\kamihaya_cms_feeds_contentserv\Plugin\Derivative\MultiLanguageGenericContentEntityProcessor",
 * )
 */
class MultiLanguageGenericContentEntityProcessor extends MultiLanguageEntityProcessorBase {

}
