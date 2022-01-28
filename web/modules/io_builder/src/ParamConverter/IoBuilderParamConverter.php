<?php

namespace Drupal\io_builder\ParamConverter;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

/**
 * Converts parameters used in the io builder.
 *
 * @package Drupal\io_builder\ParamConverter
 */
class IoBuilderParamConverter implements ParamConverterInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $entityTypeManager;

  /**
   * IoBuilderParamConverter constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    EntityTypeManager $entityTypeManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $entityType = $defaults['entity_type'] ?? NULL;
    $bundle = $defaults['bundle'] ?? NULL;
    $entity = NULL;

    $storage = $this->entityTypeManager
      ->getStorage($entityType);

    if ($value) {
      $entity = $storage->loadRevision($value);
    }

    if (!$entity) {
      $entity = $storage->create([
        'type' => $bundle
      ]);
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return isset($definition['type']) && strpos($definition['type'], 'io_builder') !== FALSE;
  }

}
