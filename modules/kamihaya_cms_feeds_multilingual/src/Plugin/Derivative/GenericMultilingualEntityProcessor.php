<?php

namespace Drupal\kamihaya_cms_feeds_multilingual\Plugin\Derivative;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\feeds\Plugin\Derivative\GenericContentEntityProcessor;

/**
 * Provides generic Feeds processor plugin definitions for multilingual content entities.
 *
 * @see \Drupal\feeds\Feeds\Processor\GenericContentEntityProcessor
 */
class GenericMultilingualEntityProcessor extends GenericContentEntityProcessor {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if (!($entity_type instanceof ContentEntityTypeInterface) || !$entity_type->hasKey('langcode')) {
        continue;
      }
      if (!$entity_type->isTranslatable()) {
        continue;
      }
      $this->derivatives[$entity_type_id] = $base_plugin_definition;
      $this->derivatives[$entity_type_id]['title'] = $entity_type->getLabel(). '(multilingual)';
      $this->derivatives[$entity_type_id]['description'] = $this->t('Creates multilingual @plural_label from feed items.', [
        '@plural_label' => $entity_type->getPluralLabel(),
      ]);
      $this->derivatives[$entity_type_id]['entity_type'] = $entity_type_id;
    }
    return $this->derivatives;
  }

}
