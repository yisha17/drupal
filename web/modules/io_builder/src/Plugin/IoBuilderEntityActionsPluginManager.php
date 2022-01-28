<?php

namespace Drupal\io_builder\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\io_builder\Annotation\IoBuilderEntityActions;
use Drupal\io_builder\Plugin\Interfaces\IoBuilderEntityActionsInterface;
use Drupal\io_builder\Plugin\IoBuilder\Context\IoBuilderEntityContextInterface;

/**
 * This plugin manager will take care of the entity action plugins.
 *
 * @package Drupal\io_builder\Plugin
 */
class IoBuilderEntityActionsPluginManager extends DefaultPluginManager {

  /**
   * Constructs a ParagraphsBehaviorManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct(
      'Plugin/IoBuilder/EntityActions',
      $namespaces,
      $module_handler,
      IoBuilderEntityActionsInterface::class,
      IoBuilderEntityActions::class
    );

    $this->setCacheBackend($cache_backend, 'io_builder_entity_actions_plugins');
    $this->alterInfo('io_builder_entity_actions_info');
  }

  /**
   * Gets the definitions by entity type.
   *
   * @param string $entityType
   *   The entity type.
   *
   * @return array
   *   An array containing the definitions by entity type.
   */
  public function getDefinitionsByEntityType(string $entityType): array {
    $definitions = $this->getDefinitions();

    if (empty($definitions)) {
      return [];
    }

    $matchingDefinitions = [];

    foreach ($definitions as $id => $definition) {
      // todo write test for the entity type matches definition.
      if ($this->entityTypeMatchesDefinition($entityType, $definition)) {
        $matchingDefinitions[$id] = $definition;
      }
    }

    return $matchingDefinitions;
  }

  /**
   * Determines if the entity type matches a plugin definition.
   *
   * @param string $entityType
   *   The entity type.
   * @param array $definition
   *   The definition.
   *
   * @return bool
   *   Entity type matches the definition?
   */
  protected function entityTypeMatchesDefinition(string $entityType, array $definition): bool {
    // Empty entity types array means this is available for all entity types.
    if (empty($definition['entityTypes']) || !is_array($definition['entityTypes'])) {
      return TRUE;
    }

    // The entity type is not mentioned in the definition.
    if (!in_array($entityType, $definition['entityTypes'])) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Returns an array of entity actions for a certainn IO Builder context.
   *
   * @param \Drupal\io_builder\Plugin\IoBuilder\Context\IoBuilderEntityContextInterface $context
   *   The io builder entity context.
   * @param array $build
   *   A build array so we can retrieve additional information.
   *
   * @return array
   *   The array of actions.
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function buildActionsFromContext(IoBuilderEntityContextInterface $context, array &$build = []): array {
    $definitions = $this->getDefinitionsByEntityType(
      $context->getEntityTypeId()
    );

    if (empty($definitions)) {
      return [];
    }

    $actions = [];

    foreach ($definitions as $key => $definition) {
      $instance = $this->createInstance($key);

      if (!$instance instanceof IoBuilderEntityActionsInterface) {
        continue;
      }

      $instance->setContext($context);

      $actions = array_merge(
        $instance->getActions($build),
        $actions,
      );
    }

    // Todo make sure a hook can extend/manipulate the actions.
    // Todo Hook name: hook_io_builder_entity_actions(array &$actions, IoBuilderEntityContext $context, array &$build = [])
    return $actions;
  }

}
