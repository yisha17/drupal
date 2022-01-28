<?php

namespace Drupal\io_builder\PrivateTempStore;

use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Abstract class to store the IO Builder storage functionality.
 *
 * @package Drupal\io_builder\PrivateTempStore
 */
abstract class IoBuilderStorage {

  /**
   * Private temp store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $privateTempStorageFactory;

  /**
   * An initialised temp store.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore|null
   */
  protected $tempStore = NULL;

  /**
   * IoBuilderStorage constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $privateTempStorageFactory
   *   The private temp store factory service.
   */
  public function __construct(PrivateTempStoreFactory $privateTempStorageFactory) {
    $this->privateTempStorageFactory = $privateTempStorageFactory;
  }

  /**
   * Initialises a temp store.
   *
   * @param string $collection
   *   The temp store to initialise.
   */
  protected function initialiseTempStore(string $collection) {
    $this->tempStore = $this->privateTempStorageFactory->get($collection);
  }

}
