<?php

namespace Drupal\io_builder_paragraphs\Plugin\IoBuilder\EntityActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\io_builder\Plugin\IoBuilder\EntityActions\BaseEntityActions;
use Drupal\io_builder_paragraphs\Plugin\IoBuilder\Context\IoBuilderParagraphFieldContext;

/**
 * Contains IO Builder actions specific to paragraphs.
 *
 * @package Drupal\io_builder_paragraphs\Plugin\IoBuilder\EntityActions
 *
 * @IoBuilderEntityActions(
 *   id = "io_builder_paragraph_actions",
 *   label = @Translation("IO Builder Paragraph Actions"),
 *   entityTypes={"paragraph"}
 * )
 */
class ParagraphActions extends BaseEntityActions {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getActions(array &$build = []): array {
    $paragraphContext = $build['#io_builder']['#paragraph_context'] ?? NULL;
    $links = [];

    if ($paragraphContext instanceof IoBuilderParagraphFieldContext) {
      $links['drag_drop'] = $this->getDragDropItemLink();
      $links['item_delete'] = $this->getDeleteItemLink($paragraphContext);
    }

    return $links;
  }

  /**
   * This creates a delete from item context.
   *
   * This won't delete the entity, only the item inside the list.
   *
   * @param \Drupal\io_builder_paragraphs\Plugin\IoBuilder\Context\IoBuilderParagraphFieldContext $paragraphContext
   *   The paragraph context.
   *
   * @return array
   *   An array containing a entity action.
   */
  protected function getDeleteItemLink(IoBuilderParagraphFieldContext $paragraphContext): array {
    return BaseEntityActions::baseActionLink() + [
      '#title' => $this->t('Delete'),
      '#url' => Url::fromRoute(
        'io_builder_paragraphs.delete',
        $paragraphContext->getRouteParameters()
      ),
      '#key' => 'delete',
    ];
  }

  /**
   * Gets the drag & drop item link.
   *
   * @return array
   *   An array containing the drag & drop functionality.
   */
  protected function getDragDropItemLink(): array {
    return [
      '#theme' => 'io_builder__action',
      '#title' => $this->t('Move'),
      '#key' => 'move',
      '#attached' => [
        'library' => [
          'io_builder/io_builder_move'
        ],
      ],
    ];
  }

}
