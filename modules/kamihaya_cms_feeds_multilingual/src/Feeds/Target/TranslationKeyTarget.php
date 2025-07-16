<?php

namespace Drupal\kamihaya_cms_feeds_multilingual\Feeds\Target;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Feeds\Target\Temporary;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\feeds\TargetDefinition;

/**
 * Defines a translation key target for all entity types.
 *
 * @FeedsTarget(
 *   id = "translation_key",
 * )
 */
class TranslationKeyTarget extends Temporary implements ConfigurableTargetInterface {

    /**
   * {@inheritdoc}
   */
  public static function targets(array &$targets, FeedTypeInterface $feed_type, array $definition) {
    /* @var \Drupal\kamihaya_cms_feeds_multilingual\Feeds\Processor\MultilingualEntityProcessor $processor */
    $processor = $feed_type->getProcessor();
    $entity_type = $processor->entityType();
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type);

    // Only add target if entity type is translatable
    if (!$entity_type->isTranslatable()) {
      return;
    }
    $targets['translation_key'] = TargetDefinition::create()
      ->setPluginId($definition['id'])
      ->setLabel(t('Translation Key'))
      ->setDescription(t('Target for translation key to group translations together.'))
      ->addProperty('value', t('Translation key value'));
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration() + [
      'key_target' => 'name',
    ];
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['key_target'] = [
      '#type' => 'select',
      '#title' => $this->t('Key target'),
      '#options' => $this->getTargetOptions(),
      '#default_value' => $this->configuration['key_target'],
    ];

    return $form;
  }

  /**
   * Get tartget options
   *
   * @return array
   *   An array of target options.
   *
   */
  private function getTargetOptions() {
    return [
      'id' => $this->t('ID'),
      'uuid' => $this->t('UUID'),
      'name' => $this->t('Name'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $options = $this->getTargetOptions();
   // $summary = parent::getSummary();

    if ($this->configuration['key_target'] && isset($options[$this->configuration['key_target']])) {
      $summary[] = $this->t('Target key: %message', ['%message' => $options[$this->configuration['key_target']]]);
    }
    else {
      $summary[] = [
        '#prefix' => '<div class="messages messages--warning">',
        '#markup' => $this->t('Please select a target key.'),
        '#suffix' => '</div>',
      ];
    }

    return $summary;
  }
}
