<?php

namespace Drupal\io_builder\Traits;

use Drupal\Core\Entity\ContentEntityBase;

/**
 * Describes methods for setting and getting the IoBuilderEntityContext.
 *
 * @package Drupal\io_builder\Traits
 */
interface IoBuilderEntitySetterInterface {

  /**
   * Sets an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase $entity
   *   The entity being set.
   */
  public function setEntity(ContentEntityBase $entity): void;

  /**
   * Returns the content entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityBase
   *   The content entity.
   */
  public function getEntity(): ContentEntityBase;

  /**
   * Returns the view mode.
   *
   * @return string
   *   The view mode.
   */
  public function getViewMode(): string;

  /**
   * Sets the view mode.
   *
   * @param string $viewMode
   *   The view mode.
   */
  public function setViewMode(string $viewMode): void;

}
