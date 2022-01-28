<?php

namespace Drupal\io_builder_paragraphs\Utility;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\io_builder\Plugin\IoBuilder\Context\IoBuilderEntityContextInterface;
use Drupal\io_builder\Plugin\IoBuilder\EntityActions\BaseEntityActions;
use Drupal\io_builder_paragraphs\Plugin\IoBuilder\Context\IoBuilderParagraphFieldContext;
use Drupal\paragraphs\Plugin\EntityReferenceSelection\ParagraphSelection;

/**
 * Class IoBuilderParagraphsUtility
 *
 * @package Drupal\io_builder_paragraphs\Utility
 */
class IoBuilderParagraphsUtility {

  use StringTranslationTrait;

  /**
   * The selection plugin manager.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  private SelectionPluginManagerInterface $selectionPluginManager;

  /**
   * IoBuilderParagraphsUtility constructor.
   *
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selectionPluginManager
   *   The selection plugin manager.
   */
  public function __construct(SelectionPluginManagerInterface $selectionPluginManager) {
    $this->selectionPluginManager = $selectionPluginManager;
  }

  /**
   * Returns the paragraph options for a field of an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase $entity
   *   The entity for which to retrieve the paragraph options.
   * @param string $field
   *   The field.
   *
   * @return array
   *   An array containing options.
   */
  public function getOptions(ContentEntityBase $entity, string $field): array {
    // Get field definition.
    $field_definition = $entity->getFieldDefinition($field);

    if (!$field_definition instanceof FieldConfig) {
      return [];
    }

    $handler = $this->selectionPluginManager->getSelectionHandler($field_definition);

    if (!$handler instanceof ParagraphSelection) {
      return [];
    }

    return $handler->getSortedAllowedTypes();
  }


  /**
   * Builds an add more button for paragraphs.
   *
   * @param \Drupal\io_builder\Plugin\IoBuilder\Context\IoBuilderEntityContextInterface $entityContext
   *   The entity context.s
   * @param int|null $delta
   *   The delta of where to add the paragraph.
   *
   * @return array
   */
  public function buildAddMore(IoBuilderEntityContextInterface $entityContext, int $delta = NULL) {
    if (is_int($delta)) {
      $queryParams = [
        'delta' => $delta,
      ];
    }

    $link = BaseEntityActions::baseActionLink() + [
      '#key' => 'add',
      '#title' => $this->t('Add new paragraph'),
      '#url' => Url::fromRoute(
        'io_builder_paragraphs.add',
        $entityContext->getRouteParameters(),
        [
          'query' => $queryParams ?? [],
        ]
      ),
    ];

    $link['#attributes']['class'] = [
      'io-add-item',
      'has-tooltip',
    ];

    return [
      '#theme' => 'io_builder__add_section',
      '#link' => $link,
    ];
  }

  /**
   * Creates a delete link for a paragraph from a field.
   *
   * @param \Drupal\io_builder_paragraphs\Plugin\IoBuilder\Context\IoBuilderParagraphFieldContext $context
   *   The context of the paragraph and the field.
   *
   * @return array
   *   The delete link.
   */
  public function createDeleteLink(IoBuilderParagraphFieldContext $context) {
    $url = Url::fromRoute(
      'io_builder_paragraphs.delete',
      $context->getRouteParameters(),
      [
        'query' => [
          'force' => TRUE,
        ],
      ]
    );

    return [
      '#theme' => 'io_builder__confirmation',
      '#title' => $this->t('Are you sure you wish to remove this paragraph?'),
      '#action' => [
        '#type' => 'link',
        '#attributes' => [
          'data-io-builder-action' => 'ajax_action',
        ],
        '#title' => $this->t('Remove paragraph'),
        '#url' => $url
      ],
    ];
  }

  /**
   * Builds the paragraph selector.
   *
   * @param \Drupal\io_builder_paragraphs\Plugin\IoBuilder\Context\IoBuilderParagraphFieldContext $context
   *   The io builder paragraph field context.
   *
   * @return array
   *   An array containing the paragraph selector.
   */
  public function buildParagraphSelector(IoBuilderParagraphFieldContext $context) {
    $options = $this->getOptions(
      $context->getEntity(),
      $context->getField()
    );

    if (empty($options)) {
      return [];
    }

    $links = [];
    $delta = $context->getDelta();

    if (is_int($delta)) {
      $queryParams['delta'] = $delta;
    }

    $build['#theme'] = 'io_builder_paragraphs__paragraphs_selector';

    foreach ($options as $key => $option) {
      $queryParams['type'] = $key;

      $links[$key] = [
        '#type' => 'link',
        '#attributes' => [
          'data-io-builder-action' => 'ajax_action',
        ],
        '#title' => $option['label'],
        '#weight' => $option['weight'] ?? 0,
        '#url' => Url::fromRoute(
          'io_builder_paragraphs.add', $context->getRouteParameters()
        ),
        '#options' => [
          'query' => $queryParams,
          'attributes' => [
            'class' => [
              'io-add-item',
              'has-tooltip',
            ],
            'data-tippy-content' => $this->t('Add paragraph'),
          ],
        ],
      ];
    }

    $build['#options'] = $links;
    return $build;
  }

}
