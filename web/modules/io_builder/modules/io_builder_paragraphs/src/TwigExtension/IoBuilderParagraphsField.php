<?php

namespace Drupal\io_builder_paragraphs\TwigExtension;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\io_builder\AccessHandler\EntityAccessHandler;
use Drupal\io_builder\Plugin\IoBuilderContextPluginManager;
use Drupal\io_builder_paragraphs\Utility\IoBuilderParagraphsUtility;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class IoBuilderParagraphsField
 *
 * @package Drupal\io_builder_paragraphs\Twig
 */
class IoBuilderParagraphsField extends AbstractExtension {

  /**
   * The context plugin manager.
   *
   * @var \Drupal\io_builder\Plugin\IoBuilderContextPluginManager
   */
  private IoBuilderContextPluginManager $contextPluginManager;

  /**
   * The entity access handler.
   *
   * @var \Drupal\io_builder\AccessHandler\EntityAccessHandler
   */
  private EntityAccessHandler $entityAccessHandler;

  /**
   * THe io builder paragraphs utility.
   *
   * @var \Drupal\io_builder_paragraphs\Utility\IoBuilderParagraphsUtility
   */
  private IoBuilderParagraphsUtility $ioBuilderParagraphsUtility;

  /**
   * IoBuilderParagraphsField constructor.
   *
   * @param \Drupal\io_builder\Plugin\IoBuilderContextPluginManager $contextPluginManager
   *   The context plugin manager.
   * @param \Drupal\io_builder\AccessHandler\EntityAccessHandler $entityAccessHandler
   *   The entity access handler.
   * @param \Drupal\io_builder_paragraphs\Utility\IoBuilderParagraphsUtility $ioBuilderParagraphsUtility
   *   The paragraphs utility.
   */
  public function __construct(
    IoBuilderContextPluginManager $contextPluginManager,
    EntityAccessHandler $entityAccessHandler,
    IoBuilderParagraphsUtility $ioBuilderParagraphsUtility
  ) {
    $this->contextPluginManager = $contextPluginManager;
    $this->entityAccessHandler = $entityAccessHandler;
    $this->ioBuilderParagraphsUtility = $ioBuilderParagraphsUtility;
  }

  /**
   * Generates a list of all Twig functions that this extension defines.
   */
  public function getFunctions() {
    return [
      new TwigFunction(
        'io_builder_paragraphs_add_more_placeholder', [$this, 'addMorePlaceholder']
      ),
    ];
  }

  /**
   * Gets a unique identifier for this Twig extension.
   */
  public function getName() {
    return 'io_builder.paragraphs_field';
  }

  /**
   * Creates an IO Builder paragraphs field.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase $entity
   *   The entity.
   * @param string $field
   *   The name of the field.
   */
  public function addMorePlaceholder(ContentEntityBase $entity, string $viewMode, string $field, int $delta = NULL) {
    if (!$this->entityAccessHandler->ioBuilderAccess($entity)) {
      return NULL;
    }

    $paragraphContext = $this->contextPluginManager->createInstance(
      'io_builder_paragraph_field_context',
      [
        'entity' => $entity,
        'field' => $field,
        'view_mode' => $viewMode,
        'delta' => $delta
      ]
    );

    return [
      '#theme' => 'io_builder__placeholder',
      '#content' => $this->ioBuilderParagraphsUtility->buildAddMore($paragraphContext) + [
        '#position' => 'center',
      ],
    ];
  }

}
