<?php

namespace Drupal\kamihaya_cms_language_selector\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\kamihaya_cms_language_selector\Plugin\Derivative\LanguageSelector as LanguageSelectorDerivative;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides an alternative language switcher block.
 */
#[Block(
  id: "kamihaya_language_selector",
  admin_label: new TranslatableMarkup("Kamihaya Language Selector"),
  category: new TranslatableMarkup("Kamihaya Blocks"),
  deriver: LanguageSelectorDerivative::class
)]
class LanguageSelector extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The Route Matcher.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  public $request;

  /**
   * Constructs a new DropdownLanguage instance.
   *
   * @param array $block_configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route Matcher.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request.
   */
  public function __construct(array $block_configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager, RouteMatchInterface $route_match, RequestStack $request) {
    parent::__construct($block_configuration, $plugin_id, $plugin_definition);

    $this->languageManager = $language_manager;
    $this->routeMatch = $route_match;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $block_configuration, $plugin_id, $plugin_definition) {
    return new static(
      $block_configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('current_route_match'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
      'selector_icon' => 'globe',
      'selector_label' => $this->t('Language'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['selector_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Selector label'),
      '#maxlength' => 255,
      '#default_value' => $this->configuration['selector_label'],
      '#required' => TRUE,
    ];
    $form['selector_icon'] = [
      '#type' => 'select',
      '#title' => $this->t('Selector icon'),
      '#options' => [
        'globe' => $this->t('Globe'),
        'language' => $this->t('Language'),
      ],
      '#default_value' => $this->configuration['selector_icon'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['selector_label'] = $form_state->getValue('selector_label');
    $this->configuration['selector_icon'] = $form_state->getValue('selector_icon');
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $access = $this->languageManager->isMultilingual() ? AccessResult::allowed() : AccessResult::forbidden();
    return $access->addCacheTags(['config:configurable_language_list']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [];

    // Do not output anything if is 404 or 403. #3119474.
    $exception = $this->request->getCurrentRequest()->attributes->get('exception');
    if ($exception && $exception instanceof HttpException && ($exception->getStatusCode() === 404 || $exception->getStatusCode() === 403)) {
      return $block;
    }

    $build = [];
    $links = [];
    $languages = $this->languageManager->getLanguages();

    if (count($languages) > 1) {
      $derivative_id = $this->getDerivativeId();
      $current_language = $this->languageManager->getCurrentLanguage($derivative_id)->getId();
      $languageSwitchLinksObject = $this->languageManager->getLanguageSwitchLinks($derivative_id, Url::fromRouteMatch($this->routeMatch));
      $links = ($languageSwitchLinksObject !== NULL) ? $languageSwitchLinksObject->links : [];

      // Place active language ontop of list.
      if (isset($links[$current_language])) {
        // Set an active class for styling.
        $links[$current_language]['attributes']['class'][] = 'active-language';
      }

      // Get native names.
      $native_names = $this->languageManager->getStandardLanguageList();
      $entity = [];
      $routedItems = $this->routeMatch;
      foreach ($routedItems->getParameters() as $param) {
        if ($param instanceof EntityInterface) {
          $entity['EntityInterface'] = $param;
        }
      }

      foreach ($links as $lid => $link) {
        $name = isset($link['language']) ? $link['language']->getName() : '';

        // Re-label as per general setting.
        $links[$lid]['title'] = isset($native_names[$lid]) ? $native_names[$lid][1] : $name;
        if (isset($native_names[$lid]) && (isset($native_names[$lid]) && $native_names[$lid][1] != $name)) {
          $links[$lid]['attributes']['title'] = $name;
        }

        // Removes unused languages from the dropdown.
        if (!empty($entity['EntityInterface'])) {
          $has_translation = (method_exists($entity['EntityInterface'], 'getTranslationStatus')) ? $entity['EntityInterface']->getTranslationStatus($lid) : FALSE;
          $this_translation = ($has_translation && method_exists($entity['EntityInterface'], 'getTranslation')) ? $entity['EntityInterface']->getTranslation($lid) : FALSE;
          $access_translation = ($this_translation && method_exists($this_translation, 'access') && $this_translation->access('view')) ? TRUE : FALSE;
          if (!$this_translation || !$access_translation) {
            unset($links[$lid]);
            continue;
          }
        }
        if (empty($links[$lid]['attributes']['class'])) {
          $links[$lid]['attributes']['class'] = [];
        }
        $links[$lid]['attributes']['class'][] = 'dropdown-item';
      }

      $container = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['dropdown', 'nav-item', 'language-selector'],
        ],
      ];
      $header = [
        'level' => 'span',
        'attributes' => [
          'class' => ['dropdown-toggle', 'nav-link', $this->configuration['selector_icon']],
          'role' => 'button',
          'id' => 'dropdownMenuLink',
          'href' => '#',
          'data-bs-toggle' => 'dropdown',
          'aria-expanded' => FALSE,
        ],
        'text' => $this->configuration['selector_label'],
      ];
      $dropdown = [
        '#theme' => 'links',
        '#subtype' => 'language_selector',
        '#links' => $links,
        '#attributes' => [
          'class' => ['dropdown-menu'],
          'aria-labelledby' => 'dropdownMenuLink',
        ],
        '#heading' => $header,
      ];
      $container[] = $dropdown;
      $block['switch-language'] = $container;
    }

    if (count($links) > 1 && !empty($block)) {
      $build['language-selector'] = $block;
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
