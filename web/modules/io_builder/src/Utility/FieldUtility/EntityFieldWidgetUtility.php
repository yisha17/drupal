<?php


namespace Drupal\io_builder\Utility\FieldUtility;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\io_builder\Form\FieldForm;
use Drupal\io_builder\Utility\BaseEntityUtility;

/**
 * Class EntityFieldWidgetUtility
 *
 * @package Drupal\io_builder\Utility\FieldUtility
 */
class EntityFieldWidgetUtility extends BaseEntityUtility {

  /**
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  private FormBuilderInterface $formBuilder;

  /**
   * EntityFieldWidgetUtility constructor.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   */
  public function __construct(FormBuilderInterface $formBuilder) {
    $this->formBuilder = $formBuilder;
  }

  /**
   * Returns a widget field for an entity.
   *
   * @return array
   *   An array containing the form.
   */
  public function getEntityFieldWidget(string $field) {
    // If we have an "in_preview" property, set it to true.
    if (property_exists($this->entity, 'in_preview')) {
      $this->entity->in_preview = TRUE;
      $this->entity->preview_view_mode = $this->view_mode;
    }

    $form_state = (new FormState())
      ->disableRedirect()
      ->addBuildInfo('args', [$this->entity, $field]);

    return $this->formBuilder
      ->buildForm(FieldForm::class, $form_state);
  }

}
