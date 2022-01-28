<?php

namespace Drupal\io_builder\PrivateTempStore;

use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Contains settings for the current user.
 *
 * @package Drupal\io_builder\PrivateTempStore
 */
class IoBuilderSettingsStorage extends IoBuilderStorage {

  /**
   * {@inheritdoc}
   */
  public function __construct(PrivateTempStoreFactory $privateTempStorageFactory) {
    parent::__construct($privateTempStorageFactory);
    $this->initialiseTempStore('io_builder_settings');
  }

  /**
   * Toggles the IO builder, if enabled entities can be adjusted.
   */
  public function toggleIoBuilder() {
    $reverse = !$this->ioBuilderEnabled();
    $this->tempStore->set('enabled', $reverse);
  }

  /**
   * Is the IO builder enabled?
   *
   * @return bool
   *   Tells us whether or not the IO builder has been enabled.
   */
  public function ioBuilderEnabled(): bool {
    $ioBuilderEnabled = $this->tempStore->get('enabled') ?? FALSE;
    return (bool) $ioBuilderEnabled;
  }

}
