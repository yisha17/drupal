<?php

namespace Drupal\io_builder\Utility;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * The abstract BaseEntityUtility class.
 *
 * @package Drupal\io_builder\Utility
 */
abstract class BaseEntityUtility {

  use StringTranslationTrait;

  /**
   * The content entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityBase|null
   */
  protected ?ContentEntityBase $entity;

  /**
   * The view mode of the entity.
   *
   * @var string
   */
  protected string $view_mode;

  /**
   * Sets the entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase|null $entity
   *   The entity to set.
   */
  public function setEntity(?ContentEntityBase $entity): void {
    $this->entity = $entity;
  }

  /**
   * Sets the entity view mode.
   *
   * @param string $view_mode
   *   The view mode.
   */
  public function setViewMode(string $view_mode): void {
    $this->view_mode = $view_mode;
  }

}
