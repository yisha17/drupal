<?php

namespace Drupal\io_builder\Utility\FormUtility;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Alters the entity type form, adding an "frontend editing" functionality.
 */
class EntityTypeFormUtility {

  use StringTranslationTrait;

  /**
   * The config entity.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityBundleBase
   */
  protected ?ConfigEntityBundleBase $entity;

  /**
   * The entity type id.
   *
   * @var string|null
   */
  private ?string $entityTypeId;

  /**
   * The bundle.
   *
   * @var int|string|null
   */
  private $bundle;

  /**
   * Sets the entity.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityBundleBase $entity
   *   The config entity bundle base.
   */
  public function setEntity(ConfigEntityBundleBase $entity): void {
    $this->entity = $entity;
    $this->entityTypeId = $entity->getEntityType()->getBundleOf();
    $this->bundle = $entity->id();
  }

  /**
   * Alters the form, adding a new third party setting.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   * @param string $form_id
   *   The form id.
   */
  public function alterForm(array &$form, FormStateInterface $formState, string $form_id) {
    // Attach the behaviors.
    $form['io_builder'] = [
      '#type' => 'details',
      '#title' => $this->t('IO Builder'),
      '#group' => 'additional_settings',
      '#tree' => TRUE,
    ];

    $form['io_builder']['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable IO builder'),
      '#description' => $this->t('By enabling this, entities can be built using the IO Builder'),
      '#default_value' => $this->entity->getThirdPartySetting('io_builder', 'enable') ?? FALSE,
    ];

    $form['#entity_builders'][] = [$this, 'updateEntity'];
  }

  /**
   * Saves the IO builder settings.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function updateEntity($entity_type, ConfigEntityBundleBase $type, &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('io_builder');

    if (empty($values)) {
      return;
    }

    foreach ($values as $key => $value) {
      $type->setThirdPartySetting('io_builder', $key, $value);
    }
  }

}
