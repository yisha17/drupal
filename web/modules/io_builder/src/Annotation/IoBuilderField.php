<?php

namespace Drupal\io_builder\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Adds an annotation type for the IO Builder Field plugins.
 *
 * @package Drupal\io_builder\Annotation
 *
 * @Annotation
 */
class IoBuilderField extends Plugin {

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
   * An array of field types the widget supports.
   *
   * @var array
   */
  public $field_types = [];

}
