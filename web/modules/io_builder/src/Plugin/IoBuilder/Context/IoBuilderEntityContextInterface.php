<?php

namespace Drupal\io_builder\Plugin\IoBuilder\Context;

use Drupal\io_builder\Plugin\Interfaces\IoBuilderContextInterface;
use Drupal\io_builder\Traits\IoBuilderEntitySetterInterface;

/**
 * Contains the methods needed for a working IoBuilderEntityContext.
 *
 * @package Drupal\io_builder\Plugin\IoBuilder\Context
 */
interface IoBuilderEntityContextInterface extends IoBuilderEntitySetterInterface, IoBuilderContextInterface {

  /**
   * Returns the entity type id.
   *
   * @return string
   *   The entity type id.
   */
  public function getEntityTypeId(): string;

  /**
   * Returns the configuration for the plugin.
   *
   * @return array
   *   The configuration for the plugin.
   */
  public function getConfiguration(): array;

}
