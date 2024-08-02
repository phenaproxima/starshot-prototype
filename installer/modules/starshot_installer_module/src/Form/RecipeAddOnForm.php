<?php

namespace Drupal\starshot_installer_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the recipe add-on selection form.
 *
 * @internal
 */
class RecipeAddOnForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'install_select_recipe_addon_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $install_state = NULL) {
    $form['#title'] = $this->t('Select preconfigured recipes');

    $options = [
      '/recipes/starshot_multilingual' => $this->t('Multilingual support'),
      '/recipes/starshot_accessibility_tools' => $this->t('Accessibility tools'),
    ];

    $form['selected_addons'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select Recipes'),
      '#title_display' => 'invisible',
      '#options' => $options,
      '#default_value' => [],
      '#description' => $this->t('Select one or more recipes.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    global $install_state;
    $addons = $form_state->getValue('selected_addons');
    $install_state['parameters']['addons'] = $addons;
  }

}
