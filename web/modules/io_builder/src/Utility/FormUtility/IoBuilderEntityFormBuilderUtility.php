<?php

namespace Drupal\io_builder\Utility\FormUtility;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\io_builder\Plugin\Interfaces\IoBuilderContextInterface;
use Drupal\io_builder\Plugin\IoBuilder\Context\IoBuilderContextBase;
use Drupal\io_builder\Plugin\IoBuilder\Context\IoBuilderEntityContextInterface;
use Drupal\io_builder\Plugin\IoBuilderContextPluginManager;

/**
 * Provides us with a single point to create io builder forms for entities.
 *
 * @package Drupal\io_builder\Utility\FormUtility
 */
class IoBuilderEntityFormBuilderUtility {

  /**
   * The entity for which to prepare the io builder form.
   *
   * @var \Drupal\io_builder\Plugin\IoBuilder\Context\IoBuilderEntityContextInterface $context
   */
  protected IoBuilderEntityContextInterface $context;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected EntityTypeManager $entityTypeManager;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected FormBuilderInterface $formBuilder;

  /**
   * The form state which is created during the prep of the form.
   *
   * @var \Drupal\Core\Form\FormState|null
   */
  protected ?FormState $formState;

  /**
   * The entity form which is created during the prep of the form.
   *
   * @var \Drupal\Core\Entity\EntityFormInterface
   */
  protected ?EntityFormInterface $formObject;

  /**
   * The form array creating during the prep.
   *
   * @var array
   */
  protected array $form = [];

  /**
   * The context plugin manager.
   *
   * @var \Drupal\io_builder\Plugin\IoBuilderContextPluginManager
   */
  private IoBuilderContextPluginManager $contextPluginManager;

  /**
   * IoBuilderEntityFormUtility constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   * @param \Drupal\io_builder\Plugin\IoBuilderContextPluginManager $contextPluginManager
   *   The context plugin manager.
   */
  public function __construct(
    EntityTypeManager $entityTypeManager,
    FormBuilderInterface $formBuilder,
    IoBuilderContextPluginManager $contextPluginManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->formBuilder = $formBuilder;
    $this->contextPluginManager = $contextPluginManager;
  }

  /**
   * Prepares the entity form.
   *
   * @param array $formStateAdditions
   *   Additional form state settings.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  public function prepareEntityForm(array $formStateAdditions = []) {
    $this->formState = (new FormState())
      ->disableRedirect()
      ->set('io_builder', TRUE)
      ->addBuildInfo('args', [$this->context->getEntity()]);

    // Set additional form state settings.
    if (!empty($formStateAdditions)) {
      foreach($formStateAdditions as $key => $value) {
        $this->formState->set($key, $value);
      }
    }

    $this->formObject = $this->entityTypeManager
      ->getFormObject($this->context->getEntityTypeId(), 'io_builder')
      ->setEntity($this->context->getEntity());

    $this->form = $this->formBuilder->buildForm(
      $this->formObject, $this->formState
    );
  }

  /**
   * The parent context passed to the form is the context that will be rebuilt.
   *
   * This is the context that we attempt to retrieve here.
   */
  public function getRebuildContext(): ?IoBuilderContextInterface {
    $ioBuilderContext = $this->formState->get('io_builder_context') ?? NULL;

    if (empty($ioBuilderContext) || empty($ioBuilderContext['top_parent'])) {
      $this->context->setEntity(
        $this->formState->get('entity')
      );

      return $this->context;
    }

    $parent = $ioBuilderContext['top_parent'];
    $contextId = $parent[IoBuilderContextBase::ID_KEY];

    if (empty($contextId)) {
      return $this->context;
    }

    $context = $this->contextPluginManager->createInstance(
      $contextId, $ioBuilderContext['top_parent']
    );

    if (!$context instanceof IoBuilderContextInterface) {
      return $this->context;
    }

    return $context;
  }

  /**
   * Sets the context.
   *
   * @param \Drupal\io_builder\Plugin\IoBuilder\Context\IoBuilderEntityContextInterface $context
   *   The context
   *
   * @returns \Drupal\io_builder\Utility\FormUtility\IoBuilderEntityFormBuilderUtility
   *   The current builder service.
   */
  public function setContext(IoBuilderEntityContextInterface $context): IoBuilderEntityFormBuilderUtility {
    $this->context = $context;
    return $this;
  }


  /**
   * Returns the form state.
   *
   * @return \Drupal\Core\Form\FormState|null
   *   The form state.
   */
  public function getFormState(): ?FormState {
    return $this->formState;
  }

  /**
   * Returns the form object.
   *
   * @return \Drupal\Core\Entity\EntityFormInterface
   *   The form object.
   */
  public function getFormObject(): ?EntityFormInterface {
    return $this->formObject;
  }

  /**
   * Returns the built form.
   *
   * @return array
   *   The form.
   */
  public function getForm(): array {
    return $this->form;
  }

  /**
   * Is the form executed?
   *
   * @return bool
   *   Is the form executed?
   */
  public function isExecuted(): bool {
    return $this->formState->isExecuted();
  }

  /**
   * Get the errors from the form state if there are any.
   *
   * @return array
   *   The errors from the form state.
   */
  public function getErrors(): array {
    return $this->formState->getErrors();
  }

}
