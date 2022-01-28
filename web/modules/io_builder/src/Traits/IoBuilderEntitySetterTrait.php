<?php

namespace Drupal\io_builder\Traits;

use Drupal\Core\Entity\ContentEntityBase;

/**
 * Adds content entity setter functionality.
 *
 * @package Drupal\io_builder\Traits
 */
trait IoBuilderEntitySetterTrait {

  protected ContentEntityBase $entity;

  /**
   * Sets an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase $entity
   *   The entity being set.
   */
  public function setEntity(ContentEntityBase $entity): void {
    $this->entity = $entity;
  }

  /**
   * Returns the content entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityBase
   *   The content entity.
   */
  public function getEntity(): ContentEntityBase {
    return $this->entity;
  }

  protected string $viewMode;

  /**
   * Sets the view mode.
   *
   * @param string $viewMode
   *   The view mode.
   */
  public function setViewMode(string $viewMode): void {
    $this->viewMode = $viewMode;
  }

  /**
   * Returns the view mode.
   *
   * @return string
   *   The view mode.
   */
  public function getViewMode(): string {
    return $this->viewMode;
  }

}
