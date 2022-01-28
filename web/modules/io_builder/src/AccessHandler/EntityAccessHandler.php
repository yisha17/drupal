<?php

namespace Drupal\io_builder\AccessHandler;

use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\io_builder\PrivateTempStore\IoBuilderSettingsStorage;

/**
 * Utility to help us with determining if an entity can be built using IO builder.
 *
 * @package Drupal\io_builder\AccessHandler
 */
class EntityAccessHandler {

  /**
   * @var \Drupal\io_builder\PrivateTempStore\IoBuilderSettingsStorage
   */
  private IoBuilderSettingsStorage $settingsStorage;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private AccountProxyInterface $currentUser;

  /**
   * @var \Drupal\Core\Routing\AdminContext
   */
  private AdminContext $adminContext;

  /**
   * EntityAccessHandler constructor.
   *
   * @param \Drupal\io_builder\PrivateTempStore\IoBuilderSettingsStorage $settingsStorage
   *   The IO Builder settings storage.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Routing\AdminContext $adminContext
   *   The admin context.
   */
  public function __construct(
    IoBuilderSettingsStorage $settingsStorage,
    AccountProxyInterface $currentUser,
    AdminContext $adminContext
  ) {
    $this->settingsStorage = $settingsStorage;
    $this->currentUser = $currentUser;
    $this->adminContext = $adminContext;
  }

  /**
   * Does the user have access to build the entity using IO builder?
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @param string|null $viewMode
   *   Optional view mode parameter.
   *
   * @return bool
   *   Does the user have access to build the entity using IO builder?
   */
  public function ioBuilderAccess(EntityInterface $entity, string $viewMode = NULL): bool {
    // If user does not have permission, skip this.
    if (!$this->currentUser->hasPermission('access io builder')) {
      return FALSE;
    }

    // Check if the user has enabled IO builder.
    if (!$this->ioBuilderEnabled()) {
      return FALSE;
    }

    // If not in frontend, skip this.
    if ($this->adminContext->isAdminRoute()) {
      return FALSE;
    }

    try {
      $entityType = $entity->get('type')->entity;
    }
    catch (\Exception $e) {
      $entityType = NULL;
    }

    if (!$entityType instanceof ThirdPartySettingsInterface) {
      return FALSE;
    }

    $enable = $entityType->getThirdPartySetting('io_builder', 'enable');

    if (empty($enable)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Checks if the IO builder has been enabled by the current user.
   *
   * @return bool
   *   Checks if the IO builder has been enabled by the current user.
   */
  protected function ioBuilderEnabled(): bool {
    return $this->settingsStorage->ioBuilderEnabled();
  }

}
