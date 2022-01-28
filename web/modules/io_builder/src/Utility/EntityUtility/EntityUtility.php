<?php

namespace Drupal\io_builder\Utility\EntityUtility;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\io_builder\Entity\IoBuilderDisplay;
use Drupal\io_builder\Plugin\IoBuilder\Field\IoBuilderFieldBase;
use Drupal\io_builder\Plugin\IoBuilderContextPluginManager;
use Drupal\io_builder\Plugin\IoBuilderEntityActionsPluginManager;
use Drupal\io_builder\Plugin\IoBuilderFieldPluginManager;
use Drupal\io_builder\Traits\IoBuilderEntityContextSetterTrait;
use Drupal\io_builder\Utility\BaseEntityUtility;

/**
 * Helps us with adding IO builder functionality to entities.
 *
 * @package Drupal\io_builder\Utility\FieldUtility
 */
class EntityUtility extends BaseEntityUtility {

  use IoBuilderEntityContextSetterTrait;

  /**
   * The action plugin manager.
   *
   * @var \Drupal\io_builder\Plugin\IoBuilderEntityActionsPluginManager
   */
  protected IoBuilderEntityActionsPluginManager $actionsPluginManager;

  /**
   * The field plugin manager.
   *
   * @var \Drupal\io_builder\Plugin\IoBuilderFieldPluginManager
   */
  protected IoBuilderFieldPluginManager $fieldPluginManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * The context plugin manager.
   *
   * @var \Drupal\io_builder\Plugin\IoBuilderContextPluginManager
   */
  private IoBuilderContextPluginManager $contextPluginManager;

  /**
   * EntityUtility constructor.
   *
   * @param \Drupal\io_builder\Plugin\IoBuilderEntityActionsPluginManager $actionsPluginManager
   *   The action plugin manager.
   * @param \Drupal\io_builder\Plugin\IoBuilderFieldPluginManager $fieldPluginManager
   *   The field plugin manager.
   * @param \Drupal\io_builder\Plugin\IoBuilderContextPluginManager $contextPluginManager
   *   The context plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    IoBuilderEntityActionsPluginManager $actionsPluginManager,
    IoBuilderFieldPluginManager $fieldPluginManager,
    IoBuilderContextPluginManager $contextPluginManager,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->actionsPluginManager = $actionsPluginManager;
    $this->fieldPluginManager = $fieldPluginManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->contextPluginManager = $contextPluginManager;
  }

  /**
   * Extends the variables passed by the io builder field.
   *
   * @param array $build
   *   Array containing the build.
   */
  public function extendBuild(array &$build) {
    if (!$this->context->getEntity()) {
      return;
    }

    $this->addAttributes($build);
    $this->addActions($build);
    $this->extendFields($build);
  }

  /**
   * Adds actions to the build array.
   *
   * @param array $build
   *   The build array.
   */
  protected function addActions(array &$build) {
    try {
      $actions = $this->actionsPluginManager->buildActionsFromContext(
        $this->context, $build
      );
    }
    catch (\Exception $e) {
      $actions = [];
    }

    if (empty($actions)) {
      return;
    }

    $build['#io_builder']['actions'] = [
      '#theme' => 'io_builder__actions',
      '#actions' => $actions,
      '#context' => $this->context
    ];
  }

  /**
   * Adds the attributes and the IO Builder library.
   *
   * @param array $build
   *   The build array.
   */
  protected function addAttributes(array &$build) {
    // Todo refactor this code, make sure hook support is added.
    $build['#attributes'] = $this->context->getIoBuilderAttributes();
    $build['#attributes']['class'][] = 'io-builder--wrapper';
    $build['#attached']['library'][] = 'io_builder/io_builder';
  }

  /**
   *
   */
  protected function getFieldConfiguration(): array {
    $formId = sprintf(
      '%s.%s.%s',
      $this->context->getEntity()->getEntityTypeId(),
      $this->context->getEntity()->bundle(),
      'default'
    );

    $ioBuilderDisplay = $this->entityTypeManager
      ->getStorage('io_builder_display')
      ->load($formId);

    if (!$ioBuilderDisplay instanceof IoBuilderDisplay) {
      return [];
    }

    $content = $ioBuilderDisplay->get('content');

    if (empty($content) || !is_array($content)) {
      return [];
    }

    return $content;
  }

  /**
   * This function will retrieve the configured IO builder fields and extend em.
   *
   * @param array $build
   *   The build passed by reference.
   */
  protected function extendFields(array &$build) {
    $fieldConfiguration = $this->getFieldConfiguration();

    foreach ($fieldConfiguration as $key => $fieldConfig) {
      if (empty($build[$key]) || empty($fieldConfig['type'])) {
        continue;
      }

      $pluginId = $fieldConfig['type'];

      if (!$this->fieldPluginManager->hasDefinition($pluginId)) {
        continue;
      }

      try {
        $instance = $this->fieldPluginManager->createInstance($pluginId);
      }
      catch (\Exception $e) {
        $instance = NULL;
      }

      if (!$instance instanceof IoBuilderFieldBase) {
        continue;
      }

      $fieldContext = $this->contextPluginManager->createInstance(
        'io_builder_entity_field_context',
        $this->context->getConfiguration() + [
          'field' => $key
        ]
      );

      $instance->setContext($fieldContext);
      $instance->alterBuild($build[$key]);
      $build[$key]['#attributes']['class'][] = 'io-builder--field-wrapper';
      $build[$key]['#attributes']['data-io-builder-field'] = $key;
      $children = Element::children($build[$key]);

      foreach ($children as $child) {
        $build[$key][$child]['#attributes']['class'][] = 'io-builder--field-item';
      }

      // todo add hook to manipulate the build.
    }
  }

}
