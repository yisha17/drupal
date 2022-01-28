<?php

namespace Drupal\io_builder\Plugin\IoBuilder\EntityActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * This class adds default entity actions for all content entities.
 *
 * @package Drupal\io_builder\Plugin\IoBuilder\EntityActions
 *
 * @IoBuilderEntityActions(
 *   id = "default",
 *   label = @Translation("Default actions"),
 * )
 */
class DefaultEntityActions extends BaseEntityActions {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getActions(array &$build = []): array {
    return [
      'edit' => $this->getEditLink(),
    ];
  }

  protected function getEditLink() {
    $link = BaseEntityActions::baseActionLink() + [
      '#title' => $this->t('Edit'),
      '#url' => $this->getEditUrl(),
      '#key' => 'edit',
    ];

    $link['#attributes']['class'][] = 'io-builder--actions--edit-field';
    return $link;
  }

  /**
   * Returns the edit entity URL.
   *
   * @return \Drupal\Core\Url
   *   The edit url.
   */
  protected function getEditUrl(): Url {
    return Url::fromRoute(
      'io_builder.entity.form', $this->context->getRouteParameters()
    );
  }

}
