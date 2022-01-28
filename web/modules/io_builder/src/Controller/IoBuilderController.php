<?php

namespace Drupal\io_builder\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\io_builder\Form\FieldForm;
use Drupal\io_builder\Plugin\Interfaces\IoBuilderContextInterface;
use Drupal\io_builder\Plugin\IoBuilderContextPluginManager;
use Drupal\io_builder\Utility\FormUtility\IoBuilderEntityFormBuilderUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class IoBuilderController
 *
 * @package Drupal\io_builder\Controller
 */
class IoBuilderController extends ControllerBase {

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected ClassResolverInterface $classResolver;

  /**
   * @var \Drupal\io_builder\Plugin\IoBuilderContextPluginManager
   */
  protected IoBuilderContextPluginManager $contextPluginManager;

  /**
   * @var \Drupal\io_builder\Utility\FormUtility\IoBuilderEntityFormBuilderUtility
   */
  protected IoBuilderEntityFormBuilderUtility $entityFormBuilderUtility;

  /**
   * IoBuilderController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $classResolver
   *   Resolves classes.
   * @param \Drupal\io_builder\Plugin\IoBuilderContextPluginManager $contextPluginManager
   *   Recreates the context plugins.
   * @param \Drupal\io_builder\Utility\FormUtility\IoBuilderEntityFormBuilderUtility $entityFormBuilderUtility
   *   The form builder.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ClassResolverInterface $classResolver,
    IoBuilderContextPluginManager $contextPluginManager,
    IoBuilderEntityFormBuilderUtility $entityFormBuilderUtility
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->classResolver = $classResolver;
    $this->contextPluginManager = $contextPluginManager;
    $this->entityFormBuilderUtility = $entityFormBuilderUtility;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('class_resolver'),
      $container->get('plugin.manager.io_builder_context'),
      $container->get('io_builder.utility.form.entity_form_builder')
    );
  }

  /**
   * Initiates the IO Builder.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase|null $entity
   *   The entity to build.
   * @param string $view_mode
   *   The view mode to build in.
   *
   * @return array
   *   The entity to build.
   */
  public function build(ContentEntityBase $entity = NULL, string $view_mode = 'full') {
    // If we have an "in_preview" property, set it to true.
    if (property_exists($entity, 'in_preview')) {
      $entity->in_preview = TRUE;
    }

    if ($entity->isNew()) {
      $entity->set('title','New node');
    }

    return $this->entityTypeManager
      ->getViewBuilder($entity->getEntityTypeId())
      ->view($entity, $view_mode);
  }

  /**
   * Generates a field widget for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase|null $entity
   *   The entity.
   * @param string $field
   *   The field to show.
   * @param string $view_mode
   *   The view mode of the entity.
   */
  public function fieldWidget(ContentEntityBase $entity = NULL, string $field, string $view_mode) {
    // If we have an "in_preview" property, set it to true.
   $this->initEntity($entity, $view_mode);

    $form_state = (new FormState())
      ->disableRedirect()
      ->set('io_builder', TRUE)
      ->addBuildInfo('args', [$entity, $field]);

    $form = $this->formBuilder()
      ->buildForm(FieldForm::class, $form_state);

    // Let's return the ajax response from the form state.
    if ($form_state->isExecuted()) {
      return $this->rebuildEntityFromFormStateCommand($form_state, $view_mode);
    }

    $ajaxResponse = new AjaxResponse();

    $insertCommand = new AppendCommand('body', [
      '#theme' => 'io_builder__panel',
      '#content' => $form,
    ]);

    $ajaxResponse->addCommand($insertCommand);
    return $ajaxResponse;
  }

  /**
   * Returns the entity form.
   */
  public function entityForm(ContentEntityBase $entity = NULL, string $view_mode = 'full', Request $request) {
    $this->initEntity($entity, $view_mode);

    $form_state = (new FormState())
      ->disableRedirect()
      ->set('io_builder', TRUE)
      ->set('io_builder_context_tree', $request->request->get('io_builder_context_tree') ?? NULL)
      ->addBuildInfo('args', [$entity]);

    $formObject = $this->entityTypeManager
      ->getFormObject($entity->getEntityTypeId(), 'io_builder')
      ->setEntity($entity);

    $form = $this->formBuilder()
      ->buildForm($formObject, $form_state);

    // Let's return the ajax response from the form state.
    if ($form_state->isExecuted()) {
      if ($form_state->get('io_builder_context') && $form_state->get('io_builder_context')['parent']) {
        return $this->rebuildParentEntity(
          $form_state->get('io_builder_context')['parent']
        );
      }
      else {
        return $this->rebuildEntityFromFormStateCommand(
          $form_state, $view_mode
        );
      }
    }
    else if ($errors = $form_state->getErrors()) {
      $ajaxResponse = new AjaxResponse();

      foreach ($errors as $key => $error) {
        $messageCommand = new AlertCommand($error);
        $ajaxResponse->addCommand($messageCommand);
      }

      return $ajaxResponse;
    }

    $ajaxResponse = new AjaxResponse();

    $insertCommand = new AppendCommand('body', [
      '#theme' => 'io_builder__panel',
      '#content' => $form,
    ]);

    $ajaxResponse->addCommand($insertCommand);
    return $ajaxResponse;
  }

  /**
   * Returns a rebuild entity command.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase $entity
   *   Returns a replace command.
   */
  public function replaceEntityCommand(ContentEntityBase $entity, string $view_mode): ReplaceCommand {
    $selectorId = sprintf(
      '[data-io-builder-element="%s--%s"]', $entity->getEntityTypeId(), $entity->id()
    );

    $build = $this->entityTypeManager()
      ->getViewBuilder($entity->getEntityTypeId())
      ->view($entity, $view_mode);

    return new ReplaceCommand($selectorId, $build);
  }

  /**
   * Returns a replace command which rebuilds a certain IO builder context.
   *
   * @param \Drupal\io_builder\Plugin\Interfaces\IoBuilderContextInterface $context
   *   The context interface.
   *
   * @return \Drupal\Core\Ajax\ReplaceCommand
   *   The replace command.
   */
  public function replaceContextCommand(IoBuilderContextInterface $context): ReplaceCommand {
    return new ReplaceCommand(
      $context->getIoBuilderElementSelectorDomQuery(), $context->rebuild()
    );
  }

  /**
   * Removes the sidebar command.
   *
   * @return \Drupal\Core\Ajax\RemoveCommand
   *   The sidebar remove command.
   */
  protected function removeSidebarCommand() {
    return new RemoveCommand('#io-builder--sidebar-panel');
  }

  /**
   * Rebuilds the entity from form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $view_mode
   *   The view mode to build in.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  protected function rebuildEntityFromFormStateCommand(FormStateInterface $form_state, string $view_mode) {
    $entity = $form_state->get('entity');
    return $this->rebuildEntityAjaxResponse($entity, $view_mode);
  }

  /**
   * Rebuilds the parent entity.
   *
   * @param array $data
   *   The data array.
   */
  protected function rebuildParentEntity(array $data) {
    $entity = $this->entityTypeManager
      ->getStorage($data['entity_type'])
      ->loadRevision($data['revision_id']);

    if (!$entity instanceof ContentEntityBase) {
      return new AjaxResponse();
    }

    return $this->rebuildEntityAjaxResponse(
      $entity, $data['view_mode'] ?? 'full'
    );
  }

  /**
   * Rebuild entity ajax response.
   *
   * Rebuilds entity and closes sidebar.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase $entity
   *   The entity.
   * @param string $view_mode
   *   The view mode.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  protected function rebuildEntityAjaxResponse(ContentEntityBase $entity, string $view_mode): AjaxResponse {
    $ajaxResponse = new AjaxResponse();

    $ajaxResponse->addCommand(
      $this->replaceEntityCommand($entity, $view_mode)
    );

    $ajaxResponse->addCommand(
      $this->removeSidebarCommand()
    );

    return $ajaxResponse;
  }

  /**
   * Initialises an entity, setting up io builder related functionality.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase|null $entity
   *   The entity.
   * @param string $view_mode
   *   The view mode of the entity.
   */
  protected function initEntity(?ContentEntityBase $entity, string $view_mode) {
    if (property_exists($entity, 'in_preview')) {
      $entity->in_preview = TRUE;
      $entity->preview_view_mode = $view_mode;
    }
  }

  /**
   * Creates an ajax response containing a dialog command.
   *
   * @param string $title
   *   Title of the modal.
   * @param $content
   *   Content of the modal.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response containing the dialog command.
   */
  protected function createModalResponse(string $title, $content): AjaxResponse {
    $ajaxResponse = new AjaxResponse();

    $modalDialog = new OpenDialogCommand(
      '#io-builder-dialog',
      $title,
      $content,
      [
        'dialogClass' => 'media-library-widget-modal',
        'height' => '75%',
        'width' => '75%',
      ],
    );

    $ajaxResponse->addCommand($modalDialog);

    $ajaxResponse->addAttachments([
      'library' => [
        'core/drupal.dialog.ajax',
      ],
    ]);

    return $ajaxResponse;
  }

  /**
   * Retrieves an io builder context from the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\io_builder\Plugin\Interfaces\IoBuilderContextInterface
   *   The IO builder context.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Error when context could not be retrieved.
   */
  protected function getIoBuilderContextFromRequest(Request $request): IoBuilderContextInterface {
    $parameters = $request->query->all();
    $contextId = $parameters['io_builder_context_id'] ?? NULL;

    if (!$contextId) {
      throw new \Exception('Could not convert the request to an IO Builder context');
    }

    $context = $this->contextPluginManager
      ->createInstance($contextId, $parameters);

    if (!$context instanceof IoBuilderContextInterface) {
      throw new \Exception('Could not convert the request to an IO Builder context');
    }

    return $context;
  }

  /**
   * Rebuilds the context.
   *
   * @param \Drupal\io_builder\Plugin\Interfaces\IoBuilderContextInterface $context
   *   The context to rebuild.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Returns the ajax response.
   */
  protected function rebuildFromContext(IoBuilderContextInterface $context): AjaxResponse {
    $ajaxResponse = new AjaxResponse();

    $ajaxResponse->addCommand(
      $this->replaceContextCommand($context)
    );

    $ajaxResponse->addCommand(
      $this->removeSidebarCommand()
    );

    $ajaxResponse->addCommand(
      new CloseDialogCommand('#io-builder-dialog')
    );

    return $ajaxResponse;
  }

  /**
   * Returns the IO builder context tree from the request.
   *
   * @param $request
   *   The request.
   *
   * @return array|null
   *   An empty array.
   */
  protected function getIoBuilderContextTreeFromRequest($request): ?array {
    $data = $request->request->get('io_builder_context_tree') ?? NULL;

    if (!is_array($data)) {
      return NULL;
    }

    return $data;
  }

  /**
   * Returns an ajax response containing multiple errors.
   *
   * @param array $errors
   *   The errors.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  protected function displayErrors(array $errors): AjaxResponse {
    $ajaxResponse = new AjaxResponse();

    foreach ($errors as $key => $error) {
      $messageCommand = new AlertCommand($error);
      $ajaxResponse->addCommand($messageCommand);
    }

    return $ajaxResponse;
  }

  /**
   * Displays the IO builder panel.
   *
   * @param mixed $build
   *   The content of the panel.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  protected function displayIoBuilderPanelCommand($build) {
    $ajaxResponse = new AjaxResponse();

    $insertCommand = new AppendCommand('body', [
      '#theme' => 'io_builder__panel',
      '#content' => $build,
    ]);

    $ajaxResponse->addCommand($insertCommand);
    return $ajaxResponse;
  }

  /**
   * Inserts a field value.
   *
   * @param \Drupal\Core\TypedData\ListInterface $field
   *   The field.
   * @param $fieldValue
   *   The field value.
   * @param int|null $delta
   *   Where to insert it.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  protected function insertFieldValue(ListInterface $field, $fieldValue, int $delta = NULL) {
    $iterator = $field->getIterator();

    if (is_null($delta)) {
      $field->appendItem($fieldValue);
    }
    else {
      $values = $iterator->getArrayCopy();

      $this->arrayInsert(
        $values, $delta, [$fieldValue]
      );

      foreach ($values as $key => $value) {
        if ($value instanceof FieldItemInterface) {
          $values[$key] = $value->toArray();
        }
      }

      $field->setValue($values);
    }
  }

  /**
   * Helper function to help us with inserting an element in an array by pos.
   *
   * @param $array
   *   The array.
   * @param $position
   *   The position to sort it in.
   * @param $insert
   *   What to insert.
   */
  protected function arrayInsert(&$array, $position, $insert) {
    if (is_int($position)) {
      array_splice($array, $position, 0, $insert);
    }
    else {
      $pos = array_search($position, array_keys($array));
      $array = array_merge(
        array_slice($array, 0, $pos),
        $insert,
        array_slice($array, $pos)
      );
    }
  }

}
