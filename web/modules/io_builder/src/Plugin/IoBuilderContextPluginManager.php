<?php

namespace Drupal\io_builder\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\io_builder\Annotation\IoBuilderContext;
use Drupal\io_builder\Plugin\Interfaces\IoBuilderContextInterface;

class IoBuilderContextPluginManager extends DefaultPluginManager {

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
      'Plugin/IoBuilder/Context',
      $namespaces,
      $module_handler,
      IoBuilderContextInterface::class,
      IoBuilderContext::class
    );

    $this->setCacheBackend($cache_backend, 'io_builder_context_plugins');
    $this->alterInfo('io_builder_context_info');
  }

}
