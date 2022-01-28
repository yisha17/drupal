<?php

namespace Drupal\io_builder\Utility\FieldUtility;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\io_builder\Utility\BaseEntityUtility;
use Twig\Markup;

/**
 * Adds an EntityFieldUtility.
 *
 * @package Drupal\io_builder\Utility\FieldUtility
 */
class EntityFieldUtility extends BaseEntityUtility {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * The field name.
   *
   * @var string
   */
  protected string $field;

  /**
   * The render.
   *
   * @var mixed
   */
  protected $render;

  /**
   * EntityFieldUtility constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * Extends the variables passed by the io builder field.
   *
   * @param array $variables
   *   Array containing variables.
   */
  public function extendVariables(array &$variables) {
    if (!$this->entity) {
      return;
    }


    if (!$this->render instanceof Markup) {
      $render = $this->renderer->render(
        $this->render
      );
    }

    $placeholder = FALSE;

    if (empty($render)) {
      $placeholder = TRUE;

      $render = [
        '#theme' => 'io_builder__field_placeholder',
      ];
    }

    $selector = Html::getUniqueId(
      sprintf('%s--%s', $this->entity->getEntityTypeId(), $this->entity->id())
    );

    $actions[] = [
      '#type' => 'link',
      '#title' => $placeholder ? $this->t('Add') : $this->t('Edit'),
      '#attributes' => [
        'data-io-builder-action' => 'ajax_action',
        'data-io-builder-selector' => $selector,
        'class' => [
          'io-builder--actions--edit-field'
        ],
      ],
      '#url' => Url::fromRoute(
        'io_builder.field_widget',
        [
          'entity_type' => $this->entity->getEntityTypeId(),
          'bundle' => $this->entity->bundle(),
          'entity' => $this->entity->getRevisionId() ?? 'new',
          'field' => $this->field,
          'view_mode' => $this->view_mode,
        ],
      ),
    ];

    $variables['actions'] = $actions;
    $variables['attributes']['data-frontend-builder-element'] = $selector;
    $variables['render'] = $render;
  }

  /**
   * Sets the field.
   *
   * @param string $field
   *   The field to set.
   */
  public function setField(string $field): void {
    $this->field = $field;
  }

  /**
   * Sets the render.
   *
   * @param mixed $render
   *   The render.
   */
  public function setRender($render): void {
    $this->render = $render;
  }

}
