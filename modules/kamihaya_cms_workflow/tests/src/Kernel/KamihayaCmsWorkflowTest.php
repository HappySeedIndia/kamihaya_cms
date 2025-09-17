<?php

namespace Drupal\Tests\kamihaya_cms_workflow\Kernel;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\InstallStorage;
use Drupal\KernelTests\KernelTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests the Kamihaya workflow.
 */
class KamihayaCmsWorkflowTest extends KernelTestBase {

  /**
   * Workflow entity.
   */
  protected Workflow $workflow;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'content_moderation',
    'workflows',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('content_moderation');

    $view_yaml = $this->getModulePath('kamihaya_cms_workflow') . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY . '/workflows.workflow.kamihaya_workflow.yml';
    $values = Yaml::decode(file_get_contents($view_yaml));

    $this->workflow = Workflow::create([
      'id' => $values['id'],
      'label' => $values['label'],
      'type' => $values['type'],
      'type_settings' => [
        'states' => $values['type_settings']['states'],
        'transitions' => $values['type_settings']['transitions'],
        // @todo 'entity_types' => $values['type_settings']['entity_types'],
      ],
    ]);
    $this->workflow->save();
  }

  /**
   * Checks for Kamihaya workflow states.
   */
  public function testWorkflowStates(): void {
    // Assert.
    $this->assertEquals('Kamihaya workflow', $this->workflow->label());
    $this->assertSame([
      'archived',
      'draft',
      'in_review',
      'published',
    ], array_keys($this->workflow->getTypePlugin()->getConfiguration()['states']));
  }

  /**
   * Check if all required Kamihaya workflow transitions.
   */
  public function testWorkflowTransitions(): void {
    // Check if required transition exists.
    $transitions = $this->workflow->getTypePlugin()->getConfiguration()['transitions'];
    $expected_transitions = [
      'archive',
      'create_new_draft',
      'publish',
      're_publish',
      'reject',
      'save_as_draft',
      'send_to_review',
    ];
    $this->assertSame($expected_transitions, array_keys($transitions));

    // Check if transition exists.
    $this->assertTrue($this->workflow->getTypePlugin()->hasTransitionFromStateToState('published', 'archived'));
    $this->assertTrue($this->workflow->getTypePlugin()->hasTransitionFromStateToState('draft', 'draft'));
    $this->assertTrue($this->workflow->getTypePlugin()->hasTransitionFromStateToState('published', 'archived'));
    $this->assertTrue($this->workflow->getTypePlugin()->hasTransitionFromStateToState('draft', 'published'));
    $this->assertTrue($this->workflow->getTypePlugin()->hasTransitionFromStateToState('archived', 'published'));
    $this->assertTrue($this->workflow->getTypePlugin()->hasTransitionFromStateToState('in_review', 'draft'));
  }

  // @codingStandardsIgnoreStart
  // Public function testWorkflowEntityTypes(): void {
  // $entity_types = $this->workflow->getTypePlugin()->getConfiguration()['entity_types'];
  // $this->assertEquals(['kamihaya_basic', 'kamihaya_news'], $entity_types['node'], 'Workflow applies to correct node bundles.');
  // $this->assertEquals(['kamihaya_block'], $entity_types['block_content'], 'Workflow applies to correct block_content bundles.');
  // $this->assertEquals(['document'], $entity_types['media'], 'Workflow applies to correct media bundles.');
  // }
  // @codingStandardsIgnoreEnd
}
