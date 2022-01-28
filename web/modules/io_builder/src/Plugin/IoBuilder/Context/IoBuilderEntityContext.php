<?php

namespace Drupal\io_builder\Plugin\IoBuilder\Context;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\io_builder\Annotation\IoBuilderContext;
use Drupal\io_builder\Traits\IoBuilderEntitySetterTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The IO builder context stores information about an entity in a view mode.
 *
 * @package Drupal\io_builder\Plugin\IoBuilder\Context
 *
 * @IoBuilderContext(
 *   id = "io_builder_entity_context"
 * )
 */
class IoBuilderEntityContext extends IoBuilderContextBase implements IoBuilderEntityContextInterface {

  use IoBuilderEntitySetterTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected EntityTypeManager $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $static = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $static->setEntityTypeManager(
      $container->get('entity_type.manager')
    );

    $static->setProperties();

    return $static;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId(): string {
    return $this->entity->getEntityTypeId();
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    return parent::getRouteParameters() + [
      'entity_type' => $this->getEntityTypeId(),
      'bundle' => $this->getBundle(),
      'revision_id' => $this->entity->getRevisionId(),
      'view_mode' => $this->getViewMode(),
      'language' => $this->entity->language()->getId(),
    ];
  }

  /**
   * Returns the bundle.
   *
   * @return mixed|string|null
   */
  public function getBundle() {
    return $this->entity->bundle();
  }

  /**
   * Retrieves the entity from the configuration.
   */
  protected function setEntityFromConfiguration() {
    $entity = $this->configuration['entity'] ?? NULL;

    // Set the entity and leave the function.
    // We have what we want.
    if ($entity instanceof ContentEntityBase) {
      $this->setEntity($entity);
      return;
    }

    $entityRevisionId = $this->configuration['revision_id'] ?? NULL;
    $entityType = $this->configuration['entity_type'] ?? NULL;

    // We won't be able to load the entity.
    if (empty($entityRevisionId) || empty($entityType)) {
      return;
    }

    // Fetch the entity.
    try {
      $entity = $this->entityTypeManager
        ->getStorage($entityType)
        ->loadRevision($entityRevisionId);
    }
    catch (\Exception $e) {
      $entity = NULL;
    }

    if (!$entity instanceof ContentEntityBase) {
      return;
    }

    $language = $this->configuration['language'] ?? NULL;

    if ($language && $entity->hasTranslation($language)) {
      $entity->getTranslation($language);
    }

    $this->setEntity($entity);
  }

  /**
   * Setter function for the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   */
  public function setEntityTypeManager(EntityTypeManager $entityTypeManager): void {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getIoBuilderElementSelector(): string {
    return sprintf(
      '%s--%s',
      $this->getEntityTypeId(),
      $this->getEntity()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIoBuilderElementData(): array {
    return [
      static::ID_KEY => $this->getPluginId(),
      'element' => $this->getIoBuilderElementSelector(),
      'entity_type' => $this->getEntityTypeId(),
      'entity_id' => $this->entity->id(),
      'revision_id' => $this->entity->getRevisionId(),
      'language' => $this->entity->language()->getId(),
      'view_mode' => $this->getViewMode(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function rebuild() {
    return $this->entityTypeManager->getViewBuilder(
      $this->getEntityTypeId()
    )->view($this->entity, $this->getViewMode());
  }

  /**
   * Called from the create function for easier inheritance.
   */
  protected function setProperties() {
    if (!empty($this->configuration['view_mode'])) {
      $this->setViewMode($this->configuration['view_mode']);
    }

    $this->setEntityFromConfiguration();
  }

}
