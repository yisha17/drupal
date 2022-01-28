<?php

namespace Drupal\io_builder\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Class IoBuilderEntityActions.
 *
 * @package Drupal\io_builder\Annotation
 *
 * @Annotation
 */
class IoBuilderEntityActions extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the paragraphs behavior plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *   Allow translation of this label.
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The plugin description.
   *
   * @var string
   *   The plugin description.
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The plugin weight.
   *
   * @var int
   */
  public $weight;

  /**
   * Limit this plugin to certain entity types.
   *
   * @var array
   */
  public $entityTypes;

}
